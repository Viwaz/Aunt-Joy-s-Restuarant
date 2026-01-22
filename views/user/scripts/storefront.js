// Shared storefront JS (runs on menu/cart/checkout/orders)
async function updateStoreCartCount() {
  const el = document.getElementById('cart-count');
  if (!el) return;

  try {
    const res = await fetch('../../../api/cart_count.php', { credentials: 'same-origin' });
    const data = await res.json();
    if (data && data.success) {
      el.textContent = String(data.count ?? 0);
    } else {
      // If not logged in or unauthorized, keep badge at 0
      el.textContent = '0';
    }
  } catch (_) {
    // ignore
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateStoreCartCount();

  // Wire login modal submit (if present)
  const form = document.getElementById('login-modal-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      await submitLoginModal(form);
    });
  }

  // Checkout submit should require login (modal) if user is guest
  const checkoutForm = document.getElementById('checkout-form');
  if (checkoutForm && checkoutForm.dataset.loggedIn === '0') {
    checkoutForm.addEventListener('submit', (e) => {
      e.preventDefault();
      window.__checkoutPendingSubmit = true;
      openLoginModal();
    });
  }
});

function openLoginModal() {
  const overlay = document.getElementById('login-modal');
  if (!overlay) return;
  overlay.style.display = 'flex';
  const err = document.getElementById('login-modal-error');
  if (err) err.style.display = 'none';
  const email = overlay.querySelector('input[name="email"]');
  if (email) email.focus();
}

function closeLoginModal() {
  const overlay = document.getElementById('login-modal');
  if (!overlay) return;
  overlay.style.display = 'none';
}

async function submitLoginModal(form) {
  const err = document.getElementById('login-modal-error');
  const email = form.querySelector('input[name="email"]')?.value?.trim() || '';
  const password = form.querySelector('input[name="password"]')?.value || '';

  try {
    const res = await fetch('../../../api/auth_api.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      if (err) {
        err.textContent = data.message || 'Login failed';
        err.style.display = 'block';
      }
      return;
    }

    closeLoginModal();
    await updateStoreCartCount();

    // If checkout was waiting, reload so PHP can auto-fill fields and allow order creation.
    if (window.__checkoutPendingSubmit) {
      window.__checkoutPendingSubmit = false;
      window.location.reload();
    } else {
      // Refresh header state (sign in -> logout) by reloading
      window.location.reload();
    }
  } catch (e) {
    if (err) {
      err.textContent = 'Login failed. Please try again.';
      err.style.display = 'block';
    }
  }
}

