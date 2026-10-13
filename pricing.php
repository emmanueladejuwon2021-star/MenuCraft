<?php
// pricing.php - Staff Quick Pricing Manager
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/pricing.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);
$error = '';

$categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();
$selectedCatId = (int)($_POST['category_id'] ?? $_GET['category_id'] ?? ($categories[0]['id'] ?? 0));
$mode = $_POST['mode'] ?? $_GET['mode'] ?? 'percent';
$amount = (float)($_POST['amount'] ?? $_GET['amount'] ?? 10);
$confirm = !empty($_POST['confirm']);

// Handle Bulk Update Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirm) {
    require_csrf();
    // existing apply logic continues
    if ($selectedCatId <= 0) {
        $error = 'Please choose a category.';
    } elseif ($mode !== 'percent' && $mode !== 'amount') {
        $error = 'Choose percent or a fixed amount.';
    } elseif ($mode === 'percent' && ($amount < -100 || $amount > 500)) {
        $error = 'Use a percent between -100 and 500.';
    } else {
        $dishes = $db->prepare("SELECT id, price FROM menu_items WHERE category_id = ?");
        $dishes->execute([$selectedCatId]);
        $rows = $dishes->fetchAll();

        if (empty($rows)) {
            flash('ok', 'No dishes in that list to update');
            header('Location: pricing.php?category_id=' . $selectedCatId);
            exit;
        }

        $floored = false;
        $upd = $db->prepare("UPDATE menu_items SET price = ? WHERE id = ?");
        foreach ($rows as $row) {
            $newPrice = applyPrice($row['price'], $mode, $amount);
            if ($newPrice === 0.0 && (float)$row['price'] > 0) {
                $floored = true;
            }
            $upd->execute([$newPrice, $row['id']]);
        }

        $msg = $floored ? "Prices updated. Some dishes stopped at zero." : "Price updated successfully";
        flash('ok', $msg);
        header('Location: pricing.php?category_id=' . $selectedCatId);
        exit;
    }
}

// Fetch preview dishes
$previewDishes = [];
if ($selectedCatId > 0) {
    $stmt = $db->prepare("SELECT id, name, price FROM menu_items WHERE category_id = ? ORDER BY name");
    $stmt->execute([$selectedCatId]);
    $previewDishes = $stmt->fetchAll();
}

$percentOk = ($mode !== 'percent') || ($amount >= -100 && $amount <= 500);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-4">
  <?php 
  $title = "Quick prices";
  $hint = "Change every dish in one list.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-line bg-card p-4 text-sm text-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="GET" action="pricing.php" class="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
    <label class="block text-sm font-medium text-ink">
      List
      <select name="category_id" class="field mt-1" onchange="this.form.submit()">
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" <?= ((int)$selectedCatId === (int)$cat['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="grid grid-cols-2 gap-2">
      <label class="block text-sm font-medium text-ink">
        Change
        <select name="mode" class="field mt-1" onchange="this.form.submit()">
          <option value="percent" <?= $mode === 'percent' ? 'selected' : '' ?>>Percent</option>
          <option value="amount" <?= $mode === 'amount' ? 'selected' : '' ?>>Fixed amount</option>
        </select>
      </label>
      <label class="block text-sm font-medium text-ink">
        <?= $mode === 'percent' ? 'Percent' : 'Amount' ?>
        <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string)$amount) ?>" class="field mt-1" onchange="this.form.submit()" />
      </label>
    </div>

    <p class="text-xs text-muted">
      <?= $mode === 'percent' 
          ? '10 raises every price by 10 percent. -10 lowers them by 10 percent.' 
          : '10 adds 10 to every price. -10 takes 10 off. Prices cannot go below zero.' ?>
    </p>

    <?php if (!$percentOk): ?>
      <p class="text-sm text-danger">Use a percent between -100 and 500.</p>
    <?php endif; ?>
  </form>

  <?php if (!empty($previewDishes)): ?>
    <!-- Confirmation Dialog / Apply Form -->
    <form method="POST" action="pricing.php" class="rounded-2xl border border-line bg-card p-4 shadow-soft space-y-3" onsubmit="return confirm('Change <?= count($previewDishes) ?> dish prices? This updates every price in the list.');">
      <?= csrf_field() ?>
      <input type="hidden" name="category_id" value="<?= (int)$selectedCatId ?>" />
      <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>" />
      <input type="hidden" name="amount" value="<?= htmlspecialchars((string)$amount) ?>" />
      <input type="hidden" name="confirm" value="1" />

      <button type="submit" <?= !$percentOk ? 'disabled' : '' ?> class="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert disabled:opacity-60">
        Update <?= count($previewDishes) ?> dish prices in this list
      </button>
    </form>
  <?php endif; ?>

  <!-- Live Preview of Price Changes -->
  <div class="space-y-2">
    <?php if (empty($previewDishes)): ?>
      <p class="rounded-2xl border border-dashed border-line bg-card px-3 py-6 text-center text-sm text-muted">No dishes in this list.</p>
    <?php else: ?>
      <?php foreach ($previewDishes as $item): 
        $newPrice = applyPrice($item['price'], $mode, $amount);
      ?>
        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-xl border border-line bg-card px-3 py-2">
          <span class="min-w-0 break-words text-sm font-medium text-ink"><?= htmlspecialchars($item['name']) ?></span>
          <span class="shrink-0 text-xs font-semibold text-ink">
            <?= money($settings['currency_symbol'], $item['price']) ?> → <?= money($settings['currency_symbol'], $newPrice) ?>
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
