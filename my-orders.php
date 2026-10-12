<?php
// my-orders.php - Guest Orders Status & Tracking
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/order_build.php';

$db = getDb();
$settings = get_restaurant_settings($db);
$currentUser = get_current_user_session();
$tickets = get_tickets();

$allOrders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$guestOrders = visibleOrders($allOrders, $currentUser, $tickets);

$ticketFilter = trim($_GET['ticket'] ?? '');
if ($ticketFilter !== '') {
    $needle = strtolower($ticketFilter);
    $guestOrders = array_filter($guestOrders, function($o) use ($needle) {
        return strpos(strtolower($o['pay_ref'] ?? ''), $needle) !== false
            || strpos(strtolower($o['guest_name'] ?? ''), $needle) !== false;
    });
}

$statusWords = [
    'New' => 'Sent to the kitchen',
    'Cooking' => 'On the fire',
    'Ready' => 'Ready to pick up',
    'Served' => 'Served',
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-5 pb-28 md:pb-8">
  <div class="space-y-1">
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Guest</p>
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Your orders</h1>
    <p class="text-sm text-muted">Newest first. This phone keeps your tickets. Pay at the counter when you pick the food up.</p>
  </div>

  <div class="flex flex-col sm:flex-row gap-3">
    <a href="menu.php" class="tap inline-flex h-12 items-center justify-center rounded-full bg-pine px-6 text-sm font-semibold text-invert">
      Order food
    </a>
  </div>

  <form method="GET" action="my-orders.php" class="block">
    <label class="block text-sm font-medium text-ink">
      Find a ticket on this phone
      <input type="text" name="ticket" value="<?= htmlspecialchars($ticketFilter) ?>" placeholder="Ticket code, if the kitchen gave you one" class="field mt-1" onchange="this.form.submit()" />
    </label>
  </form>

  <?php if (empty($guestOrders)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
      <p class="font-semibold text-ink">
        <?= $ticketFilter !== '' ? "No ticket with that code on this phone" : "No orders yet" ?>
      </p>
      <p class="mt-2 text-sm text-muted">
        <?= $ticketFilter !== '' ? "Ask the kitchen for the ticket on their board, or send a new plate." : "Open the board, add a dish to your plate, then send it." ?>
      </p>
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($guestOrders as $order): 
        $items = json_decode($order['items_json'] ?? '[]', true) ?: [];
        $timeStr = !empty($order['created_at']) ? date('M j, g:i a', strtotime($order['created_at'])) : 'Just now';
        $statusLabel = $statusWords[$order['status']] ?? ($order['status'] ?: 'Sent');
        $isReady = ($order['status'] === 'Ready');
      ?>
        <article class="rounded-3xl border border-line bg-card p-4 shadow-soft">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="font-semibold text-ink flex items-center gap-2">
                <span><?= htmlspecialchars($statusLabel) ?></span>
                <?php if ($isReady): ?>
                  <span class="inline-block h-2 w-2 rounded-full bg-pine"></span>
                <?php endif; ?>
              </p>
              <p class="text-xs text-muted"><?= htmlspecialchars($timeStr) ?></p>
            </div>
            <p class="shrink-0 font-semibold text-ink"><?= money($settings['currency_symbol'], $order['total']) ?></p>
          </div>

          <?php if (!empty($order['note'])): ?>
            <p class="mt-3 rounded-2xl bg-paper px-3 py-2 text-sm font-medium text-ink">Note: <?= htmlspecialchars($order['note']) ?></p>
          <?php endif; ?>

          <ul class="mt-3 space-y-1 text-sm text-muted">
            <?php foreach ($items as $item): ?>
              <li><?= (int)$item['qty'] ?> × <?= htmlspecialchars($item['name']) ?></li>
            <?php endforeach; ?>
          </ul>

          <p class="mt-3 text-xs text-muted">
            Pay at the counter · ticket <span class="font-semibold text-ink"><?= htmlspecialchars($order['pay_ref']) ?></span>
          </p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
