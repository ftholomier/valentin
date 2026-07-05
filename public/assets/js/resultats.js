/* Page recherche : chargement API + filtres/tri 100% côté client. */

let ALL_SLOTS = [];   // jeu complet renvoyé par l'API
let USER_POS = null;  // {lat, lng} si géoloc autorisée

function applyFilters() {
  const ville = document.getElementById('f-ville').value;
  const sport = document.getElementById('f-sport').value;
  const date  = document.getElementById('f-date').value;
  const prix  = parseFloat(document.getElementById('f-prix').value);
  const tri   = document.getElementById('f-tri').value;
  const dispo = document.getElementById('f-dispo').checked;

  let list = ALL_SLOTS.slice();

  if (ville) list = list.filter((s) => s.ville === ville);
  if (sport) list = list.filter((s) => s.sport === sport);
  if (!isNaN(prix)) list = list.filter((s) => s.prix_reduit <= prix);
  if (dispo) list = list.filter((s) => s.places_restantes > 0 && s.statut !== 'complet');
  if (date) list = filterByDate(list, date);

  list.sort((a, b) => {
    switch (tri) {
      case 'prix':   return a.prix_reduit - b.prix_reduit;
      case 'places': return b.places_restantes - a.places_restantes;
      case 'heure':  return a.date_debut.localeCompare(b.date_debut);
      default:       return (a.distance_km ?? 1e9) - (b.distance_km ?? 1e9);
    }
  });

  renderResults(list);
}

function filterByDate(list, key) {
  const now = new Date();
  const sameDay = (d, ref) => {
    const x = new Date(d.replace(' ', 'T'));
    return x.getFullYear() === ref.getFullYear() && x.getMonth() === ref.getMonth() && x.getDate() === ref.getDate();
  };
  if (key === 'ce-soir') return list.filter((s) => sameDay(s.date_debut, now));
  if (key === 'demain') { const t = new Date(now); t.setDate(t.getDate() + 1); return list.filter((s) => sameDay(s.date_debut, t)); }
  if (key === 'week-end') return list.filter((s) => { const d = new Date(s.date_debut.replace(' ', 'T')).getDay(); return d === 0 || d === 6; });
  return list;
}

function renderResults(list) {
  const wrap = document.getElementById('results-list');
  const count = document.getElementById('results-count');
  count.textContent = list.length + ' résultat' + (list.length > 1 ? 's' : '');
  if (list.length === 0) {
    wrap.innerHTML = '<div class="empty">Aucun cours ne correspond à ces critères.</div>';
    return;
  }
  wrap.innerHTML = list.map(slotRowHTML).join('');
  renderIcons();
}

async function boot() {
  // Pré-remplissage des filtres depuis l'URL (venant de la barre d'accueil).
  const pVille = qsParam('ville'), pSport = qsParam('sport'), pDate = qsParam('date');

  // Config -> options des selects
  try {
    const cfg = await api.config();
    const vSel = document.getElementById('f-ville');
    cfg.villes.forEach((v) => vSel.add(new Option(v, v)));
    const sSel = document.getElementById('f-sport');
    cfg.sports.forEach((s) => sSel.add(new Option(s, s)));
  } catch (_) {}

  if (pVille) document.getElementById('f-ville').value = matchOption('f-ville', pVille);
  if (pSport) document.getElementById('f-sport').value = matchOption('f-sport', pSport);
  if (pDate)  document.getElementById('f-date').value = pDate;

  // Géolocalisation (facultative) pour un tri par distance pertinent.
  await tryGeolocate();

  // Chargement des cours
  await reload();

  // Écouteurs de filtres
  ['f-ville', 'f-sport', 'f-date', 'f-prix', 'f-tri', 'f-dispo'].forEach((id) => {
    document.getElementById(id).addEventListener('input', applyFilters);
  });
}

/* Retrouve la valeur d'option correspondant (insensible à la casse). */
function matchOption(selectId, value) {
  const opts = [...document.getElementById(selectId).options];
  const found = opts.find((o) => o.value.toLowerCase() === value.toLowerCase());
  return found ? found.value : '';
}

function tryGeolocate() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) return resolve();
    navigator.geolocation.getCurrentPosition(
      (pos) => { USER_POS = { lat: pos.coords.latitude, lng: pos.coords.longitude }; resolve(); },
      () => resolve(),
      { timeout: 4000 }
    );
  });
}

async function reload() {
  let qs = '';
  if (USER_POS) qs = `lat=${USER_POS.lat}&lng=${USER_POS.lng}`;
  ALL_SLOTS = await api.slots(qs);
  applyFilters();
}

document.addEventListener('DOMContentLoaded', boot);
