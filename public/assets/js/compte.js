/* Espace sportif : cours à venir, cours passés, argent économisé + QR codes. */

const STATUS_LABEL = {
  paye:       ['Payé', 'badge-paye'],
  valide:     ['Validé', 'badge-valide'],
  en_attente: ['En attente', 'badge-attente'],
  annule:     ['Annulé', 'badge-annule'],
};

async function loadAccount() {
  const root = document.getElementById('account-root');

  const user = await api.me().catch(() => null);
  if (!user) { location.href = '/connexion?redirect=/mon-compte'; return; }

  let bookings = [];
  try {
    bookings = await api.bookings();
  } catch (e) {
    root.innerHTML = `<div class="empty">${escapeHtml(e.message)}</div>`;
    return;
  }

  // On ne montre pas les réservations annulées/expirées dans les listes.
  const visibles = bookings.filter((b) => b.statut_paiement !== 'annule');
  const payes    = bookings.filter((b) => b.statut_paiement === 'paye' || b.statut_paiement === 'valide');

  const futurs = visibles.filter((b) => b.a_venir)
                         .sort((a, b) => a.date_debut.localeCompare(b.date_debut));   // le plus proche d'abord
  const passes = visibles.filter((b) => !b.a_venir)
                         .sort((a, b) => b.date_debut.localeCompare(a.date_debut));   // le plus récent d'abord

  const depense   = payes.reduce((s, b) => s + b.montant_paye, 0);
  const economise = bookings.reduce((s, b) => s + (b.economie || 0), 0);

  root.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:32px">
      <div>
        <span class="uplabel muted">Espace sportif</span>
        <h1 class="page-title">Bonjour, ${escapeHtml(user.nom.split(' ')[0])} 👋</h1>
      </div>
      <a href="#" data-logout class="link-arrow uplabel" style="color:var(--ink)"><i data-lucide="log-out"></i> Déconnexion</a>
    </div>

    <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:40px">
      ${statTile(payes.length, 'Cours réservés')}
      ${statTile(futurs.length, 'À venir')}
      ${statTile(fmt.euro(depense), 'Total dépensé')}
      ${statTile(fmt.euro(economise), 'Économisé grâce aux promos', true)}
    </div>

    <div class="section-head"><h2>Cours à venir</h2></div>
    <div id="futurs-list" style="display:flex;flex-direction:column;gap:16px;margin-bottom:48px"></div>

    <div class="section-head"><h2>Cours passés</h2></div>
    <div id="passes-list" style="display:flex;flex-direction:column;gap:16px"></div>
  `;

  const emptyFuturs = `<div class="empty">Aucun cours à venir.<br>
      <a href="/resultats" class="link-arrow" style="justify-content:center;margin-top:16px;color:var(--ink)">Trouver un cours <i data-lucide="arrow-right"></i></a></div>`;

  document.getElementById('futurs-list').innerHTML =
    futurs.length ? futurs.map(bookingCardHTML).join('') : emptyFuturs;
  document.getElementById('passes-list').innerHTML =
    passes.length ? passes.map((b) => bookingCardHTML(b, true)).join('') : '<div class="empty">Aucun cours passé pour l\'instant.</div>';

  renderIcons();
}

function statTile(value, label, highlight = false) {
  const style = highlight
    ? 'border:1px solid var(--accent);background:var(--accent)'
    : 'border:1px solid var(--line)';
  return `<div style="${style};padding:20px">
    <div style="font-family:'Archivo';font-weight:700;font-size:32px;letter-spacing:-0.03em" class="tnum">${value}</div>
    <div class="uplabel" style="margin-top:4px;color:${highlight ? 'var(--ink)' : 'var(--ink-mute)'}">${label}</div>
  </div>`;
}

/* Carte de réservation. `past` = cours déjà passé → on masque le QR à présenter. */
function bookingCardHTML(b, past = false) {
  const [label, cls] = STATUS_LABEL[b.statut_paiement] || ['—', 'badge-annule'];
  const isPaid = b.statut_paiement === 'paye' || b.statut_paiement === 'valide';
  // QR utile seulement pour un cours à venir déjà payé et pas encore validé.
  const showQr = !past && b.qr_token && b.statut_paiement === 'paye';
  const qrImg = showQr
    ? `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(b.qr_token)}`
    : null;

  const economie = (isPaid && b.economie > 0)
    ? `<p class="uplabel" style="margin-top:6px;color:#2f6b1a">Économie : ${fmt.euro(b.economie)}</p>`
    : '';

  return `
    <div class="ticket" style="${past ? 'opacity:.85' : ''}">
      <div class="ticket-top">
        <div>
          <span class="uplabel muted">${escapeHtml(b.sport)}</span>
          <h3 style="font-size:19px;margin-top:2px">${escapeHtml(b.titre)}</h3>
          <p class="soft" style="font-size:13px;margin-top:4px">${escapeHtml(b.salle)} · ${escapeHtml(b.ville)}</p>
          <p class="soft" style="font-size:13px"><i data-lucide="calendar" style="width:13px;vertical-align:-2px"></i> ${fmt.jourHeure(b.date_debut)}</p>
          ${economie}
        </div>
        <div style="text-align:right">
          <span class="badge-status ${cls}">${label}</span>
          <p class="tnum" style="font-weight:600;margin-top:8px">${fmt.euro(b.montant_paye)}</p>
          ${isPaid && b.economie > 0 ? `<p class="tnum muted" style="font-size:12px;text-decoration:line-through">${fmt.euro(b.prix_initial)}</p>` : ''}
        </div>
      </div>
      ${qrImg ? `
      <div class="ticket-qr">
        <p class="uplabel muted" style="margin-bottom:12px">À présenter à l'accueil</p>
        <img src="${qrImg}" alt="QR code" onerror="this.style.display='none'" />
        <div class="ticket-token">${escapeHtml(b.qr_token)}</div>
      </div>` : (!past && b.statut_paiement === 'en_attente'
        ? `<div style="padding:16px 24px"><span class="soft" style="font-size:13px">Paiement non finalisé — <a href="/checkout?slot=${b.slot_id ?? ''}" style="color:var(--ink);font-weight:600">reprendre</a></span></div>`
        : '')}
    </div>`;
}

document.addEventListener('DOMContentLoaded', loadAccount);
