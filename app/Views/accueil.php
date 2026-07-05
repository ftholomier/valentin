  <!-- ============ HERO ============ -->
  <section class="hero">
    <img class="hero-img" src="https://images.unsplash.com/photo-1552196563-55cd4e45efb3?auto=format&fit=crop&w=2000&q=80" alt="Sportive en plein cours collectif" />
    <div class="hero-overlay"></div>
    <div class="hero-inner">
      <span class="uplabel hero-eyebrow">Le Too Good To Go du sport</span>
      <h1>Bougez plus.<br/>Payez moins<span class="accent-dot">.</span></h1>
      <p>Les places invendues des plus beaux studios près de chez vous, en dernière minute. Sans abonnement.</p>
    </div>
  </section>

  <!-- ============ RECHERCHE ============ -->
  <section class="search-wrap">
    <form class="search-box" id="search-form" action="/resultats" method="get">
      <label class="search-field">
        <i data-lucide="map-pin"></i>
        <span class="fmini"><span>Ville</span>
          <input name="ville" placeholder="Lyon" /></span>
      </label>
      <span class="search-sep"></span>
      <label class="search-field">
        <i data-lucide="dumbbell"></i>
        <span class="fmini"><span>Discipline</span>
          <input name="sport" placeholder="Yoga, CrossFit…" /></span>
      </label>
      <span class="search-sep"></span>
      <label class="search-field">
        <i data-lucide="calendar-clock"></i>
        <span class="fmini"><span>Quand</span>
          <select name="date">
            <option value="ce-soir">Ce soir</option>
            <option value="aujourdhui">Aujourd'hui</option>
            <option value="demain">Demain</option>
            <option value="week-end">Ce week-end</option>
          </select></span>
      </label>
      <button type="submit" class="search-submit">Rechercher <i data-lucide="arrow-right"></i></button>
    </form>
    <div class="search-meta">
      <span id="meta-studios">320+ studios</span><span class="sep">/</span>
      <span>Sans engagement</span><span class="sep">/</span>
      <span>Paiement sécurisé</span><span class="sep">/</span>
      <span>4,9 ★ · 2 400 avis</span>
    </div>
  </section>

  <!-- ============ STATEMENT ============ -->
  <section class="section">
    <div class="statement">
      <h2>Une place libre<span class="accent-dot">.</span> Une bonne affaire<span class="accent-dot">.</span> Une séance de plus<span class="accent-dot">.</span></h2>
      <p>Chaque cours collectif garde des places inoccupées. On les récupère et on vous les propose à prix cassé — bon pour votre corps, bon pour votre budget, bon pour la salle.
        <a href="#comment" class="link-arrow" style="margin-top:20px;color:var(--ink)">Comment ça marche <i data-lucide="arrow-right"></i></a>
      </p>
    </div>
  </section>

  <!-- ============ OFFRES (dynamique via API) ============ -->
  <section id="offres" class="section section-tight">
    <div class="section-head">
      <div>
        <span class="uplabel muted">Dernière minute · autour de vous</span>
        <h2 style="margin-top:8px">Les bonnes affaires du soir</h2>
      </div>
      <a href="/resultats" class="link-arrow uplabel" style="color:var(--ink)">Tout voir <i data-lucide="arrow-right"></i></a>
    </div>
    <div class="cards-grid" id="offres-grid">
      <div class="loading-wrap" style="grid-column:1/-1"><span class="spinner"></span><p style="margin-top:12px">Chargement des offres…</p></div>
    </div>
  </section>

  <!-- ============ BANDEAU ============ -->
  <section class="fullbleed">
    <img src="https://images.unsplash.com/photo-1534258936925-c58bed479fcb?auto=format&fit=crop&w=2000&q=80" alt="Salle de sport" />
    <div class="fb-overlay"></div>
    <div class="fb-inner">
      <h2>Testez tout<span class="accent-dot">.</span><br/>Sans rien signer<span class="accent-dot">.</span></h2>
      <p>Yoga lundi, boxe jeudi, Pilates dimanche. Changez de studio quand vous voulez, payez seulement les séances que vous faites.</p>
      <a href="/resultats" class="btn btn-light link-arrow" style="margin-top:32px;width:fit-content">Voir les offres <i data-lucide="arrow-right"></i></a>
    </div>
  </section>

  <!-- ============ DISCIPLINES ============ -->
  <section id="sports" class="section section-tight">
    <div class="section-head"><h2>Explorez par discipline</h2></div>
    <div class="pills" id="pills"></div>
  </section>

  <!-- ============ LE PRINCIPE ============ -->
  <section id="comment" class="section">
    <div style="border-top:1px solid var(--ink);padding-top:24px;margin-bottom:48px">
      <span class="uplabel muted">Le principe</span>
      <h2 style="margin-top:8px;font-size:clamp(32px,5vw,52px);letter-spacing:-0.045em;font-weight:600">Trois étapes. Deux minutes. Une séance.</h2>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step-num"><b>01</b><span class="rule"></span></div>
        <h3>Cherchez</h3>
        <p>Ville, discipline, créneau. On affiche en temps réel les places invendues près de vous.</p>
      </div>
      <div class="step">
        <div class="step-num"><b>02</b><span class="rule"></span></div>
        <h3>Réservez</h3>
        <p>Un tap, Apple Pay ou CB, prix réduit garanti. Aucun abonnement, aucun engagement.</p>
      </div>
      <div class="step">
        <div class="step-num"><b>03</b><span class="rule"></span></div>
        <h3>Présentez-vous</h3>
        <p>Votre QR code arrive aussitôt. La salle le scanne, vous entrez. Rien d'autre.</p>
      </div>
    </div>
  </section>

  <!-- ============ AVIS ============ -->
  <section style="border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--bg-soft)">
    <div class="section" style="display:grid;gap:48px;align-items:center;grid-template-columns:1fr">
      <blockquote style="max-width:900px">
        <div style="display:flex;gap:4px;margin-bottom:24px">
          <i data-lucide="star" style="fill:var(--ink);width:20px"></i><i data-lucide="star" style="fill:var(--ink);width:20px"></i><i data-lucide="star" style="fill:var(--ink);width:20px"></i><i data-lucide="star" style="fill:var(--ink);width:20px"></i><i data-lucide="star" style="fill:var(--ink);width:20px"></i>
        </div>
        <p style="font-family:'Archivo',sans-serif;font-weight:600;font-size:clamp(24px,3.5vw,40px);line-height:1.08;letter-spacing:-0.03em">« Quatre studios testés en un mois, pour le prix d'un seul cours. Un créneau se libère, je réserve, j'y suis 40 minutes plus tard. »</p>
        <div style="margin-top:28px;display:flex;align-items:center;gap:12px">
          <img src="https://i.pravatar.cc/80?img=32" style="width:44px;height:44px;object-fit:cover" alt="" />
          <div><p style="font-weight:600;font-size:14px">Léa Marchand</p><p class="uplabel muted">Membre · Lyon</p></div>
        </div>
      </blockquote>
    </div>
  </section>

  <!-- ============ FAQ ============ -->
  <section id="faq" class="faq" style="padding-top:80px">
    <h2 style="font-size:clamp(30px,4vw,44px);letter-spacing:-0.045em;font-weight:600;margin-bottom:32px">Questions fréquentes</h2>
    <div class="faq-list">
      <details><summary>Pourquoi les cours sont-ils moins chers ?<i data-lucide="plus"></i></summary>
        <p>Les salles nous confient uniquement les places qui seraient restées vides. Plutôt que de perdre ce revenu, elles les proposent à prix réduit en dernière minute.</p></details>
      <details><summary>Dois-je m'abonner à une salle ?<i data-lucide="plus"></i></summary>
        <p>Jamais. Vous payez à la séance, sans engagement — idéal pour tester plusieurs studios.</p></details>
      <details><summary>Comment j'accède au cours ?<i data-lucide="plus"></i></summary>
        <p>Un QR code vous est envoyé aussitôt. Présentez-le à l'accueil : il est scanné, votre entrée est validée.</p></details>
      <details><summary>Puis-je annuler ?<i data-lucide="plus"></i></summary>
        <p>Oui, gratuitement jusqu'à 2 heures avant le début du cours. Passé ce délai, la place n'est plus remboursable.</p></details>
    </div>
  </section>
