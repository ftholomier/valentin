<?php /** @var bool $headerSolid */ ?>
<header id="site-header"<?= !empty($headerSolid) ? ' class="solid-static"' : '' ?>>
  <div class="hdr-inner">
    <a href="/" class="hdr-word">LASTFIT<span class="accent-dot">.</span></a>
    <nav class="hdr-nav uplabel">
      <a href="/#offres" class="hdr-link">Offres</a>
      <a href="/resultats" class="hdr-link">Rechercher</a>
      <a href="/#comment" class="hdr-link">Le principe</a>
      <a href="/#faq" class="hdr-link">FAQ</a>
    </nav>
    <div class="hdr-actions">
      <a href="/pro" class="hdr-pro uplabel">Inscrire ma salle</a>
      <a href="/connexion" class="hdr-login uplabel" data-auth-login>Se connecter</a>
      <a href="/mon-compte" class="hdr-login uplabel hidden" data-auth-account>Mon compte · <span data-auth-name></span></a>
    </div>
  </div>
</header>
