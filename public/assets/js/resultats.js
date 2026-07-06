/* Page Offres : chargement API + filtres fluides (chips, pills, tri) 100% client. */

const SPORT_ICONS = {
  Yoga: 'flower-2', CrossFit: 'dumbbell', Pilates: 'activity', Boxe: 'swords',
  Cycling: 'bike', HIIT: 'heart-pulse', Zumba: 'music', Aquagym: 'waves',
};

let ALL_SLOTS = [];
let USER_POS  = null;
const FILTER = { sport: '', date: '', ville: '', tri: 'distance', dispo: false };

function applyFilters() {
  let list = ALL_SLOTS.slice();

  if (FILTER.sport) list = list.filter((s) => s.sport === FILTER.sport);
  if (FILTER.ville) list = list.filter((s) => s.ville === FILTER.ville);
  if (FILTER.dispo) list = list.filter((s) => s.places_restantes > 0 && s.statut !== 'complet');
  if (FILTER.date)  list = filterByDate(list, FILTER.date);

  list.sort((a, b) => {
    switch (FILTER.tri) {
      case 'prix':      return a.prix_reduit - b.prix_reduit;
      case 'reduction': return b.reduction - a.reduction;
      case 'places':    return b.places_restantes - a.places_restantes;
      case 'heure':     return a.date_debut.localeCompare(b.date_debut);
      default:          return (a.distance_km ?? 1e9) - (b.distance_km ?? 1e9);
    }
  });

  renderGrid(list);
}

function filterByDate(list, key) {
  const now = new Date();
  const sameDay = (d, ref) => {
    const x = new Date(d.replace(' ', 'T'));
    return x.getFullYear() === ref.getFullYear() && x.getMonth() === ref.getMonth() && x.getDate() === ref.getDate();
  };
  if (key === 'ce-soir' || key === 'aujourdhui') return list.filter((s) => sameDay(s.date_debut, now));
  if (key === 'demain') { const t = new Date(now); t.setDate(t.getDate() + 1); return list.filter((s) => sameDay(s.date_debut, t)); }
  if (key === 'week-end') return list.filter((s) => { const d = new Date(s.date_debut.replace(' ', 'T')).getDay(); return d === 0 || d === 6; });
  return list;
}

function renderGrid(list) {
  const grid = document.getElementById('grid-view');
  const count = document.getElementById('results-count');
  count.textContent = list.length + ' cours' + (list.length > 1 ? ' disponibles' : ' disponible');

  if (list.length === 0) {
    grid.classList.remove('offers-grid');
    grid.innerHTML = `<div class="empty" style="grid-column:1/-1">Aucun cours ne correspond à ces critères.<br>
      <button class="link-arrow" style="justify-content:center;margin-top:16px;color:var(--ink);background:none;cursor:pointer" onclick="resetFilters()">Réinitialiser les filtres <i data-lucide="rotate-ccw"></i></button></div>`;
    renderIcons();
    return;
  }
  grid.classList.add('offers-grid');
  grid.innerHTML = list.map(offerCardHTML).join('');
  renderIcons();
  startCountdowns();
}

function showSkeletons(n = 6) {
  const grid = document.getElementById('grid-view');
  grid.classList.add('offers-grid');
  grid.innerHTML = Array.from({ length: n }, skeletonCardHTML).join('');
}

function resetFilters() {
  FILTER.sport = ''; FILTER.date = ''; FILTER.ville = ''; FILTER.tri = 'distance'; FILTER.dispo = false;
  document.getElementById('f-ville').value = '';
  document.getElementById('f-tri').value = 'distance';
  document.getElementById('f-dispo').checked = false;
  syncChips(); syncPills();
  applyFilters();
}

/* ---------- Construction des filtres ---------- */
function buildSportChips(sports) {
  const wrap = document.getElementById('sport-chips');
  const chip = (val, label, icon) =>
    `<button type="button" class="chip-filter${FILTER.sport === val ? ' is-active' : ''}" data-sport="${escapeHtml(val)}">
       ${icon ? `<i data-lucide="${icon}"></i>` : ''}${escapeHtml(label)}</button>`;
  wrap.innerHTML = chip('', 'Toutes', 'layers') +
    sports.map((sp) => chip(sp, sp, SPORT_ICONS[sp] || 'circle')).join('');
  wrap.querySelectorAll('.chip-filter').forEach((b) => {
    b.addEventListener('click', () => { FILTER.sport = b.dataset.sport; syncChips(); applyFilters(); });
  });
  renderIcons();
}
function syncChips() {
  document.querySelectorAll('#sport-chips .chip-filter').forEach((b) =>
    b.classList.toggle('is-active', b.dataset.sport === FILTER.sport));
}
function syncPills() {
  document.querySelectorAll('#date-pills .pill-toggle').forEach((b) =>
    b.classList.toggle('is-active', b.dataset.date === FILTER.date));
}

async function boot() {
  showSkeletons();

  const pSport = qsParam('sport'), pVille = qsParam('ville'), pDate = qsParam('date');
  if (pDate) FILTER.date = pDate === 'aujourdhui' ? 'ce-soir' : pDate;

  // Config → chips sports + villes
  try {
    const cfg = await api.config();
    if (pSport) {
      const match = cfg.sports.find((s) => s.toLowerCase() === pSport.toLowerCase());
      if (match) FILTER.sport = match;
    }
    buildSportChips(cfg.sports);
    const vSel = document.getElementById('f-ville');
    cfg.villes.forEach((v) => vSel.add(new Option(v, v)));
    if (pVille) {
      const mv = cfg.villes.find((v) => v.toLowerCase() === pVille.toLowerCase());
      if (mv) { FILTER.ville = mv; vSel.value = mv; }
    }
  } catch (_) {}

  syncPills();
  await tryGeolocate();
  await reload();

  // Écouteurs
  document.getElementById('f-ville').addEventListener('change', (e) => { FILTER.ville = e.target.value; applyFilters(); });
  document.getElementById('f-tri').addEventListener('change', (e) => { FILTER.tri = e.target.value; applyFilters(); });
  document.getElementById('f-dispo').addEventListener('change', (e) => { FILTER.dispo = e.target.checked; applyFilters(); });
  document.querySelectorAll('#date-pills .pill-toggle').forEach((b) => {
    b.addEventListener('click', () => { FILTER.date = b.dataset.date; syncPills(); applyFilters(); });
  });

  // Bascule Grille / Carte
  const gridV = document.getElementById('grid-view'), mapV = document.getElementById('map-view');
  const bGrid = document.getElementById('view-grid'), bMap = document.getElementById('view-map');
  bGrid.addEventListener('click', () => {
    gridV.classList.remove('hidden'); mapV.classList.add('hidden');
    bGrid.classList.add('is-active'); bMap.classList.remove('is-active');
  });
  bMap.addEventListener('click', () => {
    gridV.classList.add('hidden'); mapV.classList.remove('hidden');
    bMap.classList.add('is-active'); bGrid.classList.remove('is-active');
  });
}

function tryGeolocate() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) return resolve();
    navigator.geolocation.getCurrentPosition(
      (pos) => { USER_POS = { lat: pos.coords.latitude, lng: pos.coords.longitude }; resolve(); },
      () => resolve(), { timeout: 4000 }
    );
  });
}

async function reload() {
  try {
    ALL_SLOTS = await api.slots(USER_POS ? `lat=${USER_POS.lat}&lng=${USER_POS.lng}` : '');
    applyFilters();
  } catch (e) {
    document.getElementById('grid-view').innerHTML =
      `<div class="empty" style="grid-column:1/-1">Impossible de charger les offres.<br><small>${escapeHtml(e.message)}</small></div>`;
  }
}

document.addEventListener('DOMContentLoaded', boot);
