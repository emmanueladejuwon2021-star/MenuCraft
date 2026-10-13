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
    // existing action handling...
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

        flash('ok', 'Saved!');
        header('Location: account.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter mx-auto max-w-lg space-y-4">
  <form method="POST" action="account.php" class="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
    <?= csrf_field() ?>
    <div>
      <h1 class="text-lg font-semibold text-ink">Account settings</h1>
      <p class="mt-1 text-sm text-muted">Signed in as <?= htmlspecialchars($currentUser['email']) ?>. Save your details, or sign out below.</p>
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
      <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" placeholder="Kitchen contact" class="field mt-1" />
    </label>

    <label class="block text-sm font-medium text-ink">
      Restaurant name
      <input required type="text" name="restaurant_name" value="<?= htmlspecialchars($currentUser['restaurant_name'] ?: $settings['restaurant_name']) ?>" class="field mt-1" />
    </label>

    <label class="block text-sm font-medium text-ink">
      Money symbol
      <input required type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" placeholder="₦" class="field mt-1" />
    </label>

    <button type="submit" class="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">
      Save settings
    </button>
  </form>

  <a href="logout.php" class="tap inline-flex items-center justify-center h-11 w-full rounded-full border border-line bg-card text-sm font-medium text-ink">
    Sign out
  </a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
