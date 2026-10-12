<?php
// includes/auth.php - Authentication and Authorization for MenuCraft

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function get_current_user_session(): ?array {
    ensure_session();
    return $_SESSION['user'] ?? null;
}

function is_staff(): bool {
    $user = get_current_user_session();
    return !empty($user) && ($user['role'] ?? '') === 'staff';
}

function is_guest(): bool {
    $user = get_current_user_session();
    return !empty($user) && ($user['role'] ?? '') === 'guest';
}

function require_staff(string $redirectPath = '/iyabisi/kitchenlock/'): void {
    if (!is_staff()) {
        flash('error', 'Kitchen staff must sign in before viewing kitchen tools.');
        header("Location: " . $redirectPath);
        exit;
    }
}

function require_guest(string $redirectPath = '/guest-account.php'): void {
    $user = get_current_user_session();
    if (empty($user)) {
        flash('error', 'Please sign in first.');
        header("Location: " . $redirectPath);
        exit;
    }
}

function hash_password_str(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password_str(string $password, string $storedHash): bool {
    // Check standard PHP password hash (bcrypt / argon2)
    if (password_verify($password, $storedHash)) {
        return true;
    }

    // Check pbkdf2 format if migrated from Node: pbkdf2$salt$digest
    $parts = explode('$', $storedHash);
    if (count($parts) === 3 && $parts[0] === 'pbkdf2') {
        $salt = $parts[1];
        $expectedDigest = $parts[2];
        $digest = hash_pbkdf2('sha256', $password, $salt, 120000, 64);
        return hash_equals($expectedDigest, $digest);
    }

    return false;
}

function claim_first_staff(PDO $db): bool {
    $staffCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
    if ($staffCount > 0) {
        return false;
    }

    $db->exec("INSERT OR IGNORE INTO staff_claim (id, user_id) VALUES (1, 0)");
    $stmt = $db->prepare("UPDATE staff_claim SET user_id = -1 WHERE id = 1 AND user_id = 0");
    $stmt->execute();
    return $stmt->rowCount() === 1;
}

function register_user(PDO $db, array $data, ?array $caller = null): array {
    $name = clean_label($data['name'] ?? '', 80);
    $email = strtolower(clean_label($data['email'] ?? '', 120));
    $password = (string)($data['password'] ?? '');
    $restaurantName = clean_label($data['restaurant_name'] ?? '', 80) ?: 'Iya Bisi Kitchen';
    $phone = clean_label($data['phone'] ?? '', 30);
    $requestedRole = ($data['role'] ?? '') === 'staff' ? 'staff' : 'guest';

    if ($name === '') {
        return ['ok' => false, 'message' => 'Please enter your name.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Please enter a valid email.'];
    }
    if (strlen($password) < 6 || strlen($password) > 200) {
        return ['ok' => false, 'message' => 'Password should be at least 6 characters.'];
    }

    // Check if email already registered
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['ok' => false, 'message' => 'An account with that email already exists.'];
    }

    $role = 'guest';
    $claimed = false;

    if ($requestedRole === 'staff') {
        if ($caller && ($caller['role'] ?? '') === 'staff') {
            $role = 'staff';
        } else {
            $claimed = claim_first_staff($db);
            if (!$claimed) {
                return ['ok' => false, 'message' => 'A kitchen account already exists. Sign in, or ask staff to create yours.'];
            }
            $role = 'staff';
        }
    }

    $hash = hash_password_str($password);

    try {
        $ins = $db->prepare("INSERT INTO users (name, email, password_hash, restaurant_name, role, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $name,
            $email,
            $hash,
            $role === 'staff' ? $restaurantName : 'Guest',
            $role,
            $phone
        ]);
        $userId = (int)$db->lastInsertId();

        if ($claimed) {
            $db->prepare("UPDATE staff_claim SET user_id = ? WHERE id = 1")->execute([$userId]);
        }

        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        $db->prepare("INSERT INTO sessions (token, user_id, created_at) VALUES (?, ?, datetime('now'))")->execute([$sessionToken, $userId]);

        $userData = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'restaurant_name' => $role === 'staff' ? $restaurantName : '',
            'role' => $role,
            'token' => $sessionToken,
        ];

        ensure_session();
        $_SESSION['user'] = $userData;

        return ['ok' => true, 'user' => $userData];
    } catch (Exception $e) {
        if ($claimed) {
            $db->exec("UPDATE staff_claim SET user_id = 0 WHERE id = 1 AND user_id = -1");
        }
        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
            return ['ok' => false, 'message' => 'An account with that email already exists.'];
        }
        return ['ok' => false, 'message' => 'Could not create the account. Please try again.'];
    }
}

function login_user(PDO $db, string $email, string $password): array {
    $cleanEmail = strtolower(trim($email));
    if ($cleanEmail === '' || $password === '') {
        return ['ok' => false, 'message' => 'Please enter your email and password.'];
    }

    $stmt = $db->prepare("SELECT id, name, email, phone, restaurant_name, role, password_hash FROM users WHERE email = ?");
    $stmt->execute([$cleanEmail]);
    $user = $stmt->fetch();

    if (!$user || !verify_password_str($password, $user['password_hash'])) {
        return ['ok' => false, 'message' => 'Email or password is not correct.'];
    }

    $sessionToken = bin2hex(random_bytes(32));
    $db->prepare("INSERT INTO sessions (token, user_id, created_at) VALUES (?, ?, datetime('now'))")->execute([$sessionToken, (int)$user['id']]);

    $userData = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'restaurant_name' => $user['restaurant_name'] ?? '',
        'role' => $user['role'] ?? 'guest',
        'token' => $sessionToken,
    ];

    ensure_session();
    $_SESSION['user'] = $userData;

    return ['ok' => true, 'user' => $userData];
}

function logout_user(PDO $db): void {
    ensure_session();
    $token = $_SESSION['user']['token'] ?? null;
    if ($token) {
        try {
            $db->prepare("DELETE FROM sessions WHERE token = ?")->execute([$token]);
        } catch (Exception $e) {
            // ignore
        }
    }
    unset($_SESSION['user']);
}
