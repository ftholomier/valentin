/* Connexion & inscription — gère les deux formulaires selon la page. */

function showAlert(msg, type = 'error') {
  const box = document.getElementById('form-alert');
  if (box) box.innerHTML = `<div class="alert alert-${type}">${escapeHtmlLite(msg)}</div>`;
}
function escapeHtmlLite(s) {
  return String(s).replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
}
function clearFieldErrors() {
  document.querySelectorAll('[data-err]').forEach((el) => { el.classList.add('hidden'); el.textContent = ''; });
}
function redirectTarget(user) {
  const r = new URLSearchParams(location.search).get('redirect');
  if (r) return r;
  // Redirection par rôle : les salles vont vers l'espace pro.
  if (user && user.role === 'partner') return '/pro';
  return '/mon-compte';
}

function initLogin() {
  const form = document.getElementById('login-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Connexion…';
    try {
      const user = await api.login({
        email: form.email.value.trim(),
        password: form.password.value,
      });
      location.href = redirectTarget(user);
    } catch (err) {
      showAlert(err.message || 'Connexion impossible.');
      btn.disabled = false; btn.textContent = 'Se connecter';
    }
  });
}

function initRegister() {
  const form = document.getElementById('register-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearFieldErrors();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Création…';
    try {
      await api.register({
        nom: form.nom.value.trim(),
        email: form.email.value.trim(),
        ville: form.ville.value.trim(),
        password: form.password.value,
      });
      location.href = redirectTarget();
    } catch (err) {
      if (err.errors) {
        Object.entries(err.errors).forEach(([field, msg]) => {
          const el = document.querySelector(`[data-err="${field}"]`);
          if (el) { el.textContent = msg; el.classList.remove('hidden'); }
        });
      }
      showAlert(err.message || 'Inscription impossible.');
      btn.disabled = false; btn.textContent = 'Créer mon compte';
    }
  });
}

/* Si déjà connecté, on saute directement vers la cible. */
async function redirectIfLogged() {
  try {
    const u = await api.me();
    if (u) location.href = redirectTarget(u);
  } catch (_) {}
}

document.addEventListener('DOMContentLoaded', () => {
  initLogin();
  initRegister();
  redirectIfLogged();
});
