<?php
// dishes.php - Staff Dish & Category Manager
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_category') {
        $name = clean_label($_POST['name'] ?? '', 40);
        if ($name === '') {
            $error = 'Please enter a category name.';
        } else {
            try {
                $maxOrder = (int)$db->query("SELECT COALESCE(MAX(display_order), 0) FROM categories")->fetchColumn();
                $stmt = $db->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
                $stmt->execute([$name, $maxOrder + 1]);
                flash('ok', 'Added to category list');
                header('Location: dishes.php');
                exit;
            } catch (Exception $e) {
                $error = (strpos($e->getMessage(), 'UNIQUE') !== false) ? 'That category already exists.' : 'Could not add the category.';
            }
        }
    }

    if ($action === 'remove_category') {
        $catId = (int)($_POST['id'] ?? 0);
        if ($catId > 0) {
            $db->prepare("DELETE FROM menu_items WHERE category_id = ?")->execute([$catId]);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
            flash('ok', 'Category and its dishes removed');
            header('Location: dishes.php');
            exit;
        }
    }

    if ($action === 'save_dish') {
        $dishId = (int)($_POST['id'] ?? 0);
        $name = clean_label($_POST['name'] ?? '', 80);
        $catId = (int)($_POST['category_id'] ?? 0);
        $rawPrice = $_POST['price'] ?? '';
        $price = is_numeric($rawPrice) ? (float)$rawPrice : null;
        $prepTime = max(1, min(240, (int)($_POST['prep_time'] ?? 10)));
        $imageUrl = clean_image_url($_POST['image_url'] ?? '');
        $description = clean_label($_POST['description'] ?? '', 280);
        $isAvailable = !empty($_POST['is_available']) ? 1 : 0;

        $tagsArray = $_POST['tags'] ?? [];
        $tags = is_array($tagsArray) ? implode(',', array_map('trim', $tagsArray)) : clean_label($tagsArray, 120);

        if ($name === '') {
            $error = 'Please enter a dish name.';
        } elseif ($catId <= 0) {
            $error = 'Please choose a category.';
        } elseif ($price === null || !is_finite($price) || $price < 0) {
            $error = 'Please enter a valid price (number 0 or higher).';
        } else {
            try {
                if ($dishId > 0) {
                    $stmt = $db->prepare("UPDATE menu_items SET category_id = ?, name = ?, description = ?, price = ?, image_url = ?, is_available = ?, tags = ?, prep_time = ? WHERE id = ?");
                    $stmt->execute([$catId, $name, $description, $price, $imageUrl, $isAvailable, $tags, $prepTime, $dishId]);
                    flash('ok', 'Saved!');
                } else {
                    $stmt = $db->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, tags, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$catId, $name, $description, $price, $imageUrl, $isAvailable, $tags, $prepTime]);
                    flash('ok', 'Added to category');
                }
                header('Location: dishes.php');
                exit;
            } catch (Exception $e) {
                $error = 'Could not save dish. Please check your inputs.';
            }
        }
    }

    if ($action === 'delete_dish') {
        $dishId = (int)($_POST['id'] ?? 0);
        if ($dishId > 0) {
            $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$dishId]);
            flash('ok', 'Dish removed');
            header('Location: dishes.php');
            exit;
        }
    }
}

$categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();
$query = trim($_GET['q'] ?? '');

if ($query !== '') {
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE name LIKE ? OR description LIKE ? ORDER BY name");
    $stmt->execute(["%$query%", "%$query%"]);
    $items = $stmt->fetchAll();
} else {
    $items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();
}

$editingDish = null;
if (!empty($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($items as $it) {
        if ((int)$it['id'] === $editId) {
            $editingDish = $it;
            break;
        }
    }
}
$showForm = isset($_GET['new']) || ($editingDish !== null);
$allTags = ['Gluten-Free', 'Vegetarian', 'Spicy'];

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-6 pb-16">
  <?php 
  $title = "Dish & Menu Manager";
  $hint = "Create menu items, adjust pricing, set descriptions and manage categories.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if ($error !== ''): ?>
    <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 p-4 text-xs font-bold text-red-600">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Toolbar & Category Drawer -->
  <div class="rounded-3xl border border-line bg-card p-5 shadow-card space-y-4">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <!-- Search bar -->
      <form method="GET" action="dishes.php" class="relative flex-1">
        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search dishes by name or ingredient..." class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" onchange="this.form.submit()" />
      </form>

      <a href="dishes.php?new=1" class="tap inline-flex h-11 items-center justify-center gap-2 rounded-full bg-pine px-6 text-xs font-bold text-white shadow-soft shrink-0">
        <span>+ Add New Dish</span>
      </a>
    </div>

    <!-- Category creation form -->
    <div class="pt-3 border-t border-line/60">
      <span class="text-xs font-bold text-muted uppercase tracking-wider block mb-2">Categories</span>
      <form method="POST" action="dishes.php" class="flex flex-col gap-2 sm:flex-row">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_category" />
        <input required type="text" name="name" placeholder="New category name (e.g. Desserts, Drinks)" class="h-10 w-full rounded-2xl border border-line bg-paper px-4 text-xs font-medium text-ink focus:border-pine focus:outline-none" />
        <button type="submit" class="tap h-10 rounded-full border border-line bg-paper px-5 text-xs font-bold text-ink hover:bg-line shrink-0">
          + Add Category
        </button>
      </form>

      <!-- Category Tags list -->
      <div class="mt-3 flex flex-wrap gap-2">
        <?php foreach ($categories as $cat): ?>
          <div class="inline-flex items-center gap-2 rounded-full border border-line bg-paper pl-3 pr-1.5 py-1 text-xs font-bold text-ink">
            <span><?= htmlspecialchars($cat['name']) ?></span>
            <form method="POST" action="dishes.php" onsubmit="return confirm('Remove category <?= htmlspecialchars(addslashes($cat['name'])) ?> and all dishes inside?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove_category" />
              <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>" />
              <button type="submit" class="tap h-5 w-5 rounded-full bg-red-100 text-red-600 hover:bg-red-200 flex items-center justify-center text-[10px] font-bold">✕</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- Dish Form (Add / Edit) -->
  <?php if ($showForm): 
    $formName = $editingDish['name'] ?? '';
    $formCatId = $editingDish['category_id'] ?? ($categories[0]['id'] ?? 0);
    $formPrice = $editingDish['price'] ?? '';
    $formPrep = $editingDish['prep_time'] ?? $editingDish['prep_time_minutes'] ?? 10;
    $formImg = $editingDish['image_url'] ?? '';
    $formDesc = $editingDish['description'] ?? '';
    $formAvail = !isset($editingDish) || !empty($editingDish['is_available']);
    $formTags = !empty($editingDish['tags']) ? array_map('trim', explode(',', $editingDish['tags'])) : (!empty($editingDish['dietary_tags']) ? array_map('trim', explode(',', $editingDish['dietary_tags'])) : []);
  ?>
    <div class="overflow-hidden rounded-3xl border border-pine/40 bg-card shadow-card">
      <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-4 border-b border-line flex items-center justify-between">
        <h2 class="text-lg font-extrabold text-ink"><?= $editingDish ? 'Edit Dish details' : 'Add New Menu Item' ?></h2>
        <a href="dishes.php" class="text-xs font-bold text-muted hover:text-ink">Cancel ✕</a>
      </div>

      <form method="POST" action="dishes.php" class="p-6 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_dish" />
        <?php if ($editingDish): ?>
          <input type="hidden" name="id" value="<?= (int)$editingDish['id'] ?>" />
        <?php endif; ?>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Dish Name <span class="text-red-500">*</span></label>
            <input required type="text" name="name" value="<?= htmlspecialchars($formName) ?>" placeholder="e.g. Jollof Rice Special" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-bold text-ink mb-1">Price (<?= htmlspecialchars($settings['currency_symbol']) ?>) <span class="text-red-500">*</span></label>
            <input required type="number" min="0" step="0.01" name="price" value="<?= htmlspecialchars($formPrice) ?>" placeholder="0.00" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-bold text-ink mb-1">Category <span class="text-red-500">*</span></label>
            <select required name="category_id" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= ((int)$formCatId === (int)$cat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-ink mb-1">Prep Time (Minutes)</label>
            <input type="number" min="1" max="240" name="prep_time" value="<?= htmlspecialchars((string)$formPrep) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Image URL <span class="text-muted font-normal">(Optional)</span></label>
          <input type="url" name="image_url" value="<?= htmlspecialchars($formImg) ?>" placeholder="https://images.unsplash.com/photo-..." class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" />
        </div>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Description</label>
          <textarea name="description" rows="2" placeholder="Brief details about ingredients or flavor profile..." class="w-full rounded-2xl border border-line bg-paper p-4 text-sm font-medium text-ink focus:border-pine focus:outline-none"><?= htmlspecialchars($formDesc) ?></textarea>
        </div>

        <div class="flex items-center gap-2">
          <label class="flex items-center gap-2 text-xs font-bold text-ink cursor-pointer">
            <input type="checkbox" name="is_available" value="1" <?= $formAvail ? 'checked' : '' ?> class="h-4 w-4 rounded accent-pine" />
            <span>Mark in stock and visible on live menu</span>
          </label>
        </div>

        <div>
          <span class="block text-xs font-bold text-ink mb-1.5">Dietary Tags</span>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($allTags as $tg): ?>
              <label class="inline-flex items-center gap-1.5 rounded-full border border-line bg-paper px-3 py-1.5 text-xs font-bold cursor-pointer">
                <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tg) ?>" <?= in_array($tg, $formTags, true) ? 'checked' : '' ?> class="accent-pine" />
                <span><?= htmlspecialchars($tg) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="flex gap-3 pt-3">
          <button type="submit" class="tap rounded-full bg-pine px-6 py-2.5 text-xs font-bold text-white shadow-soft">
            Save Dish
          </button>
          <a href="dishes.php" class="tap inline-flex items-center rounded-full border border-line bg-paper px-6 py-2.5 text-xs font-bold text-ink">
            Cancel
          </a>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- Dishes Cards List -->
  <?php if (empty($items)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <span class="text-5xl block mb-3">🍲</span>
      <h2 class="text-xl font-bold text-ink">No dishes found</h2>
      <p class="mt-2 text-sm text-muted">Get started by creating your first menu dish.</p>
      <a href="dishes.php?new=1" class="tap mt-6 inline-flex items-center gap-2 rounded-full bg-pine px-6 py-3 text-xs font-bold text-white shadow-soft">
        <span>+ Add First Dish</span>
      </a>
    </div>
  <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($items as $item): 
        $catName = 'Uncategorized';
        foreach ($categories as $c) {
            if ((int)$c['id'] === (int)$item['category_id']) {
                $catName = $c['name'];
                break;
            }
        }
      ?>
        <article class="overflow-hidden rounded-3xl border border-line bg-card shadow-soft flex flex-col justify-between">
          <div class="flex gap-4 p-4">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-20 w-20 shrink-0 rounded-2xl object-cover bg-paper" />
            <?php else: ?>
              <div class="h-20 w-20 shrink-0 rounded-2xl bg-paper flex items-center justify-center text-3xl text-muted">🍲</div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-1">
                <h3 class="font-bold text-ink truncate text-sm"><?= htmlspecialchars($item['name']) ?></h3>
                <span class="text-xs font-extrabold text-pine shrink-0"><?= money($settings['currency_symbol'], $item['price']) ?></span>
              </div>
              <p class="text-[11px] text-muted mt-0.5"><?= htmlspecialchars($catName) ?></p>
              <div class="mt-2 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold rounded-full px-2 py-0.5 <?= !empty($item['is_available']) ? 'bg-pine/10 text-pine' : 'bg-red-50 text-red-600' ?>">
                  <span class="h-1.5 w-1.5 rounded-full <?= !empty($item['is_available']) ? 'bg-pine' : 'bg-red-600' ?>"></span>
                  <?= !empty($item['is_available']) ? 'In Stock' : 'Sold Out' ?>
                </span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 border-t border-line p-3 bg-paper/40">
            <a href="dishes.php?edit=<?= (int)$item['id'] ?>" class="tap flex h-9 items-center justify-center rounded-full border border-line bg-card text-xs font-bold text-ink hover:bg-paper">
              ✏️ Edit
            </a>
            <form method="POST" action="dishes.php" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($item['name'])) ?> from menu?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_dish" />
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
              <button type="submit" class="tap h-9 w-full rounded-full border border-line bg-card text-xs font-bold text-red-600 hover:bg-red-50">
                🗑️ Remove
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
