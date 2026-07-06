<?php
/**
 * Configuration globale — LastFit
 * --------------------------------
 * Ce fichier est VERSIONNÉ et ne contient AUCUN secret.
 * Les identifiants sensibles (base de données, environnement) sont lus depuis :
 *   1. config/config.local.php  (git-ignoré — à créer sur le serveur, jamais écrasé par FTP)
 *   2. sinon des variables d'environnement LF_*
 *   3. sinon des valeurs par défaut (dev en SQLite)
 *
 * => Un envoi FTP du dépôt n'écrasera JAMAIS tes identifiants de production.
 */

$local = __DIR__ . '/config.local.php';
$secrets = is_file($local) ? (require $local) : [];

/** Résout une valeur : fichier local > variable d'env > défaut. */
function lf_cfg(array $secrets, string $key, string $env, $default)
{
    if (array_key_exists($key, $secrets)) {
        return $secrets[$key];
    }
    $v = getenv($env);
    return $v !== false ? $v : $default;
}

// --- Environnement ---------------------------------------------------------
define('APP_NAME', 'LastFit');
define('APP_ENV', lf_cfg($secrets, 'app_env', 'LF_ENV', 'prod'));   // 'dev' | 'prod'
define('SESSION_NAME', 'lastfit_sess');

// Version des assets (CSS/JS) pour l'anti-cache. En prod, on prend la date de
// modification du fichier CSS/JS le plus récent : dès qu'un asset est ré-uploadé,
// la version change automatiquement et le navigateur recharge la bonne version.
function lf_assets_version(): string
{
    $latest = 0;
    foreach (glob(__DIR__ . '/../public/assets/{css,js}/*.{css,js}', GLOB_BRACE) ?: [] as $f) {
        $m = @filemtime($f);
        if ($m && $m > $latest) {
            $latest = $m;
        }
    }
    return (string) ($latest ?: 1);
}
define('APP_VERSION', APP_ENV === 'dev' ? (string) time() : lf_assets_version());

// --- Base de données -------------------------------------------------------
define('DB_DRIVER', lf_cfg($secrets, 'db_driver', 'LF_DB_DRIVER', 'sqlite'));   // 'sqlite' | 'mysql'

// SQLite (dev) — fichier hors webroot
define('DB_SQLITE_PATH', $secrets['db_sqlite_path'] ?? (__DIR__ . '/../database/lastfit.sqlite'));

// MySQL (prod Nuxit / mutualisé)
define('DB_HOST', lf_cfg($secrets, 'db_host', 'LF_DB_HOST', 'localhost'));
define('DB_PORT', lf_cfg($secrets, 'db_port', 'LF_DB_PORT', '3306'));
define('DB_NAME', lf_cfg($secrets, 'db_name', 'LF_DB_NAME', 'lastfit'));
define('DB_USER', lf_cfg($secrets, 'db_user', 'LF_DB_USER', 'root'));
define('DB_PASS', lf_cfg($secrets, 'db_pass', 'LF_DB_PASS', ''));

// --- Métier ----------------------------------------------------------------
// Point de référence par défaut (centre de Lyon) pour le calcul de distance.
define('DEFAULT_LAT', 45.7640);
define('DEFAULT_LNG', 4.8357);

// Numéro de carte de test simulant un refus de paiement (façon Stripe).
define('STRIPE_TEST_DECLINE', '4000000000000002');
