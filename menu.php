<?php
// menu.php - Live Menu Screen
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDb();
$settings = get_restaurant_settings($db);

$query = trim($_GET['q'] ?? '');
$selectedCategory = $_GET['category'] ?? 'all';
$selectedTag = $_GET['tag'] ?? 'all';
$showSoldOut = !empty($_GET['show_sold_out']);

// Fetch categories
$categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();

// Fetch items
$items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();

$allTags = ['Gluten-Free', 'Vegetarian', 'Spicy'];

// Filter items
$visibleItems = array_filter($items, function($item) use ($query, $selectedCategory, $selectedTag, $showSoldOut) {
    if (!$showSoldOut && empty($item['is_available'])) {
        return false;
    }
    if ($selectedCategory !== 'all' && (int)$item['category_id'] !== (int)$selectedCategory) {
        return false;
    }
    $tags = array_map('trim', explode(',', $item['tags'] ?? ''));
    if ($selectedTag !== 'all' && !in_array($selectedTag, $tags, true)) {
        return false;
    }
    if ($query !== '') {
        $haystack = strtolower($item['name'] . ' ' . ($item['description'] ?? ''));
        if (strpos($haystack, strtolower($query)) === false) {
            return false;
        }
    }
    return true;
});

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-4 pb-8">
  <div class="flex items-end justify-between gap-3">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay"><?= htmlspecialchars($settings['restaurant_name']) ?></p>
      <h1 class="mt-1 text-3xl font-semibold tracking-tight text-ink">Today's board</h1>
    </div>
    <p class="text-sm font-medium text-ink">
      <?= count($visibleItems) ?> <?= count($visibleItems) === 1 ? 'dish' : 'dishes' ?>
    </p>
  </div>

  <!-- Filter & Search Toolbar -->
  <form method="GET" action="menu.php" id="filter-form" class="sticky top-[4.25rem] z-20 space-y-3 rounded-[1.4rem] border border-line bg-card/95 p-3 shadow-soft backdrop-blur">
    <div class="relative block">
      <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search the board" class="tap h-12 w-full rounded-2xl border border-line bg-paper pl-4 pr-16 text-ink" />
      <?php if ($query !== ''): ?>
        <a href="menu.php" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full px-2 text-sm font-medium text-pine">Clear</a>
      <?php endif; ?>
    </div>

    <!-- Category selector tabs -->
    <div class="nav-scroll flex gap-2 overflow-x-auto pb-1">
      <a href="menu.php?<?= http_build_query(array_merge($_GET, ['category' => 'all'])) ?>" class="tap h-11 shrink-0 rounded-full px-4 text-sm font-medium inline-flex items-center <?= $selectedCategory === 'all' ? 'bg-pine text-invert' : 'border border-line bg-paper text-ink' ?>">
        Whole board
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="menu.php?<?= http_build_query(array_merge($_GET, ['category' => $cat['id']])) ?>" class="tap h-11 shrink-0 rounded-full px-4 text-sm font-medium inline-flex items-center <?= (string)$selectedCategory === (string)$cat['id'] ? 'bg-pine text-invert' : 'border border-line bg-paper text-ink' ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Diet tag pills and sold out toggle -->
    <div class="flex flex-wrap gap-2">
      <a href="menu.php?<?= http_build_query(array_merge($_GET, ['tag' => 'all'])) ?>" class="tap h-11 shrink-0 rounded-full px-4 text-sm font-medium inline-flex items-center <?= $selectedTag === 'all' ? 'bg-pine text-invert' : 'border border-line bg-paper text-ink' ?>">
        Any diet
      </a>
      <?php foreach ($allTags as $t): ?>
        <a href="menu.php?<?= http_build_query(array_merge($_GET, ['tag' => $t])) ?>" class="tap h-11 shrink-0 rounded-full px-4 text-sm font-medium inline-flex items-center <?= $selectedTag === $t ? 'bg-pine text-invert' : 'border border-line bg-paper text-ink' ?>">
          <?= htmlspecialchars($t) ?>
        </a>
      <?php endforeach; ?>
      <a href="menu.php?<?= http_build_query(array_merge($_GET, ['show_sold_out' => $showSoldOut ? '0' : '1'])) ?>" class="tap h-11 shrink-0 rounded-full px-4 text-sm font-medium inline-flex items-center <?= $showSoldOut ? 'bg-pine text-invert' : 'border border-line bg-paper text-ink' ?>">
        <?= $showSoldOut ? 'Hide sold out' : 'Show sold out' ?>
      </a>
    </div>
  </form>

  <?php if (empty($visibleItems)): ?>
    <div class="rounded-[2rem] border border-dashed border-line bg-card px-5 py-12 text-center">
      <p class="text-lg font-semibold text-ink">Nothing matches that search</p>
      <p class="mt-2 text-sm text-muted">Try a different word, category, or diet tag.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($visibleItems as $item): ?>
        <article class="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
          <?php if (!empty($item['image_url'])): ?>
            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-48 w-full object-cover" />
          <?php else: ?>
            <div class="flex h-48 items-center justify-center bg-paper text-sm text-muted">No photo</div>
          <?php endif; ?>
          <div class="space-y-3 p-4">
            <div class="flex items-start justify-between gap-2">
              <h2 class="font-semibold leading-snug text-ink"><?= htmlspecialchars($item['name']) ?></h2>
              <p class="shrink-0 font-semibold text-ink"><?= money($settings['currency_symbol'], $item['price']) ?></p>
            </div>
            <p class="text-sm leading-relaxed text-muted line-clamp-2"><?= htmlspecialchars($item['description'] ?? '') ?></p>
            <div class="flex flex-wrap gap-1.5 text-xs">
              <?php if (!empty($item['tags'])): ?>
                <?php foreach (array_filter(array_map('trim', explode(',', $item['tags']))) as $tag): ?>
                  <span class="rounded-full bg-paper px-2 py-0.5 text-muted"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
              <?php endif; ?>
              <?php if (empty($item['is_available'])): ?>
                <span class="rounded-full bg-danger/10 px-2 py-0.5 text-danger">Sold out</span>
              <?php endif; ?>
            </div>
            <?php if (!empty($item['is_available'])): ?>
              <button type="button" onclick="addToPlate(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)" class="tap h-11 w-full rounded-full bg-pine text-sm font-semibold text-invert">
                Add to plate
              </button>
            <?php else: ?>
              <button type="button" disabled class="tap h-11 w-full rounded-full border border-line bg-paper text-sm text-muted">
                Sold out
              </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
