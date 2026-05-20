-- ╔══════════════════════════════════════════════════════════════╗
-- ║  EventHub Pro — database/schema.sql                         ║
-- ║  Schéma de la base de données complet                       ║
-- ║  ENSA Marrakech — Examen PHP Avancé                         ║
-- ╚══════════════════════════════════════════════════════════════╝

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Base de données ────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS eventhub_pro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eventhub_pro;

-- ══════════════════════════════════════════════════════════════════════════
-- TABLE : users
-- ══════════════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150)  NOT NULL,
    email        VARCHAR(255)  NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,           -- bcrypt hash
    role         ENUM('organizer', 'participant') NOT NULL DEFAULT 'participant',
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════════════
-- TABLE : categories
-- ══════════════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(50)   NOT NULL UNIQUE,    -- 'tech', 'design', etc.
    label        VARCHAR(100)  NOT NULL,
    color_primary VARCHAR(7)   NOT NULL DEFAULT '#2563EB',
    color_light   VARCHAR(7)   NOT NULL DEFAULT '#DBEAFE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════════════
-- TABLE : events
-- ══════════════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS events;
CREATE TABLE events (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(255)  NOT NULL,
    description      TEXT          NOT NULL,
    event_date       DATETIME      NOT NULL,
    location         VARCHAR(255)  NOT NULL,
    capacity         SMALLINT UNSIGNED NOT NULL CHECK (capacity > 0),
    category         VARCHAR(50)   NOT NULL,
    organizer_email  VARCHAR(255)  NOT NULL,
    organizer_id     INT UNSIGNED  NULL,
    alert_sent       TINYINT(1)    NOT NULL DEFAULT 0, -- Éviter les doublons d'email (Partie 2.2)
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Contraintes d'intégrité référentielle
    CONSTRAINT fk_events_category FOREIGN KEY (category) REFERENCES categories(slug) ON UPDATE CASCADE,
    CONSTRAINT fk_events_organizer FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════════════
-- TABLE : registrations
-- ══════════════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS registrations;
CREATE TABLE registrations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id      INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    token         VARCHAR(64)  NOT NULL UNIQUE, -- pour le lien de désinscription
    registered_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Contraintes d'intégrité référentielle et d'unicité
    CONSTRAINT fk_registrations_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT uq_event_email UNIQUE (event_id, email) -- Empêcher les doublons d'inscription
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════════════
-- TABLE : mail_logs
-- ══════════════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS mail_logs;
CREATE TABLE mail_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type          ENUM('confirmation', 'capacity_alert', 'ticket', 'other') NOT NULL,
    recipient     VARCHAR(255) NOT NULL,
    event_id      INT UNSIGNED NULL,
    error_message TEXT         NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════════════
-- INDEX DE PERFORMANCE
-- ══════════════════════════════════════════════════════════════════════════
CREATE INDEX idx_events_date_category ON events (event_date, category);

-- Justification de l'index composé idx_events_date_category pour searchEvents() :
--
-- Cet index composé (composite index) sur (event_date, category) permet d'optimiser
-- drastiquement les performances de la méthode searchEvents() du contrôleur :
-- 1. Filtrage rapide (Filtering) : Lors d'une recherche par catégorie, le moteur MySQL
--    peut directement isoler les lignes correspondantes en mémoire via la structure B-Tree.
-- 2. Tri sans coût (Sorting / No Filesort) : Étant donné que les événements de la page d'accueil
--    sont triés par date croissante (`ORDER BY e.event_date ASC`), avoir `event_date` dans l'index
--    permet à MySQL de lire les lignes dans le bon ordre directement depuis l'index, éliminant
--    ainsi l'étape très coûteuse en performance "Using filesort" (tri sur disque/mémoire temporaire).
-- 3. Combinaison des deux : C'est l'index optimal pour les requêtes du type :
--    `SELECT * FROM events WHERE category = X ORDER BY event_date ASC`

-- ══════════════════════════════════════════════════════════════════════════
-- DONNÉES DE TEST
-- ══════════════════════════════════════════════════════════════════════════
INSERT INTO categories (slug, label, color_primary, color_light) VALUES
    ('tech',     'Tech',     '#2563EB', '#DBEAFE'),
    ('design',   'Design',   '#7C3AED', '#EDE9FE'),
    ('business', 'Business', '#EA580C', '#FEF3C7'),
    ('science',  'Science',  '#16A34A', '#DCFCE7');

-- Mot de passe : "password123" hashé avec bcrypt
INSERT INTO users (name, email, password, role) VALUES
    ('Organisateur ENSA',   'orga@ensa.ma',       '$2y$10$pZ281eNkE9v7Qaks0pUr5eqvWIvF5KOp8sjYJiyXIlXiD1K.eoun6', 'organizer'),
    ('Yassine El Fassi',    'yassine@example.ma', '$2y$10$pZ281eNkE9v7Qaks0pUr5eqvWIvF5KOp8sjYJiyXIlXiD1K.eoun6', 'participant'),
    ('Salma Benali',        'salma@example.ma',   '$2y$10$pZ281eNkE9v7Qaks0pUr5eqvWIvF5KOp8sjYJiyXIlXiD1K.eoun6', 'participant'),
    ('Mehdi Khalil',        'mehdi@example.ma',   '$2y$10$pZ281eNkE9v7Qaks0pUr5eqvWIvF5KOp8sjYJiyXIlXiD1K.eoun6', 'participant'),
    ('Zineb Moussaoui',     'zineb@example.ma',   '$2y$10$pZ281eNkE9v7Qaks0pUr5eqvWIvF5KOp8sjYJiyXIlXiD1K.eoun6', 'participant');

INSERT INTO events (title, description, event_date, location, capacity, category, organizer_email, organizer_id) VALUES
    (
        'DevFest Marrakech 2025',
        'La grande conférence tech de Marrakech. Talks, ateliers pratiques et networking avec les professionnels du secteur.',
        '2025-09-20 09:00:00',
        'ENSA Marrakech — Grand Amphi',
        200,
        'tech',
        'orga@ensa.ma',
        1
    ),
    (
        'UX Design Workshop',
        'Atelier intensif de design UX : prototypage Figma, tests utilisateurs, design systems. Places très limitées.',
        '2025-07-28 14:00:00',
        'École Nationale des Arts, Marrakech',
        30,
        'design',
        'orga@ensa.ma',
        1
    ),
    (
        'PHP & MVC Day',
        'Journée dédiée à PHP 8.x, architecture MVC native, bonnes pratiques PDO et sécurité des applications web.',
        '2025-11-08 09:30:00',
        'ENSA Marrakech — Salle TP Informatique',
        5,
        'tech',
        'orga@ensa.ma',
        1
    );

-- Données de test pour la table registrations
INSERT INTO registrations (event_id, name, email, token) VALUES
    (1, 'Alice Dupont', 'alice@example.com', 'tok_alice1'),
    (1, 'Bob Martin', 'bob@example.com', 'tok_bob1'),
    (2, 'Charlie Rover', 'charlie@example.com', 'tok_charlie1'),
    (2, 'David Vance', 'david@example.com', 'tok_david1'),
    (3, 'Eve Adams', 'eve@example.com', 'tok_eve1');

SET FOREIGN_KEY_CHECKS = 1;
