/* Composants d'affichage partagés (cartes de cours, compte à rebours). */

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) => (
    { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]
  ));
}

/* Minutes restantes avant le début d'un cours (>= 0). */
function minutesUntil(dateStr) {
  const start = new Date(dateStr.replace(' ', 'T')).getTime();
  return Math.max(0, Math.round((start - Date.now()) / 60000));
}

/* Formate une durée en minutes -> "1h05" / "45". */
function fmtCountdown(mins) {
  const h = Math.floor(mins / 60), m = mins % 60;
  return (h > 0 ? h + 'h' : '') + String(m).padStart(2, '0');
}

/* Carte de cours façon page d'accueil (grille 4 colonnes). */
function slotCardHTML(s) {
  const soldout = s.places_restantes <= 0 || s.statut === 'complet';
  const mins = minutesUntil(s.date_debut);
  const dist = s.distance_km != null ? ' · ' + fmt.distance(s.distance_km) : '';
  return `
    <a class="card" href="/cours?id=${s.id}">
      <div class="card-media">
        <img src="${escapeHtml(s.image_url)}" alt="${escapeHtml(s.titre)}" loading="lazy" />
        <span class="card-badge tnum">-${s.reduction}%</span>
        ${soldout ? '' : `<span class="card-timer"><i data-lucide="timer"></i><span class="tnum" data-countdown="${mins}">${fmtCountdown(mins)}</span></span>`}
        ${soldout ? '<span class="card-soldout">Complet</span>' : ''}
      </div>
      <div class="card-body">
        <div class="card-meta">
          <span style="font-weight:500">${escapeHtml(s.sport)} · ${fmt.heure(s.date_debut)}${dist}</span>
          <span class="rating"><i data-lucide="star"></i>${s.note.toFixed(1)}</span>
        </div>
        <h3 class="card-title">${escapeHtml(s.titre)} — ${escapeHtml(s.salle)}</h3>
        <div class="card-foot">
          <div class="card-price"><span class="now tnum">${fmt.euro(s.prix_reduit)}</span><span class="was tnum">${fmt.euro(s.prix_initial)}</span></div>
          <span class="card-cta"><i data-lucide="arrow-right"></i></span>
        </div>
      </div>
    </a>`;
}

/* Ligne de résultat (page recherche). */
function slotRowHTML(s) {
  const soldout = s.places_restantes <= 0 || s.statut === 'complet';
  const dist = s.distance_km != null ? fmt.distance(s.distance_km) : '';
  return `
    <a class="result-row" href="/cours?id=${s.id}">
      <div class="rr-media">
        <img src="${escapeHtml(s.image_url)}" alt="" loading="lazy" />
        <span class="card-badge tnum">-${s.reduction}%</span>
      </div>
      <div class="rr-body">
        <div class="rr-title">${escapeHtml(s.titre)}</div>
        <div class="rr-meta">${escapeHtml(s.salle)} · ${escapeHtml(s.sport)} · ${fmt.heure(s.date_debut)}${dist ? ' · ' + dist : ''}</div>
        <div class="rr-meta">${soldout ? '<span style="color:#a5281c">Complet</span>' : s.places_restantes + ' place' + (s.places_restantes > 1 ? 's' : '') + ' restante' + (s.places_restantes > 1 ? 's' : '')}</div>
        <div class="rr-foot">
          <div class="card-price"><span class="now tnum" style="font-size:18px">${fmt.euro(s.prix_reduit)}</span><span class="was tnum">${fmt.euro(s.prix_initial)}</span></div>
          <span class="link-arrow uplabel">Voir <i data-lucide="arrow-right"></i></span>
        </div>
      </div>
    </a>`;
}

/* Anime tous les compte-à-rebours [data-countdown] présents dans le DOM. */
function startCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach((el) => {
    let mins = parseInt(el.dataset.countdown, 10);
    let secs = mins * 60;
    const tick = () => {
      if (secs <= 0) { el.textContent = 'Fini'; return; }
      el.textContent = fmtCountdown(Math.floor(secs / 60));
      secs--;
      el._t = setTimeout(tick, 1000);
    };
    tick();
  });
}
