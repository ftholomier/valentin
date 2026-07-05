<?php /** @var ?string $pageScript */ ?>
  <footer class="footer">
    <img src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=2000&q=80" alt="" />
    <div class="ft-overlay"></div>
    <div class="ft-inner">
      <div class="ft-grid">
        <div class="ft-brand">
          <a href="/" class="hdr-word" style="color:#fff">LASTFIT<span class="accent-dot">.</span></a>
          <p>Le Too Good To Go du sport. On remplit les places vides — bon pour votre corps, bon pour votre budget.</p>
          <div class="ft-social">
            <a href="#"><i data-lucide="instagram"></i></a>
            <a href="#"><i data-lucide="facebook"></i></a>
            <a href="#"><i data-lucide="linkedin"></i></a>
          </div>
        </div>
        <div><h4>Découvrir</h4><ul>
          <li><a href="/resultats?sport=Yoga">Yoga à Lyon</a></li>
          <li><a href="/resultats?sport=CrossFit">CrossFit</a></li>
          <li><a href="/resultats?sport=Pilates">Pilates</a></li>
          <li><a href="/resultats">Toutes les villes</a></li></ul></div>
        <div><h4>Studios</h4><ul>
          <li><a href="/pro">Devenir partenaire</a></li>
          <li><a href="/pro">Espace pro</a></li>
          <li><a href="#">Commission</a></li></ul></div>
        <div><h4>Aide</h4><ul>
          <li><a href="/#faq">FAQ</a></li>
          <li><a href="#">Contact</a></li>
          <li><a href="#">CGU · CGV</a></li></ul></div>
      </div>
      <div class="ft-bottom">
        <p>© 2026 LastFit — Tous droits réservés</p>
        <p>Conçu pour les sportifs de dernière minute</p>
      </div>
    </div>
  </footer>

  <script src="/assets/js/api.js?v=<?= APP_VERSION ?>"></script>
  <script src="/assets/js/components.js?v=<?= APP_VERSION ?>"></script>
  <script src="/assets/js/ui.js?v=<?= APP_VERSION ?>"></script>
  <?php if (!empty($pageScript)): ?>
  <script src="/assets/js/<?= htmlspecialchars($pageScript, ENT_QUOTES) ?>?v=<?= APP_VERSION ?>"></script>
  <?php endif; ?>
</body>
</html>
