<?php
// includes/kitchen_nav.php - Kitchen Sub-navigation Header
/**
 * @var string $title
 * @var string $hint
 */
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<div class="mb-6 space-y-4 rounded-3xl border border-pine/30 bg-card/90 p-4 shadow-soft">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-2">
      <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-pine text-xs text-white font-extrabold">👨‍🍳</span>
      <div>
        <h1 class="text-xl font-extrabold tracking-tight text-ink sm:text-2xl"><?= htmlspecialchars($title ?? 'Kitchen Entrance') ?></h1>
        <?php if (!empty($hint)): ?>
          <p class="text-xs text-muted"><?= htmlspecialchars($hint) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <span class="self-start rounded-full bg-pine/10 border border-pine/20 px-3 py-1 text-[11px] font-bold text-pine sm:self-auto">
      Kitchen Staff Mode
    </span>
  </div>

  <!-- Staff Quick Tabs -->
  <div class="flex gap-1.5 overflow-x-auto pb-1 scrollbar-none border-t border-line/60 pt-3 text-xs font-bold">
    <a href="orders.php" class="tap flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-all <?= $currentScript === 'orders.php' ? 'bg-pine text-white shadow-soft' : 'bg-paper text-muted hover:text-ink' ?>">
      <span>📋 Orders Board</span>
    </a>
    <a href="dishes.php" class="tap flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-all <?= $currentScript === 'dishes.php' ? 'bg-pine text-white shadow-soft' : 'bg-paper text-muted hover:text-ink' ?>">
      <span>🍲 Manage Dishes</span>
    </a>
    <a href="stock.php" class="tap flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-all <?= $currentScript === 'stock.php' ? 'bg-pine text-white shadow-soft' : 'bg-paper text-muted hover:text-ink' ?>">
      <span>🔘 Stock Toggle</span>
    </a>
    <a href="pricing.php" class="tap flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-all <?= $currentScript === 'pricing.php' ? 'bg-pine text-white shadow-soft' : 'bg-paper text-muted hover:text-ink' ?>">
      <span>🏷️ Quick Pricing</span>
    </a>
    <a href="share.php" class="tap flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-all <?= $currentScript === 'share.php' ? 'bg-pine text-white shadow-soft' : 'bg-paper text-muted hover:text-ink' ?>">
      <span>📱 QR & Share</span>
    </a>
    <a href="account.php" class="tap flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-all <?= $currentScript === 'account.php' ? 'bg-pine text-white shadow-soft' : 'bg-paper text-muted hover:text-ink' ?>">
      <span>⚙️ Settings</span>
    </a>
  </div>
</div>
