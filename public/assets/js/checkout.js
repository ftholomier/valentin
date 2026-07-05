/* Tunnel de paiement : récap cours -> réservation -> paiement mocké -> QR. */

let CURRENT_SLOT = null;

async function initCheckout() {
  const root = document.getElementById('checkout-root');
  const slotId = qsParam('slot');
  if (!slotId) { root.innerHTML = '<div class="empty">Aucun cours sélectionné.</div>'; return; }

  // Authentification requise
  const user = await api.me().catch(() => null);
  if (!user) {
    location.href = `/connexion?redirect=${encodeURIComponent('/checkout?slot=' + slotId)}`;
    return;
  }

  try {
    CURRENT_SLOT = await api.slot(slotId);
  } catch (e) {
    root.innerHTML = `<div class="empty">${escapeHtml(e.message)}</div>`;
    return;
  }
  const s = CURRENT_SLOT;

  if (s.places_restantes <= 0 || s.statut === 'complet') {
    root.innerHTML = `<div class="empty">Ce cours est complet.<br><a href="/resultats" class="link-arrow" style="justify-content:center;margin-top:16px;color:var(--ink)">Voir d'autres cours <i data-lucide="arrow-right"></i></a></div>`;
    renderIcons();
    return;
  }

  root.innerHTML = `
    <a href="/cours?id=${s.id}" class="link-arrow uplabel muted" style="margin-bottom:20px"><i data-lucide="arrow-left"></i> Retour au cours</a>
    <span class="uplabel muted">Finaliser la réservation</span>
    <h1 class="page-title" style="margin-bottom:24px">Paiement</h1>

    <div class="form-card" style="margin-bottom:20px">
      <div style="display:flex;gap:16px;align-items:center">
        <img src="${escapeHtml(s.image_url)}" alt="" style="width:72px;height:72px;object-fit:cover" />
        <div style="flex:1">
          <h3 style="font-size:18px">${escapeHtml(s.titre)}</h3>
          <p class="soft" style="font-size:13px">${escapeHtml(s.salle)} · ${fmt.jourHeure(s.date_debut)}</p>
        </div>
      </div>
      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line);display:flex;justify-content:space-between;align-items:baseline">
        <span class="uplabel muted">Total à payer</span>
        <span style="font-family:'Archivo';font-weight:700;font-size:28px" class="tnum">${fmt.euro(s.prix_reduit)}</span>
      </div>
    </div>

    <div id="pay-alert"></div>

    <div class="form-card">
      <p class="uplabel muted" style="margin-bottom:16px">Carte bancaire (simulation)</p>
      <form id="pay-form" novalidate>
        <div class="field">
          <label for="card">Numéro de carte</label>
          <input id="card" name="card" inputmode="numeric" placeholder="4242 4242 4242 4242" value="4242 4242 4242 4242" />
        </div>
        <div style="display:flex;gap:12px">
          <div class="field" style="flex:1"><label>Expiration</label><input placeholder="12 / 28" value="12 / 28" /></div>
          <div class="field" style="width:110px"><label>CVC</label><input placeholder="123" value="123" /></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block link-arrow" id="pay-btn">
          Payer ${fmt.euro(s.prix_reduit)} <i data-lucide="lock"></i>
        </button>
      </form>
      <p class="muted" style="font-size:12px;text-align:center;margin-top:14px">
        Paiement simulé — aucune transaction réelle.<br>
        Astuce : <b>4000 0000 0000 0002</b> simule un refus.
      </p>
    </div>
  `;
  renderIcons();

  document.getElementById('pay-form').addEventListener('submit', (e) => {
    e.preventDefault();
    pay(document.getElementById('card').value);
  });
}

async function pay(card) {
  const btn = document.getElementById('pay-btn');
  const alertBox = document.getElementById('pay-alert');
  alertBox.innerHTML = '';
  btn.disabled = true; btn.textContent = 'Paiement en cours…';

  try {
    // 1) Réserver la place (crée la réservation en attente)
    const booking = await api.book(CURRENT_SLOT.id);
    // 2) Payer (simulation Stripe) -> génère le QR
    const result = await api.checkout({ booking_id: booking.booking_id, card });
    showTicket(result);
  } catch (e) {
    alertBox.innerHTML = `<div class="alert alert-error">${escapeHtml(e.message)}</div>`;
    btn.disabled = false;
    btn.innerHTML = `Payer ${fmt.euro(CURRENT_SLOT.prix_reduit)} <i data-lucide="lock"></i>`;
    renderIcons();
  }
}

function showTicket(result) {
  const s = CURRENT_SLOT;
  const root = document.getElementById('checkout-root');
  const qrImg = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(result.qr_token)}`;
  root.innerHTML = `
    <div class="alert alert-success" style="display:flex;align-items:center;gap:10px">
      <i data-lucide="check-circle"></i> Paiement accepté — réservation confirmée !
    </div>
    <div class="ticket" style="margin-top:8px">
      <div class="ticket-top">
        <div>
          <h3 style="font-size:20px">${escapeHtml(s.titre)}</h3>
          <p class="soft" style="font-size:13px">${escapeHtml(s.salle)} · ${fmt.jourHeure(s.date_debut)}</p>
        </div>
        <span class="badge-status badge-paye">Payé</span>
      </div>
      <div class="ticket-qr">
        <p class="uplabel muted" style="margin-bottom:14px">Présentez ce QR code à l'accueil</p>
        <img src="${qrImg}" alt="QR code de validation" onerror="this.style.display='none'" />
        <div class="ticket-token">${escapeHtml(result.qr_token)}</div>
        <p class="muted" style="font-size:12px;margin-top:8px">Transaction ${escapeHtml(result.transaction_id)}</p>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:20px">
      <a href="/mon-compte" class="btn btn-primary" style="flex:1">Mes réservations</a>
      <a href="/resultats" class="btn btn-outline" style="flex:1">Autres cours</a>
    </div>
  `;
  renderIcons();
}

document.addEventListener('DOMContentLoaded', initCheckout);
