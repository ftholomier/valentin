/* UI partagée : header, icônes, toasts, état de session. */

/* Icônes Lucide (chargées via CDN dans le <head>). */
function renderIcons() {
  if (window.lucide) window.lucide.createIcons();
}

/* Header transparent -> solide au scroll (uniquement si un hero est présent). */
function initHeaderScroll() {
  const header = document.getElementById('site-header');
  if (!header || header.classList.contains('solid-static')) return;
  const onScroll = () => header.classList.toggle('is-solid', window.scrollY > 60);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

/* Toast simple. */
function toast(message, isError = false) {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    document.body.appendChild(el);
  }
  el.textContent = message;
  el.className = isError ? 'err' : '';
  requestAnimationFrame(() => el.classList.add('show'));
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3200);
}

/* Met à jour la zone "Se connecter / Mon compte" du header selon la session. */
async function refreshAuthUI() {
  const loginEl = document.querySelector('[data-auth-login]');
  const accountEl = document.querySelector('[data-auth-account]');
  try {
    const user = await api.me();
    if (user) {
      if (loginEl) loginEl.classList.add('hidden');
      if (accountEl) {
        accountEl.classList.remove('hidden');
        const label = accountEl.querySelector('[data-auth-name]');
        if (label) label.textContent = user.nom.split(' ')[0];
        // Chaque rôle pointe vers son espace.
        if (user.role === 'partner') {
          accountEl.setAttribute('href', '/pro');
        } else if (user.role === 'admin') {
          accountEl.setAttribute('href', '/admin');
        }
      }
      window.__user = user;
      return user;
    }
  } catch (_) {}
  if (loginEl) loginEl.classList.remove('hidden');
  if (accountEl) accountEl.classList.add('hidden');
  window.__user = null;
  return null;
}

document.addEventListener('DOMContentLoaded', () => {
  renderIcons();
  initHeaderScroll();
  refreshAuthUI();
});

/* Déconnexion par délégation : fonctionne aussi sur les liens créés dynamiquement. */
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-logout]');
  if (!btn) return;
  e.preventDefault();
  await api.logout();
  location.href = '/';
});
