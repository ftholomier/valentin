<?php
/**
 * Amorçage de l'application : constantes de chemin, config, classes.
 * Chargé par public/index.php (web) et par database/install.php (CLI).
 */
declare(strict_types=1);

// Fuseau horaire unique pour tous les horodatages PHP (indépendant du php.ini hébergeur).
date_default_timezone_set('Europe/Paris');

define('APP_ROOT', dirname(__DIR__));
define('VIEWS_PATH', APP_ROOT . '/app/Views');

require APP_ROOT . '/config/config.php';

// Garde-fous : garantit les constantes attendues même si config.php est une
// version antérieure (upload partiel) — évite un fatal "Undefined constant".
if (!defined('APP_ENV'))     { define('APP_ENV', 'prod'); }
if (!defined('APP_NAME'))    { define('APP_NAME', 'LastFit'); }
if (!defined('APP_VERSION')) { define('APP_VERSION', '1'); }

// Affichage des erreurs : jamais en prod (fuite de chemins), visible en dev.
error_reporting(E_ALL);
if (APP_ENV === 'prod') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
}

// Noyau
require APP_ROOT . '/app/Core/Database.php';
require APP_ROOT . '/app/Core/Response.php';
require APP_ROOT . '/app/Core/Helpers.php';
require APP_ROOT . '/app/Core/Auth.php';

// Contrôleurs
foreach (glob(APP_ROOT . '/app/Controllers/*.php') as $controller) {
    require $controller;
}
