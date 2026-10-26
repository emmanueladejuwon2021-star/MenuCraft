  </main>

  <!-- Mobile Bottom Sticky Navigation Bar -->
  <nav class="sticky bottom-0 z-40 border-t border-line bg-card/95 py-2 backdrop-blur-lg md:hidden">
    <div class="mx-auto flex max-w-md items-center justify-around px-2">

      <a href="index.php" class="flex flex-col items-center gap-1 text-center transition-colors <?= $currentPage === 'index.php' ? 'text-pine' : 'text-muted' ?>">
        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <span class="text-[11px] font-semibold">Home</span>
      </a>

      <a href="menu.php" class="flex flex-col items-center gap-1 text-center transition-colors <?= $currentPage === 'menu.php' ? 'text-pine' : 'text-muted' ?>">
        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        <span class="text-[11px] font-semibold">Menu</span>
      </a>

      <a href="plate.php" class="relative flex flex-col items-center gap-1 text-center transition-colors <?= $currentPage === 'plate.php' ? 'text-pine' : 'text-muted' ?>">
        <div class="relative">
          <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M18.06 23h1.66c.84 0 1.53-.65 1.63-1.47L23 5.05h-5V1h-2v4.05h-4V1h-2v4.05H6V1H4v4.05H1l1.65 16.48c.1.82.79 1.47 1.63 1.47h1.66c.52 0 1.01-.25 1.32-.68L9.04 19h5.92l1.78 3.32c.31.43.8.68 1.32.68zM3.19 7.05h17.62l-1.22 12.2h-1.24l-1.52-2.83c-.31-.43-.8-.68-1.32-.68H8.49c-.52 0-1.01.25-1.32.68L5.65 19.25H4.41L3.19 7.05z"/></svg>
          <span class="plate-count-badge absolute -right-2 -top-1.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-pine px-1 text-[10px] font-bold text-white transition-all <?= $plateCount > 0 ? '' : 'hidden' ?>">
            <?= $plateCount ?>
          </span>
        </div>
        <span class="text-[11px] font-semibold">Plate</span>
      </a>

      <a href="my-orders.php" class="flex flex-col items-center gap-1 text-center transition-colors <?= $currentPage === 'my-orders.php' ? 'text-pine' : 'text-muted' ?>">
        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        <span class="text-[11px] font-semibold">Track</span>
      </a>

      <?php if ($isStaffUser): ?>
        <a href="orders.php" class="flex flex-col items-center gap-1 text-center transition-colors <?= str_contains($currentPage, 'orders') ? 'text-pine' : 'text-muted' ?>">
          <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M8.1 11.7c.07-.01.14-.02.21-.02H18c.55 0 1 .45 1 1s-.45 1-1 1H8.31c-.07 0-.14-.01-.21-.02l-2.1-7.35H2V4.32h3.1l2.1 7.38zM12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"/></svg>
          <span class="text-[11px] font-semibold">Kitchen</span>
        </a>
      <?php else: ?>
        <a href="guest-account.php" class="flex flex-col items-center gap-1 text-center transition-colors <?= $currentPage === 'guest-account.php' ? 'text-pine' : 'text-muted' ?>">
          <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
          <span class="text-[11px] font-semibold">Account</span>
        </a>
      <?php endif; ?>

    </div>
  </nav>

  <!-- Desktop Global Footer -->
  <footer class="mt-auto border-t border-line bg-card py-8 text-sm text-muted">
    <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6 lg:px-8">
      <div class="flex items-center gap-2 font-semibold text-ink">
        <span class="inline-block h-2 w-2 rounded-full bg-pine"></span>
        <?= htmlspecialchars($settings['restaurant_name']) ?>
      </div>
      <p class="text-xs text-muted">
        &copy; <?= date('Y') ?> Powered by <span class="font-semibold text-ink">MenuCraft</span>. Fresh food, zero wait.
      </p>
      <div class="flex items-center gap-4 text-xs font-semibold">
        <?php if ($isStaffUser): ?>
          <a href="iyabisi/kitchenlock/index.php" class="text-muted hover:text-pine transition-colors">
            Kitchen Staff Entrance &rarr;
          </a>
        <?php endif; ?>
      </div>
    </div>
  </footer>

  <!-- Toast Notification Container -->
  <div id="toast-container" aria-live="polite"></div>

</body>
</html>
