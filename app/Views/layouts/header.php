<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title ?? 'EventHub Pro', ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            x: {
              black:   '#000000',
              dark:    '#15202b',
              border:  '#38444d',
              gray:    '#8899a6',
              blue:    '#1d9bf0',
              blueHover: '#1a8cd8',
              blueBg:  'rgba(29, 155, 240, 0.1)'
            }
          }
        }
      }
    }
  </script>
  <style>
    body { background-color: #000000; color: #e7e9ea;
           font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }

    .x-card { background-color: #000000; border: 1px solid #38444d;
               border-radius: 16px; transition: background-color 0.2s; }
    .x-card:hover { background-color: rgba(255,255,255,0.03); }

    .x-btn { background-color: #1d9bf0; color: white; border-radius: 9999px;
              font-weight: bold; transition: background-color 0.2s; }
    .x-btn:hover:not(:disabled) { background-color: #1a8cd8; }
    .x-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .x-btn-outline { background-color: transparent; color: #1d9bf0;
                     border: 1px solid #38444d; border-radius: 9999px;
                     font-weight: bold; transition: background-color 0.2s; }
    .x-btn-outline:hover:not(:disabled) { background-color: rgba(29,155,240,0.1); }
    .x-btn-outline:disabled { opacity: 0.4; cursor: not-allowed; }

    .x-input { background-color: transparent; border: 1px solid #38444d;
                border-radius: 4px; color: #e7e9ea; padding: 12px;
                width: 100%; outline: none; transition: border-color 0.2s; }
    .x-input:focus { border-color: #1d9bf0; box-shadow: 0 0 0 1px #1d9bf0; }

    .nav-link { color: #8899a6; font-weight: bold; padding: 12px;
                transition: background-color 0.2s, color 0.2s;
                border-radius: 9999px; display: flex; align-items: center; gap: 16px;
                font-size: 1.25rem; text-decoration: none; }
    .nav-link:hover { background-color: rgba(255,255,255,0.1); color: #e7e9ea; }
    .nav-link.active { color: #e7e9ea; }

    #toast-container { position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
                        z-index:999; display:flex; flex-direction:column; gap:10px; }
    .toast { min-width:300px; padding:12px 16px; border-radius:4px; color:#fff;
              font-size:15px; box-shadow:0 0 10px rgba(255,255,255,0.1);
              animation:slideUp .3s ease; text-align:center; }
    @keyframes slideUp { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
    .toast.success { background:#1d9bf0; }
    .toast.error   { background:#f4212e; }
    .toast.info    { background:#38444d; }

    .modal-overlay { position:fixed; inset:0; z-index:50;
                     background:rgba(91, 112, 131, 0.4); backdrop-filter:blur(4px);
                     display:flex; align-items:center; justify-content:center; }
    .modal-box { background:#000000; border:1px solid #38444d; border-radius:16px;
                 width:min(600px,94vw); max-height:90vh; overflow-y:auto;
                 padding:32px; position:relative; animation:popIn .2s ease; }
    @keyframes popIn { from{transform:scale(.95);opacity:0} to{transform:scale(1);opacity:1} }

    .cap-bar      { height:4px; background:#38444d; border-radius:2px; overflow:hidden; margin-top:8px; }
    .cap-bar-fill { height:100%; transition:width .6s ease; }

    .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

    #main-sidebar.collapsed { width: 5rem; }
    #main-sidebar.collapsed .nav-text { display: none; }
    #main-sidebar.collapsed .nav-link { justify-content: center; padding: 12px; }
  </style>
  <script>window.BASE_PATH = '<?= defined('BASE_PATH') ? BASE_PATH : '' ?>';</script>
</head>
<body class="flex justify-center min-h-screen">

  <div class="w-full max-w-7xl flex">

    <header id="main-sidebar" class="w-64 border-r border-x-border p-4 hidden md:flex flex-col h-screen sticky top-0 transition-all duration-300">
      <div class="mb-4 p-2 flex items-center justify-between">
        <svg viewBox="0 0 24 24" class="h-8 w-8 fill-current text-[#1d9bf0] flex-shrink-0" aria-label="EventHub Pro">
          <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm-5-7h-2v2h2v-2zm-4 0H8v2h2v-2zm8 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm-4 0H8v2h2v-2zm8 0h-2v2h2v-2z"/>
        </svg>
        <button id="sidebar-toggle" class="p-1 hover:bg-white/10 rounded-full focus:outline-none transition" aria-label="Toggle Sidebar">
          <svg viewBox="0 0 24 24" class="h-6 w-6 fill-current text-x-gray hover:text-white">
            <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
          </svg>
        </button>
      </div>

      <nav class="flex flex-col gap-1">
        <?php
          $page = $currentPage ?? 'events';
          $bp   = defined('BASE_PATH') ? BASE_PATH : '';
          $isLoggedIn = isset($_SESSION['organizer_logged_in']) && $_SESSION['organizer_logged_in'] === true;
        ?>
        <a href="<?= $bp ?>/" class="nav-link <?= $page === 'events'    ? 'active' : '' ?>" title="Événements">
          <svg viewBox="0 0 24 24" class="h-7 w-7 fill-current flex-shrink-0">
            <g><path d="M12 1.696L.622 8.807l1.06 1.696L3 9.679V19.5C3 20.881 4.119 22 5.5 22h13c1.381 0 2.5-1.119 2.5-2.5V9.679l1.318.824 1.06-1.696L12 1.696z"/></g>
          </svg>
          <span class="nav-text">Événements</span>
        </a>
        <a href="<?= $bp ?>/dashboard" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
          <svg viewBox="0 0 24 24" class="h-7 w-7 fill-current flex-shrink-0">
            <g><path d="M3 3h18v2H3V3zm4 14h10v2H7v-2zm14-7H3v2h18v-2z"/></g>
          </svg>
          <span class="nav-text">Dashboard</span>
        </a>

        <?php if ($isLoggedIn): ?>
        <a href="<?= $bp ?>/create" class="nav-link <?= $page === 'create'    ? 'active' : '' ?>" title="Créer">
          <svg viewBox="0 0 24 24" class="h-7 w-7 fill-current flex-shrink-0">
            <g><path d="M11 11V4h2v7h7v2h-7v7h-2v-7H4v-2h7z"/></g>
          </svg>
          <span class="nav-text">Créer</span>
        </a>
        <a href="<?= $bp ?>/auth/logout" class="nav-link text-red-400 hover:bg-red-500/10" title="Déconnexion">
          <svg viewBox="0 0 24 24" class="h-7 w-7 fill-current flex-shrink-0 text-red-400">
            <g><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></g>
          </svg>
          <span class="nav-text">Déconnexion</span>
        </a>
        <?php else: ?>
        <button onclick="openLoginModal()" class="nav-link w-full text-left" title="Se connecter">
          <svg viewBox="0 0 24 24" class="h-7 w-7 fill-current flex-shrink-0">
            <g><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></g>
          </svg>
          <span class="nav-text">Se connecter</span>
        </button>
        <?php endif; ?>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('main-sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            if (sidebar && toggleBtn) {
                const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
                if (isCollapsed) sidebar.classList.add('collapsed');
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
                });
            }
        });
        </script>
      </nav>
    </header>

    <div id="modal-login" class="modal-overlay hidden">
      <div class="modal-box border border-x-border" style="max-width:390px">
        <button onclick="closeLoginModal()" class="absolute top-4 right-4 h-8 w-8 rounded-full hover:bg-x-border flex items-center justify-center transition">
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current text-e7e9ea"><g><path d="M13.414 12l5.793-5.793c.39-.39.39-1.023 0-1.414s-1.023-.39-1.414 0L12 10.586 6.207 4.793c-.39-.39-1.023-.39-1.414 0s-.39 1.023 0 1.414L10.586 12l-5.793 5.793c-.39.39-.39 1.023 0 1.414.195.195.45.293.707.293s.512-.098.707-.293L12 13.414l5.793 5.793c.195.195.45.293.707.293s.512-.098.707-.293c.39-.39.39-1.023 0-1.414L13.414 12z"></path></g></svg>
        </button>
        <h3 class="text-xl font-bold mb-4 text-white">Connexion Organisateur</h3>

        <form id="modal-login-form" onsubmit="submitLogin(event)" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-x-gray uppercase tracking-widest mb-1">Email</label>
            <input type="email" id="login-email" class="x-input" placeholder="admin@ensa.ma" value="admin@ensa.ma" required autocomplete="email">
          </div>
          <div>
            <label class="block text-xs font-bold text-x-gray uppercase tracking-widest mb-1">Mot de passe</label>
            <input type="password" id="login-password" class="x-input" placeholder="••••••••" required autocomplete="current-password">
          </div>
          <button type="submit" id="btn-login-submit" class="w-full py-3 x-btn text-[17px] shadow-lg shadow-blue-500/20">
            <span id="lbl-login-submit">Se connecter</span>
          </button>
        </form>
      </div>
    </div>

    <script>
    function openLoginModal() {
        document.getElementById('modal-login')?.classList.remove('hidden');
    }
    function closeLoginModal() {
        document.getElementById('modal-login')?.classList.add('hidden');
        const pwd = document.getElementById('login-password');
        if (pwd) pwd.value = '';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const ml = document.getElementById('modal-login');
        if (ml) {
            ml.addEventListener('click', e => {
                if (e.target === e.currentTarget) closeLoginModal();
            });
        }
    });

    async function submitLogin(e) {
        e.preventDefault();
        const email = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;
        const btn = document.getElementById('btn-login-submit');
        const lbl = document.getElementById('lbl-login-submit');

        if (!email || !password) {
            if (typeof toast === 'function') toast('Veuillez remplir tous les champs.', 'error');
            return;
        }

        if (btn && lbl) {
            btn.disabled = true;
            lbl.textContent = 'Connexion…';
        }

        try {
            const bp = window.BASE_PATH || '';
            const response = await fetch(`${bp}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                if (typeof toast === 'function') toast('✅ Connexion réussie !', 'success');
                closeLoginModal();
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                if (typeof toast === 'function') {
                    toast(data.error || 'Identifiants incorrects.', 'error');
                } else {
                    alert(data.error || 'Identifiants incorrects.');
                }
            }
        } catch (err) {
            console.error('[Login Error]', err);
            if (typeof toast === 'function') {
                toast('Une erreur réseau est survenue.', 'error');
            } else {
                alert('Une erreur réseau est survenue.');
            }
        } finally {
            if (btn && lbl) {
                btn.disabled = false;
                lbl.textContent = 'Se connecter';
            }
        }
    }
    </script>

    <main class="flex-1 border-r border-x-border min-h-screen">

