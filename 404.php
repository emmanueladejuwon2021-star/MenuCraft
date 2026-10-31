<?php
// 404.php - Simple not found page
require_once __DIR__ . '/includes/header.php';
?>
<section class="page-enter flex flex-col items-center justify-center min-h-[60vh] text-center space-y-4">
  <span class="text-6xl">🍽️</span>
  <h1 class="text-3xl font-extrabold text-ink">Page not found</h1>
  <p class="text-muted max-w-md">The dish or page you requested is not on the menu.</p>
  <a href="index.php" class="tap mt-4 inline-flex items-center gap-2 rounded-full bg-pine px-6 py-3 text-sm font-bold text-white">
    Back to Home
  </a>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
