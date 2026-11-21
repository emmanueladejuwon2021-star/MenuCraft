<?php
// dishes.php - Staff Dish Manager
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = clean_label($_POST['name'] ?? '', 60);
        if ($name) {
            $maxOrder = (int)$db->query("SELECT COALESCE(MAX(display_order), 0) FROM categories")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
            $stmt->execute([$name, $maxOrder + 1]);
            flash('ok', "Category \"{$name}\" added");
        }
        header('Location: dishes.php');
        exit;
    }

    if ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            flash('ok', 'Category removed');
        }
        header('Location: dishes.php');
        exit;
    }

    if ($action === 'save_dish') {
        $id = (int)($_POST['id'] ?? 0);
        $name = clean_label($_POST['name'] ?? '', 80);
        $price = max(0, (float)($_POST['price'] ?? 0));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = clean_label($_POST['description'] ?? '', 500);
        $imageUrl = clean_image_url($_POST['image_url'] ?? '');
        $prepTime = max(0, (int)($_POST['prep_time'] ?? 0));
        $tags = clean_label($_POST['tags'] ?? '', 120);
        $isAvailable = !empty($_POST['is_available']) ? 1 : 0;

        if (!$name || $price <= 0 || !$categoryId) {
            flash('error', 'Name, price, and category are required');
            header('Location: dishes.php');
            exit;
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE menu_items SET name=?, price=?, category_id=?, description=?, image_url=?, prep_time=?, tags=?, is_available=? WHERE id=?");
            $stmt->execute([$name, $price, $categoryId, $description, $imageUrl, $prepTime, $tags, $isAvailable, $id]);
            flash('ok', "Dish \"{$name}\" updated");
        } else {
            $stmt = $db->prepare("INSERT INTO menu_items (name, price, category_id, description, image_url, prep_time, tags, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $categoryId, $description, $imageUrl, $prepTime, $tags, $isAvailable]);
            flash('ok', "Dish \"{$name}\" added");
        }
        header('Location: dishes.php');
        exit;
    }

    if ($action === 'delete_dish') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
            flash('ok', 'Dish removed');
        }
        header('Location: dishes.php');
        exit;
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY display_order, name")->fetchAll();
$items = $db->query("SELECT m.*, c.name AS category_name FROM menu_items m LEFT JOIN categories c ON c.id = m.category_id ORDER BY m.name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/kitchen_nav.php';
?>

<section class="page-enter space-y-8">
  <div>
    <h1 class="text-3xl font-extrabold text-ink">Dish Manager</h1>
    <p class="mt-1 text-sm text-muted">Add, edit, or remove dishes and categories.</p>
  </div>

  <!-- Add Category -->
  <div class="rounded-3xl border border-line bg-card p-6 shadow-soft">
    <h2 class="font-bold text-lg mb-4">Categories</h2>
    <form method="POST" class="flex gap-2 mb-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_category">
      <input type="text" name="name" required placeholder="New category name" class="flex-1 rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
      <button type="submit" class="rounded-full bg-pine px-5 py-2 text-sm font-bold text-white">Add</button>
    </form>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($categories as $cat): ?>
        <form method="POST" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_category">
          <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
          <button type="submit" onclick="return confirm('Delete this category? Dishes will remain.')" class="rounded-full border border-line bg-paper px-3 py-1 text-xs font-medium hover:bg-red-50">
            <?= htmlspecialchars($cat['name']) ?> ×
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add / Edit Dish Form -->
  <div class="rounded-3xl border border-line bg-card p-6 shadow-soft">
    <h2 class="font-bold text-lg mb-4">Add or Edit Dish</h2>
    <form method="POST" class="grid gap-4 sm:grid-cols-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_dish">
      <input type="hidden" name="id" id="edit-id" value="">

      <div>
        <label class="block text-xs font-bold text-muted mb-1">Name</label>
        <input type="text" name="name" id="edit-name" required class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-muted mb-1">Price</label>
        <input type="number" name="price" id="edit-price" step="0.01" min="0" required class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
      </div>
      <div>
        <label class="block text-xs font-bold text-muted mb-1">Category</label>
        <select name="category_id" id="edit-category" required class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-bold text-muted mb-1">Prep Time (minutes)</label>
        <input type="number" name="prep_time" id="edit-prep" min="0" class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-muted mb-1">Description</label>
        <textarea name="description" id="edit-desc" rows="2" class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm"></textarea>
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-muted mb-1">Image URL</label>
        <input type="url" name="image_url" id="edit-image" class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-muted mb-1">Tags (comma separated)</label>
        <input type="text" name="tags" id="edit-tags" placeholder="Vegetarian, Spicy, Gluten-Free" class="w-full rounded-2xl border border-line bg-paper px-4 py-2 text-sm">
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" name="is_available" id="edit-available" value="1" checked class="rounded">
        <label for="edit-available" class="text-sm font-medium">Available (in stock)</label>
      </div>
      <div class="sm:col-span-2 flex gap-2">
        <button type="submit" class="rounded-full bg-pine px-6 py-2.5 text-sm font-bold text-white">Save Dish</button>
        <button type="button" onclick="resetForm()" class="rounded-full border border-line px-6 py-2.5 text-sm font-bold">Clear</button>
      </div>
    </form>
  </div>

  <!-- Dishes List -->
  <div class="space-y-3">
    <h2 class="font-bold text-lg">Current Dishes (<?= count($items) ?>)</h2>
    <?php foreach ($items as $item): ?>
      <article class="rounded-3xl border border-line bg-card p-4 shadow-soft flex items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-ink"><?= htmlspecialchars($item['name']) ?></h3>
            <span class="text-xs font-bold text-pine"><?= money($settings['currency_symbol'], $item['price']) ?></span>
            <?php if (empty($item['is_available'])): ?><span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Sold Out</span><?php endif; ?>
          </div>
          <p class="text-xs text-muted mt-0.5"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?> · <?= htmlspecialchars($item['tags'] ?? '') ?></p>
        </div>
        <div class="flex gap-2 shrink-0">
          <button type="button" onclick="editDish(<?= htmlspecialchars(json_encode($item)) ?>)" class="rounded-full border border-line px-3 py-1.5 text-xs font-bold hover:bg-paper">Edit</button>
          <form method="POST" class="inline" onsubmit="return confirm('Delete this dish permanently?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_dish">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <button type="submit" class="rounded-full bg-red-50 text-red-600 px-3 py-1.5 text-xs font-bold hover:bg-red-100">Delete</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<script>
function editDish(item) {
  document.getElementById('edit-id').value = item.id || '';
  document.getElementById('edit-name').value = item.name || '';
  document.getElementById('edit-price').value = item.price || '';
  document.getElementById('edit-category').value = item.category_id || '';
  document.getElementById('edit-desc').value = item.description || '';
  document.getElementById('edit-image').value = item.image_url || '';
  document.getElementById('edit-prep').value = item.prep_time || '';
  document.getElementById('edit-tags').value = item.tags || '';
  document.getElementById('edit-available').checked = !!item.is_available;
  document.getElementById('edit-name').scrollIntoView({ behavior: 'smooth' });
}
function resetForm() {
  document.getElementById('edit-id').value = '';
  document.querySelector('form').reset();
  document.getElementById('edit-available').checked = true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
