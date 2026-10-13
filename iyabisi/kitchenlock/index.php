<?php
// iyabisi/kitchenlock/index.php - Secret Kitchen Entrance Door
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

// If already signed in as staff, proceed directly to kitchen orders
if (is_staff()) {
    header('Location: ../../orders.php');
    exit;
}

$db = getDb();
$settings = get_restaurant_settings($db);
$mode = $_GET['mode'] ?? 'signin';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = login_user($db, $email, $password);
        if ($res['ok']) {
            if ($res['user']['role'] === 'staff') {
                flash('ok', "Welcome to the kitchen, {$res['user']['name']}");
                header('Location: ../../orders.php');
                exit;
            } else {
                flash('error', 'This account does not have kitchen staff permissions.');
                header('Location: ../../menu.php');
                exit;
            }
        } else {
            $error = $res['message'];
        }
    }

    if ($action === 'signup') {
        $res = register_user($db, [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'restaurant_name' => $_POST['restaurant_name'] ?? $settings['restaurant_name'],
            'role' => 'staff'
        ], get_current_user_session());

        if ($res['ok']) {
            // Save restaurant name to settings
            $restName = clean_label($_POST['restaurant_name'] ?? '', 80);
            if ($restName !== '') {
                $db->prepare("UPDATE restaurant_settings SET restaurant_name = ? WHERE id = 1")->execute([$restName]);
            }
            flash('ok', 'Kitchen account created. Welcome!');
            header('Location: ../../orders.php');
            exit;
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Kitchen Gate — <?= htmlspecialchars($settings['restaurant_name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../assets/css/app.css">
  <script src="../../assets/js/app.js" defer></script>
</head>
<body class="min-h-screen bg-paper pb-16 flex flex-col justify-center items-center px-4">
  <div class="w-full max-w-lg space-y-4">
    <div class="text-center">
      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Kitchen Door Lock</p>
      <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-ink mt-1">Staff Entrance</h1>
      <p class="text-xs text-muted mt-1">Bookmark this screen on the kitchen tablet only.</p>
    </div>

    <form method="POST" action="index.php?mode=<?= htmlspecialchars($mode) ?>" class="space-y-3 rounded-2xl border border-line bg-card p-6 shadow-soft">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $mode === 'signup' ? 'signup' : 'login' ?>" />

      <div class="grid grid-cols-2 gap-2 rounded-full bg-paper p-1">
        <a href="index.php?mode=signin" class="tap inline-flex items-center justify-center rounded-full text-sm font-medium <?= $mode === 'signin' ? 'bg-pine text-invert' : 'text-muted' ?>">
          Sign in
        </a>
        <a href="index.php?mode=signup" class="tap inline-flex items-center justify-center rounded-full text-sm font-medium <?= $mode === 'signup' ? 'bg-pine text-invert' : 'text-muted' ?>">
          Create account
        </a>
      </div>

      <?php if ($error !== ''): ?>
        <p class="rounded-xl bg-card border border-line p-3 text-sm text-danger"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if ($mode === 'signup'): ?>
        <label class="block text-sm font-medium text-ink">
          Your name
          <input required type="text" name="name" class="field mt-1" placeholder="Chef Ada" />
        </label>
      <?php endif; ?>

      <label class="block text-sm font-medium text-ink">
        Email
        <input required type="email" name="email" class="field mt-1" placeholder="kitchen@restaurant.com" />
      </label>

      <label class="block text-sm font-medium text-ink">
        Password
        <input required type="password" name="password" minlength="6" class="field mt-1" placeholder="••••••••" />
      </label>

      <?php if ($mode === 'signup'): ?>
        <label class="block text-sm font-medium text-ink">
          Restaurant name
          <input required type="text" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" class="field mt-1" />
        </label>
      <?php endif; ?>

      <button type="submit" class="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert">
        <?= $mode === 'signup' ? 'Create kitchen account' : 'Sign in to kitchen' ?>
      </button>

      <div class="text-center pt-2">
        <a href="../../menu.php" class="text-sm text-muted hover:underline">
          Just want food? Open the menu
        </a>
      </div>
    </form>
  </div>
</body>
</html>
