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
    $orderId = (int)($_POST['id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['New', 'Cooking', 'Ready', 'Served'];

    if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        $msg = ($newStatus === 'Cooking') ? 'Kitchen has started this order' : (($newStatus === 'Ready') ? 'Order is ready' : 'Order marked served');
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
    'New' => ['label' => 'Start cooking', 'status' => 'Cooking'],
    'Cooking' => ['label' => 'Mark ready', 'status' => 'Ready'],
    'Ready' => ['label' => 'Mark served', 'status' => 'Served'],
];

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-5">
  <?php 
  $title = "Kitchen orders";
  $hint = "New orders land at the top. Move each one as you cook. Pay is still at the counter.";
  require __DIR__ . '/includes/kitchen_nav.php'; 
  ?>

  <!-- Filter tabs -->
  <div class="flex flex-wrap items-center gap-2">
    <a href="orders.php?filter=open" class="tap h-11 inline-flex items-center rounded-full px-4 text-sm font-medium <?= $filter === 'open' ? 'bg-pine text-invert' : 'border border-line bg-card text-ink' ?>">
      Open (<?= $openCount ?>)
    </a>
    <a href="orders.php?filter=served" class="tap h-11 inline-flex items-center rounded-full px-4 text-sm font-medium <?= $filter === 'served' ? 'bg-pine text-invert' : 'border border-line bg-card text-ink' ?>">
      Served
    </a>
    <a href="orders.php?filter=all" class="tap h-11 inline-flex items-center rounded-full px-4 text-sm font-medium <?= $filter === 'all' ? 'bg-pine text-invert' : 'border border-line bg-card text-ink' ?>">
      All
    </a>
  </div>

  <?php if (empty($visibleOrders)): ?>
    <div class="rounded-3xl border border-dashed border-line bg-card px-5 py-12 text-center">
      <p class="font-semibold text-ink"><?= $filter === 'served' ? 'Nothing served yet' : 'No open orders' ?></p>
      <p class="mt-2 text-sm text-muted">Tickets show up here after a guest sends a plate.</p>
      <a href="share.php" class="tap mt-5 inline-flex items-center justify-center rounded-full bg-pine px-5 text-sm font-semibold text-invert">
        Share the menu
      </a>
    </div>
  <?php else: ?>
    <div class="grid gap-3 md:grid-cols-2">
      <?php foreach ($visibleOrders as $order): 
        $items = json_decode($order['items_json'] ?? '[]', true) ?: [];
        $action = $nextActionMap[$order['status']] ?? null;
        $timeStr = !empty($order['created_at']) ? date('M j, g:i a', strtotime($order['created_at'])) : 'Just now';
        $contact = !empty($order['phone']) ? $order['phone'] : (!empty($order['guest_email']) && strpos($order['guest_email'], 'walkin:') === false ? $order['guest_email'] : 'Walk-in');
      ?>
        <article class="rounded-3xl border border-line bg-card p-4 shadow-soft">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate font-semibold text-ink"><?= htmlspecialchars($order['guest_name'] ?: 'Guest') ?></p>
              <p class="truncate text-xs text-muted"><?= htmlspecialchars($contact) ?> · <?= htmlspecialchars($timeStr) ?></p>
            </div>
            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold <?= $order['status'] === 'Ready' ? 'bg-pine text-invert' : 'bg-paper text-clay' ?>">
              <?= htmlspecialchars($order['status'] ?: 'New') ?>
            </span>
          </div>

          <?php if (!empty($order['note'])): ?>
            <p class="mt-3 rounded-2xl bg-paper px-3 py-2 text-sm font-medium text-ink">Note: <?= htmlspecialchars($order['note']) ?></p>
          <?php endif; ?>

          <ul class="mt-3 space-y-1 text-sm text-ink">
            <?php foreach ($items as $item): ?>
              <li><?= (int)$item['qty'] ?> × <?= htmlspecialchars($item['name']) ?></li>
            <?php endforeach; ?>
          </ul>

          <p class="mt-3 text-sm font-semibold text-ink">
            <?= money($settings['currency_symbol'], $order['total']) ?> · Pay at the counter
          </p>

          <?php if ($action): ?>
            <form method="POST" action="orders.php?filter=<?= urlencode($filter) ?>">
              <input type="hidden" name="id" value="<?= (int)$order['id'] ?>" />
              <input type="hidden" name="status" value="<?= htmlspecialchars($action['status']) ?>" />
              <button type="submit" class="tap mt-3 h-12 w-full rounded-full bg-pine text-sm font-semibold text-invert">
                <?= htmlspecialchars($action['label']) ?>
              </button>
            </form>
          <?php else: ?>
            <p class="mt-3 text-sm text-muted">Served</p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Auto-refresh orders board every 15 seconds -->
<script>
  setTimeout(() => {
    // Soft reload to keep tickets updated
    if (!document.hidden) {
      window.location.reload();
    }
  }, 15000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
