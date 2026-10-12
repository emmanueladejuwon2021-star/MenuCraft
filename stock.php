<?php
// stock.php - Kitchen Stock Toggle Switches
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();

// Handle Toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $isAvailable = !empty($_POST['is_available']) ? 1 : 0;

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        $stmt->execute([$isAvailable, $id]);

        $item = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
        $item->execute([$id]);
        $name = $item->fetchColumn() ?: 'Item';

        $msg = $isAvailable ? "Marked {$name} in stock" : "Marked {$name} sold out";
        if (is_ajax()) {
            json_response(['ok' => true, 'is_available' => $isAvailable, 'message' => $msg]);
        }
        flash('ok', $msg);
        header('Location: stock.php');
        exit;
    }
}

$categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();
$items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-4">
  <?php 
  $title = "Stock";
  $hint = "Tap a switch to mark a dish in stock or sold out.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if (empty($categories)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
      <p class="font-semibold text-ink">No lists yet</p>
      <p class="mt-2 text-sm text-muted">Add a category on the dishes page, then the switches show up here.</p>
    </div>
  <?php else: ?>
    <?php foreach ($categories as $cat): 
      $catDishes = array_filter($items, fn($i) => (int)$i['category_id'] === (int)$cat['id']);
    ?>
      <div class="rounded-2xl border border-line bg-card p-4 shadow-soft">
        <h2 class="mb-3 font-semibold text-ink text-base"><?= htmlspecialchars($cat['name']) ?></h2>
        <?php if (empty($catDishes)): ?>
          <p class="rounded-xl bg-paper px-3 py-3 text-sm text-muted">No dishes on this list.</p>
        <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($catDishes as $item): 
              $isAvail = !empty($item['is_available']);
            ?>
              <div class="flex items-center justify-between gap-3 rounded-xl bg-paper px-3 py-2">
                <div class="min-w-0">
                  <p class="font-medium text-ink truncate"><?= htmlspecialchars($item['name']) ?></p>
                  <p id="stock-label-<?= (int)$item['id'] ?>" class="text-xs font-semibold <?= $isAvail ? 'text-pine' : 'text-danger' ?>">
                    <?= $isAvail ? 'In stock' : 'Sold out' ?>
                  </p>
                </div>

                <!-- Tactile Switch Button -->
                <button
                  type="button"
                  aria-pressed="<?= $isAvail ? 'true' : 'false' ?>"
                  aria-label="<?= $isAvail ? 'Mark ' . htmlspecialchars($item['name']) . ' sold out' : 'Put ' . htmlspecialchars($item['name']) . ' back in stock' ?>"
                  onclick="toggleStock(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)"
                  class="tap relative flex h-11 w-[4.5rem] shrink-0 items-center rounded-full border px-1 <?= $isAvail ? 'border-pine bg-pine' : 'border-line bg-card' ?>"
                >
                  <span class="stock-dot block h-8 w-8 rounded-full shadow <?= $isAvail ? 'ml-auto bg-invert' : 'ml-0 bg-ink' ?>"></span>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
