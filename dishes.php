<?php
// dishes.php - Staff Dish & Category Manager
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);
$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = clean_label($_POST['category_name'] ?? '', 60);
        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            try {
                $maxOrder = (int)$db->query("SELECT COALESCE(MAX(display_order), 0) FROM categories")->fetchColumn();
                $stmt = $db->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
                $stmt->execute([$name, $maxOrder + 1]);
                flash('ok', "Category \"{$name}\" added.");
                header('Location: dishes.php');
                exit;
            } catch (Exception $e) {
                $error = 'Could not add category (name may already exist).';
            }
        }
    }

    if ($action === 'delete_category') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
            $stmt->execute([$catId]);
            $dishCount = (int)$stmt->fetchColumn();
            if ($dishCount > 0) {
                $error = 'Cannot delete category that still has dishes. Move or remove dishes first.';
            } else {
                $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
                flash('ok', 'Category removed.');
                header('Location: dishes.php');
                exit;
            }
        }
    }

    if ($action === 'add_dish' || $action === 'edit_dish') {
        $id = (int)($_POST['id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = clean_label($_POST['name'] ?? '', 80);
        $description = clean_label($_POST['description'] ?? '', 300);
        $price = is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : -1;
        $imageUrl = clean_image_url($_POST['image_url'] ?? '');
        $tags = clean_label($_POST['tags'] ?? '', 120);
        $prepTime = max(1, min(180, (int)($_POST['prep_time'] ?? 10)));
        $isAvailable = !empty($_POST['is_available']) ? 1 : 0;

        if ($name === '' || $categoryId <= 0 || $price < 0) {
            $error = 'Name, category, and a valid price are required.';
        } else {
            try {
                if ($action === 'add_dish') {
                    $stmt = $db->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, tags, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$categoryId, $name, $description, $price, $imageUrl, $isAvailable, $tags, $prepTime]);
                    flash('ok', "Dish \"{$name}\" added.");
                } else {
                    $stmt = $db->prepare("UPDATE menu_items SET category_id=?, name=?, description=?, price=?, image_url=?, is_available=?, tags=?, prep_time=? WHERE id=?");
                    $stmt->execute([$categoryId, $name, $description, $price, $imageUrl, $isAvailable, $tags, $prepTime, $id]);
                    flash('ok', "Dish \"{$name}\" updated.");
                }
                header('Location: dishes.php');
                exit;
            } catch (Exception $e) {
                $error = 'Could not save dish.';
            }
        }
    }

    if ($action === 'delete_dish') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $nameStmt = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
            $nameStmt->execute([$id]);
            $name = $nameStmt->fetchColumn() ?: 'Dish';
            $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
            flash('ok', "\"{$name}\" removed.");
            header('Location: dishes.php');
            exit;
        }
    }
}

// Fetch data
$categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();
$items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();

$byCategory = [];
foreach ($categories as $cat) {
    $byCategory[(int)$cat['id']] = ['name' => $cat['name'], 'dishes' => []];
}
foreach ($items as $item) {
    $cid = (int)$item['category_id'];
    if (isset($byCategory[$cid])) {
        $byCategory[$cid]['dishes'][] = $item;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-8 pb-16">
  <?php
  $title = "Dish Manager";
  $hint = "Add, edit, or remove dishes and categories. Changes appear on the live menu immediately.";
  require __DIR__ . '/includes/kitchen_nav.php';
  ?>

  <?php if ($error): ?>
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Add Category -->
  <div class="rounded-3xl border border-line bg-card p-6 shadow-soft">
    <h2 class="text-lg font-bold text-ink mb-4">Add Category</h2>
    <form method="POST" class="flex flex-col sm:flex-row gap-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_category">
      <input type="text" name="category_name" placeholder="e.g. Small Chops" required maxlength="60" class="flex-1 rounded-xl border border-line px-4 py-2.5 text-sm">
      <button type="submit" class="tap rounded-full bg-pine px-6 py-2.5 text-sm font-bold text-white">Add Category</button>
    </form>
  </div>

  <!-- Dishes by Category -->
  <?php foreach ($byCategory as $catId => $cat): ?>
    <div class="rounded-3xl border border-line bg-card shadow-soft overflow-hidden">
      <div class="flex items-center justify-between bg-paper/60 px-6 py-4 border-b border-line">
        <h2 class="text-lg font-bold text-ink"><?= htmlspecialchars($cat['name']) ?></h2>
        <?php if (empty($cat['dishes'])): ?>
          <form method="POST" class="inline" onsubmit="return confirm('Delete this empty category?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="category_id" value="<?= $catId ?>">
            <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800">Delete Category</button>
          </form>
        <?php endif; ?>
      </div>

      <div class="divide-y divide-line">
        <?php foreach ($cat['dishes'] as $dish): ?>
          <div class="p-5 flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-ink"><?= htmlspecialchars($dish['name']) ?></h3>
                <?php if (empty($dish['is_available'])): ?>
                  <span class="text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Sold out</span>
                <?php endif; ?>
              </div>
              <p class="text-xs text-muted mt-0.5"><?= htmlspecialchars($dish['description'] ?: 'No description') ?></p>
              <p class="text-sm font-bold text-pine mt-1"><?= money($settings['currency_symbol'], $dish['price']) ?> · <?= (int)$dish['prep_time'] ?> min · <?= htmlspecialchars($dish['tags'] ?: 'No tags') ?></p>
            </div>
            <div class="flex gap-2 shrink-0">
              <button type="button" onclick="openEditModal(<?= (int)$dish['id'] ?>, <?= htmlspecialchars(json_encode($dish), ENT_QUOTES) ?>)" class="tap rounded-full border border-line px-4 py-1.5 text-xs font-bold">Edit</button>
              <form method="POST" class="inline" onsubmit="return confirm('Remove this dish permanently?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_dish">
                <input type="hidden" name="id" value="<?= (int)$dish['id'] ?>">
                <button type="submit" class="tap rounded-full border border-red-200 text-red-600 px-4 py-1.5 text-xs font-bold">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Add dish form for this category -->
      <div class="p-5 bg-paper/30 border-t border-line">
        <details class="group">
          <summary class="cursor-pointer text-sm font-bold text-pine">+ Add dish to <?= htmlspecialchars($cat['name']) ?></summary>
          <form method="POST" class="mt-4 grid gap-3 sm:grid-cols-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_dish">
            <input type="hidden" name="category_id" value="<?= $catId ?>">
            <input type="text" name="name" placeholder="Dish name" required class="rounded-xl border border-line px-3 py-2 text-sm">
            <input type="number" name="price" placeholder="Price" step="0.01" min="0" required class="rounded-xl border border-line px-3 py-2 text-sm">
            <input type="text" name="description" placeholder="Short description" class="sm:col-span-2 rounded-xl border border-line px-3 py-2 text-sm">
            <input type="url" name="image_url" placeholder="Image URL (https://...)" class="rounded-xl border border-line px-3 py-2 text-sm">
            <input type="text" name="tags" placeholder="Tags (Spicy, Vegetarian)" class="rounded-xl border border-line px-3 py-2 text-sm">
            <input type="number" name="prep_time" placeholder="Prep minutes" value="10" min="1" max="180" class="rounded-xl border border-line px-3 py-2 text-sm">
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" name="is_available" value="1" checked class="rounded"> Available
            </label>
            <button type="submit" class="tap sm:col-span-2 rounded-full bg-pine px-5 py-2 text-sm font-bold text-white">Save Dish</button>
          </form>
        </details>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (empty($categories)): ?>
    <p class="text-center text-muted">No categories yet. Add one above to get started.</p>
  <?php endif; ?>
</section>

<!-- Edit Dish Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
  <div class="w-full max-w-lg rounded-3xl border border-line bg-card p-6 shadow-card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-ink">Edit Dish</h3>
      <button type="button" onclick="closeEditModal()" class="text-muted hover:text-ink text-2xl leading-none">&times;</button>
    </div>
    <form id="edit-form" method="POST" class="space-y-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_dish">
      <input type="hidden" name="id" id="edit-id">
      <input type="hidden" name="category_id" id="edit-category">

      <div>
        <label class="block text-xs font-bold text-ink mb-1">Name</label>
        <input type="text" name="name" id="edit-name" required class="w-full rounded-xl border border-line px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-ink mb-1">Price</label>
        <input type="number" name="price" id="edit-price" step="0.01" min="0" required class="w-full rounded-xl border border-line px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-ink mb-1">Description</label>
        <textarea name="description" id="edit-desc" rows="2" class="w-full rounded-xl border border-line px-3 py-2 text-sm"></textarea>
      </div>
      <div>
        <label class="block text-xs font-bold text-ink mb-1">Tags</label>
        <input type="text" name="tags" id="edit-tags" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-ink mb-1">Prep time (min)</label>
        <input type="number" name="prep_time" id="edit-prep" min="1" max="180" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-ink mb-1">Image URL</label>
        <input type="url" name="image_url" id="edit-image" class="w-full rounded-xl border border-line px-3 py-2 text-sm">
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_available" id="edit-available" value="1" class="rounded"> Available
      </label>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="tap flex-1 rounded-full bg-pine px-5 py-2.5 text-sm font-bold text-white">Save Changes</button>
        <button type="button" onclick="closeEditModal()" class="tap flex-1 rounded-full border border-line px-5 py-2.5 text-sm font-bold">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(id, dish) {
  document.getElementById('edit-id').value = id;
  document.getElementById('edit-category').value = dish.category_id;
  document.getElementById('edit-name').value = dish.name || '';
  document.getElementById('edit-price').value = dish.price || '';
  document.getElementById('edit-desc').value = dish.description || '';
  document.getElementById('edit-tags').value = dish.tags || '';
  document.getElementById('edit-prep').value = dish.prep_time || 10;
  document.getElementById('edit-image').value = dish.image_url || '';
  document.getElementById('edit-available').checked = !!dish.is_available;
  document.getElementById('edit-modal').classList.remove('hidden');
  document.getElementById('edit-modal').classList.add('flex');
}
function closeEditModal() {
  document.getElementById('edit-modal').classList.add('hidden');
  document.getElementById('edit-modal').classList.remove('flex');
}
// Close on backdrop click
document.getElementById('edit-modal').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});
document.addEventListener("keydown", function(e) {
  if (e.key === "Escape" && !document.getElementById("edit-modal").classList.contains("hidden")) {
    closeEditModal();
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
