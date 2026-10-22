<?php
// share.php - Share Menu & QR Code Generator
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = clean_label($_POST['restaurant_name'] ?? '', 80);
    $symbol = clean_label($_POST['currency_symbol'] ?? '₦', 4);

    if ($name === '') {
        flash('error', 'Please enter a restaurant name.');
    } else {
        $stmt = $db->prepare("UPDATE restaurant_settings SET restaurant_name = ?, currency_symbol = ? WHERE id = 1");
        $stmt->execute([$name, $symbol]);
        flash('ok', 'Restaurant branding saved!');
        header('Location: share.php');
        exit;
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$guestLink = "$protocol://$host$dir/menu.php";
$kitchenLink = "$protocol://$host$dir/iyabisi/kitchenlock/index.php";

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-enter space-y-6 pb-16">
  <?php
  $title = "QR Code & Share Portal";
  $hint = "Generate table QR codes, copy live menu links, and manage restaurant branding.";
  require __DIR__ . '/includes/kitchen_nav.php';
  ?>

  <div class="grid gap-6 md:grid-cols-[1fr_340px]">

    <!-- Left Column: Details & Links -->
    <div class="space-y-6">

      <!-- Restaurant Details Form -->
      <div class="overflow-hidden rounded-3xl border border-line bg-card shadow-card">
        <div class="bg-gradient-to-r from-pine/15 via-pine/5 to-transparent px-6 py-4 border-b border-line">
          <span class="text-xs font-bold uppercase tracking-wider text-pine">🏪 Brand Identity</span>
          <h2 class="mt-0.5 text-lg font-extrabold text-ink">Restaurant & Currency Settings</h2>
        </div>

        <form method="POST" action="share.php" class="p-6 space-y-4">
          <?= csrf_field() ?>

          <div>
            <label class="block text-xs font-bold text-ink mb-1">Restaurant Name</label>
            <input required type="text" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-bold text-ink focus:border-pine focus:outline-none" />
          </div>

          <div>
            <label class="block text-xs font-bold text-ink mb-1">Currency Symbol</label>
            <input required type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" class="h-11 w-full rounded-2xl border border-line bg-paper px-4 text-sm font-bold text-ink focus:border-pine focus:outline-none" />
          </div>

          <button type="submit" class="tap shrink-0 rounded-full bg-pine px-6 py-2.5 text-xs font-bold text-white shadow-soft">
            Save Details
          </button>
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
            <button type="button" onclick="copyText('<?= htmlspecialchars(addslashes($guestLink)) ?>', 'Guest menu link copied!')" class="tap shrink-0 rounded-full border border-line bg-paper px-4 py-2.5 text-xs font-bold text-ink hover:bg-line">
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
            <button type="button" onclick="copyText('<?= htmlspecialchars(addslashes($kitchenLink)) ?>', 'Kitchen entrance link copied!')" class="tap shrink-0 rounded-full border border-line bg-paper px-4 py-2.5 text-xs font-bold text-ink hover:bg-line">
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
        <p class="text-xs text-muted">Point any smartphone camera to scan and view the live menu instantly.</p>
      </div>

      <div class="my-6 rounded-2xl bg-white p-4 shadow-soft border border-line">
        <div id="qrcode-container" class="inline-block"></div>
      </div>

      <button type="button" onclick="downloadQr()" class="tap h-12 w-full flex items-center justify-center gap-2 rounded-full bg-pine font-bold text-xs text-white shadow-soft">
        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
        <span>Download Printable PNG</span>
      </button>
    </div>

  </div>
</section>

<script src="assets/js/qrcode.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const guestUrl = "<?= htmlspecialchars($guestLink) ?>";
    new QRCode(document.getElementById("qrcode-container"), {
      text: guestUrl,
      width: 200,
      height: 200,
      colorDark: "#1b4d3e",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H
    });
  });

  function downloadQr() {
    const canvas = document.querySelector("#qrcode-container canvas");
    if (!canvas) return;
    const link = document.createElement("a");
    link.download = "<?= strtolower(preg_replace('/[^a-z0-9]+/i', '-', $settings['restaurant_name'])) ?>-qr.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
