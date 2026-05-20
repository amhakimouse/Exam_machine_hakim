<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="p-4 border-b border-x-border sticky top-0 bg-black/80 backdrop-blur-md z-10">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-white">Dashboard Organisateur</h1>
      <p class="text-x-gray text-xs mt-0.5">
        Actualisation toutes les <strong class="text-x-blue">30 secondes</strong>
      </p>
    </div>
    <div class="flex items-center gap-2 border border-x-border rounded-full px-3 py-1.5">
      <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
      <span id="last-update" class="text-xs text-x-gray">En attente…</span>
    </div>
  </div>
</div>

<div id="dashboard-kpi" class="grid grid-cols-2 border-b border-x-border">
  <div class="p-6 border-r border-b border-x-border hover:bg-white/[0.02] transition group">
    <p class="text-x-gray text-xs font-bold uppercase tracking-widest mb-3">Total inscrits</p>
    <div class="text-4xl font-black text-white" id="dash-total">—</div>
    <p class="text-x-gray text-xs mt-2">tous événements confondus</p>
  </div>

  <div class="p-6 border-b border-x-border hover:bg-white/[0.02] transition group">
    <p class="text-x-gray text-xs font-bold uppercase tracking-widest mb-3">Nouvelles 24h</p>
    <div class="text-4xl font-black text-green-400" id="dash-new24">—</div>
    <p class="text-x-gray text-xs mt-2">inscriptions récentes</p>
  </div>

  <div class="p-6 border-r border-x-border hover:bg-white/[0.02] transition group">
    <p class="text-x-gray text-xs font-bold uppercase tracking-widest mb-3">Taux moyen</p>
    <div class="text-4xl font-black text-x-blue" id="dash-taux">—</div>
    <p class="text-x-gray text-xs mt-2">de remplissage</p>
  </div>

  <div class="p-6 hover:bg-white/[0.02] transition group">
    <p class="text-x-gray text-xs font-bold uppercase tracking-widest mb-3">Alertes 80%</p>
    <div class="text-4xl font-black text-yellow-400" id="dash-alert">—</div>
    <p class="text-x-gray text-xs mt-2">événements quasi complets</p>
  </div>
</div>

<div class="p-4 border-b border-x-border">
  <h2 class="text-base font-bold text-white mb-4">🏆 Top 3 — Événements</h2>
  <div id="top-list" class="flex flex-col gap-3">
    <?php for ($i = 0; $i < 3; $i++): ?>
    <div class="animate-pulse flex items-center gap-4 p-4 rounded-xl border border-x-border">
      <div class="w-8 h-8 rounded bg-x-border flex-shrink-0"></div>
      <div class="flex-1 space-y-2">
        <div class="h-3 rounded bg-x-border" style="width:<?= [70,55,80][$i] ?>%"></div>
        <div class="h-2 rounded bg-x-border w-full"></div>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<div class="p-4 flex items-center gap-3 text-x-gray text-sm border-b border-x-border">
  <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current flex-shrink-0 text-x-blue">
    <g><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></g>
  </svg>
  <span>Données rafraîchies automatiquement. Notification en cas de complet (100%).</span>
</div>

<script>
  if (typeof startDash === 'function' && !window._dashStarted) {
    window._dashStarted = true;
    startDash();
  }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
