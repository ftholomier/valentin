<?php
/**
 * Configuration globale — LastFit MVP
 * ------------------------------------
 * Aucun secret sensible ici en clair pour la prod : sur ton hébergeur,
 * remplace les identifiants MySQL ci-dessous (ou passe le driver à 'mysql').
 *
 * Le moteur par défaut est SQLite (zéro configuration, fichier auto-créé).
 * Pour basculer sur MySQL : DB_DRIVER = 'mysql' + renseigne DB_HOST/NAME/USER/PASS,
 * puis importe database/lastfit_mysql.sql via phpMyAdmin.
 */

// --- Base de données -------------------------------------------------------
define('DB_DRIVER', getenv('LF_DB_DRIVER') ?: 'sqlite');   // 'sqlite' | 'mysql'

// SQLite (défaut)
define('DB_SQLITE_PATH', __DIR__ . '/../database/lastfit.sqlite');

// MySQL (prod hébergeur)
define('DB_HOST', getenv('LF_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('LF_DB_PORT') ?: '3306');
define('DB_NAME', getenv('LF_DB_NAME') ?: 'lastfit');
define('DB_USER', getenv('LF_DB_USER') ?: 'root');
define('DB_PASS', getenv('LF_DB_PASS') ?: '');

// --- Application -----------------------------------------------------------
define('APP_NAME', 'LastFit');
define('APP_ENV', getenv('LF_ENV') ?: 'dev');              // 'dev' | 'prod'
define('SESSION_NAME', 'lastfit_sess');

// Point de référence par défaut (centre de Lyon) pour le calcul de distance
// quand la géolocalisation du navigateur n'est pas disponible.
define('DEFAULT_LAT', 45.7640);
define('DEFAULT_LNG', 4.8357);

// Numéro de carte de test qui simule un refus de paiement (façon Stripe).
define('STRIPE_TEST_DECLINE', '4000000000000002');
