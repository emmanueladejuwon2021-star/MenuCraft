<?php
// orders.php - Staff Kitchen Orders Board
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);

// Handle status advance POST action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $orderId = (int)($_POST['id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['New', 'Cooking', 'Ready', 'Served'];

    if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        $msg = ($newStatus === 'Cooking') ? 'Kitchen has started cooking this order' : (($newStatus === 'Ready') ? 'Order marked ready for pickup!' : 'Order marked served');
        if (is_ajax()) {
            json_response(['ok' => true, 'message' => $msg]);
        }
        flash('ok', $msg);
        header('Location: orders.php?filter=' . urlencode($_GET['filter'] ?? 'open'));
        exit;
    }
}

$filter = $_GET['filter'] ?? 'open';

$allOrders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

// Calculate open count
$openCount = count(array_filter($allOrders, fn($o) => ($o['status'] ?? 'New') !== 'Served'));

// Filter orders
$visibleOrders = array_filter($allOrders, function($o) use ($filter) {
    $status = $o['status'] ?? 'New';
    if ($filter === 'open') return $status !== 'Served';
    if ($filter === 'served') return $status === 'Served';
    return true; // 'all'
});

// Sort by status ranking: New (0) -> Cooking (1) -> Ready (2) -> Served (3), then by ID desc
$rank = ['New' => 0, 'Cooking' => 1, 'Ready' => 2, 'Served' => 3];
usort($visibleOrders, function($a, $b) use ($rank) {
    $rA = $rank[$a['status'] ?? 'New'] ?? 9;
    $rB = $rank[$b['status'] ?? 'New'] ?? 9;
    if ($rA !== $rB) return $rA <=> $rB;
    return ($b['id'] <=> $a['id']);
});

$nextActionMap = [
    'New' => ['label' => 'Start Cooking 🔥', 'status' => 'Cooking', 'class' => 'bg-amber-600 text-white hover:bg-amber-700'],
    'Cooking' => ['label' => 'Mark Ready 🔔', 'status' => 'Ready', 'class' => 'bg-pine text-white hover:bg-pine-hover'],
    'Ready' => ['label' => 'Mark Served ✅', 'status' => 'Served', 'class' => 'bg-emerald-700 text-white hover:bg-emerald-800'],
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-6 pb-16">
  <?php 
  $title = "Kitchen Orders Board";
  $hint = "Live ticket queue for cooks and counters. Advance orders as they prepare.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <!-- Filter tabs bar -->
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-1.5 rounded-full bg-paper p-1 border border-line text-xs font-bold">
      <a href="orders.php?filter=open" class="tap inline-flex h-8 items-center rounded-full px-4 transition-all <?= $filter === 'open' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
        Open Queue (<?= $openCount ?>)
      </a>
      <a href="orders.php?filter=served" class="tap inline-flex h-8 items-center rounded-full px-4 transition-all <?= $filter === 'served' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
        Completed / Served
      </a>
      <a href="orders.php?filter=all" class="tap inline-flex h-8 items-center rounded-full px-4 transition-all <?= $filter === 'all' ? 'bg-pine text-white shadow-soft' : 'text-muted hover:text-ink' ?>">
        All Orders
      </a>
    </div>

    <button onclick="window.location.reload()" class="tap hidden sm:inline-flex items-center gap-1.5 rounded-full border border-line bg-card px-3.5 py-1.5 text-xs font-bold text-muted hover:text-ink">
      <span>🔄 Refresh</span>
    </button>
  </div>

  <?php if (empty($visibleOrders)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card p-12 text-center shadow-soft">
      <span class="text-5xl block mb-3">👨‍🍳</span>
      <h2 class="text-xl font-bold text-ink"><?= $filter === 'served' ? 'No served orders yet' : 'Order queue is empty' ?></h2>
      <p class="mt-2 text-sm text-muted">New guest orders will appear automatically on this board.</p>
      <a href="share.php" class="tap mt-6 inline-flex items-center gap-2 rounded-full bg-pine px-6 py-3 text-xs font-bold text-white shadow-soft">
        <span>Share QR Menu Code</span>
      </a>
    </div>
  <?php else: ?>
    <div class="grid gap-5 md:grid-cols-2">
      <?php foreach ($visibleOrders as $order): 
        $items = json_decode($order['items_json'] ?? '[]', true) ?: [];
        $action = $nextActionMap[$order['status']] ?? null;
        $timeStr = !empty($order['created_at']) ? date('g:i a', strtotime($order['created_at'])) : 'Just now';
        $status = $order['status'] ?: 'New';

        $badgeColors = [
          'New' => 'bg-red-500/10 text-red-600 border-red-500/30',
          'Cooking' => 'bg-amber-500/10 text-amber-600 border-amber-500/30',
          'Ready' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30',
          'Served' => 'bg-gray-500/10 text-gray-500 border-gray-500/30',
        ];
      ?>
        <article class="overflow-hidden rounded-3xl border border-line bg-card shadow-card space-y-4 p-5 transition-all hover:border-pine/30">

          <div class="flex items-start justify-between gap-3 border-b border-line pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-base text-ink"><?= htmlspecialchars($order['guest_name'] ?: 'Walk-in Guest') ?></span>
                <span class="text-xs font-mono font-bold text-muted">#<?= htmlspecialchars($order['pay_ref']) ?></span>
              </div>
              <p class="text-xs text-muted mt-0.5"><?= htmlspecialchars($order['phone'] ?: 'No phone provided') ?> · Received at <?= htmlspecialchars($timeStr) ?></p>
            </div>

            <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-extrabold <?= $badgeColors[$status] ?? 'bg-paper text-muted border-line' ?>">
              <?= htmlspecialchars($status) ?>
            </span>
          </div>

          <?php if (!empty($order['note'])): ?>
            <div class="rounded-2xl bg-paper px-3.5 py-2 text-xs font-semibold text-ink border border-line/60">
              <span class="text-clay font-bold">📌 Table / Note:</span> <?= htmlspecialchars($order['note']) ?>
            </div>
          <?php endif; ?>

          <ul class="space-y-1.5 text-xs border-b border-line pb-3">
            <?php foreach ($items as $line): ?>
              <li class="flex items-center justify-between">
                <span class="font-bold text-ink"><span class="inline-block w-6 text-pine font-extrabold"><?= (int)$line['qty'] ?>x</span> <?= htmlspecialchars($line['name']) ?></span>
                <span class="font-semibold text-muted"><?= money($settings['currency_symbol'], (float)$line['price'] * (int)$line['qty']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="flex items-center justify-between gap-3 pt-1">
            <div>
              <span class="text-[10px] font-bold text-muted block uppercase">Counter Total</span>
              <span class="text-base font-extrabold text-pine"><?= money($settings['currency_symbol'], $order['total']) ?></span>
            </div>

            <?php if ($action): ?>
              <form method="POST" action="orders.php?filter=<?= urlencode($filter) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$order['id'] ?>" />
                <input type="hidden" name="status" value="<?= htmlspecialchars($action['status']) ?>" />
                <button type="submit" class="tap rounded-full px-5 py-2.5 text-xs font-bold transition-transform active:scale-95 shadow-soft <?= $action['class'] ?>">
                  <?= htmlspecialchars($action['label']) ?>
                </button>
              </form>
            <?php endif; ?>
          </div>

        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
