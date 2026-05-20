<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="p-4 border-b border-x-border sticky top-0 bg-x-black/80 backdrop-blur z-10">
  <h1 class="text-xl font-bold">Créer un événement</h1>
</div>

<div class="p-8 max-w-2xl mx-auto">
  <div class="x-card p-6 border border-x-border rounded-2xl">
    <div class="space-y-6">
      <div>
        <label class="block text-sm font-bold text-x-gray mb-2">Titre *</label>
        <input type="text" id="c-title" class="x-input" placeholder="Ex: Hackathon Dev 2026">
      </div>
      
      <div>
        <label class="block text-sm font-bold text-x-gray mb-2">Description *</label>
        <textarea id="c-desc" rows="3" class="x-input resize-none" placeholder="Description de l'événement..."></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-bold text-x-gray mb-2">Date *</label>
          <input type="datetime-local" id="c-date" class="x-input" style="color-scheme: dark;">
        </div>
        <div>
          <label class="block text-sm font-bold text-x-gray mb-2">Lieu *</label>
          <input type="text" id="c-loc" class="x-input" placeholder="Ex: ENSA Marrakech">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-bold text-x-gray mb-2">Capacité *</label>
          <input type="number" id="c-cap" min="1" class="x-input" placeholder="100">
        </div>
        <div>
          <label class="block text-sm font-bold text-x-gray mb-2">Catégorie *</label>
          <select id="c-cat" class="x-input bg-x-dark">
            <option value="">— Choisir —</option>
            <option value="tech">Tech</option>
            <option value="design">Design</option>
            <option value="business">Business</option>
            <option value="science">Science</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold text-x-gray mb-2">Email organisateur *</label>
        <input type="email" id="c-email" class="x-input" placeholder="admin@ensa.ma">
      </div>

      <button onclick="submitCreate()" id="btn-create" class="w-full py-3 x-btn text-[17px] mt-4">
        <span id="lbl-create">Publier l'événement</span>
      </button>
    </div>
  </div>
</div>

<script>
async function submitCreate() {
    const title = document.getElementById('c-title').value.trim();
    const desc = document.getElementById('c-desc').value.trim();
    const date = document.getElementById('c-date').value;
    const loc = document.getElementById('c-loc').value.trim();
    const cap = document.getElementById('c-cap').value;
    const cat = document.getElementById('c-cat').value;
    const email = document.getElementById('c-email').value.trim();

    if (!title || !desc || !date || !loc || !cap || !cat || !email) {
        toast('Tous les champs sont obligatoires.', 'error');
        return;
    }

    const btn = document.getElementById('btn-create');
    const lbl = document.getElementById('lbl-create');
    btn.disabled = true;
    lbl.textContent = 'Création en cours...';

    try {
        const bp = window.BASE_PATH || '';
        const res = await fetch(bp + '/api/events/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: title, description: desc, date: date,
                location: loc, capacity: cap, category: cat, organizer_email: email
            })
        });

        const data = await res.json();
        
        if (res.ok) {
            toast('Événement créé avec succès !', 'success');
            setTimeout(() => { window.location.href = bp + '/'; }, 1000);
        } else {
            toast(data.error || 'Erreur lors de la création.', 'error');
            btn.disabled = false;
            lbl.textContent = "Publier l'événement";
        }
    } catch (e) {
        toast('Erreur réseau.', 'error');
        btn.disabled = false;
        lbl.textContent = "Publier l'événement";
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
