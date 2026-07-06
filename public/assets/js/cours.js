/* Fiche cours — structure inspirée d'une annonce Airbnb, style LastFit. */

// Banques de photos d'ambiance par discipline (les salles téléverseront
// leurs vraies photos plus tard). IDs Unsplash choisis par sport + intérieurs
// de salle génériques, pour une galerie crédible et variée.
const GYM_INTERIORS = ['1534438327276-14e5300c3a48', '1571019614242-c5c5dee9f50b', '1534258936925-c58bed479fcb', '1518611012118-696072aa579a'];
const SPORT_PHOTOS = {
  Yoga:     ['1544367567-0f2fcb009e0b', '1552196563-55cd4e45efb3', '1506126613408-eca07ce68773', '1588286840104-8957b019727f', '1599901860904-17e6ed7083a0', '1545205597-3d9d02c29597'],
  Pilates:  ['1571902943202-507ec2618e8f', '1517836357463-d25dfeac3438', '1518310383802-640c2de311b2', '1591258370814-01609b341790', '1600965962361-9035dbfd1c50'],
  CrossFit: ['1534438327276-14e5300c3a48', '1534258936925-c58bed479fcb', '1517963879433-6ad2b056d712', '1541534741688-6078c6bfb5c5', '1571019614242-c5c5dee9f50b'],
  HIIT:     ['1534258936925-c58bed479fcb', '1518611012118-696072aa579a', '1541534741688-6078c6bfb5c5', '1571019614242-c5c5dee9f50b', '1517838277536-f5f99be501cd'],
  Boxe:     ['1549719386-74dfcbf7dbed', '1544216717-3bbf52512659', '1517438476312-10d79c077509', '1590487988256-9ed24133863e', '1571019613454-1cb2f99b2d8b'],
  Cycling:  ['1518310383802-640c2de311b2', '1534787238916-9ba6764efd4f', '1571019613454-1cb2f99b2d8b', '1594737625785-a6cbdabd333c', '1517838277536-f5f99be501cd'],
  Zumba:    ['1524594152303-9fd13543fe6e', '1547153760-18fc86324498', '1508700115892-45ecd05ae2ad', '1571019614242-c5c5dee9f50b'],
  Aquagym:  ['1560089000-7433a4ebbd64', '1600965962361-9035dbfd1c50', '1576013551627-0cc20b96c2a7', '1530549387789-4c1017266635'],
};
const imgFromId = (id) => `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=1000&q=80`;
const photoId = (u) => { const m = String(u).match(/photo-([^?]+)/); return m ? m[1] : u; };

/* Sélection variée et STABLE selon l'id du cours (deux cours diffèrent),
   dédupliquée par identifiant de photo (même image en tailles différentes = 1). */
function buildGallery(s) {
  const pool = (SPORT_PHOTOS[s.sport] || GYM_INTERIORS).concat(GYM_INTERIORS);
  const start = (s.id || 1) % pool.length;
  const rotated = pool.slice(start).concat(pool.slice(0, start));
  const seen = new Set([photoId(s.image_url)]);
  const out = [s.image_url];
  for (const id of rotated) {
    if (seen.has(id)) continue;
    seen.add(id);
    out.push(imgFromId(id));
    if (out.length >= 5) break;
  }
  return out;
}

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
  const gallery = buildGallery(s);
  window.__gallery = gallery;
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
      <div class="gallery-main" data-photo="0">
        <img src="${escapeHtml(gallery[0])}" alt="${escapeHtml(s.titre)}" />
        <span class="card-badge tnum">-${s.reduction}%</span>
      </div>
      <div class="gallery-side">
        ${side.map((u, i) => `<div class="gallery-cell" data-photo="${i + 1}"><img src="${escapeHtml(u)}" alt="Photo de ${escapeHtml(s.salle)}" loading="lazy" /></div>`).join('')}
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

  // Visionneuse plein écran (clic sur une photo ou "Toutes les photos").
  document.querySelectorAll('[data-photo]').forEach((el) => el.addEventListener('click', () => openLightbox(+el.dataset.photo)));
  const allBtn = document.querySelector('.gallery-all');
  if (allBtn) allBtn.addEventListener('click', (e) => { e.stopPropagation(); openLightbox(0); });
}

function highlight(icon, title, sub) {
  return `<div class="highlight">
    <i data-lucide="${icon}"></i>
    <div><b>${title}</b><span>${sub}</span></div>
  </div>`;
}

/* ---------- Visionneuse photos ---------- */
function openLightbox(startIndex = 0) {
  const photos = window.__gallery || [];
  if (!photos.length) return;
  let box = document.getElementById('lightbox');
  if (!box) {
    box = document.createElement('div');
    box.id = 'lightbox';
    document.body.appendChild(box);
  }
  box.innerHTML = `
    <div class="lb-bar">
      <button type="button" class="lb-close" aria-label="Fermer"><i data-lucide="x"></i></button>
      <span class="lb-count"></span>
    </div>
    <div class="lb-scroll">
      ${photos.map((u) => `<img src="${escapeHtml(u)}" alt="" />`).join('')}
    </div>`;
  document.body.style.overflow = 'hidden';
  box.classList.add('show');
  renderIcons();

  const imgs = box.querySelectorAll('.lb-scroll img');
  const counter = box.querySelector('.lb-count');
  const setCount = (i) => { counter.textContent = `${i + 1} / ${photos.length}`; };
  setCount(startIndex);
  if (imgs[startIndex]) imgs[startIndex].scrollIntoView({ behavior: 'instant', block: 'center' });

  const scroller = box.querySelector('.lb-scroll');
  scroller.addEventListener('scroll', () => {
    let best = 0, bestD = Infinity;
    imgs.forEach((im, i) => { const d = Math.abs(im.getBoundingClientRect().top); if (d < bestD) { bestD = d; best = i; } });
    setCount(best);
  }, { passive: true });

  const close = () => { box.classList.remove('show'); document.body.style.overflow = ''; document.removeEventListener('keydown', onKey); };
  const onKey = (e) => { if (e.key === 'Escape') close(); };
  box.querySelector('.lb-close').addEventListener('click', close);
  box.addEventListener('click', (e) => { if (e.target === box) close(); });
  document.addEventListener('keydown', onKey);
}

async function shareCourse(s) {
  const url = location.href;
  const data = { title: `LastFit — ${s.titre}`, text: `${s.titre} à ${s.salle} : ${fmt.euro(s.prix_reduit)} au lieu de ${fmt.euro(s.prix_initial)} !`, url };
  if (navigator.share) {
    try { await navigator.share(data); return; } catch (_) {}
  }
  try { await navigator.clipboard.writeText(url); toast('Lien copié dans le presse-papier'); }
  catch (_) { toast("Copiez le lien depuis la barre d'adresse"); }
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
