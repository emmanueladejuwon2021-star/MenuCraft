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

<section class="page-enter space-y-6 pb-12">
  <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-pine">
        <span>🍲 Live Restaurant Menu</span>
      </div>
      <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Today’s Board</h1>
    </div>
    <div class="text-xs font-bold text-muted bg-paper px-3 py-1.5 rounded-full border border-line self-start sm:self-auto">
      Showing <?= count($visibleItems) ?> <?= count($visibleItems) === 1 ? 'dish' : 'dishes' ?>
    </div>
  </div>

  <!-- Filter & Search Sticky Bar -->
  <div class="sticky top-[4.25rem] z-20 space-y-3 rounded-3xl border border-line bg-card/90 p-3.5 shadow-card backdrop-blur-md">

    <!-- Search Bar -->
    <form method="GET" action="menu.php" class="relative block">
      <?php if ($selectedCategory !== 'all'): ?><input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>"><?php endif; ?>
      <?php if ($selectedTag !== 'all'): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($selectedTag) ?>"><?php endif; ?>
      <?php if ($showSoldOut): ?><input type="hidden" name="show_sold_out" value="1"><?php endif; ?>

      <div class="relative flex items-center">
        <svg class="absolute left-4 h-5 w-5 text-muted fill-current pointer-events-none" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search dishes, ingredients..." class="h-11 w-full rounded-2xl border border-line bg-paper pl-11 pr-16 text-sm font-medium text-ink placeholder:text-muted focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
        <?php if ($query !== ''): ?>
          <a href="menu.php?<?= http_build_query(array_filter(['category' => $selectedCategory !== 'all' ? $selectedCategory : null, 'tag' => $selectedTag !== 'all' ? $selectedTag : null])) ?>" class="absolute right-3 rounded-full bg-paper px-2.5 py-1 text-xs font-bold text-pine hover:bg-line">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Category selector tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
      <a href="menu.php?<?= http_build_query(array_filter(['q' => $query, 'tag' => $selectedTag !== 'all' ? $selectedTag : null, 'show_sold_out' => $showSoldOut ? '1' : null])) ?>" class="tap shrink-0 rounded-full px-4 py-2 text-xs font-bold transition-all <?= $selectedCategory === 'all' ? 'bg-pine text-white shadow-soft' : 'border border-line bg-paper text-ink hover:bg-line/60' ?>">
        All Dishes
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="menu.php?<?= http_build_query(array_filter(['q' => $query, 'category' => $cat['id'], 'tag' => $selectedTag !== 'all' ? $selectedTag : null, 'show_sold_out' => $showSoldOut ? '1' : null])) ?>" class="tap shrink-0 rounded-full px-4 py-2 text-xs font-bold transition-all <?= (string)$selectedCategory === (string)$cat['id'] ? 'bg-pine text-white shadow-soft' : 'border border-line bg-paper text-ink hover:bg-line/60' ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Diet tag pills & Sold out filter toggle -->
    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-line/60">
      <span class="text-[11px] font-bold text-muted mr-1">Filter:</span>
      <a href="menu.php?<?= http_build_query(array_filter(['q' => $query, 'category' => $selectedCategory !== 'all' ? $selectedCategory : null, 'show_sold_out' => $showSoldOut ? '1' : null])) ?>" class="tap rounded-full px-3 py-1 text-[11px] font-semibold transition-all <?= $selectedTag === 'all' ? 'bg-ink text-canvas' : 'bg-paper text-muted hover:text-ink' ?>">
        Any Diet
      </a>
      <?php foreach ($allTags as $t): ?>
        <a href="menu.php?<?= http_build_query(array_filter(['q' => $query, 'category' => $selectedCategory !== 'all' ? $selectedCategory : null, 'tag' => $t, 'show_sold_out' => $showSoldOut ? '1' : null])) ?>" class="tap rounded-full px-3 py-1 text-[11px] font-semibold transition-all <?= $selectedTag === $t ? 'bg-pine text-white' : 'bg-paper text-muted hover:text-ink' ?>">
          <?= htmlspecialchars($t) ?>
        </a>
      <?php endforeach; ?>

      <div class="ml-auto">
        <a href="menu.php?<?= http_build_query(array_filter(['q' => $query, 'category' => $selectedCategory !== 'all' ? $selectedCategory : null, 'tag' => $selectedTag !== 'all' ? $selectedTag : null, 'show_sold_out' => !$showSoldOut ? '1' : null])) ?>" class="tap inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold transition-all <?= $showSoldOut ? 'bg-pine/10 text-pine border border-pine/30' : 'bg-paper text-muted hover:text-ink' ?>">
          <span><?= $showSoldOut ? '✓ Showing Sold Out' : '+ Show Sold Out' ?></span>
        </a>
      </div>
    </div>

  </div>

  <!-- Dishes Grid -->
  <?php if (empty($visibleItems)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <span class="text-4xl block mb-3">🔍</span>
      <h3 class="text-lg font-bold text-ink">No dishes match your filter</h3>
      <p class="mt-1 text-sm text-muted">Try resetting your search, category selection, or dietary tag filters.</p>
      <a href="menu.php" class="mt-4 inline-block rounded-full bg-pine px-5 py-2.5 text-xs font-bold text-white">Reset All Filters</a>
    </div>
  <?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($visibleItems as $item): ?>
        <?php
          $tags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));
          $isAvailable = !empty($item['is_available']);
        ?>
        <article class="group relative flex flex-col overflow-hidden rounded-3xl border border-line bg-card shadow-soft transition-all hover:border-pine/40 hover:shadow-card">

          <div class="relative h-48 w-full overflow-hidden bg-paper">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105 <?= $isAvailable ? '' : 'grayscale opacity-60' ?>" />
            <?php else: ?>
              <div class="flex h-full items-center justify-center text-3xl text-muted">🍲</div>
            <?php endif; ?>

            <?php if (!$isAvailable): ?>
              <div class="absolute inset-0 flex items-center justify-center bg-black/50 backdrop-blur-xs">
                <span class="rounded-full bg-red-600 px-3.5 py-1 text-xs font-extrabold text-white uppercase tracking-wider shadow">Sold Out</span>
              </div>
            <?php endif; ?>

            <?php if (!empty($item['prep_time']) && $isAvailable): ?>
              <div class="absolute bottom-3 right-3 rounded-full bg-card/90 px-2.5 py-0.5 text-[11px] font-bold text-ink backdrop-blur-md shadow-xs">
                ⏱️ <?= (int)$item['prep_time'] ?>m
              </div>
            <?php endif; ?>
          </div>

          <div class="flex flex-1 flex-col justify-between p-5">
            <div class="space-y-2">
              <div class="flex items-start justify-between gap-2">
                <h3 class="font-bold text-lg text-ink leading-snug"><?= htmlspecialchars($item['name']) ?></h3>
                <span class="shrink-0 text-base font-extrabold text-pine"><?= money($settings['currency_symbol'], $item['price']) ?></span>
              </div>

              <p class="text-xs leading-relaxed text-muted line-clamp-2"><?= htmlspecialchars($item['description'] ?? '') ?></p>

              <?php if (!empty($tags)): ?>
                <div class="flex flex-wrap gap-1.5 pt-1">
                  <?php foreach ($tags as $tag): ?>
                    <span class="rounded-full bg-paper px-2 py-0.5 text-[10px] font-bold text-muted border border-line/60">
                      <?= htmlspecialchars($tag) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="mt-4 pt-3 border-t border-line/50">
              <?php if ($isAvailable): ?>
                <button type="button" onclick="addToPlate(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)" class="tap flex h-11 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-xs text-white shadow-soft transition-transform active:scale-95">
                  <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                  <span>Add to Plate</span>
                </button>
              <?php else: ?>
                <button type="button" disabled class="h-11 w-full rounded-full border border-line bg-paper text-xs font-bold text-muted cursor-not-allowed">
                  Currently Sold Out
                </button>
              <?php endif; ?>
            </div>
          </div>

        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
