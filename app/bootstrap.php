<?php
/**
 * Amorçage de l'application : constantes de chemin, config, classes.
 * Chargé par public/index.php (web) et par database/install.php (CLI).
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('VIEWS_PATH', APP_ROOT . '/app/Views');

require APP_ROOT . '/config/config.php';

// Noyau
require APP_ROOT . '/app/Core/Database.php';
require APP_ROOT . '/app/Core/Response.php';
require APP_ROOT . '/app/Core/Helpers.php';
require APP_ROOT . '/app/Core/Auth.php';

// Contrôleurs
foreach (glob(APP_ROOT . '/app/Controllers/*.php') as $controller) {
    require $controller;
}
