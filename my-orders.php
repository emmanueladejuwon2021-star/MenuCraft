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

$statusSteps = ['New', 'Cooking', 'Ready', 'Served'];

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-6 pb-16">

  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-pine">🛵 Live Tracker</span>
      <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Your Orders</h1>
    </div>
    <a href="menu.php" class="tap inline-flex items-center gap-2 rounded-full bg-pine px-5 py-2.5 text-xs font-bold text-white shadow-soft self-start sm:self-auto">
      <span>+ Order More Food</span>
    </a>
  </div>

  <!-- Search Ticket Box -->
  <form method="GET" action="my-orders.php" class="relative block">
    <div class="relative flex items-center">
      <svg class="absolute left-4 h-5 w-5 text-muted fill-current pointer-events-none" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" name="ticket" value="<?= htmlspecialchars($ticketFilter) ?>" placeholder="Search by ticket code or name..." class="h-11 w-full rounded-2xl border border-line bg-card pl-11 pr-16 text-sm font-medium text-ink placeholder:text-muted focus:border-pine focus:outline-none focus:ring-2 focus:ring-pine/20 shadow-soft" onchange="this.form.submit()" />
      <?php if ($ticketFilter !== ''): ?>
        <a href="my-orders.php" class="absolute right-3 rounded-full bg-paper px-2.5 py-1 text-xs font-bold text-pine hover:bg-line">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($guestOrders)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <span class="text-5xl block mb-3">🧾</span>
      <h2 class="text-xl font-bold text-ink">
        <?= $ticketFilter !== '' ? "No matching ticket found" : "No active orders yet" ?>
      </h2>
      <p class="mt-2 text-sm text-muted">
        <?= $ticketFilter !== '' ? "Check your ticket code and try again." : "Select your favourite meal from today's menu board and place an order." ?>
      </p>
      <a href="menu.php" class="tap mt-6 inline-flex items-center gap-2 rounded-full bg-pine px-6 py-3 text-sm font-bold text-white shadow-soft">
        <span>Browse Menu Board</span>
      </a>
    </div>
  <?php else: ?>
    <div class="space-y-6">
      <?php foreach ($guestOrders as $order): 
        $items = json_decode($order['items_json'] ?? '[]', true) ?: [];
        $timeStr = !empty($order['created_at']) ? date('M j, g:i a', strtotime($order['created_at'])) : 'Just now';
        $currentStatus = $order['status'] ?: 'New';
        $currentIndex = array_search($currentStatus, $statusSteps);
        if ($currentIndex === false) $currentIndex = 0;
      ?>
        <article class="overflow-hidden rounded-3xl border border-line bg-card shadow-card space-y-4 p-6 transition-all hover:border-pine/30">

          <!-- Order Header -->
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-line pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-base text-ink">Ticket #<?= htmlspecialchars($order['pay_ref']) ?></span>
                <span class="rounded-full bg-pine/10 border border-pine/20 px-2.5 py-0.5 text-[11px] font-extrabold text-pine">
                  <?= htmlspecialchars($order['guest_name'] ?? 'Guest') ?>
                </span>
              </div>
              <p class="text-xs text-muted mt-1">Placed: <?= htmlspecialchars($timeStr) ?></p>
            </div>
            <div class="text-right">
              <span class="text-xs font-bold text-muted block">Total (Pay at counter)</span>
              <span class="text-xl font-extrabold text-pine"><?= money($settings['currency_symbol'], $order['total']) ?></span>
            </div>
          </div>

          <!-- Progress Stepper Bar -->
          <div class="py-2">
            <div class="relative flex items-center justify-between max-w-xl mx-auto px-2">
              <!-- Connecting Line -->
              <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-1 bg-line z-0"></div>

              <?php
                $labels = [
                  'New' => 'Sent',
                  'Cooking' => 'Cooking',
                  'Ready' => 'Ready!',
                  'Served' => 'Served'
                ];
                foreach ($statusSteps as $idx => $step):
                  $isPassed = $idx <= $currentIndex;
                  $isCurrent = $idx === $currentIndex;
              ?>
                <div class="relative z-10 flex flex-col items-center gap-1.5">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-extrabold transition-all <?= $isCurrent ? 'bg-pine text-white ring-4 ring-pine/20 scale-110 shadow-soft' : ($isPassed ? 'bg-pine text-white' : 'bg-paper text-muted border border-line') ?>">
                    <?php if ($isPassed && !$isCurrent): ?>
                      ✓
                    <?php else: ?>
                      <?= $idx + 1 ?>
                    <?php endif; ?>
                  </div>
                  <span class="text-[11px] font-bold <?= $isCurrent ? 'text-pine' : ($isPassed ? 'text-ink' : 'text-muted') ?>">
                    <?= $labels[$step] ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Note if present -->
          <?php if (!empty($order['note'])): ?>
            <div class="rounded-2xl bg-paper px-4 py-2.5 text-xs font-semibold text-ink border border-line/60 flex items-center gap-2">
              <span class="text-pine font-bold">📌 Note:</span>
              <span><?= htmlspecialchars($order['note']) ?></span>
            </div>
          <?php endif; ?>

          <!-- Order Items Breakdown -->
          <div class="rounded-2xl bg-paper/60 p-4 space-y-1.5 text-xs">
            <span class="font-bold text-muted uppercase tracking-wider block mb-1">Ordered Dishes</span>
            <?php foreach ($items as $item): ?>
              <div class="flex justify-between items-center py-1">
                <span class="font-bold text-ink"><?= (int)$item['qty'] ?> × <?= htmlspecialchars($item['name']) ?></span>
                <span class="font-semibold text-muted"><?= money($settings['currency_symbol'], (float)($item['price'] ?? 0) * (int)($item['qty'] ?? 1)) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
