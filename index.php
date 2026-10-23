<?php
// index.php - Guest Home Page
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// If logged in as kitchen staff, redirect to orders board
if (is_staff()) {
    header('Location: orders.php');
    exit;
}

$db = getDb();
$settings = get_restaurant_settings($db);

// Fetch items
$items = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();
$readyItems = array_values(array_filter($items, fn($r) => !empty($r['is_available'])));
$spotlight = $readyItems[0] ?? null;
$extras = array_slice($readyItems, 1, 4);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-8">
  <div class="grid items-start gap-6 lg:grid-cols-[1.1fr_0.9fr]">

    <!-- Hero Banner Card -->
    <div class="rounded-[2rem] border border-line bg-card px-6 py-8 shadow-card sm:px-8">
      <div class="inline-flex items-center gap-2 rounded-full bg-pine/10 px-3.5 py-1.5 text-xs font-bold text-pine border border-pine/20">
        <span class="h-2 w-2 rounded-full bg-pine animate-pulse"></span>
        Fresh & Hot Today
      </div>
      <h1 class="mt-4 max-w-md text-4xl font-extrabold leading-[1.1] text-ink sm:text-5xl">
        <?= htmlspecialchars($settings['restaurant_name']) ?>
      </h1>
      <p class="mt-4 max-w-md text-base leading-relaxed text-muted">
        Order directly from your table or pickup on the go. No app download needed, no wait time.
      </p>

      <div class="mt-8 flex flex-wrap gap-3">
        <a href="menu.php" class="tap inline-flex items-center gap-2 rounded-full bg-pine px-6 py-3.5 text-sm font-bold text-white shadow-soft transition-transform hover:scale-[1.02]">
          <span>See Today’s Menu</span>
          <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </a>
        <a href="plate.php" class="tap inline-flex items-center gap-2 rounded-full border border-line bg-paper px-6 py-3.5 text-sm font-bold text-ink hover:bg-line/50">
          <span>Check Your Plate</span>
        </a>
      </div>

      <ol class="mt-8 grid gap-3 text-sm text-ink sm:grid-cols-3">
        <li class="rounded-2xl bg-paper/80 border border-line/60 p-4 transition-transform hover:-translate-y-0.5">
          <span class="text-xs font-bold text-pine uppercase tracking-wider block">Step 1</span>
          <span class="font-bold block mt-1">Pick Dishes</span>
          <span class="mt-1 block text-xs text-muted">Browse today’s fresh menu board.</span>
        </li>
        <li class="rounded-2xl bg-paper/80 border border-line/60 p-4 transition-transform hover:-translate-y-0.5">
          <span class="text-xs font-bold text-pine uppercase tracking-wider block">Step 2</span>
          <span class="font-bold block mt-1">Send Order</span>
          <span class="mt-1 block text-xs text-muted">Add a table note. No card needed.</span>
        </li>
        <li class="rounded-2xl bg-paper/80 border border-line/60 p-4 transition-transform hover:-translate-y-0.5">
          <span class="text-xs font-bold text-pine uppercase tracking-wider block">Step 3</span>
          <span class="font-bold block mt-1">Pay & Enjoy</span>
          <span class="mt-1 block text-xs text-muted">Pay at counter when food is ready.</span>
        </li>
      </ol>
    </div>

    <!-- Spotlight Featured Dish Card -->
    <?php if ($spotlight): ?>
      <article class="group overflow-hidden rounded-[2rem] border border-line bg-card shadow-card transition-all hover:shadow-2xl">
        <div class="relative h-60 w-full overflow-hidden bg-paper">
          <?php if (!empty($spotlight['image_url'])): ?>
            <img src="<?= htmlspecialchars($spotlight['image_url']) ?>" alt="<?= htmlspecialchars($spotlight['name']) ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
          <?php else: ?>
            <div class="flex h-full items-center justify-center bg-gradient-to-br from-pine/20 to-paper p-6">
              <span class="text-4xl">🍲</span>
            </div>
          <?php endif; ?>
          <div class="absolute top-4 left-4 rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-white backdrop-blur-md">
            🔥 House Spotlight
          </div>
          <?php if (!empty($spotlight['prep_time'])): ?>
            <div class="absolute bottom-4 right-4 rounded-full bg-card/90 px-3 py-1 text-xs font-bold text-ink shadow-sm backdrop-blur-md">
              ⏱️ <?= (int)$spotlight['prep_time'] ?> mins
            </div>
          <?php endif; ?>
        </div>

        <div class="space-y-4 p-6">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-2xl font-bold text-ink leading-snug"><?= htmlspecialchars($spotlight['name']) ?></h2>
              <?php if (!empty($spotlight['tags'])): ?>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                  <?php foreach (explode(',', $spotlight['tags']) as $tag): ?>
                    <span class="rounded-full bg-paper px-2.5 py-0.5 text-[11px] font-semibold text-muted border border-line">
                      <?= htmlspecialchars(trim($tag)) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <p class="shrink-0 text-xl font-extrabold text-pine"><?= money($settings['currency_symbol'], $spotlight['price']) ?></p>
          </div>

          <p class="text-sm leading-relaxed text-muted line-clamp-3"><?= htmlspecialchars($spotlight['description']) ?></p>

          <button type="button" onclick="addToPlate(<?= (int)$spotlight['id'] ?>, '<?= htmlspecialchars(addslashes($spotlight['name'])) ?>', this)" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-white shadow-soft transition-transform active:scale-95">
            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            <span>Add to Plate</span>
          </button>
        </div>
      </article>
    <?php else: ?>
      <article class="rounded-[2rem] border border-dashed border-line bg-card p-8 text-center shadow-soft">
        <span class="text-4xl block mb-3">👨‍🍳</span>
        <h2 class="text-xl font-bold text-ink">The kitchen board is being prepared</h2>
        <p class="mt-2 text-sm text-muted">Please check back in a few minutes or view the full menu.</p>
        <a href="menu.php" class="mt-4 inline-block rounded-full bg-pine px-5 py-2.5 text-xs font-bold text-white">Browse Menu</a>
      </article>
    <?php endif; ?>
  </div>

  <!-- More items on the board -->
  <?php if (!empty($extras)): ?>
    <div class="space-y-4">
      <div class="flex items-center justify-between px-1">
        <h2 class="text-xl font-bold text-ink">Popular On Today’s Board</h2>
        <a href="menu.php" class="text-xs font-bold text-pine hover:underline">View All &rarr;</a>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <?php foreach ($extras as $item): ?>
          <article class="flex items-center gap-4 rounded-2xl border border-line bg-card p-3.5 shadow-soft transition-all hover:border-pine/30 hover:shadow-card">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-24 w-24 shrink-0 object-cover rounded-xl bg-paper" />
            <?php else: ?>
              <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-paper text-2xl text-muted">🍲</div>
            <?php endif; ?>

            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-ink truncate"><?= htmlspecialchars($item['name']) ?></h3>
              <p class="mt-1 text-xs text-muted line-clamp-2"><?= htmlspecialchars($item['description']) ?></p>
              <div class="mt-3 flex items-center justify-between">
                <span class="text-sm font-extrabold text-pine"><?= money($settings['currency_symbol'], $item['price']) ?></span>
                <button type="button" onclick="addToPlate(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)" class="tap rounded-full bg-pine px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition-transform active:scale-95">
                  + Add
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
