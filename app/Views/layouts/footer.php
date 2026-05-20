    </main><!-- end .flex-1 main -->

    <!-- ── RIGHT SIDEBAR ───────────────────────────────────────────────── -->
    <aside class="w-80 p-4 hidden lg:block h-screen sticky top-0 overflow-y-auto">
      <?php
        $isLoggedIn = isset($_SESSION['organizer_logged_in']) && $_SESSION['organizer_logged_in'] === true;
        $bp = defined('BASE_PATH') ? BASE_PATH : '';
      ?>
      
      <!-- Card Login / Connected status -->
      <?php if (!$isLoggedIn): ?>
      <div class="x-card p-5 mb-4 border border-x-border bg-black rounded-2xl">
        <h2 class="text-base font-bold text-white mb-2 flex items-center gap-2">
          <svg class="w-5 h-5 text-x-blue fill-current" viewBox="0 0 24 24">
            <g><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></g>
          </svg>
          Connexion Organisateur
        </h2>
        <p class="text-xs text-x-gray mb-4">Gérez vos événements et accédez aux statistiques en temps réel.</p>
        <button onclick="openLoginModal()" class="x-btn w-full py-2.5 text-[15px] shadow-lg shadow-blue-500/10">
          Se connecter
        </button>
      </div>
      <?php else: ?>
      <div class="x-card p-5 mb-4 border border-x-border bg-black rounded-2xl">
        <h2 class="text-base font-bold text-white mb-2 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
          Organisateur Connecté
        </h2>
        <p class="text-xs text-x-gray truncate mb-4"><?= htmlspecialchars($_SESSION['organizer_email']) ?></p>
        <a href="<?= $bp ?>/auth/logout" class="x-btn-outline w-full py-2 text-center text-[14px] block">
          Se déconnecter
        </a>
      </div>
      <?php endif; ?>

      <div class="x-card p-5 mb-4">
        <h2 class="text-base font-bold text-white mb-4">📊 Statistiques rapides</h2>
        <div class="space-y-4">
          <div>
            <p class="text-x-gray text-xs uppercase tracking-widest font-bold mb-1">Événements</p>
            <p class="text-2xl font-black text-white" id="stat-total-events">—</p>
          </div>
          <div>
            <p class="text-x-gray text-xs uppercase tracking-widest font-bold mb-1">Inscrits totaux</p>
            <p class="text-2xl font-black text-white" id="stat-total-regs">—</p>
          </div>
        </div>
      </div>

      <div class="x-card p-5">
        <p class="text-x-gray text-xs">
          EventHub Pro &mdash; Projet Examen PHP Avancé<br>
          ENSA Marrakech · Université Cadi Ayyad
        </p>
        <div class="flex gap-2 flex-wrap mt-3">
          <span class="text-xs text-x-blue border border-x-border px-2 py-0.5 rounded-full">PDO</span>
          <span class="text-xs text-x-blue border border-x-border px-2 py-0.5 rounded-full">mPDF</span>
          <span class="text-xs text-x-blue border border-x-border px-2 py-0.5 rounded-full">PHPMailer</span>
          <span class="text-xs text-x-blue border border-x-border px-2 py-0.5 rounded-full">MVC</span>
        </div>
      </div>
    </aside>

  </div><!-- end .max-w-7xl -->

  <!-- Toast container -->
  <div id="toast-container"></div>

  <!-- App JS — BASE_PATH set by PHP in header.php -->
  <script src="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/assets/js/app.js"></script>
</body>
</html>
