<?php
// account.php - Staff Account Settings & Sign Out
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$currentUser = get_current_user_session();
$settings = get_restaurant_settings($db);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = clean_label($_POST['name'] ?? '', 80);
    $phone = clean_label($_POST['phone'] ?? '', 30);
    $restaurantName = clean_label($_POST['restaurant_name'] ?? '', 80) ?: 'Iya Bisi Kitchen';
    $currencySymbol = clean_label($_POST['currency_symbol'] ?? '₦', 4);

    if ($name === '') {
        $error = 'Please enter your name.';
    } else {
        // Update user
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, restaurant_name = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $restaurantName, (int)$currentUser['id']]);

        // Update settings
        $st = $db->prepare("UPDATE restaurant_settings SET restaurant_name = ?, currency_symbol = ? WHERE id = 1");
        $st->execute([$restaurantName, $currencySymbol]);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['restaurant_name'] = $restaurantName;
        $currentUser = $_SESSION['user'];

        flash('ok', 'Kitchen staff settings saved!');
        header('Location: account.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter mx-auto max-w-xl space-y-6 pb-16">
  <?php
  $title = "Kitchen Staff Account";
  $hint = "Manage staff preferences, restaurant name, and currency settings.";
  require __DIR__ . '/includes/kitchen_nav.php';
  ?>

  <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">
    <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-4 border-b border-line">
      <span class="text-xs font-bold uppercase tracking-wider text-pine">⚙️ Staff Credentials</span>
      <h2 class="mt-0.5 text-lg font-extrabold text-ink">Settings & Profile</h2>
      <p class="text-xs text-muted">Logged in as <?= htmlspecialchars($currentUser['email']) ?></p>
    </div>

    <form method="POST" action="account.php" class="p-6 space-y-4">
      <?= csrf_field() ?>

      <?php if ($error !== ''): ?>
        <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 p-3 text-xs font-bold text-red-600">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <div>
        <label class="block text-xs font-bold text-ink mb-1">Your Name</label>
        <input required type="text" name="name" value="<?= htmlspecialchars($currentUser['name']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
      </div>

      <div>
        <label class="block text-xs font-bold text-ink mb-1">Email Address</label>
        <input type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled class="h-11 w-full rounded-2xl border border-line bg-paper/50 px-4 text-sm font-medium text-muted cursor-not-allowed" />
      </div>

      <div>
        <label class="block text-xs font-bold text-ink mb-1">Phone Number</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" placeholder="Kitchen tablet phone" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
      </div>

      <div>
        <label class="block text-xs font-bold text-ink mb-1">Restaurant Name</label>
        <input required type="text" name="restaurant_name" value="<?= htmlspecialchars($currentUser['restaurant_name'] ?: $settings['restaurant_name']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
      </div>

      <div>
        <label class="block text-xs font-bold text-ink mb-1">Currency Symbol</label>
        <input required type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" placeholder="₦" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
      </div>

      <div class="pt-3 space-y-2">
        <button type="submit" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-sm text-white shadow-soft transition-transform active:scale-95">
          Save Staff Settings
        </button>

        <a href="logout.php" class="tap flex h-11 w-full items-center justify-center rounded-full border border-line bg-paper text-xs font-bold text-red-600 hover:bg-red-50">
          Sign Out of Kitchen Staff Account
        </a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
