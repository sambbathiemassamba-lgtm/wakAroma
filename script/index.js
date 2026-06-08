// ── Badge panier ──────────────────────────────────────────────
function wrapCartIcon() {
  // Cherche le lien vers panier.php dans le header
  const cartLink = document.querySelector('a[href*="panier"]');
  if (!cartLink) return;
  // Évite de wrapper deux fois
  if (cartLink.querySelector('#cart-badge-count')) return;

  cartLink.style.position = 'relative';
  cartLink.style.display  = 'inline-flex';
  cartLink.style.alignItems = 'center';

  const badge = document.createElement('span');
  badge.id = 'cart-badge-count';
  badge.textContent = '0';
  cartLink.appendChild(badge);
}

function updateCartBadge(nb, animate = false) {
  const badge = document.getElementById('cart-badge-count');
  if (!badge) return;
  const n = parseInt(nb) || 0;
  badge.textContent = n > 99 ? '99+' : n;
  if (n > 0) {
    badge.classList.add('visible');
  } else {
    badge.classList.remove('visible');
  }
  if (animate && n > 0) {
    badge.classList.remove('bump');
    void badge.offsetWidth; // force reflow
    badge.classList.add('bump');
    setTimeout(() => badge.classList.remove('bump'), 300);
  }
}
