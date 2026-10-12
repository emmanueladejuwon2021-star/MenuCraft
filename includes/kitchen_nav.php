<?php
// includes/kitchen_nav.php - Kitchen Sub-navigation Header
/**
 * @var string $title
 * @var string $hint
 */
?>
<div class="space-y-3 pb-2">
  <div class="flex items-end justify-between gap-3">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-clay">Kitchen Door</p>
      <h1 class="mt-1 text-2xl sm:text-3xl font-semibold tracking-tight text-ink"><?= htmlspecialchars($title ?? 'Kitchen') ?></h1>
    </div>
  </div>
  <?php if (!empty($hint)): ?>
    <p class="text-sm text-muted"><?= htmlspecialchars($hint) ?></p>
  <?php endif; ?>
</div>
