-- ============================================================
--  LastFit — Schéma SQLite (moteur de dev/MVP)
--  Version MySQL équivalente : database/lastfit_mysql.sql
-- ============================================================

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS slots;
DROP TABLE IF EXISTS partners;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS config;

-- ---------- Utilisateurs (sportifs, salles, admin) ----------
CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    role          TEXT NOT NULL DEFAULT 'sportif',      -- sportif | partner | admin
    nom           TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    ville         TEXT,
    partner_id    INTEGER,                              -- si role=partner : salle rattachée
    created_at    TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

-- ---------- Établissements / Salles ----------
CREATE TABLE partners (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nom         TEXT NOT NULL,
    description TEXT,
    adresse     TEXT,
    ville       TEXT NOT NULL,
    lat         REAL,
    lng         REAL,
    equipements TEXT,                                   -- liste séparée par des virgules
    note        REAL DEFAULT 5.0,
    statut      TEXT NOT NULL DEFAULT 'actif',          -- actif | inactif
    created_at  TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

-- ---------- Cours / Créneaux ----------
CREATE TABLE slots (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    partner_id       INTEGER NOT NULL,
    sport            TEXT NOT NULL,
    titre            TEXT NOT NULL,
    coach            TEXT,
    date_debut       TEXT NOT NULL,                     -- 'YYYY-MM-DD HH:MM:SS'
    duree_min        INTEGER NOT NULL DEFAULT 60,
    prix_initial     REAL NOT NULL,
    prix_reduit      REAL NOT NULL,
    places_totales   INTEGER NOT NULL DEFAULT 10,
    places_restantes INTEGER NOT NULL DEFAULT 10,
    image_url        TEXT,
    statut           TEXT NOT NULL DEFAULT 'disponible', -- disponible | complet | termine
    created_at       TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
);

-- ---------- Réservations ----------
CREATE TABLE bookings (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER NOT NULL,
    slot_id         INTEGER NOT NULL,
    statut_paiement TEXT NOT NULL DEFAULT 'en_attente', -- en_attente | paye | valide | annule
    qr_token        TEXT UNIQUE,
    montant_paye    REAL NOT NULL DEFAULT 0,
    commission      REAL NOT NULL DEFAULT 0,
    montant_salle   REAL NOT NULL DEFAULT 0,
    created_at      TEXT NOT NULL DEFAULT (datetime('now','localtime')),
    validated_at    TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE
);

-- ---------- Configuration (clé/valeur, JSON pour les listes) ----------
CREATE TABLE config (
    cle    TEXT PRIMARY KEY,
    valeur TEXT NOT NULL
);

CREATE INDEX idx_slots_partner ON slots(partner_id);
CREATE INDEX idx_slots_date    ON slots(date_debut);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_slot ON bookings(slot_id);
