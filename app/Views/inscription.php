  <main class="page page-narrow">
    <span class="uplabel muted">Rejoignez LastFit</span>
    <h1 class="page-title" style="margin-bottom:24px">Créer un compte</h1>

    <div class="form-card">
      <div id="form-alert"></div>
      <form id="register-form" novalidate>
        <div class="field">
          <label for="nom">Nom complet</label>
          <input id="nom" name="nom" type="text" autocomplete="name" placeholder="Prénom Nom" required />
          <div class="err hidden" data-err="nom"></div>
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" autocomplete="email" placeholder="vous@email.fr" required />
          <div class="err hidden" data-err="email"></div>
        </div>
        <div class="field">
          <label for="ville">Ville</label>
          <input id="ville" name="ville" type="text" placeholder="Lyon" />
        </div>
        <div class="field">
          <label for="password">Mot de passe</label>
          <input id="password" name="password" type="password" autocomplete="new-password" placeholder="6 caractères minimum" required />
          <div class="err hidden" data-err="password"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" id="submit-btn">Créer mon compte</button>
      </form>
      <p class="muted" style="font-size:13px;margin-top:20px;text-align:center">
        Déjà inscrit ? <a href="/connexion" style="color:var(--ink);font-weight:600">Connectez-vous</a>
      </p>
    </div>
  </main>
