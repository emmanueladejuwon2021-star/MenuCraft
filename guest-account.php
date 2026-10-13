<?php
// guest-account.php - Guest Authentication & Profile
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDb();
$currentUser = get_current_user_session();
$isGuest = is_guest();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = login_user($db, $email, $password);
        if ($res['ok']) {
            flash('ok', "Welcome back, {$res['user']['name']}");
            if ($res['user']['role'] === 'staff') {
                header('Location: orders.php');
            } else {
                header('Location: ' . (cart_count() > 0 ? 'plate.php' : 'menu.php'));
            }
            exit;
        } else {
            $error = $res['message'];
        }
    }

    if ($action === 'signup') {
        $res = register_user($db, [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role' => 'guest'
        ]);
        if ($res['ok']) {
            flash('ok', 'Guest account created. You can order now.');
            header('Location: ' . (cart_count() > 0 ? 'plate.php' : 'menu.php'));
            exit;
        } else {
            $error = $res['message'];
        }
    }

    if ($action === 'update' && $isGuest) {
        $name = clean_label($_POST['name'] ?? '', 80);
        $phone = clean_label($_POST['phone'] ?? '', 30);
        if ($name === '') {
            $error = 'Please enter your name.';
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $phone, (int)$currentUser['id']]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
            $currentUser = $_SESSION['user'];
            flash('ok', 'Saved!');
            header('Location: guest-account.php');
            exit;
        }
    }
}

$mode = $_GET['mode'] ?? 'signin';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter mx-auto max-w-lg space-y-4">
  <?php if ($isGuest && $currentUser): ?>
    <!-- Signed-in Profile View -->
    <form method="POST" action="guest-account.php" class="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update" />
      <div>
        <h1 class="text-lg font-semibold text-ink">Your account</h1>
        <p class="mt-1 text-sm text-muted">Signed in as <?= htmlspecialchars($currentUser['email']) ?>. The kitchen uses this name on your ticket.</p>
      </div>

      <?php if ($error !== ''): ?>
        <p class="rounded-xl bg-card border border-line p-3 text-sm text-danger"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <label class="block text-sm font-medium text-ink">
        Your name
        <input required type="text" name="name" value="<?= htmlspecialchars($currentUser['name']) ?>" class="field mt-1" />
      </label>

      <label class="block text-sm font-medium text-ink">
        Email
        <input type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled class="field mt-1 opacity-70" />
      </label>

      <label class="block text-sm font-medium text-ink">
        Phone
        <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" placeholder="Optional" class="field mt-1" />
      </label>

      <button type="submit" class="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">
        Save details
      </button>
    </form>

    <a href="logout.php" class="tap inline-flex items-center justify-center h-11 w-full rounded-full border border-line bg-card text-sm font-medium text-ink">
      Sign out
    </a>
  <?php else: ?>
    <!-- Guest Auth Form (Sign In / Sign Up) -->
    <form method="POST" action="guest-account.php?mode=<?= htmlspecialchars($mode) ?>" class="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $mode === 'signup' ? 'signup' : 'login' ?>" />

      <div>
        <h1 class="text-lg font-semibold text-ink"><?= $mode === 'signup' ? 'Create your account' : 'Sign in' ?></h1>
        <p class="mt-1 text-sm text-muted">An account is optional. It keeps your name on the ticket so you can follow the kitchen.</p>
      </div>

      <!-- Mode switcher tabs -->
      <div class="grid grid-cols-2 gap-2 rounded-full bg-paper p-1">
        <a href="guest-account.php?mode=signin" class="tap inline-flex items-center justify-center rounded-full text-sm font-medium <?= $mode === 'signin' ? 'bg-pine text-invert' : 'text-muted' ?>">
          Sign in
        </a>
        <a href="guest-account.php?mode=signup" class="tap inline-flex items-center justify-center rounded-full text-sm font-medium <?= $mode === 'signup' ? 'bg-pine text-invert' : 'text-muted' ?>">
          Create account
        </a>
      </div>

      <?php if ($error !== ''): ?>
        <p class="rounded-xl bg-card border border-line p-3 text-sm text-danger"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if ($mode === 'signup'): ?>
        <label class="block text-sm font-medium text-ink">
          Your name
          <input required type="text" name="name" class="field mt-1" placeholder="Jane Doe" />
        </label>
      <?php endif; ?>

      <label class="block text-sm font-medium text-ink">
        Email
        <input required type="email" name="email" class="field mt-1" placeholder="jane@example.com" />
      </label>

      <?php if ($mode === 'signup'): ?>
        <label class="block text-sm font-medium text-ink">
          Phone
          <input type="text" name="phone" class="field mt-1" placeholder="Optional" />
        </label>
      <?php endif; ?>

      <label class="block text-sm font-medium text-ink">
        Password
        <input required type="password" name="password" minlength="6" class="field mt-1" placeholder="••••••••" />
      </label>

      <button type="submit" class="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">
        <?= $mode === 'signup' ? 'Create account' : 'Sign in' ?>
      </button>

      <a href="menu.php" class="tap inline-flex w-full items-center justify-center text-sm text-muted">
        Continue without an account
      </a>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
