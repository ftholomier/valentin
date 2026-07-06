  <main class="page">
    <div class="offers-head">
      <span class="uplabel muted">Dernière minute · autour de vous</span>
      <h1 class="page-title">Les offres du moment</h1>
      <p class="soft" id="offers-sub">Des cours à prix cassé, près de chez vous, en dernière minute.</p>
    </div>

    <!-- Barre d'outils collante -->
    <div class="offers-toolbar">
      <div class="chips-row no-scrollbar" id="sport-chips"></div>
      <div class="toolbar-controls">
        <div class="date-pills" id="date-pills">
          <button type="button" class="pill-toggle is-active" data-date="">Tout</button>
          <button type="button" class="pill-toggle" data-date="ce-soir">Ce soir</button>
          <button type="button" class="pill-toggle" data-date="demain">Demain</button>
          <button type="button" class="pill-toggle" data-date="week-end">Week-end</button>
        </div>
        <select id="f-ville" aria-label="Ville"><option value="">Toutes les villes</option></select>
        <select id="f-tri" aria-label="Trier par">
          <option value="distance">Trier : distance</option>
          <option value="prix">Trier : prix ↑</option>
          <option value="reduction">Trier : promo ↓</option>
          <option value="places">Trier : places</option>
          <option value="heure">Trier : heure</option>
        </select>
        <label class="dispo-toggle"><input id="f-dispo" type="checkbox" /> Dispo</label>
        <div class="view-toggle">
          <button type="button" id="view-grid" class="is-active"><i data-lucide="layout-grid"></i> Grille</button>
          <button type="button" id="view-map"><i data-lucide="map"></i> Carte</button>
        </div>
      </div>
    </div>

    <p class="offers-count uplabel muted" id="results-count">—</p>

    <div id="grid-view" class="cards-grid offers-grid"></div>

    <div id="map-view" class="map-full hidden">
      <iframe id="map-frame" title="Carte des offres" loading="lazy"
        src="https://www.openstreetmap.org/export/embed.html?bbox=4.79%2C45.74%2C4.89%2C45.78&layer=mapnik&marker=45.764%2C4.8357"></iframe>
    </div>
  </main>
