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
$rawAmount = $_POST['amount'] ?? $_GET['amount'] ?? '10';
$amount = is_numeric($rawAmount) ? (float)$rawAmount : null;
$confirm = !empty($_POST['confirm']);

// Handle Bulk Update Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirm) {
    require_csrf();
    if ($selectedCatId <= 0) {
        $error = 'Please choose a category.';
    } elseif ($mode !== 'percent' && $mode !== 'amount') {
        $error = 'Choose percent or a fixed amount.';
    } elseif ($amount === null || !is_finite($amount)) {
        $error = 'Please enter a valid number for the adjustment.';
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

        $msg = $floored ? "Prices updated. Some dishes stopped at zero floor." : "Category prices updated successfully!";
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

$percentOk = ($mode !== 'percent') || ($amount !== null && $amount >= -100 && $amount <= 500);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-6 pb-16">
  <?php 
  $title = "Quick Bulk Pricing Engine";
  $hint = "Adjust dish prices for an entire category at once by percentage (+10%, -5%) or fixed amount.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if ($error !== ''): ?>
    <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 p-4 text-xs font-bold text-red-600">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">
    <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-4 border-b border-line">
      <span class="text-xs font-bold uppercase tracking-wider text-pine">🏷️ Category Price Adjuster</span>
      <h2 class="mt-0.5 text-lg font-extrabold text-ink">Set Price Rules</h2>
    </div>

    <form method="GET" action="pricing.php" class="p-6 space-y-4">
      <div>
        <label class="block text-xs font-bold text-ink mb-1">Select Target Category</label>
        <select name="category_id" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-bold text-ink focus:border-pine focus:outline-none" onchange="this.form.submit()">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= ((int)$selectedCatId === (int)$cat['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-xs font-bold text-ink mb-1">Adjustment Type</label>
          <select name="mode" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-bold text-ink focus:border-pine focus:outline-none" onchange="this.form.submit()">
            <option value="percent" <?= $mode === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
            <option value="amount" <?= $mode === 'amount' ? 'selected' : '' ?>>Fixed Amount (<?= htmlspecialchars($settings['currency_symbol']) ?>)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-ink mb-1"><?= $mode === 'percent' ? 'Percentage (+ or -)' : 'Amount (+ or -)' ?></label>
          <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars((string)($amount ?? '')) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-bold text-ink focus:border-pine focus:outline-none" onchange="this.form.submit()" />
        </div>
      </div>

      <div class="rounded-2xl bg-paper/60 p-3 text-xs text-muted border border-line/60">
        💡 <strong>Tip:</strong> Entering <code>10</code> increases prices by 10% (or +<?= htmlspecialchars($settings['currency_symbol']) ?>10). Entering <code>-10</code> decreases them. Prices automatically stop at 0 to prevent negative pricing.
      </div>

      <?php if (!$percentOk): ?>
        <p class="text-xs font-bold text-red-600">Please enter a percentage between -100% and +500%.</p>
      <?php endif; ?>
    </form>
  </div>

  <?php if (!empty($previewDishes)): ?>
    <!-- Apply Form -->
    <form method="POST" action="pricing.php" class="rounded-3xl border border-pine/40 bg-card p-6 shadow-card space-y-4" onsubmit="return confirm('Apply new prices to all <?= count($previewDishes) ?> dishes in this category?');">
      <?= csrf_field() ?>
      <input type="hidden" name="category_id" value="<?= (int)$selectedCatId ?>" />
      <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>" />
      <input type="hidden" name="amount" value="<?= htmlspecialchars((string)($amount ?? '')) ?>" />
      <input type="hidden" name="confirm" value="1" />

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-line pb-4">
        <div>
          <h3 class="font-extrabold text-base text-ink">Live Price Change Preview</h3>
          <p class="text-xs text-muted">Review calculated prices before applying changes</p>
        </div>
        <button type="submit" <?= !$percentOk ? 'disabled' : '' ?> class="tap shrink-0 rounded-full bg-pine px-6 py-3 text-xs font-bold text-white shadow-soft transition-transform active:scale-95 disabled:opacity-40">
          Apply Prices to <?= count($previewDishes) ?> Dishes
        </button>
      </div>

      <!-- Preview Table -->
      <div class="divide-y divide-line/60">
        <?php foreach ($previewDishes as $item):
          $newPrice = ($amount !== null && is_finite($amount)) ? applyPrice($item['price'], $mode, $amount) : (float)$item['price'];
          $diff = $newPrice - (float)$item['price'];
        ?>
          <div class="flex items-center justify-between py-2.5 text-xs">
            <span class="font-bold text-ink"><?= htmlspecialchars($item['name']) ?></span>
            <div class="flex items-center gap-3">
              <span class="text-muted line-through"><?= money($settings['currency_symbol'], $item['price']) ?></span>
              <span class="font-extrabold text-pine text-sm"><?= money($settings['currency_symbol'], $newPrice) ?></span>
              <span class="rounded-full px-2 py-0.5 font-bold text-[10px] <?= $diff >= 0 ? 'bg-pine/10 text-pine' : 'bg-red-100 text-red-600' ?>">
                <?= $diff >= 0 ? '+' : '' ?><?= money($settings['currency_symbol'], $diff) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </form>
  <?php else: ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <p class="text-sm font-bold text-muted">No dishes found in this category to modify.</p>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
