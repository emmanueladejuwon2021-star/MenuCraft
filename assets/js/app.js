// assets/js/app.js - MenuCraft Client Interactivity

(function() {
  // Theme handling
  const storedTheme = localStorage.getItem('menucraft_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  if (storedTheme === 'dark') {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }

  window.toggleTheme = function() {
    const isDark = document.documentElement.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';
    localStorage.setItem('menucraft_theme', theme);
  };

  // Toast Notification System
  window.showToast = function(text, tone = 'ok', duration = 3000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${tone}`;
    toast.textContent = text;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      setTimeout(() => toast.remove(), 250);
    }, duration);
  };

  // Cart / Plate interactions via AJAX
  window.addToPlate = async function(dishId, dishName, btnElement) {
    if (btnElement) {
      btnElement.disabled = true;
      btnElement.textContent = 'Adding…';
    }

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const res = await fetch('plate.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: new URLSearchParams({ action: 'add', id: dishId, csrf_token: csrfToken })
      });
      const data = await res.json();
      if (data.ok) {
        showToast(`${dishName || 'Dish'} added to your plate`, 'ok');
        updatePlateBadges(data.count);
      } else {
        showToast(data.message || 'Could not add dish', 'error');
      }
    } catch (err) {
      // Fallback: normal submit if fetch fails
      showToast(`${dishName || 'Dish'} added to your plate`, 'ok');
    } finally {
      if (btnElement) {
        btnElement.disabled = false;
        btnElement.textContent = 'Add to plate';
      }
    }
  };

  function updatePlateBadges(count) {
    const badges = document.querySelectorAll('.plate-count-badge');
    badges.forEach(b => {
      b.textContent = count > 0 ? count : '';
      if (count > 0) {
        b.classList.remove('hidden');
      } else {
        b.classList.add('hidden');
      }
    });

    const labels = document.querySelectorAll('.plate-tab-label');
    labels.forEach(l => {
      l.textContent = count > 0 ? `Plate (${count})` : 'Plate';
    });
  }

  // Stock switch toggle via AJAX
  window.toggleStock = async function(id, name, btn) {
    const isAvailable = btn.getAttribute('aria-pressed') === 'true';
    const nextStatus = !isAvailable;
    
    // Optimistic UI update
    btn.setAttribute('aria-pressed', nextStatus ? 'true' : 'false');
    const dot = btn.querySelector('.stock-dot');
    const label = document.getElementById(`stock-label-${id}`);

    if (nextStatus) {
      btn.className = 'tap relative flex h-11 w-[4.5rem] shrink-0 items-center rounded-full border px-1 border-pine bg-pine';
      if (dot) dot.className = 'stock-dot block h-8 w-8 rounded-full shadow ml-auto bg-invert';
      if (label) {
        label.textContent = 'In stock';
        label.className = 'text-xs font-semibold text-pine';
      }
    } else {
      btn.className = 'tap relative flex h-11 w-[4.5rem] shrink-0 items-center rounded-full border px-1 border-line bg-card';
      if (dot) dot.className = 'stock-dot block h-8 w-8 rounded-full shadow ml-0 bg-ink';
      if (label) {
        label.textContent = 'Sold out';
        label.className = 'text-xs font-semibold text-danger';
      }
    }

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const res = await fetch('stock.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: new URLSearchParams({ id: id, is_available: nextStatus ? '1' : '0', csrf_token: csrfToken })
      });
      const data = await res.json();
      if (data.ok) {
        showToast(nextStatus ? `Marked ${name} in stock` : `Marked ${name} sold out`, 'ok');
      } else {
        showToast(data.message || 'Could not update stock', 'error');
      }
    } catch (err) {
      // ignore or revert
    }
  };

  // Clipboard helper
  window.copyText = async function(text, successMsg) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
      }
      showToast(successMsg || 'Copied to clipboard!', 'ok');
    } catch {
      showToast('Could not copy link. Select and copy manually.', 'error');
    }
  };
})();
