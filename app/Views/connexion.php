  <main class="page page-narrow">
    <span class="uplabel muted">Espace sportif</span>
    <h1 class="page-title" style="margin-bottom:24px">Connexion</h1>

    <div class="form-card">
      <div id="form-alert"></div>
      <form id="login-form" novalidate>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" autocomplete="email" placeholder="vous@email.fr" required />
        </div>
        <div class="field">
          <label for="password">Mot de passe</label>
          <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required />
        </div>
        <button type="submit" class="btn btn-primary btn-block" id="submit-btn">Se connecter</button>
      </form>
      <p class="muted" style="font-size:13px;margin-top:20px;text-align:center">
        Pas encore de compte ? <a href="/inscription" style="color:var(--ink);font-weight:600">Inscrivez-vous</a>
      </p>
    </div>

    <div class="alert alert-info" style="margin-top:20px">
      <b>Compte de démo :</b> lea@demo.fr · mot de passe <b>demo1234</b>
    </div>
  </main>
