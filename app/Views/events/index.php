<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="p-4 border-b border-x-border sticky top-0 bg-x-black/80 backdrop-blur z-10">
  <h1 class="text-xl font-bold">Événements</h1>
</div>

<div class="p-4 border-b border-x-border flex gap-4 flex-col sm:flex-row">
  <div class="flex-1 relative">
    <svg viewBox="0 0 24 24" class="h-5 w-5 absolute left-3 top-3 text-x-gray fill-current"><g><path d="M10.25 3.75c-3.59 0-6.5 2.91-6.5 6.5s2.91 6.5 6.5 6.5c1.795 0 3.419-.726 4.596-1.904 1.178-1.177 1.904-2.801 1.904-4.596 0-3.59-2.91-6.5-6.5-6.5zm-8.5 6.5c0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.986-.682 3.815-1.824 5.262l4.781 4.781-1.414 1.414-4.781-4.781c-1.447 1.142-3.276 1.824-5.262 1.824-4.694 0-8.5-3.806-8.5-8.5z"></path></g></svg>
    <input type="text" id="search-input" placeholder="Rechercher des événements" class="x-input pl-10 rounded-full bg-x-dark border-transparent focus:bg-x-black" oninput="debounceSearch()">
  </div>
  <select id="filter-cat" class="x-input w-full sm:w-auto rounded-full bg-x-dark border-transparent" onchange="loadEvents()">
    <option value="">Toutes catégories</option>
    <option value="tech">Tech</option>
    <option value="design">Design</option>
    <option value="business">Business</option>
    <option value="science">Science</option>
  </select>
</div>

<div id="events-grid" class="flex flex-col">
  <div class="p-8 text-center text-x-gray">Chargement des événements...</div>
</div>

<div id="modal-reg" class="modal-overlay hidden">
  <div class="modal-box border border-x-border">
    <button onclick="closeReg()" class="absolute top-4 right-4 h-8 w-8 rounded-full hover:bg-x-border flex items-center justify-center transition">
        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current text-e7e9ea"><g><path d="M13.414 12l5.793-5.793c.39-.39.39-1.023 0-1.414s-1.023-.39-1.414 0L12 10.586 6.207 4.793c-.39-.39-1.023-.39-1.414 0s-.39 1.023 0 1.414L10.586 12l-5.793 5.793c-.39.39-.39 1.023 0 1.414.195.195.45.293.707.293s.512-.098.707-.293L12 13.414l5.793 5.793c.195.195.45.293.707.293s.512-.098.707-.293c.39-.39.39-1.023 0-1.414L13.414 12z"></path></g></svg>
    </button>
    <h2 class="text-xl font-bold mb-2" id="m-title">Inscription</h2>
    <p class="text-x-gray mb-6" id="m-info">Détails</p>
    
    <div class="mb-4">
      <input type="text" id="r-name" class="x-input" placeholder="Nom complet">
    </div>
    <div class="mb-6">
      <input type="email" id="r-email" class="x-input" placeholder="Adresse email">
    </div>
    
    <div class="mb-6 p-4 border border-x-border rounded-xl">
      <div class="flex justify-between text-sm font-bold mb-1">
        <span class="text-x-gray">Places</span>
        <span id="m-places" class="text-e7e9ea">-</span>
      </div>
      <div class="cap-bar"><div class="cap-bar-fill bg-x-blue" id="m-bar" style="width: 0%;"></div></div>
    </div>
    
    <button onclick="submitReg()" id="btn-reg" class="w-full py-3 x-btn text-[17px]">
      <span id="lbl-reg">S'inscrire & recevoir PDF</span>
    </button>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
