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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kitchen Gate — <?= htmlspecialchars($settings['restaurant_name']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../assets/css/app.css">
  <script src="../../assets/js/app.js" defer></script>
</head>
<body class="min-h-screen bg-canvas pb-16 flex flex-col justify-center items-center px-4">
  <div class="w-full max-w-md space-y-5">

    <div class="text-center">
      <span class="text-xs font-bold uppercase tracking-wider text-pine">🔐 Kitchen Lock</span>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-ink mt-1">Staff Entrance</h1>
      <p class="text-xs text-muted mt-1">Bookmark this screen on the kitchen tablet only.</p>
    </div>

    <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card p-6 space-y-4">
      <form method="POST" action="index.php?mode=<?= htmlspecialchars($mode) ?>" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $mode === 'signup' ? 'signup' : 'login' ?>" />

        <div class="grid grid-cols-2 gap-1 rounded-full bg-paper p-1 border border-line">
          <a href="index.php?mode=signin" class="tap inline-flex h-9 items-center justify-center rounded-full text-xs font-bold transition-all <?= $mode === 'signin' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
            Sign In
          </a>
          <a href="index.php?mode=signup" class="tap inline-flex h-9 items-center justify-center rounded-full text-xs font-bold transition-all <?= $mode === 'signup' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
            Create Account
          </a>
        </div>

        <?php if ($error !== ''): ?>
          <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 p-3 text-xs font-bold text-red-600">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <?php if ($mode === 'signup'): ?>
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Your Name</label>
            <input required type="text" name="name" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" placeholder="Chef Ada" />
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Kitchen Email</label>
          <input required type="email" name="email" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" placeholder="kitchen@restaurant.com" />
        </div>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Password</label>
          <input required type="password" name="password" minlength="6" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" placeholder="••••••••" />
        </div>

        <?php if ($mode === 'signup'): ?>
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Restaurant Name</label>
            <input required type="text" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
          </div>
        <?php endif; ?>

        <div class="pt-2 space-y-3">
          <button type="submit" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-sm text-white shadow-soft transition-transform active:scale-95">
            <span><?= $mode === 'signup' ? 'Create Kitchen Account' : 'Sign In to Kitchen' ?></span>
            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
          </button>

          <div class="text-center pt-1">
            <a href="../../menu.php" class="text-xs font-bold text-muted hover:text-ink">
              Just want food? Open guest menu &rarr;
            </a>
          </div>
        </div>
      </form>
    </div>

  </div>
</body>
</html>
