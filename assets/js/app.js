// MenuCraft Client Interactions

(function () {
  'use strict';

  // ---------- Theme ----------
  function applyTheme(theme) {
    const html = document.documentElement;
    if (theme === 'dark') {
      html.classList.add('dark');
    } else {
      html.classList.remove('dark');
    }
    try {
      localStorage.setItem('menucraft-theme', theme);
    } catch (e) {}
  }

  function initTheme() {
    let theme = 'light';
    try {
      theme = localStorage.getItem('menucraft-theme') || 
              (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    } catch (e) {}
    applyTheme(theme);
  }

  window.toggleTheme = function () {
    const isDark = document.documentElement.classList.contains('dark');
    applyTheme(isDark ? 'light' : 'dark');
  };

  // ---------- CSRF ----------
  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // ---------- Toast ----------
  function showToast(message, tone = 'ok') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${tone}`;
    toast.setAttribute('role', 'status');
    toast.innerHTML = `
      <span class="shrink-0">${tone === 'ok' ? '\u2713' : '!'}</span>
      <span class="flex-1">${message}</span>
      <button type="button" class="shrink-0 opacity-70 hover:opacity-100" aria-label="Dismiss">\u00d7</button>
    `;

    const dismiss = () => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(4px)';
      setTimeout(() => toast.remove(), 200);
    };

    toast.querySelector('button').addEventListener('click', dismiss);
    container.appendChild(toast);

    setTimeout(dismiss, 4200);
  }

  window.showToast = showToast;

  // ---------- Plate count badge ----------
  function updatePlateBadge(count) {
    const badges = document.querySelectorAll('.plate-count-badge');
    const labels = document.querySelectorAll('.plate-tab-label');
    badges.forEach(badge => {
      if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    });
    labels.forEach(label => {
      label.textContent = count > 0 ? `Plate (${count})` : 'Plate';
    });
  }

  // ---------- Add to plate ----------
  window.addToPlate = async function (id, name, button) {
    if (!button) return;
    const originalHtml = button.innerHTML;
    button.classList.add('btn-loading');
    button.disabled = true;

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('id', id);
    formData.append('csrf_token', getCsrfToken());

    try {
      const res = await fetch('plate.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
      });

      const data = await res.json().catch(() => ({}));

      if (res.ok && data.ok) {
        updatePlateBadge(data.count || 0);
        showToast(`${name} added to your plate`, 'ok');
        button.innerHTML = '<span>\u2713 Added</span>';
        setTimeout(() => {
          button.innerHTML = originalHtml;
          button.classList.remove('btn-loading');
          button.disabled = false;
        }, 1400);
      } else {
        showToast(data.message || 'Could not add dish. Try again.', 'error');
        button.classList.remove('btn-loading');
        button.disabled = false;
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'error');
      button.classList.remove('btn-loading');
      button.disabled = false;
    }
  };

  // ---------- Stock toggle ----------
  window.toggleStock = async function (id, name, button) {
    if (!button) return;
    const isCurrentlyOn = button.getAttribute('data-available') === '1' || button.classList.contains('bg-pine');
    const newAvailable = isCurrentlyOn ? 0 : 1;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('is_available', newAvailable);
    formData.append('csrf_token', getCsrfToken());

    button.classList.add('btn-loading');
    button.disabled = true;

    try {
      const res = await fetch('stock.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
      });

      const data = await res.json().catch(() => ({}));

      if (res.ok && data.ok) {
        showToast(data.message || (newAvailable ? `${name} marked in stock` : `${name} marked sold out`), 'ok');
        setTimeout(() => location.reload(), 600);
      } else {
        showToast(data.message || 'Could not update stock.', 'error');
        button.classList.remove('btn-loading');
        button.disabled = false;
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'error');
      button.classList.remove('btn-loading');
      button.disabled = false;
    }
  };

  // ---------- Copy text ----------
  window.copyText = async function (text, message) {
    try {
      await navigator.clipboard.writeText(text);
      showToast(message || 'Copied to clipboard', 'ok');
    } catch (err) {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        showToast(message || 'Copied to clipboard', 'ok');
      } catch (e) {
        showToast('Could not copy. Please select and copy manually.', 'error');
      }
      document.body.removeChild(ta);
    }
  };

  // ---------- Download QR (fallback if not defined on page) ----------
  if (typeof window.downloadQr === 'undefined') {
    window.downloadQr = function () {
      const canvas = document.querySelector('#qrcode-container canvas');
      if (!canvas) {
        showToast('QR code not ready yet.', 'error');
        return;
      }
      const link = document.createElement('a');
      link.download = 'menucraft-qr.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
      showToast('QR code downloaded', 'ok');
    };
  }

  // ---------- Init ----------
  document.addEventListener('DOMContentLoaded', initTheme);

  if (document.readyState !== 'loading') {
    initTheme();
  }
})();
