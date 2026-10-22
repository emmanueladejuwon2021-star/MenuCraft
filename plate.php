<?php
// plate.php - Guest Plate (Cart)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDb();
$plate = sync_cart_with_menu($db);
$settings = get_restaurant_settings($db);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $dishId = (int)($_POST['id'] ?? 0);

    if ($action === 'add' && $dishId > 0) {
        $dish = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
        $dish->execute([$dishId]);
        $row = $dish->fetch();
        if ($row && !empty($row['is_available'])) {
            add_to_cart($row);
            if (is_ajax()) {
                json_response(['ok' => true, 'count' => cart_count(), 'total' => cart_total()]);
            }
            flash('ok', "{$row['name']} added to your plate");
        } else {
            if (is_ajax()) {
                json_response(['ok' => false, 'message' => 'This dish is currently sold out.'], 400);
            }
            flash('error', 'This dish is currently sold out.');
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'menu.php'));
        exit;
    }

    if ($action === 'update' && $dishId > 0) {
        $qty = (int)($_POST['qty'] ?? 0);
        update_cart_qty($dishId, $qty);
        if (is_ajax()) {
            json_response(['ok' => true, 'count' => cart_count(), 'total' => cart_total()]);
        }
        header('Location: plate.php');
        exit;
    }

    if ($action === 'remove' && $dishId > 0) {
        update_cart_qty($dishId, 0);
        if (is_ajax()) {
            json_response(['ok' => true, 'count' => cart_count(), 'total' => cart_total()]);
        }
        flash('ok', 'Dish removed from plate');
        header('Location: plate.php');
        exit;
    }

    if ($action === 'clear') {
        clear_cart();
        if (is_ajax()) {
            json_response(['ok' => true, 'count' => 0, 'total' => 0]);
        }
        flash('ok', 'Plate cleared');
        header('Location: plate.php');
        exit;
    }
}

$plate = get_cart();
$total = cart_total();
$portions = cart_count();
$currentUser = get_current_user_session();
$guestName = is_guest() ? ($currentUser['name'] ?? '') : '';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-6 pb-40 md:pb-12">

  <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-pine">🍽 Your Current Order</span>
      <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Your Plate</h1>
    </div>
    <p class="text-xs font-bold text-muted bg-paper px-3 py-1.5 rounded-full border border-line self-start sm:self-auto">
      <?= $portions ?> <?= $portions === 1 ? 'portion' : 'portions' ?>
    </p>
  </div>

  <?php if (empty($plate)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <span class="text-5xl block mb-3">🍽️</span>
      <h2 class="text-xl font-bold text-ink">Your plate is empty</h2>
      <p class="mt-2 text-sm text-muted">Pick a dish from today’s fresh menu board to start your order.</p>
      <a href="menu.php" class="tap mt-6 inline-flex items-center gap-2 rounded-full bg-pine px-6 py-3 text-sm font-bold text-white shadow-soft">
        <span>Browse Today's Board</span>
        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
      </a>
    </div>
  <?php else: ?>

    <div class="grid gap-4 md:grid-cols-[1fr_320px]">

      <!-- Plate Items List -->
      <div class="space-y-3">
        <?php foreach ($plate as $row):
          $lineTotal = (float)$row['price'] * (int)$row['qty'];
          $atMax = (int)$row['qty'] >= 20;
        ?>
          <article class="overflow-hidden rounded-3xl border border-line bg-card p-4 shadow-soft transition-all hover:border-pine/30">
            <div class="flex gap-4">
              <?php if (!empty($row['image_url'])): ?>
                <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="h-24 w-24 shrink-0 rounded-2xl object-cover bg-paper" />
              <?php else: ?>
                <div class="h-24 w-24 shrink-0 rounded-2xl bg-paper flex items-center justify-center text-3xl text-muted">🍲</div>
              <?php endif; ?>

              <div class="min-w-0 flex-1 space-y-2">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h3 class="font-bold text-base text-ink leading-snug"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="text-xs text-muted mt-0.5"><?= money($settings['currency_symbol'], $row['price']) ?> / portion</p>
                  </div>
                  <p class="shrink-0 font-extrabold text-pine text-base"><?= money($settings['currency_symbol'], $lineTotal) ?></p>
                </div>

                <div class="flex items-center justify-between gap-3 pt-2 border-t border-line/50">
                  <!-- Quantity adjuster -->
                  <form method="POST" action="plate.php" class="inline-flex items-center rounded-full border border-line bg-paper p-0.5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />

                    <button type="submit" name="qty" value="<?= (int)$row['qty'] - 1 ?>" class="tap flex h-8 w-8 items-center justify-center rounded-full font-bold text-ink hover:bg-line" aria-label="Decrease quantity">−</button>
                    <span class="w-8 text-center text-xs font-bold text-ink"><?= (int)$row['qty'] ?></span>
                    <button type="submit" name="qty" value="<?= (int)$row['qty'] + 1 ?>" <?= $atMax ? 'disabled' : '' ?> class="tap flex h-8 w-8 items-center justify-center rounded-full font-bold text-ink hover:bg-line disabled:opacity-30" aria-label="Increase quantity">+</button>
                  </form>

                  <!-- Remove item button -->
                  <form method="POST" action="plate.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove" />
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                    <button type="submit" class="tap flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                      <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                      <span>Remove</span>
                    </button>
                  </form>
                </div>

                <?php if ($atMax): ?>
                  <p class="text-[10px] font-bold text-clay">Maximum limit of 20 portions reached for this dish.</p>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- Order Summary Card -->
      <div class="space-y-4">
        <div class="rounded-3xl border border-line bg-card p-6 shadow-card space-y-4">
          <h2 class="font-extrabold text-lg text-ink pb-3 border-b border-line">Order Summary</h2>

          <div class="space-y-2 text-sm">
            <div class="flex justify-between text-muted">
              <span>Total Portions</span>
              <span class="font-bold text-ink"><?= $portions ?></span>
            </div>
            <div class="flex justify-between text-muted">
              <span>Payment Type</span>
              <span class="font-bold text-pine">Pay at counter</span>
            </div>
            <?php if ($guestName): ?>
              <div class="flex justify-between text-muted">
                <span>Guest Name</span>
                <span class="font-bold text-ink"><?= htmlspecialchars($guestName) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="pt-3 border-t border-line flex items-center justify-between">
            <span class="font-bold text-ink">Estimated Total</span>
            <span class="text-2xl font-extrabold text-pine"><?= money($settings['currency_symbol'], $total) ?></span>
          </div>

          <div class="space-y-2 pt-2">
            <a href="pay.php" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-white shadow-soft transition-transform active:scale-95">
              <span>Proceed to Checkout</span>
              <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
            </a>

            <form method="POST" action="plate.php" onsubmit="return confirm('Clear the whole plate?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear" />
              <button type="submit" class="tap h-10 w-full rounded-full border border-line bg-paper text-xs font-bold text-muted hover:text-ink">
                Clear Plate
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>

    <!-- Fixed Mobile Floating Checkout Strip -->
    <div class="fixed inset-x-3 bottom-16 z-30 rounded-2xl border border-line bg-card/95 p-3 shadow-card backdrop-blur-md md:hidden">
      <div class="flex items-center justify-between gap-3">
        <div>
          <span class="text-[10px] font-bold text-muted block uppercase tracking-wider"><?= $portions ?> <?= $portions === 1 ? 'Portion' : 'Portions' ?></span>
          <span class="text-lg font-extrabold text-pine leading-none"><?= money($settings['currency_symbol'], $total) ?></span>
        </div>
        <a href="pay.php" class="tap inline-flex items-center gap-2 rounded-full bg-pine px-5 py-2.5 text-xs font-bold text-white shadow-soft">
          <span>Checkout</span>
          <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
        </a>
      </div>
    </div>

  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
