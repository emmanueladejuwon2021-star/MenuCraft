<?php
// includes/helpers.php - General utilities for MenuCraft

function ensure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function get_restaurant_settings(PDO $db): array {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $row = $db->query("SELECT restaurant_name, currency_symbol FROM restaurant_settings WHERE id = 1")->fetch();
    $settings = [
        'restaurant_name' => $row['restaurant_name'] ?? 'Iya Bisi Kitchen',
        'currency_symbol' => $row['currency_symbol'] ?? '₦',
    ];
    return $settings;
}

function money(?string $symbol, $value): string {
    $amount = is_numeric($value) ? (float)$value : 0.0;
    $symbol = $symbol ?: '₦';
    $decimals = (fmod($amount, 1.0) == 0.0) ? 0 : 2;
    return $symbol . number_format($amount, $decimals);
}

function clean_label($value, int $max = 0): string {
    $str = trim((string)($value ?? ''));
    if ($max > 0 && mb_strlen($str) > $max) {
        $str = mb_substr($str, 0, $max);
    }
    return $str;
}

function clean_image_url(?string $url): string {
    $val = trim((string)($url ?? ''));
    if ($val === '') return '';
    if (filter_var($val, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $val)) {
        return $val;
    }
    return '';
}

function flash(string $tone, string $message): void {
    ensure_session();
    $_SESSION['flash'] = [
        'tone' => $tone, // 'ok' or 'error'
        'text' => $message,
    ];
}

function get_flash(): ?array {
    ensure_session();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Cart (Plate) management
const PLATE_SESSION_KEY = 'menucraft_plate';

function get_cart(): array {
    ensure_session();
    return $_SESSION[PLATE_SESSION_KEY] ?? [];
}

function save_cart(array $plate): void {
    ensure_session();
    $_SESSION[PLATE_SESSION_KEY] = array_values($plate);
}

function clear_cart(): void {
    ensure_session();
    $_SESSION[PLATE_SESSION_KEY] = [];
}

function add_to_cart(array $dish): array {
    $cart = get_cart();
    $found = false;
    foreach ($cart as &$item) {
        if ((int)$item['id'] === (int)$dish['id']) {
            $item['qty'] = min(20, (int)$item['qty'] + 1);
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $cart[] = [
            'id' => (int)$dish['id'],
            'name' => (string)$dish['name'],
            'price' => (float)$dish['price'],
            'image_url' => (string)($dish['image_url'] ?? ''),
            'qty' => 1,
        ];
    }

    save_cart($cart);
    return $cart;
}

function update_cart_qty(int $id, int $qty): array {
    $cart = get_cart();
    $updated = [];
    foreach ($cart as $item) {
        if ((int)$item['id'] === $id) {
            $safeQty = max(0, min(20, $qty));
            if ($safeQty > 0) {
                $item['qty'] = $safeQty;
                $updated[] = $item;
            }
        } else {
            $updated[] = $item;
        }
    }
    save_cart($updated);
    return $updated;
}

function cart_total(): float {
    $cart = get_cart();
    $total = 0.0;
    foreach ($cart as $item) {
        $total += (float)$item['price'] * (int)$item['qty'];
    }
    return round($total, 2);
}

function cart_count(): int {
    $cart = get_cart();
    $count = 0;
    foreach ($cart as $item) {
        $count += (int)$item['qty'];
    }
    return $count;
}

// Guest Ticket Token management
const TICKETS_SESSION_KEY = 'menucraft_tickets';

function get_tickets(): array {
    ensure_session();
    return $_SESSION[TICKETS_SESSION_KEY] ?? [];
}

function add_ticket(string $token): void {
    if (!$token) return;
    ensure_session();
    $tickets = $_SESSION[TICKETS_SESSION_KEY] ?? [];
    if (!in_array($token, $tickets, true)) {
        array_unshift($tickets, $token);
        $_SESSION[TICKETS_SESSION_KEY] = array_slice($tickets, 0, 20);
    }
}

function is_ajax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function csrf_token(): string {
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool {
    ensure_session();
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $submitted);
}

function base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
    return "$protocol://$host$scriptDir";
}
