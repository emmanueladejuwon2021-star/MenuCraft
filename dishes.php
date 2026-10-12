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
        $price = (float)($_POST['price'] ?? 0);
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
        } elseif ($price < 0) {
            $error = 'Please enter a valid price.';
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
    $needle = strtolower($query);
    $items = array_filter($items, function($i) use ($needle) {
        return strpos(strtolower($i['name'] . ' ' . ($i['description'] ?? '')), $needle) !== false;
    });
}

// Edit dish prefill
$editingDish = null;
if (isset($_GET['edit'])) {
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

<section class="page-enter space-y-5">
  <?php 
  $title = "Kitchen dishes";
  $hint = "Add, edit, or remove dishes guests will see.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <?php if ($error !== ''): ?>
    <div class="rounded-2xl border border-line bg-card p-4 text-sm text-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Search, Add Dish button, and Categories Manager -->
  <div class="rounded-3xl border border-line bg-card p-4 shadow-soft">
    <form method="GET" action="dishes.php" class="block">
      <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Find a dish or description" class="tap h-11 w-full rounded-2xl border border-line bg-paper px-3 text-ink" onchange="this.form.submit()" />
    </form>

    <div class="mt-3">
      <a href="dishes.php?new=1" class="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert inline-flex items-center justify-center">
        Add dish
      </a>
    </div>

    <!-- Create new Category -->
    <form method="POST" action="dishes.php" class="mt-3 flex flex-col gap-2 sm:flex-row">
      <input type="hidden" name="action" value="create_category" />
      <input required type="text" name="name" placeholder="New category name" class="tap h-11 w-full rounded-2xl border border-line bg-paper px-3 text-ink sm:flex-1" />
      <button type="submit" class="tap h-11 rounded-full border border-line bg-paper px-4 text-sm font-medium text-ink sm:shrink-0">
        Add category
      </button>
    </form>

    <!-- Categories List -->
    <div class="mt-3 space-y-2">
      <?php foreach ($categories as $cat): ?>
        <div class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-paper px-3 py-2">
          <p class="text-sm font-medium text-ink"><?= htmlspecialchars($cat['name']) ?></p>
          <form method="POST" action="dishes.php" onsubmit="return confirm('Remove category <?= htmlspecialchars(addslashes($cat['name'])) ?> and all dishes inside?');">
            <input type="hidden" name="action" value="remove_category" />
            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>" />
            <button type="submit" class="tap h-9 shrink-0 rounded-full border border-line px-3 text-xs font-medium text-danger">
              Remove list
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Dish Form (Add / Edit) -->
  <?php if ($showForm): 
    $formName = $editingDish['name'] ?? '';
    $formCatId = $editingDish['category_id'] ?? ($categories[0]['id'] ?? 0);
    $formPrice = $editingDish['price'] ?? '';
    $formPrep = $editingDish['prep_time'] ?? 10;
    $formImg = $editingDish['image_url'] ?? '';
    $formDesc = $editingDish['description'] ?? '';
    $formAvail = !isset($editingDish) || !empty($editingDish['is_available']);
    $formTags = !empty($editingDish['tags']) ? array_map('trim', explode(',', $editingDish['tags'])) : [];
  ?>
    <form method="POST" action="dishes.php" class="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
      <input type="hidden" name="action" value="save_dish" />
      <?php if ($editingDish): ?>
        <input type="hidden" name="id" value="<?= (int)$editingDish['id'] ?>" />
      <?php endif; ?>

      <h2 class="text-lg font-semibold text-ink"><?= $editingDish ? 'Edit Dish' : 'Add New Dish' ?></h2>

      <div class="grid gap-3 md:grid-cols-2">
        <label class="block text-sm text-ink">
          Dish name
          <input required type="text" name="name" value="<?= htmlspecialchars($formName) ?>" class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
        </label>
        <label class="block text-sm text-ink">
          Price
          <input required type="number" min="0" step="0.01" name="price" value="<?= htmlspecialchars($formPrice) ?>" class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
        </label>
        <label class="block text-sm text-ink">
          Category
          <select required name="category_id" class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ((int)$formCatId === (int)$cat['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="block text-sm text-ink">
          Prep minutes
          <input type="number" min="1" max="240" name="prep_time" value="<?= htmlspecialchars((string)$formPrep) ?>" class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
        </label>
      </div>

      <label class="block text-sm text-ink">
        Photo link
        <input type="url" name="image_url" value="<?= htmlspecialchars($formImg) ?>" placeholder="https://images.unsplash.com/..." class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
      </label>

      <label class="block text-sm text-ink">
        Description
        <textarea name="description" rows="2" class="mt-1 w-full rounded-xl border border-line bg-paper px-3 py-2 text-ink"><?= htmlspecialchars($formDesc) ?></textarea>
      </label>

      <div class="flex items-center gap-2">
        <label class="flex items-center gap-2 text-sm font-medium text-ink cursor-pointer">
          <input type="checkbox" name="is_available" value="1" <?= $formAvail ? 'checked' : '' ?> class="h-4 w-4" />
          <span>In stock (available on menu)</span>
        </label>
      </div>

      <div class="space-y-1">
        <p class="text-sm font-medium text-ink">Diet Tags</p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($allTags as $tg): ?>
            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-line bg-paper text-xs cursor-pointer">
              <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tg) ?>" <?= in_array($tg, $formTags, true) ? 'checked' : '' ?> />
              <span><?= htmlspecialchars($tg) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <button type="submit" class="tap rounded-full bg-pine px-5 text-sm font-semibold text-invert">
          Save dish
        </button>
        <a href="dishes.php" class="tap inline-flex items-center rounded-full border border-line px-5 text-sm text-ink">
          Cancel
        </a>
      </div>
    </form>
  <?php endif; ?>

  <!-- Dishes List -->
  <?php if (empty($items)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
      <p class="font-semibold text-ink">No dishes match that search.</p>
      <a href="dishes.php?new=1" class="tap mt-4 inline-flex items-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">
        Add First Dish
      </a>
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($items as $item): 
        $catName = 'No category';
        foreach ($categories as $c) {
            if ((int)$c['id'] === (int)$item['category_id']) {
                $catName = $c['name'];
                break;
            }
        }
      ?>
        <article class="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
          <div class="flex gap-3 p-3 sm:p-4">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-24 w-24 shrink-0 rounded-2xl object-cover" />
            <?php else: ?>
              <div class="h-24 w-24 shrink-0 rounded-2xl bg-paper flex items-center justify-center text-xs text-muted">No photo</div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h3 class="font-semibold leading-snug text-ink"><?= htmlspecialchars($item['name']) ?></h3>
                  <p class="mt-1 text-sm text-muted"><?= htmlspecialchars($catName) ?></p>
                </div>
                <p class="shrink-0 font-semibold text-ink"><?= money($settings['currency_symbol'], $item['price']) ?></p>
              </div>
              <p class="mt-2 text-xs font-medium <?= !empty($item['is_available']) ? 'text-pine' : 'text-muted' ?>">
                <?= !empty($item['is_available']) ? 'In Stock' : 'Sold Out' ?>
              </p>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2 border-t border-line p-3">
            <a href="dishes.php?edit=<?= (int)$item['id'] ?>" class="tap inline-flex items-center justify-center rounded-full border border-line bg-paper text-sm font-medium text-ink">
              Edit
            </a>
            <form method="POST" action="dishes.php" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($item['name'])) ?> from the menu?');">
              <input type="hidden" name="action" value="delete_dish" />
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>" />
              <button type="submit" class="tap w-full rounded-full border border-line bg-paper text-sm font-medium text-danger">
                Remove
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
