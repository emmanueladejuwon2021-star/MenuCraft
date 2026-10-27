<?php
// stock.php - Kitchen Stock Toggle Switches
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();

// Handle Toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
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

<section class="page-enter space-y-6 pb-16">
  <?php 
  $title = "Tactile Stock Switches";
  $hint = "Designed for kitchen tablets. Tap any switch to instantly mark dishes in stock or sold out.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if (empty($categories)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <span class="text-5xl block mb-3">🔘</span>
      <h2 class="text-xl font-bold text-ink">No categories found</h2>
      <p class="mt-2 text-sm text-muted">Create menu categories first on the Dishes page.</p>
    </div>
  <?php else: ?>
    <div class="space-y-6">
      <?php foreach ($categories as $cat):
        $catDishes = array_filter($items, fn($i) => (int)$i['category_id'] === (int)$cat['id']);
      ?>
        <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">
          <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-3.5 border-b border-line flex items-center justify-between">
            <h2 class="font-extrabold text-base text-ink flex items-center gap-2">
              <span>📁 <?= htmlspecialchars($cat['name']) ?></span>
              <span class="text-xs font-bold text-muted bg-paper px-2.5 py-0.5 rounded-full border border-line/60"><?= count($catDishes) ?> items</span>
            </h2>
          </div>

          <?php if (empty($catDishes)): ?>
            <div class="p-6 text-center text-xs font-semibold text-muted">No dishes in this category.</div>
          <?php else: ?>
            <div class="p-4 grid gap-3 sm:grid-cols-2">
              <?php foreach ($catDishes as $item):
                $isAvail = !empty($item['is_available']);
              ?>
                <div class="flex items-center justify-between gap-3 rounded-2xl bg-paper/80 p-3.5 border border-line/60 transition-all hover:bg-paper">
                  <div class="min-w-0 flex-1">
                    <p class="font-bold text-sm text-ink truncate"><?= htmlspecialchars($item['name']) ?></p>
                    <p id="stock-label-<?= (int)$item['id'] ?>" class="text-xs font-bold mt-0.5 <?= $isAvail ? 'text-pine' : 'text-danger' ?>">
                      <?= $isAvail ? '● In stock' : '○ Sold out' ?>
                    </p>
                  </div>

                  <!-- Tactile Switch Button -->
                  <button
                    type="button"
                    aria-pressed="<?= $isAvail ? 'true' : 'false' ?>"
                    aria-label="<?= $isAvail ? 'Mark ' . htmlspecialchars($item['name']) . ' sold out' : 'Put ' . htmlspecialchars($item['name']) . ' back in stock' ?>"
                    onclick="toggleStock(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)"
                    class="tap relative flex h-11 w-20 shrink-0 items-center rounded-full border p-1 transition-colors duration-200 cursor-pointer <?= $isAvail ? 'border-pine bg-pine' : 'border-line bg-card' ?>"
                  >
                    <span class="stock-dot flex h-9 w-9 items-center justify-center rounded-full shadow-md font-extrabold text-xs transition-all duration-200 <?= $isAvail ? 'ml-auto bg-white text-pine' : 'ml-0 bg-ink text-canvas' ?>">
                      <?= $isAvail ? 'ON' : 'OFF' ?>
                    </span>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
