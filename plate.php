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

<section class="page-enter space-y-5 pb-44 md:pb-8">
  <div class="space-y-1">
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Guest</p>
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Your plate</h1>
    <p class="text-sm text-muted">Check the dishes, change amounts, then send the order. Pay at the counter later.</p>
  </div>

  <?php if (empty($plate)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
      <p class="text-base font-semibold text-ink">Your plate is empty</p>
      <p class="mt-2 text-sm text-muted">Pick a dish from today’s board to start.</p>
      <a href="menu.php" class="tap mt-5 inline-flex items-center justify-center rounded-full bg-pine px-6 text-sm font-semibold text-invert">
        Open the board
      </a>
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($plate as $row): 
        $lineTotal = (float)$row['price'] * (int)$row['qty'];
        $atMax = (int)$row['qty'] >= 20;
      ?>
        <article class="overflow-hidden rounded-3xl border border-line bg-card shadow-soft">
          <div class="flex gap-3 p-3 sm:p-4">
            <?php if (!empty($row['image_url'])): ?>
              <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="h-24 w-24 shrink-0 rounded-2xl object-cover" />
            <?php else: ?>
              <div class="h-24 w-24 shrink-0 rounded-2xl bg-paper flex items-center justify-center text-xs text-muted">No photo</div>
            <?php endif; ?>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <p class="font-semibold leading-snug text-ink"><?= htmlspecialchars($row['name']) ?></p>
                <p class="shrink-0 font-semibold text-ink"><?= money($settings['currency_symbol'], $lineTotal) ?></p>
              </div>
              <p class="mt-1 text-sm text-muted"><?= money($settings['currency_symbol'], $row['price']) ?> each</p>

              <div class="mt-3 flex items-center justify-between gap-2">
                <form method="POST" action="plate.php" class="inline-flex items-center rounded-full border border-line bg-paper">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update" />
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                  <button type="submit" name="qty" value="<?= (int)$row['qty'] - 1 ?>" class="tap w-11 text-lg text-ink" aria-label="Decrease quantity">−</button>
                  <span class="w-8 text-center text-sm font-semibold text-ink"><?= (int)$row['qty'] ?></span>
                  <button type="submit" name="qty" value="<?= (int)$row['qty'] + 1 ?>" <?= $atMax ? 'disabled' : '' ?> class="tap w-11 text-lg text-ink disabled:opacity-40" aria-label="Increase quantity">+</button>
                </form>

                <form method="POST" action="plate.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove" />
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>" />
                  <button type="submit" class="tap rounded-full px-3 text-sm font-medium text-danger">Remove</button>
                </form>
              </div>
              <?php if ($atMax): ?>
                <p class="mt-2 text-xs text-muted">20 is the most for one dish.</p>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Sticky Bottom Checkout Bar -->
    <div class="fixed inset-x-3 bottom-[4.75rem] z-30 rounded-3xl border border-line bg-card/95 px-4 py-3 shadow-soft backdrop-blur lg:sticky lg:inset-auto lg:bottom-4 lg:z-10">
      <div class="mx-auto flex max-w-6xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs text-muted">
            <?= $portions ?> <?= $portions === 1 ? 'portion' : 'portions' ?><?= $guestName ? " · " . htmlspecialchars($guestName) : "" ?>
          </p>
          <p class="text-lg font-semibold text-ink"><?= money($settings['currency_symbol'], $total) ?></p>
        </div>
        <div class="grid grid-cols-2 gap-2 sm:flex sm:w-auto">
          <form method="POST" action="plate.php" onsubmit="return confirm('Clear the whole plate?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear" />
            <button type="submit" class="tap w-full rounded-full border border-line px-4 text-sm text-ink">Clear</button>
          </form>
          <a href="pay.php" class="tap inline-flex items-center justify-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">
            Send order
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
