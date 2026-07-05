/* Espace sportif : historique des réservations + QR codes de validation. */

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

  const actifs = bookings.filter((b) => b.statut_paiement === 'paye' || b.statut_paiement === 'valide');
  const dépense = bookings.reduce((sum, b) => sum + (b.statut_paiement === 'annule' ? 0 : b.montant_paye), 0);

  root.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:32px">
      <div>
        <span class="uplabel muted">Espace sportif</span>
        <h1 class="page-title">Bonjour, ${escapeHtml(user.nom.split(' ')[0])} 👋</h1>
      </div>
      <a href="#" id="logout-link" class="link-arrow uplabel" style="color:var(--ink)"><i data-lucide="log-out"></i> Déconnexion</a>
    </div>

    <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:40px">
      ${statTile(bookings.length, 'Réservations')}
      ${statTile(actifs.length, 'Billets actifs')}
      ${statTile(fmt.euro(dépense), 'Total dépensé')}
    </div>

    <div class="section-head"><h2>Mes réservations</h2></div>
    <div id="bookings-list" style="display:flex;flex-direction:column;gap:16px"></div>
  `;

  const list = document.getElementById('bookings-list');
  if (bookings.length === 0) {
    list.innerHTML = `<div class="empty">Aucune réservation pour l'instant.<br>
      <a href="/resultats" class="link-arrow" style="justify-content:center;margin-top:16px;color:var(--ink)">Trouver un cours <i data-lucide="arrow-right"></i></a></div>`;
  } else {
    list.innerHTML = bookings.map(bookingCardHTML).join('');
  }
  renderIcons();

  const logout = document.getElementById('logout-link');
  if (logout) {
    logout.addEventListener('click', async (e) => {
      e.preventDefault();
      await api.logout();
      location.href = '/';
    });
  }
}

function statTile(value, label) {
  return `<div style="border:1px solid var(--line);padding:20px">
    <div style="font-family:'Archivo';font-weight:700;font-size:32px;letter-spacing:-0.03em" class="tnum">${value}</div>
    <div class="uplabel muted" style="margin-top:4px">${label}</div>
  </div>`;
}

function bookingCardHTML(b) {
  const [label, cls] = STATUS_LABEL[b.statut_paiement] || ['—', 'badge-annule'];
  const canShowQr = b.qr_token && (b.statut_paiement === 'paye' || b.statut_paiement === 'valide');
  const qrImg = canShowQr
    ? `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(b.qr_token)}`
    : null;

  return `
    <div class="ticket">
      <div class="ticket-top">
        <div>
          <span class="uplabel muted">${escapeHtml(b.sport)}</span>
          <h3 style="font-size:19px;margin-top:2px">${escapeHtml(b.titre)}</h3>
          <p class="soft" style="font-size:13px;margin-top:4px">${escapeHtml(b.salle)} · ${escapeHtml(b.ville)}</p>
          <p class="soft" style="font-size:13px"><i data-lucide="calendar" style="width:13px;vertical-align:-2px"></i> ${fmt.jourHeure(b.date_debut)}</p>
        </div>
        <div style="text-align:right">
          <span class="badge-status ${cls}">${label}</span>
          <p class="tnum" style="font-weight:600;margin-top:8px">${fmt.euro(b.montant_paye)}</p>
        </div>
      </div>
      ${qrImg ? `
      <div class="ticket-qr">
        <p class="uplabel muted" style="margin-bottom:12px">${b.statut_paiement === 'valide' ? 'Entrée déjà validée' : "À présenter à l'accueil"}</p>
        <img src="${qrImg}" alt="QR code" onerror="this.style.display='none'" />
        <div class="ticket-token">${escapeHtml(b.qr_token)}</div>
      </div>` : (b.statut_paiement === 'en_attente'
        ? `<div style="padding:16px 24px"><span class="soft" style="font-size:13px">Paiement non finalisé.</span></div>`
        : '')}
    </div>`;
}

document.addEventListener('DOMContentLoaded', loadAccount);
