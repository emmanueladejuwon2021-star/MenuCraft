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
            flash('ok', 'Guest account created! You can now place orders.');
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
            flash('ok', 'Profile details updated!');
            header('Location: guest-account.php');
            exit;
        }
    }
}

$mode = $_GET['mode'] ?? 'signin';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter mx-auto max-w-lg space-y-6 pb-16">

  <?php if ($isGuest && $currentUser): ?>
    <!-- Signed-in Profile View -->
    <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">
      <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-6 border-b border-line">
        <span class="text-xs font-bold uppercase tracking-wider text-pine">👤 Registered Guest</span>
        <h1 class="mt-1 text-2xl font-extrabold text-ink">My Profile</h1>
        <p class="mt-1 text-xs text-muted">Signed in as <?= htmlspecialchars($currentUser['email']) ?></p>
      </div>

      <form method="POST" action="guest-account.php" class="p-6 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update" />

        <?php if ($error !== ''): ?>
          <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-3 text-xs font-bold text-red-600">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Full Name</label>
          <input required type="text" name="name" value="<?= htmlspecialchars($currentUser['name']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
        </div>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Email Address</label>
          <input type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled class="h-11 w-full rounded-2xl border border-line bg-paper/50 px-4 text-sm font-medium text-muted cursor-not-allowed" />
        </div>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Phone Number</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" placeholder="e.g. 08012345678" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
        </div>

        <div class="pt-2 space-y-2">
          <button type="submit" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-sm text-white shadow-soft transition-transform active:scale-95">
            Save Details
          </button>

          <a href="logout.php" class="tap flex h-11 w-full items-center justify-center rounded-full border border-line bg-paper text-xs font-bold text-red-600 hover:bg-red-50">
            Sign Out
          </a>
        </div>
      </form>
    </div>

  <?php else: ?>

    <!-- Guest Auth Form (Sign In / Sign Up) -->
    <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">

      <div class="p-6 border-b border-line space-y-4">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-pine">👋 Guest Portal</span>
          <h1 class="mt-1 text-2xl font-extrabold text-ink"><?= $mode === 'signup' ? 'Create Guest Account' : 'Welcome Back' ?></h1>
          <p class="mt-1 text-xs text-muted">Sign in or create an account to easily save your orders and profile details.</p>
        </div>

        <!-- Mode switcher tabs -->
        <div class="grid grid-cols-2 gap-1 rounded-full bg-paper p-1 border border-line">
          <a href="guest-account.php?mode=signin" class="tap inline-flex h-9 items-center justify-center rounded-full text-xs font-bold transition-all <?= $mode === 'signin' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
            Sign In
          </a>
          <a href="guest-account.php?mode=signup" class="tap inline-flex h-9 items-center justify-center rounded-full text-xs font-bold transition-all <?= $mode === 'signup' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
            Create Account
          </a>
        </div>
      </div>

      <form method="POST" action="guest-account.php?mode=<?= htmlspecialchars($mode) ?>" class="p-6 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $mode === 'signup' ? 'signup' : 'login' ?>" />

        <?php if ($error !== ''): ?>
          <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-3 text-xs font-bold text-red-600">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <?php if ($mode === 'signup'): ?>
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Full Name</label>
            <input required type="text" name="name" placeholder="e.g. Alex Johnson" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Email Address</label>
          <input required type="email" name="email" placeholder="alex@example.com" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
        </div>

        <?php if ($mode === 'signup'): ?>
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Phone Number <span class="text-muted font-normal">(Optional)</span></label>
            <input type="text" name="phone" placeholder="08012345678" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Password</label>
          <input required type="password" name="password" minlength="6" placeholder="••••••••" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
        </div>

        <div class="pt-2 space-y-3">
          <button type="submit" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-sm text-white shadow-soft transition-transform active:scale-95">
            <span><?= $mode === 'signup' ? 'Create Account' : 'Sign In' ?></span>
            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
          </button>

          <a href="menu.php" class="tap flex h-11 w-full items-center justify-center rounded-full border border-line bg-paper text-xs font-bold text-muted hover:text-ink">
            Continue as Guest without Account &rarr;
          </a>
        </div>
      </form>
    </div>

  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
