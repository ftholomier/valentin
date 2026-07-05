/* Espace pro (salle) : dashboard cours du jour, validation QR, remplissage & CA. */

async function loadPro() {
  const root = document.getElementById('pro-root');

  const user = await api.me().catch(() => null);
  if (!user) { location.href = '/connexion?redirect=/pro'; return; }
  if (user.role !== 'partner' && user.role !== 'admin') {
    root.innerHTML = `<div class="empty">Cet espace est réservé aux salles partenaires.<br>
      <a href="/" class="link-arrow" style="justify-content:center;margin-top:16px;color:var(--ink)">Retour à l'accueil <i data-lucide="arrow-right"></i></a></div>`;
    renderIcons();
    return;
  }

  let d;
  try {
    d = await api.proDashboard();
  } catch (e) {
    root.innerHTML = `<div class="empty">${escapeHtml(e.message)}</div>`;
    return;
  }

  const s = d.stats;
  root.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:32px">
      <div>
        <span class="uplabel muted">Espace pro · ${escapeHtml(d.partner.ville)}</span>
        <h1 class="page-title">${escapeHtml(d.partner.nom)}</h1>
      </div>
      <a href="#" data-logout class="link-arrow uplabel" style="color:var(--ink)"><i data-lucide="log-out"></i> Déconnexion</a>
    </div>

    <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:40px">
      ${tile(s.cours_jour, 'Cours du jour')}
      ${tile(s.vendues_jour + '/' + s.places_jour, 'Places vendues (jour)')}
      ${tile(s.remplissage_jour + '%', 'Remplissage (jour)')}
      ${tile(fmt.euro(s.ca_total), 'CA généré (total)')}
      ${tile(fmt.euro(s.commission_total), 'Commission plateforme')}
    </div>

    <div class="section-head"><h2>Valider une entrée</h2></div>
    <div class="form-card" style="margin-bottom:40px;max-width:560px">
      <div id="scan-alert"></div>
      <form id="scan-form">
        <div class="field">
          <label for="qr">Code du billet (QR)</label>
          <input id="qr" name="qr" placeholder="LF-XXXXXXXXXXXXXXXX" autocomplete="off" style="font-family:'Archivo';letter-spacing:0.08em" />
        </div>
        <button type="submit" class="btn btn-primary btn-block link-arrow" id="scan-btn">Valider l'entrée <i data-lucide="scan-line"></i></button>
      </form>
      <p class="muted" style="font-size:12px;margin-top:12px">Saisis ou colle le code présenté par le sportif. (Un vrai lecteur QR renverra ce même code.)</p>
    </div>

    <div class="section-head"><h2>Mes cours</h2></div>
    <div id="pro-slots" style="display:flex;flex-direction:column;gap:12px"></div>
  `;

  document.getElementById('pro-slots').innerHTML = d.slots.length
    ? d.slots.map(slotRow).join('')
    : '<div class="empty">Aucun cours programmé.</div>';

  renderIcons();
  bindScan();
}

function tile(value, label) {
  return `<div style="border:1px solid var(--line);padding:20px">
    <div style="font-family:'Archivo';font-weight:700;font-size:30px;letter-spacing:-0.03em" class="tnum">${value}</div>
    <div class="uplabel muted" style="margin-top:4px">${label}</div>
  </div>`;
}

function slotRow(s) {
  const pct = s.places_totales > 0 ? Math.round(s.vendus / s.places_totales * 100) : 0;
  const badge = s.aujourdhui ? '<span class="badge-status badge-paye">Aujourd\'hui</span>' : '';
  return `
    <div style="border:1px solid var(--line);padding:16px 20px;display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center">
      <div style="min-width:0">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <span class="uplabel muted">${escapeHtml(s.sport)}</span>${badge}
        </div>
        <h3 style="font-size:17px;margin-top:2px">${escapeHtml(s.titre)}</h3>
        <p class="soft" style="font-size:13px">${fmt.jourHeure(s.date_debut)} · ${escapeHtml(s.coach || '')}</p>
        <div style="margin-top:10px;height:6px;background:var(--line);max-width:280px">
          <div style="height:100%;width:${pct}%;background:var(--accent)"></div>
        </div>
        <p class="soft" style="font-size:12px;margin-top:6px">${s.vendus}/${s.places_totales} places · ${s.valides} entrée${s.valides>1?'s':''} validée${s.valides>1?'s':''}</p>
      </div>
      <div style="text-align:right">
        <div class="tnum" style="font-family:'Archivo';font-weight:700;font-size:20px">${fmt.euro(s.ca_salle)}</div>
        <div class="uplabel muted">CA salle</div>
      </div>
    </div>`;
}

function bindScan() {
  const form = document.getElementById('scan-form');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const box = document.getElementById('scan-alert');
    const btn = document.getElementById('scan-btn');
    const token = document.getElementById('qr').value.trim();
    if (!token) return;
    box.innerHTML = '';
    btn.disabled = true; btn.textContent = 'Vérification…';
    try {
      const r = await api.validateQr(token);
      box.innerHTML = `<div class="alert alert-success"><b>Entrée validée ✅</b><br>${escapeHtml(r.client)} — ${escapeHtml(r.titre)}</div>`;
      document.getElementById('qr').value = '';
      setTimeout(loadPro, 900); // rafraîchit les stats
    } catch (err) {
      box.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
    } finally {
      btn.disabled = false; btn.innerHTML = "Valider l'entrée <i data-lucide=\"scan-line\"></i>";
      renderIcons();
    }
  });
}

document.addEventListener('DOMContentLoaded', loadPro);
