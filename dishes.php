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
        $price = is_numeric($rawPrice) ? round((float)$rawPrice, 2) : null;
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
                $error = 'Could not save the dish. ' . $e->getMessage();
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
$items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();

$query = trim($_GET['q'] ?? '');
if ($query !== '') {
    $q = '%' . $query . '%';
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE name LIKE ? OR description LIKE ? OR tags LIKE ? ORDER BY name");
    $stmt->execute([$q, $q, $q]);
    $items = $stmt->fetchAll();
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-5">
  <?php 
  $title = "Dish manager";
  $hint = "Add, edit, or remove dishes and lists.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-line bg-card p-4 text-sm text-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Search -->
  <form method="GET" action="dishes.php" class="flex gap-2">
    <input type="search" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search dishes" class="field flex-1" />
    <button type="submit" class="tap h-12 rounded-full bg-pine px-5 text-sm font-semibold text-invert">Search</button>
    <?php if ($query !== ''): ?>
      <a href="dishes.php" class="tap h-12 inline-flex items-center rounded-full border border-line px-4 text-sm">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Category management -->
  <div class="rounded-2xl border border-line bg-card p-4 shadow-soft">
    <h2 class="text-base font-semibold text-ink">Lists</h2>
    <form method="POST" action="dishes.php" class="mt-3 flex gap-2">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_category" />
      <input type="text" name="name" placeholder="New list name" class="field flex-1" required />
      <button type="submit" class="tap h-12 rounded-full bg-pine px-4 text-sm font-semibold text-invert">Add</button>
    </form>
    <div class="mt-3 flex flex-wrap gap-2">
      <?php foreach ($categories as $cat): ?>
        <div class="inline-flex items-center gap-1 rounded-full border border-line bg-paper px-3 py-1 text-sm">
          <span><?= htmlspecialchars($cat['name']) ?></span>
          <form method="POST" action="dishes.php" class="inline" onsubmit="return confirm('Remove this list and all its dishes?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove_category" />
            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>" />
            <button type="submit" class="text-danger hover:underline text-xs">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Dish form -->
  <div class="rounded-2xl border border-line bg-card p-4 shadow-soft">
    <h2 class="text-base font-semibold text-ink"><?= $editing ? 'Edit dish' : 'Add dish' ?></h2>
    <form method="POST" action="dishes.php" class="mt-3 space-y-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_dish" />
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>" />
      <?php endif; ?>

      <label class="block text-sm font-medium text-ink">
        Name
        <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required class="field mt-1" />
      </label>

      <label class="block text-sm font-medium text-ink">
        List
        <select name="category_id" class="field mt-1" required>
          <option value="">Choose…</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= ((int)($editing['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="grid grid-cols-2 gap-3">
        <label class="block text-sm font-medium text-ink">
          Price
          <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($editing['price'] ?? '') ?>" required class="field mt-1" />
        </label>
        <label class="block text-sm font-medium text-ink">
          Prep time (min)
          <input type="number" name="prep_time" min="1" max="240" value="<?= (int)($editing['prep_time'] ?? 10) ?>" class="field mt-1" />
        </label>
      </div>

      <label class="block text-sm font-medium text-ink">
        Photo URL (optional)
        <input type="url" name="image_url" value="<?= htmlspecialchars($editing['image_url'] ?? '') ?>" placeholder="https://…" class="field mt-1" />
      </label>

      <label class="block text-sm font-medium text-ink">
        Description
        <textarea name="description" rows="2" class="field mt-1 h-auto py-2"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
      </label>

      <label class="flex items-center gap-2 text-sm text-ink">
        <input type="checkbox" name="is_available" value="1" <?= empty($editing) || !empty($editing['is_available']) ? 'checked' : '' ?> />
        In stock
      </label>

      <div class="flex gap-2 text-sm">
        <label class="flex items-center gap-1"><input type="checkbox" name="tags[]" value="Vegetarian" <?= str_contains($editing['tags'] ?? '', 'Vegetarian') ? 'checked' : '' ?> /> Vegetarian</label>
        <label class="flex items-center gap-1"><input type="checkbox" name="tags[]" value="Gluten-Free" <?= str_contains($editing['tags'] ?? '', 'Gluten-Free') ? 'checked' : '' ?> /> Gluten-Free</label>
        <label class="flex items-center gap-1"><input type="checkbox" name="tags[]" value="Spicy" <?= str_contains($editing['tags'] ?? '', 'Spicy') ? 'checked' : '' ?> /> Spicy</label>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="tap h-12 flex-1 rounded-full bg-pine text-sm font-semibold text-invert">
          <?= $editing ? 'Save changes' : 'Add dish' ?>
        </button>
        <?php if ($editing): ?>
          <a href="dishes.php" class="tap h-12 inline-flex items-center justify-center rounded-full border border-line px-5 text-sm">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Dish list -->
  <div class="space-y-2">
    <?php if (empty($items)): ?>
      <p class="text-center text-sm text-muted py-8">No dishes yet. Add one above.</p>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <article class="flex items-center gap-3 rounded-2xl border border-line bg-card p-3">
          <?php if (!empty($item['image_url'])): ?>
            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="" class="h-14 w-14 shrink-0 rounded-xl object-cover" />
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-ink"><?= htmlspecialchars($item['name']) ?></p>
            <p class="text-xs text-muted"><?= money($settings['currency_symbol'], $item['price']) ?> · <?= htmlspecialchars($item['tags'] ?: 'No tags') ?></p>
          </div>
          <div class="flex shrink-0 gap-1">
            <a href="dishes.php?edit=<?= (int)$item['id'] ?>" class="tap-icon inline-flex items-center justify-center rounded-full border border-line text-sm">Edit</a>
            <form method="POST" action="dishes.php" class="inline" onsubmit="return confirm('Remove this dish?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_dish" />
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
              <button type="submit" class="tap-icon inline-flex items-center justify-center rounded-full border border-line text-sm text-danger">Remove</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
