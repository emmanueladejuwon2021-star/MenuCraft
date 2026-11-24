<?php
// stock.php - Kitchen Stock Toggle Screen
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);

// Handle AJAX / POST stock toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $isAvailable = (int)($_POST['is_available'] ?? 0) === 1 ? 1 : 0;

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
        $stmt->execute([$isAvailable, $id]);

        $nameStmt = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
        $nameStmt->execute([$id]);
        $name = $nameStmt->fetchColumn() ?: 'Dish';

        if (is_ajax()) {
            json_response([
                'ok' => true,
                'message' => $isAvailable ? "{$name} marked in stock" : "{$name} marked sold out",
                'is_available' => $isAvailable,
            ]);
        }
        flash('ok', $isAvailable ? "{$name} is back in stock" : "{$name} marked sold out");
    }
    header('Location: stock.php');
    exit;
}

// Fetch all dishes grouped by category
$categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();
$items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();

$byCategory = [];
foreach ($categories as $cat) {
    $byCategory[(int)$cat['id']] = [
        'name' => $cat['name'],
        'dishes' => [],
    ];
}
foreach ($items as $item) {
    $cid = (int)$item['category_id'];
    if (isset($byCategory[$cid])) {
        $byCategory[$cid]['dishes'][] = $item;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/kitchen_nav.php';
?>

<section class="page-enter space-y-6 pb-12">
  <div>
    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-pine">
      <span>📊 Stock Control</span>
    </div>
    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Sold Out Switches</h1>
    <p class="mt-1 text-sm text-muted">Big tactile buttons for kitchen tablets. Tap to mark a dish sold out or put it back in stock instantly.</p>
  </div>

  <div class="space-y-6">
    <?php foreach ($byCategory as $catId => $group): ?>
      <?php if (empty($group['dishes'])) continue; ?>
      <div class="rounded-3xl border border-line bg-card shadow-soft overflow-hidden">
        <div class="border-b border-line bg-paper/60 px-5 py-3">
          <h2 class="font-bold text-base text-ink"><?= htmlspecialchars($group['name']) ?></h2>
        </div>
        <div class="p-4 grid gap-3 sm:grid-cols-2">
          <?php foreach ($group['dishes'] as $item):
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
                onclick="toggleStock(<?= (int)$item['id'] ?>, <?= json_encode($item['name']) ?>, this)"
                class="tap stock-switch relative flex h-12 w-24 shrink-0 items-center rounded-full border p-1 transition-colors duration-200 cursor-pointer <?= $isAvail ? 'border-pine bg-pine' : 'border-line bg-card' ?>"
              >
                <span class="stock-dot flex h-9 w-9 items-center justify-center rounded-full shadow-md font-extrabold text-xs transition-all duration-200 <?= $isAvail ? 'ml-auto bg-white text-pine' : 'ml-0 bg-ink text-canvas' ?>">
                  <?= $isAvail ? 'ON' : 'OFF' ?>
                </span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
