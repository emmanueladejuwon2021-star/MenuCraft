<?php
// share.php - QR Code & Share Screen (Staff)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_settings') {
    require_csrf();
    $name = clean_label($_POST['restaurant_name'] ?? '', 80);
    $symbol = clean_label($_POST['currency_symbol'] ?? '', 8);

    if ($name !== '') {
        $db->prepare("UPDATE restaurant_settings SET restaurant_name = ?, currency_symbol = ? WHERE id = 1")
           ->execute([$name, $symbol ?: '₦']);
        flash('ok', 'Restaurant details updated.');
    } else {
        flash('error', 'Restaurant name cannot be empty.');
    }
    header('Location: share.php');
    exit;
}

$guestLink = base_url() . '/menu.php';
$kitchenLink = base_url() . '/iyabisi/kitchenlock/';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/kitchen_nav.php';
?>

<section class="page-enter space-y-6 pb-12">
  <div>
    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-pine">
      <span>📱 Share & QR</span>
    </div>
    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Table QR & Links</h1>
    <p class="mt-1 text-sm text-muted">Print the QR for tables or share the guest menu link. Kitchen door stays private.</p>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <!-- Left: Settings & Links -->
    <div class="space-y-6">
      <!-- Restaurant Settings -->
      <div class="rounded-3xl border border-line bg-card p-6 shadow-card">
        <h3 class="font-bold text-base text-ink mb-4">Restaurant Details</h3>
        <form method="POST" action="share.php" class="space-y-4">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_settings">
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Restaurant Name</label>
            <input type="text" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-ink mb-1">Currency Symbol</label>
            <input type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" maxlength="8" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-medium text-ink focus:border-pine focus:outline-none" placeholder="₦, $, €, £">
          </div>
          <button type="submit" class="tap h-11 w-full rounded-full bg-pine font-bold text-sm text-white shadow-soft">Save Details</button>
        </form>
      </div>

      <!-- Links Box -->
      <div class="rounded-3xl border border-line bg-card p-6 shadow-card space-y-5">
        <div>
          <h3 class="font-bold text-base text-ink flex items-center gap-2">
            <span>🔗 Public Guest Menu Link</span>
          </h3>
          <p class="text-xs text-muted mt-1">Print or share this link with guests at tables.</p>
          <div class="mt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <p class="truncate flex-1 rounded-2xl bg-paper px-4 py-2.5 text-xs font-mono font-bold text-ink border border-line/60"><?= htmlspecialchars($guestLink) ?></p>
            <button type="button" onclick="copyText(<?= json_encode($guestLink) ?>, 'Guest menu link copied!')" class="tap shrink-0 rounded-full border border-line bg-paper px-4 py-2.5 text-xs font-bold text-ink hover:bg-line">
              📋 Copy Link
            </button>
          </div>
        </div>

        <div class="pt-4 border-t border-line/60">
          <h3 class="font-bold text-base text-ink flex items-center gap-2">
            <span>🔐 Private Kitchen Door Link</span>
          </h3>
          <p class="text-xs text-muted mt-1">Staff entrance for kitchen tablets. Do not share publicly.</p>
          <div class="mt-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <p class="truncate flex-1 rounded-2xl bg-paper px-4 py-2.5 text-xs font-mono font-bold text-ink border border-line/60"><?= htmlspecialchars($kitchenLink) ?></p>
            <button type="button" onclick="copyText(<?= json_encode($kitchenLink) ?>, 'Kitchen entrance link copied!')" class="tap shrink-0 rounded-full border border-line bg-paper px-4 py-2.5 text-xs font-bold text-ink hover:bg-line">
              📋 Copy Link
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: QR Code Display -->
    <div class="rounded-3xl border border-line bg-card p-6 text-center shadow-card flex flex-col items-center justify-between">
      <div class="space-y-1">
        <span class="text-xs font-bold text-pine uppercase tracking-wider block">Scannable QR Code</span>
        <h3 class="font-extrabold text-lg text-ink">Table QR Poster</h3>
        <p class="text-xs text-muted">Guests scan to open the live menu instantly.</p>
      </div>

      <div id="qrcode-container" class="my-6 flex items-center justify-center rounded-2xl bg-white p-4 shadow-soft" style="min-height: 220px; min-width: 220px;"></div>

      <button type="button" onclick="downloadQr()" class="tap h-11 w-full rounded-full bg-pine font-bold text-sm text-white shadow-soft">
        Download QR as PNG
      </button>
    </div>
  </div>
</section>

<script src="assets/js/qrcode.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('qrcode-container');
    if (container && typeof QRCode !== 'undefined') {
      new QRCode(container, {
        text: <?= json_encode($guestLink) ?>,
        width: 200,
        height: 200,
        colorDark: '#1c1917',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
