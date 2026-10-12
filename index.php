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
    <div class="rounded-[2rem] border border-line bg-card px-6 py-8 shadow-soft sm:px-8">
      <h1 class="max-w-md text-4xl font-semibold leading-[1.05] text-ink sm:text-5xl">
        <?= htmlspecialchars($settings['restaurant_name']) ?>
      </h1>
      <p class="mt-4 max-w-md text-base leading-relaxed text-muted">
        Today’s board is short on purpose so you can pick fast and eat well.
      </p>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="menu.php" class="tap inline-flex items-center rounded-full bg-pine px-6 text-sm font-semibold text-invert">
          See today’s board
        </a>
        <a href="plate.php" class="tap inline-flex items-center rounded-full border border-line bg-paper px-6 text-sm font-semibold text-ink">
          Check your plate
        </a>
      </div>
      <ol class="mt-8 grid gap-2 text-sm text-ink sm:grid-cols-3">
        <li class="rounded-2xl bg-paper px-3 py-3">
          <span class="font-semibold block">1. Pick</span>
          <span class="mt-1 block text-muted">Choose from today’s board.</span>
        </li>
        <li class="rounded-2xl bg-paper px-3 py-3">
          <span class="font-semibold block">2. Send</span>
          <span class="mt-1 block text-muted">Add a table note. No card.</span>
        </li>
        <li class="rounded-2xl bg-paper px-3 py-3">
          <span class="font-semibold block">3. Pay</span>
          <span class="mt-1 block text-muted">Pay at the counter when it’s ready.</span>
        </li>
      </ol>
    </div>

    <?php if ($spotlight): ?>
      <article class="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
        <?php if (!empty($spotlight['image_url'])): ?>
          <img src="<?= htmlspecialchars($spotlight['image_url']) ?>" alt="<?= htmlspecialchars($spotlight['name']) ?>" class="h-52 w-full object-cover rounded-none" />
        <?php else: ?>
          <div class="flex h-52 items-end bg-gradient-to-br from-blue-200 to-sky-100 p-6 dark:from-blue-900/40 dark:to-slate-900">
            <p class="text-sm font-medium text-ink">First dish on the board</p>
          </div>
        <?php endif; ?>
        <div class="space-y-3 p-6">
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Start here</p>
          <div class="flex items-start justify-between gap-3">
            <h2 class="text-2xl font-semibold leading-tight text-ink"><?= htmlspecialchars($spotlight['name']) ?></h2>
            <p class="shrink-0 text-lg font-semibold text-ink"><?= money($settings['currency_symbol'], $spotlight['price']) ?></p>
          </div>
          <p class="text-sm leading-relaxed text-muted"><?= htmlspecialchars($spotlight['description']) ?></p>
          <button type="button" onclick="addToPlate(<?= (int)$spotlight['id'] ?>, '<?= htmlspecialchars(addslashes($spotlight['name'])) ?>', this)" class="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert">
            Add to plate
          </button>
        </div>
      </article>
    <?php else: ?>
      <article class="rounded-[2rem] border border-dashed border-line bg-card px-6 py-10 shadow-soft">
        <h2 class="text-xl font-semibold text-ink">The board is still being set</h2>
        <p class="mt-2 text-sm text-muted">Check the menu in a moment, or create an account so you are ready when the food is.</p>
      </article>
    <?php endif; ?>
  </div>

  <?php if (!empty($extras)): ?>
    <div class="space-y-3">
      <h2 class="px-1 text-lg font-semibold text-ink">More from the board</h2>
      <div class="grid gap-3">
        <?php foreach ($extras as $item): ?>
          <article class="grid grid-cols-[96px_1fr] items-center gap-4 rounded-[1.6rem] border border-line bg-card p-3 shadow-soft sm:grid-cols-[120px_1fr_auto]">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-24 w-full object-cover rounded-2xl" />
            <?php else: ?>
              <div class="h-24 w-full rounded-2xl bg-paper flex items-center justify-center text-xs text-muted">No photo</div>
            <?php endif; ?>
            <div class="min-w-0">
              <p class="font-semibold text-ink"><?= htmlspecialchars($item['name']) ?></p>
              <p class="mt-1 line-clamp-2 text-sm text-muted"><?= htmlspecialchars($item['description']) ?></p>
              <p class="mt-2 text-sm font-semibold text-ink sm:hidden"><?= money($settings['currency_symbol'], $item['price']) ?></p>
            </div>
            <div class="col-span-2 flex items-center justify-between gap-3 sm:col-span-1 sm:flex-col sm:items-end">
              <p class="hidden text-base font-semibold text-ink sm:block"><?= money($settings['currency_symbol'], $item['price']) ?></p>
              <button type="button" onclick="addToPlate(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', this)" class="tap h-11 rounded-full bg-pine px-4 text-sm font-semibold text-invert">
                Add to plate
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
