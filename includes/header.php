<?php
// includes/header.php - Global Header & Navigation
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$db = getDb();
$settings = get_restaurant_settings($db);
$currentUser = get_current_user_session();
$isStaffUser = is_staff();
$isGuestUser = is_guest();
$plateCount = cart_count();
$flash = get_flash();

$activePage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

$guestNav = [
    ['href' => 'index.php', 'label' => 'Home', 'icon' => 'home'],
    ['href' => 'menu.php', 'label' => 'Menu', 'icon' => 'book-open'],
    ['href' => 'plate.php', 'label' => ($plateCount > 0 ? "Plate ($plateCount)" : 'Plate'), 'icon' => 'shopping-bag', 'is_plate' => true],
    ['href' => 'my-orders.php', 'label' => 'Orders', 'icon' => 'bell'],
    ['href' => 'guest-account.php', 'label' => 'Account', 'icon' => 'user'],
];

$staffNav = [
    ['href' => 'menu.php', 'label' => 'Board', 'icon' => 'book-open'],
    ['href' => 'dishes.php', 'label' => 'Dishes', 'icon' => 'list'],
    ['href' => 'stock.php', 'label' => 'Stock', 'icon' => 'package'],
    ['href' => 'pricing.php', 'label' => 'Prices', 'icon' => 'tag'],
    ['href' => 'orders.php', 'label' => 'Orders', 'icon' => 'bell'],
    ['href' => 'share.php', 'label' => 'Share', 'icon' => 'qr-code'],
    ['href' => 'account.php', 'label' => 'Account', 'icon' => 'user'],
];

$navItems = $isStaffUser ? $staffNav : $guestNav;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars($settings['restaurant_name']) ?> — MenuCraft</title>
  <meta name="description" content="A live restaurant menu and price manager. Kitchen staff can mark dishes sold out, managers can change prices in bulk, and guests can open the menu from a QR code.">
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            ink: 'var(--ink)',
            invert: 'var(--invert)',
            paper: 'var(--paper)',
            card: 'var(--card)',
            line: 'var(--line)',
            pine: 'var(--pine)',
            clay: 'var(--clay)',
            muted: 'var(--muted)',
            danger: 'var(--danger)',
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="assets/css/app.css">
  <script src="assets/js/app.js" defer></script>
</head>
<body class="min-h-screen bg-paper pb-28 lg:pb-8 flex flex-col">

  <!-- Desktop & Top Navigation -->
  <header class="sticky top-0 z-40 border-b border-line bg-card/95 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
      <div class="min-w-0 flex-1">
        <a href="<?= $isStaffUser ? 'orders.php' : 'index.php' ?>" class="block">
          <p class="truncate text-base font-semibold tracking-tight sm:text-lg text-ink">
            <?= htmlspecialchars($settings['restaurant_name']) ?>
          </p>
        </a>
        <p class="truncate text-xs text-muted">
          <?php if ($isStaffUser): ?>
            Kitchen · <?= htmlspecialchars($currentUser['name'] ?? 'Staff') ?>
          <?php elseif ($isGuestUser): ?>
            Guest · <?= htmlspecialchars($currentUser['name'] ?? 'User') ?>
          <?php else: ?>
            Guest menu
          <?php endif; ?>
        </p>
      </div>

      <!-- Theme Switcher -->
      <button type="button" onclick="toggleTheme()" class="tap-icon inline-flex items-center justify-center rounded-full border border-line bg-paper text-ink" aria-label="Toggle dark mode">
        <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <svg class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
      </button>

      <!-- Desktop Tabs -->
      <nav class="hidden items-center justify-end gap-1 lg:flex" aria-label="Main">
        <?php foreach ($navItems as $item): 
          $isActive = ($activePage === $item['href']) || ($item['href'] === 'index.php' && ($activePage === '' || $activePage === 'index.php'));
        ?>
          <a href="<?= $item['href'] ?>" class="inline-flex h-10 items-center gap-2 rounded-full px-3 text-sm font-medium <?= $isActive ? 'bg-pine text-invert' : 'text-muted hover:bg-paper' ?>">
            <span class="<?= !empty($item['is_plate']) ? 'plate-tab-label' : '' ?>"><?= htmlspecialchars($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>

  <!-- Mobile Bottom Navigation Bar -->
  <nav class="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-line bg-card/95 backdrop-blur lg:hidden" aria-label="Mobile Navigation">
    <div class="nav-scroll flex gap-1 overflow-x-auto px-2 pt-1 justify-around">
      <?php foreach ($navItems as $item): 
        $isActive = ($activePage === $item['href']) || ($item['href'] === 'index.php' && ($activePage === '' || $activePage === 'index.php'));
      ?>
        <a href="<?= $item['href'] ?>" class="flex min-h-11 min-w-[4.5rem] shrink-0 flex-col items-center justify-center gap-1 rounded-2xl px-2 py-2 text-[11px] leading-none <?= $isActive ? 'bg-paper font-semibold text-pine' : 'text-muted' ?>">
          <span class="<?= !empty($item['is_plate']) ? 'plate-tab-label' : '' ?>"><?= htmlspecialchars($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>

  <!-- Flash Toast message (if set in session) -->
  <div id="toast-container">
    <?php if ($flash): ?>
      <div class="toast toast-<?= htmlspecialchars($flash['tone']) ?>">
        <?= htmlspecialchars($flash['text']) ?>
      </div>
      <script>
        setTimeout(() => {
          const t = document.querySelector('.toast');
          if (t) {
            t.style.opacity = '0';
            setTimeout(() => t.remove(), 250);
          }
        }, 3000);
      </script>
    <?php endif; ?>
  </div>

  <!-- Main Content Container -->
  <main class="mx-auto max-w-6xl w-full px-4 py-5 flex-1">
