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
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/app.js" defer></script>
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
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
        $iconSvg = '';
        switch ($item['icon'] ?? '') {
          case 'home':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>';
            break;
          case 'book-open':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>';
            break;
          case 'shopping-bag':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>';
            break;
          case 'bell':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>';
            break;
          case 'user':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>';
            break;
          case 'list':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>';
            break;
          case 'package':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>';
            break;
          case 'tag':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>';
            break;
          case 'qr-code':
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>';
            break;
          default:
            $iconSvg = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>';
        }
      ?>
        <a href="<?= $item['href'] ?>" class="tap flex min-w-[4.5rem] flex-col items-center justify-center gap-0.5 rounded-2xl px-2 py-1.5 text-center text-[0.65rem] font-medium <?= $isActive ? 'text-pine' : 'text-muted' ?>">
          <?= $iconSvg ?>
          <span class="<?= !empty($item['is_plate']) ? 'plate-tab-label' : '' ?>"><?= htmlspecialchars($item['label']) ?></span>
          <?php if (!empty($item['is_plate']) && $plateCount > 0): ?>
            <span class="plate-count-badge absolute -top-1 -right-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-pine px-1 text-[0.6rem] font-bold text-invert"><?= $plateCount ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>

  <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-5">
    <?php if ($flash): ?>
      <div class="mb-4 rounded-2xl border border-line bg-card px-4 py-3 text-sm <?= $flash['tone'] === 'error' ? 'text-danger' : 'text-ink' ?>" role="alert">
        <?= htmlspecialchars($flash['text']) ?>
      </div>
    <?php endif; ?>
