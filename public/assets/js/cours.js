/* Fiche cours — structure inspirée d'une annonce Airbnb, style LastFit. */

// Petit fonds d'images d'ambiance pour composer une galerie (les salles
// pourront téléverser leurs vraies photos plus tard).
const GALLERY_POOL = [
  '1534438327276-14e5300c3a48', '1571019614242-c5c5dee9f50b', '1517836357463-d25dfeac3438',
  '1518310383802-640c2de311b2', '1534258936925-c58bed479fcb', '1544367567-0f2fcb009e0b',
];
const imgFromId = (id) => `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=800&q=80`;

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
  const economie = s.prix_initial - s.prix_reduit;

  // Galerie : image du cours + images d'ambiance (uniques), 5 au total.
  const gallery = [s.image_url, ...GALLERY_POOL.map(imgFromId)]
    .filter((v, i, a) => a.indexOf(v) === i).slice(0, 5);
  const side = gallery.slice(1, 5);

  const equip = (s.equipements || []).map((e) =>
    `<div class="equip-item"><i data-lucide="check"></i> ${escapeHtml(e)}</div>`).join('');

  const mapSrc = (s.lat && s.lng)
    ? `https://www.openstreetmap.org/export/embed.html?bbox=${s.lng - 0.01}%2C${s.lat - 0.006}%2C${s.lng + 0.01}%2C${s.lat + 0.006}&layer=mapnik&marker=${s.lat}%2C${s.lng}`
    : null;

  root.innerHTML = `
    <a href="/resultats" class="link-arrow muted" style="margin-bottom:16px;font-size:14px"><i data-lucide="arrow-left"></i> Toutes les offres</a>

    <div class="listing-head">
      <h1 class="listing-title">${escapeHtml(s.titre)}</h1>
      <div class="listing-actions">
        <button type="button" data-share class="listing-action"><i data-lucide="share"></i> Partager</button>
        <button type="button" data-save class="listing-action"><i data-lucide="heart"></i> Enregistrer</button>
      </div>
    </div>
    <p class="listing-sub">
      <span class="rating"><i data-lucide="star"></i> ${s.note.toFixed(1)}</span>
      <span class="dot-sep">·</span> <span class="tnum" style="color:#2f6b1a;font-weight:600">-${s.reduction}%</span>
      <span class="dot-sep">·</span> ${escapeHtml(s.salle)}, ${escapeHtml(s.ville)}
    </p>

    <div class="gallery">
      <div class="gallery-main">
        <img src="${escapeHtml(gallery[0])}" alt="${escapeHtml(s.titre)}" />
        <span class="card-badge tnum">-${s.reduction}%</span>
      </div>
      <div class="gallery-side">
        ${side.map((u) => `<div class="gallery-cell"><img src="${escapeHtml(u)}" alt="" loading="lazy" /></div>`).join('')}
        <button type="button" class="gallery-all"><i data-lucide="layout-grid"></i> Toutes les photos</button>
      </div>
    </div>

    <div class="listing-body">
      <div class="listing-main">
        <div class="listing-block">
          <h2 class="listing-h2">Cours de ${escapeHtml(s.sport)} — ${escapeHtml(s.salle)}</h2>
          <p class="listing-facts">${s.duree_min} min · ${s.places_totales} places · ${escapeHtml(s.ville)}${dist ? ' · à ' + dist : ''}</p>
        </div>

        <hr class="listing-hr" />

        <div class="host-row">
          <img class="host-avatar" src="https://i.pravatar.cc/96?u=${encodeURIComponent(s.coach || s.salle)}" alt="" onerror="this.style.visibility='hidden'" />
          <div>
            <p class="host-name">Coach ${escapeHtml(s.coach || '—')}</p>
            <p class="soft" style="font-size:14px">${escapeHtml(s.salle)} · Salle vérifiée</p>
          </div>
        </div>

        <hr class="listing-hr" />

        <div class="highlights">
          ${highlight('calendar-clock', fmt.jourHeure(s.date_debut), 'Créneau du cours')}
          ${highlight('clock', s.duree_min + ' minutes', 'Durée de la séance')}
          ${highlight('map-pin', dist ? 'À ' + dist : escapeHtml(s.ville), 'Emplacement')}
          ${highlight('shield-check', 'Annulation gratuite', "jusqu'à 2h avant le début")}
        </div>

        <hr class="listing-hr" />

        <div class="listing-block">
          <h2 class="listing-h2">À propos de la salle</h2>
          ${s.salle_description ? `<p class="soft" style="max-width:46rem;line-height:1.7">${escapeHtml(s.salle_description)}</p>` : ''}
          ${s.adresse ? `<p class="soft" style="margin-top:12px;display:flex;align-items:center;gap:8px"><i data-lucide="map-pin" style="width:16px"></i> ${escapeHtml(s.adresse)}</p>` : ''}
          ${equip ? `<div class="equip-grid">${equip}</div>` : ''}
        </div>

        ${mapSrc ? `
        <hr class="listing-hr" />
        <div class="listing-block">
          <h2 class="listing-h2">Où se déroule le cours</h2>
          <div class="listing-map"><iframe title="Carte" loading="lazy" src="${mapSrc}"></iframe></div>
        </div>` : ''}
      </div>

      <aside class="listing-aside">
        <div class="booking-card">
          <div class="rare-badge"><i data-lucide="flame"></i> Offre dernière minute</div>
          <div class="price-row">
            <span class="now tnum">${fmt.euro(s.prix_reduit)}</span>
            <span class="was tnum">${fmt.euro(s.prix_initial)}</span>
          </div>
          <span class="save-badge">Vous économisez ${fmt.euro(economie)}</span>

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
          <p class="muted" style="font-size:12px;text-align:center;margin-top:12px">Aucun montant débité maintenant · Annulation gratuite jusqu'à 2h avant</p>
        </div>
      </aside>
    </div>
  `;

  renderIcons();
  startCourseCountdown();

  const btn = document.getElementById('book-btn');
  if (btn && !soldout) btn.addEventListener('click', () => reserve(s.id));

  const shareBtn = document.querySelector('[data-share]');
  if (shareBtn) shareBtn.addEventListener('click', () => shareCourse(s));
  const saveBtn = document.querySelector('[data-save]');
  if (saveBtn) saveBtn.addEventListener('click', () => {
    saveBtn.classList.toggle('is-saved');
    toast(saveBtn.classList.contains('is-saved') ? 'Ajouté à vos favoris (bientôt synchronisé)' : 'Retiré des favoris');
  });
}

function highlight(icon, title, sub) {
  return `<div class="highlight">
    <i data-lucide="${icon}"></i>
    <div><b>${title}</b><span>${sub}</span></div>
  </div>`;
}

async function shareCourse(s) {
  const url = location.href;
  const data = { title: `LastFit — ${s.titre}`, text: `${s.titre} à ${s.salle} : ${fmt.euro(s.prix_reduit)} au lieu de ${fmt.euro(s.prix_initial)} !`, url };
  if (navigator.share) {
    try { await navigator.share(data); return; } catch (_) {}
  }
  try { await navigator.clipboard.writeText(url); toast('Lien copié dans le presse-papier'); }
  catch (_) { toast('Copiez le lien depuis la barre d\'adresse'); }
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

function reserve(slotId) {
  location.href = `/checkout?slot=${slotId}`;
}

document.addEventListener('DOMContentLoaded', loadCourse);
