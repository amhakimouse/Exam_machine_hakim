const BASE = window.BASE_PATH || '';

let currentEvents   = [];
let debTimer        = null;
let selectedEvent   = null;
let dashTimer       = null;
let prevEventStates = {};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('events-grid'))   loadEvents();
    if (document.getElementById('dashboard-kpi')) { window._dashStarted = true; startDash(); }
});

function toast(msg, type = 'info') {
    const c = document.getElementById('toast-container');
    if (!c) return;
    const t = document.createElement('div');
    t.className   = `toast ${type}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => {
        t.style.cssText = 'opacity:0;transform:translateY(100%);transition:all .3s';
        setTimeout(() => t.remove(), 300);
    }, 4000);
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        weekday: 'short', day: 'numeric', month: 'short',
        hour: '2-digit', minute: '2-digit',
    });
}

function animateNumber(id, target) {
    const el = document.getElementById(id);
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    const diff  = target - start;
    const steps = 20;
    let   i     = 0;
    const iv = setInterval(() => {
        i++;
        el.textContent = Math.round(start + diff * (i / steps));
        if (i >= steps) { el.textContent = target; clearInterval(iv); }
    }, 20);
}

async function loadEvents() {
    const grid = document.getElementById('events-grid');
    if (!grid) return;

    const kw  = document.getElementById('search-input')?.value  || '';
    const cat = document.getElementById('filter-cat')?.value    || '';

    grid.innerHTML = '<div class="p-8 text-center text-x-gray animate-pulse">Chargement des événements…</div>';

    try {
        const res = await fetch(`${BASE}/api/events`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ keyword: kw, category: cat }),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data   = await res.json();
        currentEvents = Array.isArray(data) ? data : [];
        renderEvents(currentEvents);

        const statTotal = document.getElementById('stat-total-events');
        const statRegs  = document.getElementById('stat-total-regs');
        if (statTotal) statTotal.textContent = currentEvents.length;
        if (statRegs)  statRegs.textContent  = currentEvents.reduce(
            (s, e) => s + parseInt(e.registered_count || 0), 0
        );

    } catch (err) {
        grid.innerHTML = `<div class="p-8 text-center text-red-400">
            ⚠️ Erreur de chargement. <button onclick="loadEvents()" class="underline">Réessayer</button>
        </div>`;
        console.error('[loadEvents]', err);
    }
}

function debounceSearch() {
    clearTimeout(debTimer);
    debTimer = setTimeout(loadEvents, 400);
}

function renderEvents(events) {
    const grid = document.getElementById('events-grid');
    if (!events.length) {
        grid.innerHTML = `<div class="p-12 text-center text-x-gray">
            <div class="text-5xl mb-4">🔍</div>
            <p class="font-bold text-white">Aucun événement trouvé</p>
            <p class="text-sm mt-1">Modifiez vos filtres pour voir plus de résultats.</p>
        </div>`;
        return;
    }

    const catColors = {
        tech:     'text-blue-400',
        design:   'text-purple-400',
        business: 'text-yellow-400',
        science:  'text-green-400',
    };

    grid.innerHTML = events.map(e => {
        const capacity   = parseInt(e.capacity   || 0);
        const registered = parseInt(e.registered_count || 0);
        const pct     = capacity > 0 ? Math.min(Math.round(registered / capacity * 100), 100) : 100;
        const full    = registered >= capacity;
        const warn    = pct >= 80 && !full;
        const barClass = full ? 'bg-red-500' : warn ? 'bg-yellow-400' : 'bg-x-blue';
        const catColor = catColors[e.category] || 'text-x-gray';
        const initial  = (e.title || '?')[0].toUpperCase();

        return `
        <div class="x-card mx-3 my-2 p-4 flex gap-4" data-event-id="${e.id}">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-full flex items-center justify-center
                        text-lg font-black select-none"
                 style="background:rgba(29,155,240,.15);color:#1d9bf0">
              ${initial}
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-1">
              <span class="font-bold text-white">${e.title}</span>
              <span class="${catColor} text-sm">@${e.category}</span>
              ${warn ? '<span class="text-xs text-yellow-400 font-bold">🔥 Quasi plein</span>' : ''}
              ${full ? '<span class="text-xs text-red-400 font-bold">· Complet</span>'         : ''}
            </div>

            <p class="text-x-gray text-sm mb-3 line-clamp-2">${e.description}</p>

            <div class="flex flex-wrap gap-x-4 gap-y-1 text-x-gray text-sm mb-3">
              <span>📅 ${formatDate(e.date)}</span>
              <span>📍 ${e.location}</span>
              <span class="${full ? 'text-red-400' : warn ? 'text-yellow-400' : ''}">
                👥 <span id="reg-count-${e.id}">${registered}</span> / ${capacity}
              </span>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex-1 cap-bar">
                <div class="cap-bar-fill ${barClass}" id="bar-${e.id}" style="width:${pct}%"></div>
              </div>
              <button
                onclick="openReg(${e.id})"
                id="btn-ev-${e.id}"
                class="px-4 py-1.5 rounded-full font-bold text-sm flex-shrink-0 transition
                       ${full ? 'bg-x-border text-x-gray cursor-not-allowed' : 'x-btn-outline'}"
                ${full ? 'disabled' : ''}>
                ${full ? 'Complet' : "S'inscrire"}
              </button>
            </div>
          </div>
        </div>`;
    }).join('');
}

function openReg(id) {
    selectedEvent = currentEvents.find(e => e.id == id);
    if (!selectedEvent) return;

    const capacity   = parseInt(selectedEvent.capacity   || 0);
    const registered = parseInt(selectedEvent.registered_count || 0);
    const pct = capacity > 0 ? Math.min(Math.round(registered / capacity * 100), 100) : 100;

    document.getElementById('m-title').textContent  = selectedEvent.title;
    document.getElementById('m-info').textContent   =
        `${formatDate(selectedEvent.date)} · ${selectedEvent.location}`;
    document.getElementById('m-places').textContent = `${registered} / ${capacity}`;

    const bar      = document.getElementById('m-bar');
    bar.style.width = pct + '%';
    bar.className   = `cap-bar-fill ${pct >= 100 ? 'bg-red-500' : pct >= 80 ? 'bg-yellow-400' : 'bg-x-blue'}`;

    document.getElementById('modal-reg').classList.remove('hidden');
}

function closeReg() {
    document.getElementById('modal-reg').classList.add('hidden');
    selectedEvent = null;
    document.getElementById('r-name').value  = '';
    document.getElementById('r-email').value = '';
}

async function submitReg() {
    if (!selectedEvent) return;

    const name  = document.getElementById('r-name').value.trim();
    const email = document.getElementById('r-email').value.trim();
    if (!name || !email) { toast('Veuillez remplir tous les champs.', 'error'); return; }

    const btn = document.getElementById('btn-reg');
    const lbl = document.getElementById('lbl-reg');
    btn.disabled    = true;
    lbl.textContent = 'Inscription en cours…';

    try {
        const res = await fetch(`${BASE}/api/events/register`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                event_id:   selectedEvent.id,
                user_name:  name,
                user_email: email,
            }),
        });
        const data = await res.json();

        if (res.ok) {
            const regCountEl = document.getElementById(`reg-count-${selectedEvent.id}`);
            const barEl      = document.getElementById(`bar-${selectedEvent.id}`);
            const btnEl      = document.getElementById(`btn-ev-${selectedEvent.id}`);
            const newCount   = data.registered_count;
            const cap        = data.capacity;
            const newPct     = Math.min(Math.round(newCount / cap * 100), 100);

            if (regCountEl) regCountEl.textContent = newCount;
            if (barEl) {
                barEl.style.width = newPct + '%';
                barEl.className   = `cap-bar-fill ${newPct >= 100 ? 'bg-red-500' : newPct >= 80 ? 'bg-yellow-400' : 'bg-x-blue'}`;
            }
            if (btnEl && newCount >= cap) {
                btnEl.disabled   = true;
                btnEl.textContent = 'Complet';
                btnEl.className  = btnEl.className.replace('x-btn-outline', 'bg-x-border text-x-gray cursor-not-allowed');
            }

            const ev = currentEvents.find(e => e.id == selectedEvent.id);
            if (ev) ev.registered_count = newCount;

            toast('✅ Inscription réussie ! Votre ticket PDF arrive par email.', 'success');

            if (data.alert_80) {
                const pct = Math.round(newCount / cap * 100);
                setTimeout(() =>
                    toast(`⚠️ Alerte : ${selectedEvent.title} est à ${pct}% — email envoyé à l'organisateur.`, 'info'),
                    500
                );
            }

            closeReg();

        } else {
            toast(data.error || "Erreur lors de l'inscription.", 'error');
        }

    } catch (err) {
        toast('Erreur réseau. Vérifiez votre connexion.', 'error');
        console.error('[submitReg]', err);
    } finally {
        btn.disabled    = false;
        lbl.textContent = "S'inscrire & recevoir PDF";
    }
}

function startDash() {
    fetchStats();
    if (dashTimer) clearInterval(dashTimer);
    dashTimer = setInterval(fetchStats, 30_000);
}

async function fetchStats() {
    try {
        const res = await fetch(`${BASE}/api/stats`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        renderStats(data);
    } catch (err) {
        console.error('[fetchStats] error — will retry in 10 s:', err);
        setTimeout(fetchStats, 10_000);
    }
}

function renderStats(data) {

    animateNumber('dash-total',  data.total_registrations || 0);
    animateNumber('dash-new24',  data.new_24h             || 0);

    const tauxEl  = document.getElementById('dash-taux');
    const alertEl = document.getElementById('dash-alert');
    if (tauxEl)  tauxEl.textContent  = (data.avg_fill_rate || 0) + '%';
    if (alertEl) alertEl.textContent = data.events_at_80   || 0;

    const lastEl = document.getElementById('last-update');
    if (lastEl) lastEl.textContent = 'Mis à jour à ' + (data.generated_at || '—');

    const topList = document.getElementById('top-list');
    if (!topList || !Array.isArray(data.top_events)) return;

    const medals = ['🥇', '🥈', '🥉'];

    topList.innerHTML = data.top_events.map((e, i) => {
        const pct  = parseInt(e.fill_rate || 0);
        const prev = prevEventStates[e.id];

        if (prev !== undefined && prev < 100 && pct >= 100) {
            toast(`🎉 "${e.title}" vient d'atteindre 100% — complet !`, 'success');
        }
        prevEventStates[e.id] = pct;

        const barColor = pct >= 100 ? '#ef4444' : pct >= 80 ? '#f59e0b' : '#1d9bf0';

        return `
        <div class="flex items-center gap-4 p-4 rounded-xl border border-x-border
                    hover:bg-white/[0.03] transition">
          <span class="text-2xl w-8 text-center flex-shrink-0 select-none">
            ${medals[i] ?? (i + 1)}
          </span>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2 mb-2">
              <p class="font-bold text-sm text-white leading-tight truncate">
                ${e.title}
              </p>
              <span class="text-xs font-black flex-shrink-0 px-2 py-0.5 rounded-full border"
                    style="color:${barColor};border-color:${barColor}30;background:${barColor}18">
                ${pct}%
              </span>
            </div>
            <div class="cap-bar">
              <div class="cap-bar-fill"
                   style="width:${pct}%;background:${barColor};transition:width .8s ease">
              </div>
            </div>
            <p class="text-x-gray text-xs mt-1.5">
              ${e.registered_count} / ${e.capacity} inscrits · @${e.category}
            </p>
          </div>
        </div>`;
    }).join('');
}
