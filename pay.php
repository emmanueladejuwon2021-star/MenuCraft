<?php
// pay.php - Guest Checkout & Order Submission
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_build.php';

$plate = get_cart();
if (empty($plate)) {
    header('Location: plate.php');
    exit;
}

$db = getDb();
$settings = get_restaurant_settings($db);
$currentUser = get_current_user_session();
$signedGuest = is_guest();

$name = $signedGuest ? ($currentUser['name'] ?? '') : '';
$phone = $signedGuest ? ($currentUser['phone'] ?? '') : '';
$note = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedName = clean_label($_POST['name'] ?? $name, 80);
    $submittedPhone = clean_label($_POST['phone'] ?? $phone, 30);
    $submittedNote = clean_label($_POST['note'] ?? '', 240);
    $email = $signedGuest ? ($currentUser['email'] ?? '') : '';

    if ($submittedName === '') {
        $error = "Add your name so the kitchen can call the plate.";
    } else {
        if ($email === '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $submittedName)) ?: 'guest';
            $email = "walkin:{$slug}-" . base_convert((string)time(), 10, 36);
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

            flash('ok', "Order sent. Pay at the counter. The kitchen can see it.");
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

<section class="page-enter mx-auto max-w-lg space-y-5 pb-8">
  <div class="overflow-hidden rounded-[2rem] border border-line bg-card shadow-soft">
    <div class="bg-gradient-to-br from-blue-200/70 to-transparent px-6 py-6 dark:from-blue-500/15">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-clay">Checkout</p>
      <h1 class="mt-1 text-2xl font-semibold text-ink">Send this plate</h1>
      <p class="mt-2 text-sm leading-relaxed text-muted">No card is needed. The kitchen gets the order now. You pay at the counter when you pick it up.</p>
    </div>

    <div class="space-y-4 px-6 pb-6">
      <?php if ($signedGuest): ?>
        <p class="text-sm text-ink">Sending as <span class="font-semibold"><?= htmlspecialchars($currentUser['name']) ?></span></p>
      <?php else: ?>
        <p class="text-sm text-muted">No account needed. Leave a name for the ticket.</p>
      <?php endif; ?>

      <div class="rounded-2xl bg-paper px-4 py-3 space-y-1">
        <?php foreach ($plate as $row): ?>
          <p class="flex justify-between gap-3 py-1 text-sm text-ink">
            <span><?= (int)$row['qty'] ?> × <?= htmlspecialchars($row['name']) ?></span>
            <span><?= money($settings['currency_symbol'], (float)$row['price'] * (int)$row['qty']) ?></span>
          </p>
        <?php endforeach; ?>
        <p class="mt-2 flex justify-between border-t border-line pt-3 text-base font-semibold text-ink">
          <span>Total at the counter</span>
          <span><?= money($settings['currency_symbol'], $total) ?></span>
        </p>
      </div>

      <form method="POST" action="pay.php" class="space-y-3">
        <?php if (!$signedGuest): ?>
          <label class="block text-sm font-medium text-ink">
            Your name
            <input required type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Name on the ticket" class="field mt-1" />
          </label>
          <label class="block text-sm font-medium text-ink">
            Phone (optional)
            <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="If the kitchen should call you" class="field mt-1" />
          </label>
        <?php endif; ?>

        <label class="block text-sm font-medium text-ink">
          Table or pickup note
          <input type="text" name="note" value="<?= htmlspecialchars($note) ?>" placeholder="Table 4, or pickup" class="field mt-1" />
        </label>

        <?php if ($error !== ''): ?>
          <p class="rounded-2xl border border-line bg-card px-3 py-3 text-sm text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <button type="submit" class="tap h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert">
          Send order · <?= money($settings['currency_symbol'], $total) ?>
        </button>

        <a href="plate.php" class="tap inline-flex h-11 w-full items-center justify-center rounded-full border border-line text-sm font-medium text-ink">
          Back to plate
        </a>
      </form>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
