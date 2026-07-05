# LastFit — Le Too Good To Go du sport 🏋️

Plateforme web de **cours collectifs de dernière minute** : les salles de sport proposent
leurs places invendues à prix réduit. Réservation flash, paiement, QR code d'accès.

> **État du projet : MVP — Tranche verticale n°1 livrée**
> Parcours sportif complet de bout en bout : accueil → recherche/filtres → fiche cours →
> réservation → paiement (simulé) → **QR code de validation**.

---

## 🧱 Stack technique

| Couche | Choix |
|---|---|
| **Backend** | PHP 8.x **100% natif**, orienté objet, routeur/switch propre (aucun framework) |
| **Base de données** | **SQL via PDO natif (aucun ORM)** — SQLite par défaut (dev), MySQL/MariaDB en prod |
| **Frontend** | HTML5 + **CSS3 vanille** (design LastFit réimplémenté sans Tailwind) + **JavaScript vanilla** |
| **Communication** | **API First** — le PHP sert des endpoints JSON, le front consomme via `fetch()` |
| **Icônes / polices** | Lucide (CDN) + Google Fonts (Archivo / Inter) — chargés côté navigateur |

---

## 📁 Arborescence

```
valentin/
├── index.html              Accueil (hero, recherche, offres dynamiques, principe, FAQ)
├── resultats.html          Recherche : filtres JS (ville, sport, prix, tri) + carte OSM
├── cours.html              Fiche cours : détail, compte à rebours, réservation
├── checkout.html           Paiement simulé → génération du QR code
├── connexion.html          Connexion (session)
├── inscription.html        Création de compte sportif
├── mon-compte.html         Espace sportif : historique + QR codes
├── router.php              Routeur du serveur PHP intégré (dev uniquement)
├── .htaccess               Réécriture /api + protection des dossiers sensibles
│
├── api/
│   ├── index.php           Front controller de l'API (routeur natif)
│   └── .htaccess           Réécriture des URLs propres
│
├── src/                    Code PHP applicatif (hors accès web)
│   ├── Database.php        Connexion PDO (SQLite/MySQL) + helpers requêtes
│   ├── Response.php        Réponses JSON normalisées
│   ├── Auth.php            Authentification par session + hash mots de passe
│   ├── Helpers.php         Distance Haversine, token QR, commission…
│   ├── .htaccess           Deny (aucun accès web)
│   └── Controllers/
│       ├── ConfigController.php     Sports, villes, taux de commission
│       ├── SlotsController.php       Liste + fiche des cours
│       ├── AuthController.php        Register / login / logout / me
│       ├── BookingsController.php    Réservation, historique, validation QR
│       └── PaymentsController.php    Paiement (simulation Stripe)
│
├── config/
│   ├── config.php          Configuration (driver DB, identifiants, environnement)
│   └── .htaccess           Deny
│
├── database/
│   ├── schema.sql          Schéma SQLite
│   ├── install.php         Installateur + jeu de démo (CLI)
│   ├── lastfit_mysql.sql   ⬇️ DUMP MySQL téléchargeable (schéma + données)
│   └── .htaccess           Deny
│
├── assets/
│   ├── css/app.css         Design system LastFit (CSS natif)
│   └── js/
│       ├── api.js          Client fetch + formatage
│       ├── components.js   Cartes de cours, compte à rebours
│       ├── ui.js           Header, icônes, toasts, état de session
│       ├── accueil.js / resultats.js / cours.js / checkout.js / compte.js / auth.js
│
└── design/
    └── reference-lastfit.html   Maquette validée d'origine (référence design)
```

---

## 🗄️ Modèle de données (5 tables)

- **users** — sportifs / salles / admin, rôles, rattachement salle, mot de passe hashé.
- **partners** — salles : description, adresse, GPS (lat/lng), équipements, note.
- **slots** — cours : sport, coach, horaire, prix initial/réduit, places totales/restantes, statut.
- **bookings** — réservations : statut paiement, token QR, montants (payé / commission / part salle).
- **config** — clé/valeur : `commission_rate`, `sports`, `villes`.

**Business model** : commission configurable (défaut **15%**). Ex. cours à 8 € → 1,20 € plateforme / 6,80 € salle.

---

## 🔌 API (endpoints JSON)

| Méthode | Route | Rôle |
|---|---|---|
| GET  | `/api/config` | Sports, villes, taux de commission |
| GET  | `/api/slots` | Liste des cours (filtres : `ville`, `sport`, `date`, `q`, `lat`, `lng`) |
| GET  | `/api/slots/{id}` | Fiche détaillée d'un cours |
| POST | `/api/auth/register` | Inscription |
| POST | `/api/auth/login` | Connexion |
| POST | `/api/auth/logout` | Déconnexion |
| GET  | `/api/auth/me` | Utilisateur courant |
| POST | `/api/bookings` | Réserver une place (atomique, anti-survente) |
| GET  | `/api/bookings` | Historique du sportif |
| POST | `/api/payments/checkout` | Paiement simulé → génère le QR |
| POST | `/api/bookings/validate` | Validation d'un QR côté salle |

Réponse type : `{ "success": true, "message": "...", "data": ... }`.

---

## 🚀 Lancer en local (dev, SQLite)

```bash
# 1. Créer et peupler la base de démo
php database/install.php

# 2. Démarrer le serveur
php -S localhost:8000 router.php

# 3. Ouvrir http://localhost:8000
```

**Comptes de démo** (mot de passe : `demo1234`) :
- `lea@demo.fr` — sportif
- `pro@demo.fr` — salle (espace pro, à venir)
- `admin@demo.fr` — admin (backoffice, à venir)

**Cartes de test au paiement** : `4242 4242 4242 4242` = accepté · `4000 0000 0000 0002` = refusé.

---

## 🌐 Déploiement sur hébergeur PHP (MySQL)

1. Uploader les fichiers (le `document root` doit pointer sur la racine du projet).
2. Créer une base MySQL, puis **importer `database/lastfit_mysql.sql`** (phpMyAdmin).
3. Dans `config/config.php`, passer `DB_DRIVER` à `mysql` et renseigner
   `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` (ou via variables d'environnement `LF_DB_*`).
4. Vérifier que `mod_rewrite` est actif (URLs `/api/...`).

> ⚠️ **GitHub Pages ne peut PAS héberger ce projet** (Pages ne sert que du statique).
> Il faut un hébergement avec PHP + base SQL.

---

## 🔒 Sécurité (MVP)

- Requêtes **PDO préparées** partout (anti-injection SQL).
- Mots de passe **hashés** (`password_hash` / bcrypt).
- **Sessions** HttpOnly + `SameSite=Lax` + régénération d'ID à la connexion.
- Dossiers `src/`, `config/`, `database/` **bloqués** en accès web direct (`.htaccess`).
- Sortie échappée côté client (`escapeHtml`).
- Réservation **atomique** (UPDATE conditionnel) → pas de survente sur les places.

---

## ✅ Fonctionnalités livrées (itération 1)

- [x] Accueil fidèle au design validé, offres du soir **dynamiques** (API)
- [x] Recherche avec filtres **100% client** (ville, sport, date, prix, tri) + carte
- [x] Fiche cours : compte à rebours JS, gestion complet/disponible
- [x] Inscription / connexion / session
- [x] Réservation flash avec mise à jour des places (anti-survente)
- [x] Paiement **simulé** (Stripe mock, carte de refus de test)
- [x] Génération et affichage du **QR code** (billet)
- [x] Espace sportif : historique + statistiques + QR
- [x] Endpoint de **validation QR** côté salle
- [x] Base SQL (SQLite dev + **dump MySQL téléchargeable**)

## 🔜 Prochaines itérations

- [ ] **Dashboard salle (espace pro)** : cours du jour, scan/validation QR, CA & remplissage
- [ ] **Backoffice admin** : gestion de la commission, ajout/suppression de sports & villes
- [ ] Favoris sportifs, factures téléchargeables
- [ ] Vraie intégration Stripe (webhook)

---

*Dernière mise à jour : itération 1 — tranche verticale sportif de bout en bout.*
