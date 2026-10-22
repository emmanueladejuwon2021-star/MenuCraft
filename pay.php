<?php
// pay.php - Guest Checkout & Order Submission
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_build.php';

$db = getDb();
$plate = sync_cart_with_menu($db);
if (empty($plate)) {
    header('Location: plate.php');
    exit;
}

$settings = get_restaurant_settings($db);
$currentUser = get_current_user_session();
$signedGuest = is_guest();

$name = $signedGuest ? ($currentUser['name'] ?? '') : '';
$phone = $signedGuest ? ($currentUser['phone'] ?? '') : '';
$note = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $submittedName = clean_label($_POST['name'] ?? $name, 80);
    $submittedPhone = clean_label($_POST['phone'] ?? $phone, 30);
    $submittedNote = clean_label($_POST['note'] ?? '', 240);
    $email = $signedGuest ? ($currentUser['email'] ?? '') : '';

    if ($submittedName === '') {
        $error = "Add your name so the kitchen can identify your order.";
    } else {
        if ($email === '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $submittedName)) ?: 'guest';
            $email = "walkin:{$slug}-" . bin2hex(random_bytes(4));
        }

        try {
            $menuRows = $db->query("SELECT id, name, price, is_available, image_url FROM menu_items")->fetchAll();
            $built = buildOrderLines($plate, $menuRows);

            $payRef = 'COUNTER-' . substr((string)time(), -8);
            $viewToken = bin2hex(random_bytes(18));
            $createdAt = gmdate('Y-m-d\TH:i:s\Z');

            $stmt = $db->prepare("INSERT INTO orders (guest_name, guest_email, phone, note, items_json, total, paid, pay_ref, status, view_token, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'New', ?, ?)");
            $stmt->execute([
                $submittedName,
                $email,
                $submittedPhone,
                $submittedNote,
                json_encode($built['lines']),
                $built['total'],
                $payRef,
                $viewToken,
                $createdAt
            ]);

            clear_cart();
            add_ticket($viewToken);

            flash('ok', "Order sent successfully! Pay at the counter when called.");
            header('Location: my-orders.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage() ?: "Could not send the order. Please try again.";
        }
    }
}

$total = cart_total();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter mx-auto max-w-xl space-y-6 pb-12">
  <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">

    <!-- Header banner -->
    <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-6 border-b border-line">
      <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-pine">
        <span>📝 Table / Counter Checkout</span>
      </div>
      <h1 class="mt-1 text-2xl font-extrabold text-ink">Send Order to Kitchen</h1>
      <p class="mt-1 text-xs leading-relaxed text-muted">No card or online payment required. Pay in cash or transfer at the counter when your food is ready.</p>
    </div>

    <div class="space-y-6 p-6">

      <!-- Order Items Summary Box -->
      <div class="rounded-2xl bg-paper/80 border border-line/60 p-4 space-y-2">
        <span class="text-xs font-bold text-muted uppercase tracking-wider block border-b border-line/60 pb-2">Plate Summary</span>
        <div class="divide-y divide-line/40">
          <?php foreach ($plate as $row): ?>
            <div class="flex items-center justify-between py-2 text-sm">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-pine/10 text-xs font-bold text-pine"><?= (int)$row['qty'] ?></span>
                <span class="font-bold text-ink"><?= htmlspecialchars($row['name']) ?></span>
              </div>
              <span class="font-extrabold text-ink"><?= money($settings['currency_symbol'], (float)$row['price'] * (int)$row['qty']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-2 flex items-center justify-between border-t border-line/80 pt-3 text-base">
          <span class="font-bold text-ink">Total Due at Counter</span>
          <span class="text-xl font-extrabold text-pine"><?= money($settings['currency_symbol'], $total) ?></span>
        </div>
      </div>

      <!-- Submission Form -->
      <form method="POST" action="pay.php" class="space-y-4">
        <?= csrf_field() ?>

        <?php if ($signedGuest): ?>
          <div class="rounded-2xl border border-line bg-paper/50 p-3.5 flex items-center justify-between text-xs">
            <span class="text-muted">Ordering as registered guest:</span>
            <span class="font-bold text-ink"><?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($currentUser['email'] ?? '') ?>)</span>
          </div>
        <?php else: ?>
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Your Name <span class="text-red-500">*</span></label>
            <input required type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="e.g. Table 5 / Alex" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
          </div>

          <div>
            <label class="block text-xs font-bold text-ink mb-1">Phone Number <span class="text-muted font-normal">(Optional, for notification)</span></label>
            <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="e.g. 08012345678" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold text-ink mb-1">Table # or Pickup Note</label>
          <input type="text" name="note" value="<?= htmlspecialchars($note) ?>" placeholder="e.g. Table 4 / Dine-in / Extra spicy" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20" />
        </div>

        <?php if ($error !== ''): ?>
          <div class="rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-3 text-xs font-bold text-red-600">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <div class="pt-2 space-y-2">
          <button type="submit" class="tap flex h-12 w-full items-center justify-center gap-2 rounded-full bg-pine font-bold text-sm text-white shadow-soft transition-transform active:scale-95">
            <span>Send Order · <?= money($settings['currency_symbol'], $total) ?></span>
            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
          </button>

          <a href="plate.php" class="tap flex h-11 w-full items-center justify-center rounded-full border border-line bg-paper text-xs font-bold text-ink hover:bg-line">
            Back to Plate
          </a>
        </div>
      </form>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
