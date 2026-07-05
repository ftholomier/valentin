/* Fiche cours : détail, compte à rebours avant le début, réservation. */

async function loadCourse() {
  const root = document.getElementById('course-root');
  const id = qsParam('id');
  if (!id) { root.innerHTML = '<div class="empty">Cours non précisé.</div>'; return; }

  let s;
  try {
    s = await api.slot(id);
  } catch (e) {
    root.innerHTML = `<div class="empty">${escapeHtml(e.message)}</div>`;
    return;
  }

  const soldout = s.places_restantes <= 0 || s.statut === 'complet';
  const dist = s.distance_km != null ? fmt.distance(s.distance_km) : '';
  const equip = (s.equipements || []).map((e) => `<span class="tag">${escapeHtml(e)}</span>`).join('');

  root.innerHTML = `
    <a href="/resultats" class="link-arrow uplabel muted" style="margin-bottom:20px"><i data-lucide="arrow-left"></i> Retour aux résultats</a>

    <div class="detail-hero">
      <img src="${escapeHtml(s.image_url)}" alt="${escapeHtml(s.titre)}" />
    </div>

    <div class="detail-grid">
      <div>
        <div class="detail-badges" style="margin-top:24px">
          <span class="tag tag-accent tnum">-${s.reduction}%</span>
          <span class="tag">${escapeHtml(s.sport)}</span>
          <span class="tag"><i data-lucide="star" style="width:14px;fill:var(--ink)"></i> ${s.note.toFixed(1)}</span>
        </div>
        <h1 class="page-title">${escapeHtml(s.titre)}</h1>
        <p class="soft" style="font-size:18px;margin-top:8px">${escapeHtml(s.salle)} · ${escapeHtml(s.ville)}</p>

        <div style="display:flex;flex-wrap:wrap;gap:24px;margin:28px 0;padding:20px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
          <div><span class="uplabel muted">Coach</span><p style="font-weight:600;margin-top:2px">${escapeHtml(s.coach || '—')}</p></div>
          <div><span class="uplabel muted">Horaire</span><p style="font-weight:600;margin-top:2px">${fmt.jourHeure(s.date_debut)}</p></div>
          <div><span class="uplabel muted">Durée</span><p style="font-weight:600;margin-top:2px">${s.duree_min} min</p></div>
          ${dist ? `<div><span class="uplabel muted">Distance</span><p style="font-weight:600;margin-top:2px">${dist}</p></div>` : ''}
        </div>

        ${s.salle_description ? `<h3 style="font-size:20px;margin-bottom:8px">À propos de la salle</h3><p class="soft" style="max-width:44rem">${escapeHtml(s.salle_description)}</p>` : ''}
        ${s.adresse ? `<p class="soft" style="margin-top:12px;display:flex;align-items:center;gap:8px"><i data-lucide="map-pin" style="width:16px"></i> ${escapeHtml(s.adresse)}</p>` : ''}
        ${equip ? `<div class="detail-badges" style="margin-top:20px">${equip}</div>` : ''}
      </div>

      <div>
        <div class="booking-card">
          <div class="price-row">
            <span class="now tnum">${fmt.euro(s.prix_reduit)}</span>
            <span class="was tnum">${fmt.euro(s.prix_initial)}</span>
          </div>
          <span class="uplabel" style="color:#2f6b1a">Vous économisez ${fmt.euro(s.prix_initial - s.prix_reduit)}</span>

          <div class="countdown-box">
            <div class="cd" data-cd-start="${escapeHtml(s.date_debut)}">—</div>
            <div class="cd-lbl">avant le début du cours</div>
          </div>

          <div class="stock-line">
            <span class="stock-dot" style="${soldout ? 'background:#c0392b' : ''}"></span>
            ${soldout ? 'Complet — plus de place' : `Plus que <b style="margin:0 3px">${s.places_restantes}</b> place${s.places_restantes > 1 ? 's' : ''} sur ${s.places_totales}`}
          </div>

          <button id="book-btn" class="btn btn-primary btn-block link-arrow" style="margin-top:20px" ${soldout ? 'disabled' : ''} data-slot="${s.id}">
            ${soldout ? 'Cours complet' : 'Réserver'} ${soldout ? '' : '<i data-lucide="arrow-right"></i>'}
          </button>
          <p class="muted" style="font-size:12px;text-align:center;margin-top:12px">Sans engagement · Annulation gratuite jusqu'à 2h avant</p>
        </div>
      </div>
    </div>
  `;

  renderIcons();
  startCourseCountdown();

  const btn = document.getElementById('book-btn');
  if (btn && !soldout) {
    btn.addEventListener('click', () => reserve(s.id));
  }
}

/* Compte à rebours (jours/heures/minutes) jusqu'au début du cours. */
function startCourseCountdown() {
  const el = document.querySelector('[data-cd-start]');
  if (!el) return;
  const start = new Date(el.dataset.cdStart.replace(' ', 'T')).getTime();
  const tick = () => {
    let diff = Math.floor((start - Date.now()) / 1000);
    if (diff <= 0) { el.textContent = 'En cours'; return; }
    const h = Math.floor(diff / 3600), m = Math.floor((diff % 3600) / 60), sec = diff % 60;
    el.textContent = `${String(h).padStart(2,'0')}h ${String(m).padStart(2,'0')}m ${String(sec).padStart(2,'0')}s`;
    setTimeout(tick, 1000);
  };
  tick();
}

async function reserve(slotId) {
  const user = window.__user;
  const target = `/checkout?slot=${slotId}`;
  if (!user) {
    location.href = `/connexion?redirect=${encodeURIComponent(target)}`;
    return;
  }
  location.href = target;
}

document.addEventListener('DOMContentLoaded', loadCourse);
