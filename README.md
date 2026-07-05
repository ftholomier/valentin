# LastFit — Le Too Good To Go du sport 🏋️

Plateforme web de **cours collectifs de dernière minute** : les salles de sport proposent
leurs places invendues à prix réduit. Réservation flash, paiement, QR code d'accès.

> **État : MVP — Tranche verticale n°1** (parcours sportif complet)
> Accueil → recherche/filtres → fiche cours → réservation → paiement (simulé) → **QR code**.

---

## 🧱 Stack technique

| Couche | Choix |
|---|---|
| **Backend** | PHP 8.x **100% natif**, orienté objet, **front controller unique** (aucun framework) |
| **Base de données** | **SQL via PDO natif (aucun ORM)** — SQLite (dev) / MySQL-MariaDB (prod) |
| **Frontend** | HTML5 + **CSS3 vanille** (design LastFit sans Tailwind) + **JavaScript vanilla** |
| **Communication** | **API First** — endpoints JSON consommés via `fetch()` |

---

## 📁 Arborescence (webroot isolé)

> ⚠️ **Le document root de l'hébergeur doit pointer sur `public/`.**
> Tout le code, la config et les données sont **au-dessus** du webroot → inaccessibles depuis le web.

```
valentin/
├── public/                     ← 🌐 DOCUMENT ROOT (seul dossier exposé)
│   ├── index.php               Front controller unique (pages + /api)
│   ├── .htaccess               Réécriture : tout vers index.php
│   └── assets/
│       ├── css/app.css         Design system LastFit (CSS natif)
│       └── js/                 api, components, ui + scripts de page
│
├── app/                        ← Code applicatif (hors web)
│   ├── bootstrap.php           Chargement config + classes
│   ├── Core/                   Database, Response, Auth, Helpers
│   ├── Controllers/            Config, Slots, Auth, Bookings, Payments
│   └── Views/                  Vues + partials (head, header, footer)
│
├── config/                     ← Configuration (hors web)
│   ├── config.php              VERSIONNÉ — aucun secret
│   ├── config.local.example.php  Modèle à copier
│   └── config.local.php        🔒 git-ignoré — TES identifiants (créé sur le serveur)
│
├── database/                   ← Base & migrations (hors web)
│   ├── schema.sql              Schéma SQLite
│   ├── install.php             Installateur + jeu de démo (CLI)
│   └── lastfit_mysql.sql       ⬇️ Dump MySQL (schéma + données)
│
├── design/reference-lastfit.html   Maquette validée d'origine
├── router.php                  Serveur PHP intégré (dev uniquement)
└── README.md
```

---

## 🔐 Configuration & secrets (important)

Les identifiants ne sont **jamais** dans le dépôt :

- `config/config.php` (versionné) ne contient **aucun secret** ; il lit les valeurs depuis
  `config/config.local.php`, sinon des variables d'environnement `LF_*`, sinon des défauts.
- `config/config.local.php` est **git-ignoré**. Tu le crées **une fois** sur le serveur
  → un envoi FTP du projet ne l'écrase jamais.

**Mise en place sur le serveur :**
```bash
cp config/config.local.example.php config/config.local.php
# puis éditer config.local.php avec tes identifiants MySQL Nuxit
```

---

## 🗄️ Modèle de données (5 tables)

- **users** — sportifs / salles / admin, rôles, mot de passe hashé.
- **partners** — salles : description, adresse, GPS, équipements, note.
- **slots** — cours : sport, coach, horaire, prix initial/réduit, places, statut.
- **bookings** — réservations : statut paiement, token QR, montants (payé/commission/salle).
- **config** — clé/valeur : `commission_rate`, `sports`, `villes`.

**Commission** configurable (défaut **15%**). Ex. 12 € → 1,80 € plateforme / 10,20 € salle.

---

## 🔌 API (endpoints JSON, préfixe `/api`)

| Méthode | Route | Rôle |
|---|---|---|
| GET  | `/api/config` | Sports, villes, taux de commission |
| GET  | `/api/slots` | Cours (filtres `ville`, `sport`, `date`, `q`, `lat`, `lng`) |
| GET  | `/api/slots/{id}` | Fiche d'un cours |
| POST | `/api/auth/register` · `/login` · `/logout` | Session |
| GET  | `/api/auth/me` | Utilisateur courant |
| POST | `/api/bookings` | Réserver (atomique, anti-survente) |
| GET  | `/api/bookings` | Historique du sportif |
| POST | `/api/payments/checkout` | Paiement simulé → QR |
| POST | `/api/bookings/validate` | Validation d'un QR **(salle propriétaire / admin uniquement)** |
| GET  | `/api/pro/dashboard` | Dashboard salle : cours, remplissage, CA (rôle `partner`) |

Réponse : `{ "success": bool, "message": string, "data": ... }`.

**Pages (URLs propres)** : `/` · `/resultats` · `/cours?id=` · `/checkout?slot=` · `/connexion` · `/inscription` · `/mon-compte` · `/pro`.

---

## 🚀 Lancer en local (dev, SQLite)

```bash
php database/install.php                       # crée + peuple la base de démo
php -S localhost:8000 -t public router.php     # http://localhost:8000
```

**Comptes de démo** (mot de passe `demo1234`) : `lea@demo.fr` (sportif), `pro@demo.fr`, `admin@demo.fr`.
**Cartes de test** : `4242 4242 4242 4242` = accepté · `4000 0000 0000 0002` = refusé.

---

## 🌐 Déploiement sur Nuxit (mutualisé, MySQL)

1. **FTP** : uploader tout le projet.
2. **Document root** : dans le panel Nuxit, faire pointer le domaine sur le dossier **`public/`**.
3. **Base MySQL** : créer une base dans le panel, puis **importer `database/lastfit_mysql.sql`** (phpMyAdmin).
4. **Config serveur** : copier `config/config.local.example.php` → `config/config.local.php`,
   renseigner l'hôte/base/user/pass MySQL Nuxit, et `app_env => 'prod'`.
5. `mod_rewrite` est actif chez Nuxit → les URLs `/api/...` et les pages propres fonctionnent.

> ⚠️ **GitHub Pages ne peut pas héberger ce projet** (PHP + base SQL requis).

---

## 🔒 Sécurité (MVP)

- **Code, config et données hors du webroot** (seul `public/` est exposé) → non téléchargeables.
- Requêtes **PDO préparées** partout (anti-injection SQL).
- Mots de passe **hashés** (`password_hash`/bcrypt).
- **Sessions** HttpOnly + `SameSite=Lax` + régénération d'ID à la connexion.
- Secrets **hors dépôt** (`config.local.php` git-ignoré).
- Sortie échappée côté client (`escapeHtml`) et serveur (`htmlspecialchars`).
- Réservation **atomique** (UPDATE conditionnel) → pas de survente.

---

## ✅ Livré (itération 1) — 🔜 Prochaines étapes

**Fait :** accueil dynamique · recherche filtrable + carte · fiche cours (compte à rebours) ·
inscription/connexion/session · réservation anti-survente · paiement simulé (Stripe mock) ·
génération + affichage **QR code** · espace sportif (historique, stats, QR) ·
**Dashboard salle (espace pro)** : cours du jour, validation QR sécurisée, remplissage & CA ·
redirection par rôle (sportif → `/mon-compte`, salle → `/pro`) ·
base SQL (SQLite + dump MySQL) · **architecture webroot isolé + secrets séparés**.

**À venir :**
- [ ] **Backoffice admin** : commission, gestion des sports & villes
- [ ] Favoris, factures téléchargeables, vraie intégration Stripe (webhook)

---

*Dernière mise à jour : itération 3 — Espace Pro Salle (dashboard, validation QR, CA & remplissage).*
