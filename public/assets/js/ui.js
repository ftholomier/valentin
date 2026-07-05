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

  const logoutBtn = document.querySelector('[data-logout]');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await api.logout();
      toast('Déconnecté.');
      setTimeout(() => location.href = '/', 500);
    });
  }
});
