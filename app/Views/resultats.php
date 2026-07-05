  <main class="page">
    <div style="margin-bottom:24px">
      <span class="uplabel muted">Dernière minute</span>
      <h1 class="page-title" id="results-title">Cours disponibles</h1>
    </div>

    <!-- Filtres (client-side JS) -->
    <div class="filters">
      <div class="filter">
        <label>Ville</label>
        <select id="f-ville"><option value="">Toutes</option></select>
      </div>
      <div class="filter">
        <label>Discipline</label>
        <select id="f-sport"><option value="">Toutes</option></select>
      </div>
      <div class="filter">
        <label>Quand</label>
        <select id="f-date">
          <option value="">Tout</option>
          <option value="ce-soir">Ce soir</option>
          <option value="demain">Demain</option>
          <option value="week-end">Week-end</option>
        </select>
      </div>
      <div class="filter">
        <label>Prix max</label>
        <input id="f-prix" type="number" min="0" placeholder="€" style="width:90px" />
      </div>
      <div class="filter">
        <label>Trier par</label>
        <select id="f-tri">
          <option value="distance">Distance</option>
          <option value="prix">Prix croissant</option>
          <option value="places">Places restantes</option>
          <option value="heure">Heure</option>
        </select>
      </div>
      <div class="filter">
        <label>&nbsp;</label>
        <label style="display:flex;align-items:center;gap:8px;border:1px solid var(--line);padding:9px 12px;cursor:pointer;font-size:14px">
          <input id="f-dispo" type="checkbox" style="width:auto" /> Places dispo
        </label>
      </div>
    </div>

    <div class="results-layout">
      <div>
        <p class="uplabel muted" id="results-count" style="margin-bottom:16px">—</p>
        <div class="results-list" id="results-list">
          <div class="loading-wrap"><span class="spinner"></span><p style="margin-top:12px">Recherche…</p></div>
        </div>
      </div>
      <aside class="map-panel">
        <iframe id="map-frame" title="Carte" loading="lazy"
          src="https://www.openstreetmap.org/export/embed.html?bbox=4.79%2C45.74%2C4.89%2C45.78&layer=mapnik&marker=45.764%2C4.8357"></iframe>
      </aside>
    </div>
  </main>
