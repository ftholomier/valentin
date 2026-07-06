/* Backoffice admin : KPI plateforme + gestion commission / sports / villes. */

let ADMIN = null; // dernière config chargée { commission_rate, sports, villes }

async function loadAdmin() {
  const root = document.getElementById('admin-root');

  const user = await api.me().catch(() => null);
  if (!user) { location.href = '/connexion?redirect=/admin'; return; }
  if (user.role !== 'admin') {
    root.innerHTML = `<div class="empty">Espace réservé à l'administration.<br>
      <a href="/" class="link-arrow" style="justify-content:center;margin-top:16px;color:var(--ink)">Retour à l'accueil <i data-lucide="arrow-right"></i></a></div>`;
    renderIcons();
    return;
  }

  let d;
  try {
    d = await api.adminOverview();
  } catch (e) {
    root.innerHTML = `<div class="empty">${escapeHtml(e.message)}</div>`;
    return;
  }
  render(d);
}

function render(d) {
  ADMIN = d.config;
  const s = d.stats;
  const root = document.getElementById('admin-root');

  root.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:32px">
      <div>
        <span class="uplabel muted">Administration</span>
        <h1 class="page-title">Backoffice</h1>
      </div>
      <a href="#" data-logout class="link-arrow uplabel" style="color:var(--ink)"><i data-lucide="log-out"></i> Déconnexion</a>
    </div>

    <div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:40px">
      ${tile(s.sportifs, 'Sportifs')}
      ${tile(s.salles, 'Salles')}
      ${tile(s.cours, 'Cours')}
      ${tile(s.reservations, 'Réservations payées')}
      ${tile(fmt.euro(s.ca_commission), 'Commission plateforme')}
      ${tile(fmt.euro(s.ca_total), 'Volume total')}
    </div>

    <div id="admin-alert"></div>

    <!-- Commission -->
    <div class="section-head"><h2>Commission</h2></div>
    <div class="form-card" style="margin-bottom:40px;max-width:420px">
      <form id="commission-form">
        <div class="field">
          <label for="rate">Taux prélevé par la plateforme (%)</label>
          <input id="rate" type="number" min="0" max="90" step="0.5" value="${(ADMIN.commission_rate * 100).toFixed(1)}" />
        </div>
        <p class="muted" style="font-size:12px;margin-bottom:14px">Exemple : sur un cours à 12 €, une commission de ${(ADMIN.commission_rate * 100).toFixed(0)} % = ${fmt.euro(12 * ADMIN.commission_rate)} pour la plateforme, ${fmt.euro(12 * (1 - ADMIN.commission_rate))} pour la salle.</p>
        <button type="submit" class="btn btn-primary btn-block">Enregistrer le taux</button>
      </form>
    </div>

    <!-- Sports -->
    <div class="section-head"><h2>Disciplines</h2></div>
    <div class="form-card" style="margin-bottom:40px;max-width:560px">
      <div id="sports-chips" class="pills" style="margin-bottom:16px"></div>
      <form id="sport-form" style="display:flex;gap:8px">
        <input id="sport-input" placeholder="Ajouter un sport (ex : Escalade)" style="flex:1;border:1px solid var(--line);padding:12px 14px;font-size:15px;outline:none" />
        <button type="submit" class="btn btn-outline">Ajouter</button>
      </form>
    </div>

    <!-- Villes -->
    <div class="section-head"><h2>Villes actives</h2></div>
    <div class="form-card" style="max-width:560px">
      <div id="villes-chips" class="pills" style="margin-bottom:16px"></div>
      <form id="ville-form" style="display:flex;gap:8px">
        <input id="ville-input" placeholder="Ajouter une ville (ex : Nantes)" style="flex:1;border:1px solid var(--line);padding:12px 14px;font-size:15px;outline:none" />
        <button type="submit" class="btn btn-outline">Ajouter</button>
      </form>
    </div>
  `;

  renderChips();
  renderIcons();
  bindForms();
}

function tile(value, label) {
  return `<div style="border:1px solid var(--line);padding:20px">
    <div style="font-family:'Archivo';font-weight:700;font-size:28px;letter-spacing:-0.03em" class="tnum">${value}</div>
    <div class="uplabel muted" style="margin-top:4px">${label}</div>
  </div>`;
}

function chip(label, kind) {
  return `<span class="pill" style="padding-right:8px">
    <span class="lbl">${escapeHtml(label)}</span>
    <button type="button" class="chip-del" data-kind="${kind}" data-val="${escapeHtml(label)}"
      title="Retirer" style="border:none;background:none;cursor:pointer;color:var(--ink-mute);font-size:18px;line-height:1">&times;</button>
  </span>`;
}

function renderChips() {
  document.getElementById('sports-chips').innerHTML =
    ADMIN.sports.map((x) => chip(x, 'sports')).join('') || '<span class="muted">Aucun sport.</span>';
  document.getElementById('villes-chips').innerHTML =
    ADMIN.villes.map((x) => chip(x, 'villes')).join('') || '<span class="muted">Aucune ville.</span>';
  document.querySelectorAll('.chip-del').forEach((b) => {
    b.addEventListener('click', () => removeItem(b.dataset.kind, b.dataset.val));
  });
}

function bindForms() {
  document.getElementById('commission-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const pct = parseFloat(document.getElementById('rate').value);
    if (isNaN(pct)) return;
    await save({ commission_rate: pct / 100 }, 'Taux de commission mis à jour.');
  });

  document.getElementById('sport-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const v = document.getElementById('sport-input').value.trim();
    if (!v) return;
    save({ sports: [...ADMIN.sports, v] }, 'Sport ajouté.');
  });

  document.getElementById('ville-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const v = document.getElementById('ville-input').value.trim();
    if (!v) return;
    save({ villes: [...ADMIN.villes, v] }, 'Ville ajoutée.');
  });
}

function removeItem(kind, val) {
  const next = ADMIN[kind].filter((x) => x !== val);
  if (!next.length) {
    alertBox(`Impossible de retirer le dernier élément.`, true);
    return;
  }
  save({ [kind]: next }, 'Mise à jour effectuée.');
}

async function save(payload, okMsg) {
  try {
    const d = await api.adminConfig(payload);
    render(d);
    alertBox(okMsg, false);
  } catch (e) {
    alertBox(e.message, true);
  }
}

function alertBox(msg, isError) {
  const box = document.getElementById('admin-alert');
  if (box) box.innerHTML = `<div class="alert ${isError ? 'alert-error' : 'alert-success'}">${escapeHtml(msg)}</div>`;
}

document.addEventListener('DOMContentLoaded', loadAdmin);
