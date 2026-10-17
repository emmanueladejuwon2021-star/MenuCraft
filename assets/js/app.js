// assets/js/app.js - MenuCraft Client Interactivity (improved)

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

  // Toast Notification System with better dark mode support
  window.showToast = function(text, tone = 'ok', duration = 3000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${tone}`;
    toast.setAttribute('role', 'status');
    toast.textContent = text;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      setTimeout(() => toast.remove(), 250);
    }, duration);
  };

  // Helper to get CSRF
  function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  // Cart / Plate interactions via AJAX with loading state
  window.addToPlate = async function(dishId, dishName, btnElement) {
    if (btnElement) {
      btnElement.disabled = true;
      const originalText = btnElement.textContent;
      btnElement.textContent = 'Adding…';
      btnElement.dataset.originalText = originalText;
    }

    try {
      const csrfToken = getCsrf();
      const res = await fetch('plate.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: new URLSearchParams({ action: 'add', id: dishId, csrf_token: csrfToken })
      });

      if (!res.ok) {
        const text = await res.text();
        throw new Error(text || 'Server error');
      }

      const data = await res.json();
      if (data.ok) {
        showToast(`${dishName || 'Dish'} added to your plate`, 'ok');
        updatePlateBadges(data.count);
      } else {
        showToast(data.message || 'Could not add dish', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Network error. Please try again.', 'error');
    } finally {
      if (btnElement) {
        btnElement.disabled = false;
        btnElement.textContent = btnElement.dataset.originalText || 'Add to plate';
      }
    }
  };

  function updatePlateBadges(count) {
    const badges = document.querySelectorAll('.plate-count-badge');
    badges.forEach(b => {
      b.textContent = count > 0 ? count : '';
      b.classList.toggle('hidden', count <= 0);
    });

    const labels = document.querySelectorAll('.plate-tab-label');
    labels.forEach(l => {
      l.textContent = count > 0 ? `Plate (${count})` : 'Plate';
    });
  }

  // Stock switch toggle via AJAX — more resilient class handling
  window.toggleStock = async function(id, name, btn) {
    const isAvailable = btn.getAttribute('aria-pressed') === 'true';
    const nextStatus = !isAvailable;
    const originalPressed = isAvailable;
    const label = document.getElementById(`stock-label-${id}`);
    const originalLabelText = label ? label.textContent : '';
    const originalLabelClass = label ? label.className : '';

    // Optimistic UI update using classList for reliability
    btn.setAttribute('aria-pressed', nextStatus ? 'true' : 'false');
    const dot = btn.querySelector('.stock-dot');

    btn.classList.remove('border-pine', 'bg-pine', 'border-line', 'bg-card');
    if (nextStatus) {
      btn.classList.add('border-pine', 'bg-pine');
      if (dot) {
        dot.classList.remove('ml-0', 'bg-ink');
        dot.classList.add('ml-auto', 'bg-invert');
      }
      if (label) {
        label.textContent = 'In stock';
        label.className = 'text-xs font-semibold text-pine';
      }
    } else {
      btn.classList.add('border-line', 'bg-card');
      if (dot) {
        dot.classList.remove('ml-auto', 'bg-invert');
        dot.classList.add('ml-0', 'bg-ink');
      }
      if (label) {
        label.textContent = 'Sold out';
        label.className = 'text-xs font-semibold text-danger';
      }
    }

    try {
      const csrfToken = getCsrf();
      const res = await fetch('stock.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: new URLSearchParams({ id: id, is_available: nextStatus ? '1' : '0', csrf_token: csrfToken })
      });

      const data = await res.json().catch(() => ({ ok: false, message: 'Invalid response' }));
      if (data.ok) {
        showToast(nextStatus ? `Marked ${name} in stock` : `Marked ${name} sold out`, 'ok');
      } else {
        revertStock(btn, originalPressed, label, originalLabelText, originalLabelClass);
        showToast(data.message || 'Could not update stock', 'error');
      }
    } catch (err) {
      revertStock(btn, originalPressed, label, originalLabelText, originalLabelClass);
      showToast('Network error updating stock', 'error');
    }
  };

  function revertStock(btn, originalPressed, label, originalLabelText, originalLabelClass) {
    btn.setAttribute('aria-pressed', originalPressed ? 'true' : 'false');
    const isAvail = originalPressed;
    btn.className = 'tap relative flex h-11 w-[4.5rem] shrink-0 items-center rounded-full border px-1 ' +
      (isAvail ? 'border-pine bg-pine' : 'border-line bg-card');
    const dot = btn.querySelector('.stock-dot');
    if (dot) {
      dot.className = 'stock-dot block h-8 w-8 rounded-full shadow ' +
        (isAvail ? 'ml-auto bg-invert' : 'ml-0 bg-ink');
    }
    if (label) {
      label.textContent = originalLabelText;
      label.className = originalLabelClass;
    }
  }

  // Clipboard helper
  window.copyText = async function(text, successMsg) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
      } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
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

  // Prevent double-submit on forms
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.dataset.submitting === 'true') {
      e.preventDefault();
      return;
    }
    form.dataset.submitting = 'true';
    const btn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (btn && !btn.disabled) {
      btn.dataset.originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Sending…';
    }
  });
})();
