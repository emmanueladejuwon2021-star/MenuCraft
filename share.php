<?php
// share.php - Share Menu & QR Code Generator
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_staff();

$db = getDb();
$settings = get_restaurant_settings($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_label($_POST['restaurant_name'] ?? '', 80);
    $symbol = clean_label($_POST['currency_symbol'] ?? '₦', 4);

    if ($name === '') {
        flash('error', 'Please enter a restaurant name.');
    } else {
        $stmt = $db->prepare("UPDATE restaurant_settings SET restaurant_name = ?, currency_symbol = ? WHERE id = 1");
        $stmt->execute([$name, $symbol]);
        flash('ok', 'Saved!');
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

<section class="page-enter grid gap-4 md:grid-cols-[1fr_300px]">
  <div class="space-y-4">
    <?php 
    $title = "Share the live menu";
    $hint = "Guests scan the code or open the guest link. They only see the menu, not kitchen tools.";
    require __DIR__ . '/includes/kitchen_nav.php'; 
    ?>

    <form method="POST" action="share.php" class="space-y-3 rounded-2xl border border-line bg-card p-4 shadow-soft">
      <h2 class="text-base font-semibold text-ink">Restaurant details</h2>
      <label class="block text-sm text-ink">
        Restaurant name
        <input required type="text" name="restaurant_name" value="<?= htmlspecialchars($settings['restaurant_name']) ?>" class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
      </label>

      <label class="block text-sm text-ink">
        Money symbol
        <input required type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" class="tap mt-1 w-full rounded-xl border border-line bg-paper px-3 text-ink" />
      </label>

      <button type="submit" class="tap h-11 rounded-full bg-pine px-5 text-sm font-semibold text-invert">
        Save details
      </button>

      <hr class="border-line my-4" />

      <p class="text-sm font-medium text-ink">Guest link</p>
      <p class="break-all rounded-xl bg-paper px-3 py-2 text-sm text-ink"><?= htmlspecialchars($guestLink) ?></p>
      <button type="button" onclick="copyText('<?= htmlspecialchars(addslashes($guestLink)) ?>', 'Link copied! Guests can scan or open it to view the live menu.')" class="tap h-11 rounded-full border border-line px-4 text-sm text-ink">
        Copy guest link
      </button>

      <p class="pt-3 text-sm font-medium text-ink">Kitchen door (staff only)</p>
      <p class="text-xs text-muted">Bookmark this on the kitchen tablet. Do not print it on the guest QR code.</p>
      <p class="break-all rounded-xl bg-paper px-3 py-2 text-sm text-ink"><?= htmlspecialchars($kitchenLink) ?></p>
      <button type="button" onclick="copyText('<?= htmlspecialchars(addslashes($kitchenLink)) ?>', 'Kitchen door copied! Save it on the kitchen tablet only.')" class="tap h-11 rounded-full border border-line px-4 text-sm text-ink">
        Copy kitchen door
      </button>
    </form>
  </div>

  <!-- QR Code Box -->
  <div class="rounded-2xl border border-line bg-card p-5 text-center shadow-soft flex flex-col items-center justify-center">
    <div id="qrcode-container" class="rounded-xl bg-white p-3 shadow inline-block"></div>
    <p class="mt-4 text-sm text-muted">Point a phone camera at this code to open the live menu.</p>
    <button type="button" onclick="downloadQr()" class="tap mt-3 h-11 rounded-full bg-pine px-4 text-sm font-semibold text-invert">
      Download QR image
    </button>
  </div>
</section>

<script src="assets/js/qrcode.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const guestUrl = "<?= htmlspecialchars($guestLink) ?>";
    new QRCode(document.getElementById("qrcode-container"), {
      text: guestUrl,
      width: 220,
      height: 220,
      colorDark: "#1d4ed8",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H
    });
  });

  function downloadQr() {
    const canvas = document.querySelector("#qrcode-container canvas");
    if (!canvas) return;
    const link = document.createElement("a");
    link.download = "menu-qr.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
