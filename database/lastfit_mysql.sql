-- ============================================================
--  LastFit — Dump MySQL / MariaDB (schéma + données de démo)
--  À importer via phpMyAdmin ou :  mysql -u USER -p BASE < lastfit_mysql.sql
--
--  Les cours sont datés dynamiquement sur AUJOURD'HUI (CURDATE()) afin que
--  les offres "de ce soir" restent pertinentes après l'import.
--  Comptes de démo : lea@demo.fr / pro@demo.fr / admin@demo.fr  (mdp : demo1234)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS slots;
DROP TABLE IF EXISTS partners;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS config;

-- ---------- Utilisateurs ----------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    role          VARCHAR(20)  NOT NULL DEFAULT 'sportif',
    nom           VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    ville         VARCHAR(120),
    partner_id    INT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Salles ----------
CREATE TABLE partners (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(160) NOT NULL,
    description TEXT,
    adresse     VARCHAR(255),
    ville       VARCHAR(120) NOT NULL,
    lat         DECIMAL(10,7),
    lng         DECIMAL(10,7),
    equipements VARCHAR(255),
    note        DECIMAL(2,1) DEFAULT 5.0,
    statut      VARCHAR(20)  NOT NULL DEFAULT 'actif',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Cours ----------
CREATE TABLE slots (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    partner_id       INT NOT NULL,
    sport            VARCHAR(60)  NOT NULL,
    titre            VARCHAR(160) NOT NULL,
    coach            VARCHAR(120),
    date_debut       DATETIME NOT NULL,
    duree_min        INT NOT NULL DEFAULT 60,
    prix_initial     DECIMAL(6,2) NOT NULL,
    prix_reduit      DECIMAL(6,2) NOT NULL,
    places_totales   INT NOT NULL DEFAULT 10,
    places_restantes INT NOT NULL DEFAULT 10,
    image_url        VARCHAR(255),
    statut           VARCHAR(20) NOT NULL DEFAULT 'disponible',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_slots_partner (partner_id),
    KEY idx_slots_date (date_debut),
    CONSTRAINT fk_slots_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Réservations ----------
CREATE TABLE bookings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    slot_id         INT NOT NULL,
    statut_paiement VARCHAR(20) NOT NULL DEFAULT 'en_attente',
    qr_token        VARCHAR(40) UNIQUE,
    montant_paye    DECIMAL(6,2) NOT NULL DEFAULT 0,
    commission      DECIMAL(6,2) NOT NULL DEFAULT 0,
    montant_salle   DECIMAL(6,2) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validated_at    DATETIME NULL,
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_slot (slot_id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_slot FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Configuration ----------
CREATE TABLE config (
    cle    VARCHAR(60) PRIMARY KEY,
    valeur TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  DONNÉES DE DÉMO
-- ============================================================

INSERT INTO config (cle, valeur) VALUES
 ('commission_rate', '0.15'),
 ('sports', '["Yoga","CrossFit","Pilates","Boxe","Cycling","HIIT","Zumba","Aquagym"]'),
 ('villes', '["Lyon","Paris","Bordeaux","Marseille"]');

INSERT INTO partners (id, nom, description, adresse, ville, lat, lng, equipements, note) VALUES
 (1,'Studio Lumen','Studio de yoga & Pilates baigné de lumière, en plein cœur de la Presqu''île.','12 rue de la République, 69002 Lyon','Lyon',45.7645000,4.8352000,'Tapis fournis, Douches, Vestiaires',4.9),
 (2,'CrossBox 12','Box CrossFit haute intensité, coachs certifiés, matériel neuf.','34 cours Lafayette, 69003 Lyon','Lyon',45.7620000,4.8560000,'Rig complet, Assault bikes, Douches',4.7),
 (3,'Coreo Pilates','Le spécialiste du Reformer à Lyon, cours en petit comité.','8 rue Auguste Comte, 69002 Lyon','Lyon',45.7560000,4.8300000,'Reformers, Cardio, Vestiaires',5.0),
 (4,'Ring Club','Salle de boxe anglaise & cardio-boxing, ambiance club.','21 rue Paul Bert, 69003 Lyon','Lyon',45.7600000,4.8600000,'Rings, Sacs, Douches',4.8),
 (5,'Pulse Cycling','Studio de cycling indoor immersif, son et lumière.','5 quai Victor Augagneur, 69003 Lyon','Lyon',45.7580000,4.8450000,'Vélos connectés, Douches, Serviettes',4.6);

INSERT INTO slots (partner_id, sport, titre, coach, date_debut, duree_min, prix_initial, prix_reduit, places_totales, places_restantes, image_url, statut) VALUES
 (1,'Yoga','Vinyasa Flow','Camille R.',CONCAT(CURDATE(),' 19:30:00'),60,18,8,14,5,'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=80','disponible'),
 (2,'CrossFit','WOD du soir','Marco T.',CONCAT(CURDATE(),' 18:00:00'),60,20,12,16,3,'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80','disponible'),
 (3,'Pilates','Pilates Reformer','Julie B.',CONCAT(CURDATE(),' 20:15:00'),55,26,9,8,2,'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=800&q=80','disponible'),
 (4,'Boxe','Boxing Cardio','Sofiane K.',CONCAT(CURDATE(),' 18:45:00'),60,22,11,12,6,'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=800&q=80','disponible'),
 (5,'Cycling','Ride Nocturne','Ana P.',CONCAT(CURDATE(),' 19:00:00'),45,19,10,20,8,'https://images.unsplash.com/photo-1518310383802-640c2de311b2?auto=format&fit=crop&w=800&q=80','disponible'),
 (1,'Yoga','Yin & Relaxation','Camille R.',CONCAT(CURDATE(),' 21:00:00'),60,16,7,12,4,'https://images.unsplash.com/photo-1552196563-55cd4e45efb3?auto=format&fit=crop&w=800&q=80','disponible'),
 (2,'HIIT','HIIT Express','Marco T.',CONCAT(CURDATE(),' 19:45:00'),45,18,9,15,7,'https://images.unsplash.com/photo-1534258936925-c58bed479fcb?auto=format&fit=crop&w=800&q=80','disponible'),
 (3,'Pilates','Reformer Débutant','Julie B.',CONCAT(CURDATE(),' 18:30:00'),55,24,10,8,1,'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80','disponible');

-- Mots de passe : demo1234 (hash bcrypt)
INSERT INTO users (role, nom, email, password_hash, ville, partner_id) VALUES
 ('sportif','Léa Marchand','lea@demo.fr','$2y$12$0lQHmlyYd1taq52scGF59uVtprTjChPVWrtzL.KgVSo6gH4yKPTne','Lyon',NULL),
 ('partner','Studio Lumen (pro)','pro@demo.fr','$2y$12$0lQHmlyYd1taq52scGF59uVtprTjChPVWrtzL.KgVSo6gH4yKPTne','Lyon',1),
 ('admin','Admin LastFit','admin@demo.fr','$2y$12$0lQHmlyYd1taq52scGF59uVtprTjChPVWrtzL.KgVSo6gH4yKPTne','Lyon',NULL);
