<?php
// includes/header.php - MenuCraft Global Header & Navigation
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

ensure_session();
$db = getDb();
$settings = get_restaurant_settings($db);
$plateCount = cart_count();
$flash = get_flash();
$currentPage = basename($_SERVER['PHP_SCRIPT'] ?? $_SERVER['SCRIPT_NAME'] ?? '');
$isStaffUser = is_staff();
$currentUser = get_current_user_session();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($settings['restaurant_name']) ?> — Digital Menu & Ordering</title>
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            pine: {
              DEFAULT: 'var(--color-pine)',
              hover: 'var(--color-pine-hover)',
              light: 'var(--color-pine-light)',
            },
            clay: 'var(--color-clay)',
            canvas: 'var(--bg-canvas)',
            card: 'var(--bg-card)',
            paper: 'var(--bg-paper)',
            line: 'var(--border-line)',
            ink: 'var(--text-ink)',
            muted: 'var(--text-muted)',
            invert: 'var(--color-invert)',
          },
          boxShadow: {
            soft: 'var(--shadow-soft)',
            card: 'var(--shadow-card)',
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="assets/css/app.css">
  <script src="assets/js/app.js" defer></script>
</head>
<body class="flex min-h-full flex-col bg-canvas text-ink antialiased selection:bg-pine selection:text-white">

  <!-- Main Navigation Header -->
  <header class="sticky top-0 z-40 border-b border-line bg-card/85 backdrop-blur-md transition-colors">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">

      <!-- Brand Logo / Name -->
      <a href="index.php" class="group flex items-center gap-3 font-bold text-ink">
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-pine text-white shadow-soft transition-transform group-hover:scale-105">
          <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
            <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.46 3.9 3.45 4.35L6 22h2l-.55-8.65C9.44 12.9 11 11.12 11 9zm7-7h-2c-1.66 0-3 1.34-3 3v6h2v11h2V2z"/>
          </svg>
        </div>
        <div>
          <span class="block text-lg font-bold leading-tight tracking-tight text-ink">
            <?= htmlspecialchars($settings['restaurant_name']) ?>
          </span>
          <span class="block text-[10px] font-medium uppercase tracking-wider text-muted">
            Live Digital Menu
          </span>
        </div>
      </a>

      <!-- Desktop Navigation Links -->
      <nav class="hidden items-center gap-1 md:flex">
        <a href="index.php" class="tap rounded-full px-4 py-2 text-sm font-semibold transition-colors <?= $currentPage === 'index.php' ? 'bg-paper text-pine' : 'text-muted hover:text-ink' ?>">
          Home
        </a>
        <a href="menu.php" class="tap rounded-full px-4 py-2 text-sm font-semibold transition-colors <?= $currentPage === 'menu.php' ? 'bg-paper text-pine' : 'text-muted hover:text-ink' ?>">
          Today’s Menu
        </a>
        <a href="plate.php" class="tap relative inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-colors <?= $currentPage === 'plate.php' ? 'bg-paper text-pine' : 'text-muted hover:text-ink' ?>">
          <span class="plate-tab-label"><?= $plateCount > 0 ? "Plate ($plateCount)" : "Plate" ?></span>
          <span class="plate-count-badge inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-pine px-1.5 text-xs font-bold text-white transition-all <?= $plateCount > 0 ? '' : 'hidden' ?>">
            <?= $plateCount ?>
          </span>
        </a>
        <a href="my-orders.php" class="tap rounded-full px-4 py-2 text-sm font-semibold transition-colors <?= $currentPage === 'my-orders.php' ? 'bg-paper text-pine' : 'text-muted hover:text-ink' ?>">
          Track Order
        </a>
      </nav>

      <!-- User / Role / Theme Actions -->
      <div class="flex items-center gap-2">
        <?php if ($isStaffUser): ?>
          <a href="orders.php" class="inline-flex items-center gap-1.5 rounded-full bg-pine/10 px-3 py-1.5 text-xs font-bold text-pine border border-pine/20">
            <span class="h-2 w-2 rounded-full bg-pine animate-pulse"></span>
            Kitchen Staff
          </a>
        <?php elseif (!empty($currentUser)): ?>
          <a href="guest-account.php" class="hidden text-xs font-semibold text-muted hover:text-ink sm:block">
            Hi, <?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?>
          </a>
        <?php else: ?>
          <a href="guest-account.php" class="hidden rounded-full border border-line bg-card px-3.5 py-1.5 text-xs font-semibold text-ink shadow-sm hover:bg-paper sm:block">
            Sign In
          </a>
        <?php endif; ?>

        <!-- Theme Switcher Toggle -->
        <button type="button" onclick="toggleTheme()" class="tap flex h-9 w-9 items-center justify-center rounded-full border border-line bg-card text-muted hover:bg-paper hover:text-ink" title="Toggle Theme" aria-label="Toggle dark/light mode">
          <!-- Sun Icon -->
          <svg class="hidden h-4 w-4 dark:block fill-current" viewBox="0 0 24 24">
            <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0s-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41s-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
          </svg>
          <!-- Moon Icon -->
          <svg class="block h-4 w-4 dark:hidden fill-current" viewBox="0 0 24 24">
            <path d="M12.3 2c.43 0 .77.35.73.78-.34 4.24 2.8 7.91 7.05 8.25.43.03.73.42.6.84-1.22 4.1-5 7.13-9.53 7.13-5.52 0-10-4.48-10-10 0-4.53 3.03-8.31 7.13-9.53.13-.04.27-.04.41-.04l3.61.57z"/>
          </svg>
        </button>
      </div>

    </div>
  </header>

  <?php if ($isStaffUser): ?>
    <?php require_once __DIR__ . '/kitchen_nav.php'; ?>
  <?php endif; ?>

  <!-- Flash Messages -->
  <?php if ($flash): ?>
    <div class="mx-auto max-w-5xl px-4 pt-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between rounded-2xl px-5 py-3.5 text-sm font-medium shadow-soft transition-all <?= $flash['tone'] === 'ok' ? 'bg-pine text-white' : 'bg-red-600 text-white' ?>">
        <div class="flex items-center gap-3">
          <svg class="h-5 w-5 shrink-0 fill-current" viewBox="0 0 24 24">
            <?php if ($flash['tone'] === 'ok'): ?>
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            <?php else: ?>
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            <?php endif; ?>
          </svg>
          <span><?= htmlspecialchars($flash['text']) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="opacity-75 hover:opacity-100">
          <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Main Content Body -->
  <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
