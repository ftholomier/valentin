/* Page d'accueil : offres du soir dynamiques + disciplines depuis l'API. */

const SPORT_ICONS = {
  Yoga: 'flower-2', CrossFit: 'dumbbell', Pilates: 'activity', Boxe: 'swords',
  Cycling: 'bike', HIIT: 'heart-pulse', Zumba: 'music', Aquagym: 'waves',
};

async function loadOffres() {
  const grid = document.getElementById('offres-grid');
  try {
    // Position du navigateur si dispo, sinon fallback serveur (Lyon).
    const slots = await api.slots('date=ce-soir');
    const top = slots.slice(0, 4);
    if (top.length === 0) {
      grid.innerHTML = '<div class="empty" style="grid-column:1/-1">Aucune offre pour ce soir. Revenez plus tard !</div>';
      return;
    }
    grid.innerHTML = top.map(slotCardHTML).join('');
    renderIcons();
    startCountdowns();

    const meta = document.getElementById('meta-studios');
    if (meta) meta.textContent = slots.length + ' cours ce soir';
  } catch (e) {
    grid.innerHTML = `<div class="empty" style="grid-column:1/-1">Impossible de charger les offres.<br><small>${escapeHtml(e.message)}</small></div>`;
  }
}

async function loadDisciplines() {
  const wrap = document.getElementById('pills');
  if (!wrap) return;
  try {
    const cfg = await api.config();
    const pills = cfg.sports.map((sport) => {
      const icon = SPORT_ICONS[sport] || 'circle';
      return `<a class="pill" href="/resultats?sport=${encodeURIComponent(sport)}">
        <span class="ic"><i data-lucide="${icon}"></i></span><span class="lbl">${escapeHtml(sport)}</span></a>`;
    }).join('');
    wrap.innerHTML = pills + `<a class="pill pill-dark link-arrow" href="/resultats">Toutes <i data-lucide="arrow-right"></i></a>`;
    renderIcons();
  } catch (_) {}
}

document.addEventListener('DOMContentLoaded', () => {
  loadOffres();
  loadDisciplines();
});
