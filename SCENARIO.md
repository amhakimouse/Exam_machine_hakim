# SCENARIO.md — EventHub Pro
## End-to-End Flow Documentation
**Projet :** Examen PHP Avancé — EventHub Pro  
**Architecture :** MVC natif (Bonus +5 pts)  
**ENSA Marrakech · Université Cadi Ayyad · 2025-2026**

---

## 1. Stack Technique

| Composant | Technologie |
|-----------|-------------|
| Architecture | MVC natif PHP 8.x |
| Base de données | MySQL (PDO, prepared statements, transactions) |
| Email | PHPMailer 7.x via SMTP Gmail |
| PDF | mPDF 8.x (tickets + rapports) |
| Frontend | Vanilla JS (Fetch API, debounce, animations) |
| UI Theme | X/Twitter Dark (TailwindCSS CDN) |
| Dépendances | Composer (vlucas/phpdotenv, mpdf/mpdf, phpmailer/phpmailer) |

---

## 2. Structure MVC

```
Exam_machine_hakim/
├── public/                  ← Document root Apache
│   ├── index.php            ← Front Controller (point d'entrée unique)
│   ├── .htaccess            ← Réécriture URL → index.php
│   └── assets/js/app.js    ← JavaScript côté client
├── core/
│   ├── Router.php           ← Routeur HTTP (méthode + URI)
│   ├── Controller.php       ← Classe de base (view(), json())
│   └── Database.php         ← Singleton PDO
├── app/
│   ├── Controllers/
│   │   ├── EventController.php      ← Pages HTML événements
│   │   ├── DashboardController.php  ← Page HTML dashboard
│   │   ├── ApiController.php        ← Endpoints JSON (search, register, createEvent, stats)
│   │   ├── MailController.php       ← Emails HTML (confirmation + alerte 80%)
│   │   └── PdfController.php        ← Génération PDF (ticket + rapport organisateur)
│   ├── Models/
│   │   ├── EventModel.php           ← CRUD événements (PDO sécurisé)
│   │   └── RegistrationModel.php    ← Inscriptions (transaction + FOR UPDATE)
│   └── Views/
│       ├── layouts/header.php       ← Layout global (nav, CSS, BASE_PATH)
│       ├── layouts/footer.php       ← Layout global (sidebar, JS)
│       ├── events/index.php         ← Liste des événements + modal
│       ├── events/create.php        ← Formulaire de création
│       └── dashboard/index.php      ← Dashboard organisateur
├── services/
│   ├── mailer.php           ← Fonction sendEmail() (PHPMailer wrapper)
│   └── pdf_service.php      ← Fonction generatePDF() (mPDF wrapper)
├── database/
│   └── schema.sql           ← Tables + données de démonstration
├── storage/tmp/             ← PDFs temporaires (supprimés après envoi)
└── .env                     ← Credentials DB + SMTP (jamais commité)
```

---

## 3. Configuration Initiale (Manuel)

### 3.1 Base de données (phpMyAdmin EasyPHP)
1. Ouvrir **EasyPHP Devserver → Administration → phpMyAdmin**
2. Cliquer **Importer** → Sélectionner `database/schema.sql`
3. Cliquer **Exécuter**

Cela crée :
- Base `eventhub_pro`
- Tables `events` et `registrations` avec contraintes et indexes
- 3 événements + 5 inscriptions de démonstration

### 3.2 Variables d'environnement (`.env`)
```env
DB_HOST=127.0.0.1
DB_NAME=eventhub_pro
DB_USER=root
DB_PASS=

SMTP_HOST=smtp.gmail.com
SMTP_USER=email@gmail.com
SMTP_PASS=xxxx-xxxx-xxxx-xxxx   # App Password Gmail (16 caractères)
SMTP_PORT=587
SMTP_SECURE=tls

FROM_EMAIL=email@gmail.com
FROM_NAME="EventHub Pro"
```

> **App Password Gmail :** myaccount.google.com → Sécurité → Mots de passe d'application

### 3.3 mod_rewrite Apache (EasyPHP)
Vérifier que `mod_rewrite` est activé dans `httpd.conf` :
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```
Et que le dossier `public/` a `AllowOverride All`.

---

## 4. Scénario de Démonstration Complet

### Scénario A — Recherche et filtrage d'événements (Partie 1.3 + 4.1)

1. Ouvrir `http://127.0.0.1/Exam_machine_hakim/public/`
2. La page charge → **Fetch API** envoie `POST /api/events` (body: `{keyword:"", category:""}`)
3. `ApiController::searchEvents()` → `EventModel::search()` → requête PDO préparée
4. Les cartes s'affichent avec : titre, catégorie, jauge de capacité, bouton S'inscrire
5. **Taper "DevFest"** dans la recherche → debounce 400 ms → nouvelle requête → seul DevFest apparaît
6. **Sélectionner "Tech"** dans le filtre catégorie → résultats filtrés instantanément

---

### Scénario B — Inscription + Email + PDF Ticket (Partie 2.1 + 5)

1. Cliquer **"S'inscrire"** sur un événement → modal s'ouvre
2. Saisir : Nom = `Test Étudiant`, Email = `test@ensa.ma`
3. Cliquer **"S'inscrire & recevoir PDF"**
4. **Côté serveur :**
   ```
   POST /api/events/register
   ↓
   ApiController::register()
   ↓ validate inputs (FILTER_VALIDATE_INT, FILTER_VALIDATE_EMAIL)
   ↓
   RegistrationModel::register()
   ├── Vérif doublon (SELECT)
   ├── beginTransaction()
   ├── SELECT ... FOR UPDATE (verrouille la ligne events)
   ├── Vérif capacité
   ├── INSERT registration + token = bin2hex(random_bytes(16))
   └── commit()
   ↓
   PdfController::generateTicket()
   ├── HTML avec QR code (api.qrserver.com)
   └── mPDF → storage/tmp/ticket_[token].pdf
   ↓
   MailController::sendConfirmation()
   ├── PHPMailer SMTP → walid.bouarifi@gmail.com
   ├── Corps HTML (X dark theme)
   └── Pièce jointe : ticket_[token].pdf
   ↓
   PdfController::cleanup() → supprime le fichier tmp
   ↓
   JSON: { success:true, registered_count, capacity, alert_80 }
   ```
5. **Côté client :**
   - Le compteur de la carte se met à jour **sans rechargement de page**
   - Toast vert : "✅ Inscription réussie ! Votre ticket PDF arrive par email."
   - Si `alert_80 = true` → toast bleu supplémentaire (alerte organisateur)
6. **Email reçu** sur `walid.bouarifi@gmail.com` avec le ticket PDF en pièce jointe

---

### Scénario C — Alerte 80% Capacité (Partie 2.2 + 5)

**Conditions de déclenchement :** `registered_count / capacity >= 0.80`

1. Inscrire suffisamment d'utilisateurs jusqu'à atteindre 80% d'un événement
2. **Côté serveur (automatique après chaque inscription) :**
   ```
   if (fillRate >= 0.80):
     PdfController::generateOrganizerReport()
     ├── HTML avec tableau de stats + graphique à barres (10 colonnes HTML)
     └── mPDF → storage/tmp/report_event[id]_[timestamp].pdf
     ↓
     MailController::sendCapacityAlert()
     ├── Sujet : "⚠️ Alerte capacité 80% — [Titre]"
     ├── Corps HTML (en-tête ambré, carte stats, mini bar chart)
     └── Pièce jointe : rapport_organisateur.pdf
     ↓
     PdfController::cleanup() → supprime le fichier tmp
   ```
3. Email reçu sur `walid.bouarifi@gmail.com` avec rapport PDF en pièce jointe

---

### Scénario D — Création d'événement (Partie 1.2 + 4.1)

1. Cliquer **"Créer"** dans la navigation
2. Remplir le formulaire (tous les champs obligatoires)
3. Cliquer **"Publier l'événement"**
4. **Côté serveur :**
   ```
   POST /api/events/create
   ↓
   ApiController::createEvent()
   ├── Validation : FILTER_VALIDATE_INT (capacity)
   ├── Validation : FILTER_VALIDATE_EMAIL (organizer_email)
   ├── Validation : DateTime::createFromFormat (date)
   ├── Whitelist : category in ['tech','design','business','science']
   ├── Sanitisation : htmlspecialchars + strip_tags
   └── EventModel::create() → INSERT préparé
   ↓
   JSON: { success:true, event_id:N }
   ```
5. Redirection automatique vers `/` — le nouvel événement apparaît dans la liste

---

### Scénario E — Dashboard Temps Réel (Partie 4.2)

1. Cliquer **"Dashboard"** dans la navigation
2. Page se charge → `DashboardController::index()` → vue dashboard/index.php
3. **`DOMContentLoaded` → `startDash()`** → premier appel immédiat à `fetchStats()`
4. **`GET /api/stats`** retourne :
   ```json
   {
     "total_registrations": 12,
     "new_24h": 3,
     "avg_fill_rate": 67,
     "events_at_80": 1,
     "top_events": [
       { "title": "DevFest Marrakech", "fill_rate": 81, "registered_count": 162, "capacity": 200 },
       ...
     ],
     "generated_at": "14:32:05"
   }
   ```
5. `renderStats()` → compteurs animés, top-3 leaderboard avec barres de progression
6. **Toutes les 30 secondes** → `fetchStats()` relancé automatiquement
7. **Si un événement passe à 100%** → toast `🎉 "X" vient d'atteindre 100% — complet !`
8. **En cas d'erreur réseau** → retry automatique après 10 s (sans casser l'intervalle 30 s)

---

## 5. Sécurité Implémentée

| Vecteur d'attaque | Contre-mesure |
|-------------------|---------------|
| Injection SQL | PDO prepared statements partout, aucune concaténation de variable dans SQL |
| XSS | `htmlspecialchars()` + `strip_tags()` sur tous les inputs avant INSERT |
| Race condition (double-booking) | Transaction PDO + `SELECT ... FOR UPDATE` verrouille la ligne |
| Duplicate registration | Vérification email+event_id avant INSERT |
| Input invalide | `FILTER_VALIDATE_INT`, `FILTER_VALIDATE_EMAIL`, whitelist catégories |
| Path traversal (PDF) | `preg_replace('/[^a-f0-9]/i', '', $token)` pour sanitiser les noms de fichiers |
| Erreurs internes exposées | Toutes les exceptions sont loggées (`error_log`) jamais affichées au client |
| Mot de passe SMTP | Stocké dans `.env` (exclu du dépôt via `.gitignore`) |

---

## 6. Points Bonus Implémentés

- ✅ **Architecture MVC** (+5 pts) — Router, Controller de base, Models, Views séparés
- ✅ **Transactions PDO** — `beginTransaction` + `FOR UPDATE` + `rollBack` on error
- ✅ **QR Code** sur le ticket PDF (via api.qrserver.com)
- ✅ **Graphique HTML** dans le rapport PDF (tableau 10 colonnes, rendu mPDF)
- ✅ **Debounce** sur la recherche (400 ms)
- ✅ **Mise à jour live** du compteur de carte sans rechargement de page
- ✅ **Retry automatique** sur erreur de fetch stats (10 s, sans casser l'intervalle)
- ✅ **BASE_PATH** dynamique — fonctionne dans n'importe quel sous-répertoire EasyPHP
