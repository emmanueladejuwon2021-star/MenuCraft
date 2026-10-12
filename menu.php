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
      <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search the board" class="tap h-12 w-full rounded-2xl border border-line bg-paper pl-4 pr-16 text-ink" onchange="this.form.submit()" />
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
      <p class="mt-2 text-sm text-muted">Clear the search or pick another list.</p>
      <a href="menu.php" class="tap mt-5 inline-flex items-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">
        Show the whole board
      </a>
    </div>
  <?php else: ?>
    <!-- Groups by category -->
    <div class="space-y-5">
      <?php 
      foreach ($categories as $cat): 
        $catDishes = array_filter($visibleItems, fn($i) => (int)$i['category_id'] === (int)$cat['id']);
        if (empty($catDishes)) continue;
      ?>
        <section class="overflow-hidden rounded-[1.6rem] border border-line bg-card shadow-soft">
          <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <h2 class="text-lg font-semibold text-ink"><?= htmlspecialchars($cat['name']) ?></h2>
            <p class="text-xs text-muted"><?= count($catDishes) ?> <?= count($catDishes) === 1 ? 'dish' : 'dishes' ?></p>
          </div>
          <?php foreach ($catDishes as $item): 
            $tags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));
            $isAvail = !empty($item['is_available']);
          ?>
            <article class="flex flex-col gap-3 border-b border-line px-4 py-3 last:border-b-0 sm:flex-row sm:items-center <?= $isAvail ? '' : 'opacity-70' ?>">
              <div class="flex min-w-0 flex-1 items-center gap-3">
                <?php if (!empty($item['image_url'])): ?>
                  <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-20 w-20 shrink-0 rounded-2xl object-cover" />
                <?php else: ?>
                  <div class="h-20 w-20 shrink-0 rounded-2xl bg-paper flex items-center justify-center text-xs text-muted">No photo</div>
                <?php endif; ?>
                <div class="min-w-0 flex-1">
                  <div class="flex items-baseline justify-between gap-3">
                    <h3 class="min-w-0 break-words text-base font-semibold text-ink"><?= htmlspecialchars($item['name']) ?></h3>
                    <p class="shrink-0 text-base font-semibold text-ink"><?= money($settings['currency_symbol'], $item['price']) ?></p>
                  </div>
                  <?php if (!empty($item['description'])): ?>
                    <p class="mt-1 text-sm leading-relaxed text-muted"><?= htmlspecialchars($item['description']) ?></p>
                  <?php endif; ?>
                  <p class="mt-2 text-xs font-semibold text-ink">
                    <?= $isAvail ? 'Ready' : 'Sold out' ?>
                    <?php if (!empty($tags)): ?>
                      <span class="font-normal text-muted"> · <?= htmlspecialchars(implode(', ', $tags)) ?></span>
                    <?php endif; ?>
                  </p>
                </div>
              </div>

              <?php if ($isAvail): ?>
                <button type="button" onclick="addToPlate(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)" class="tap h-11 w-full shrink-0 rounded-full bg-pine px-5 text-sm font-semibold text-invert sm:w-auto">
                  Add to plate
                </button>
              <?php else: ?>
                <span class="inline-flex h-11 shrink-0 items-center justify-center rounded-full bg-paper px-3 text-xs font-semibold text-ink">
                  Sold out
                </span>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
