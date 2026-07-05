<?php
/**
 * Front controller de l'API LastFit — routeur natif (switch propre).
 * Toutes les requêtes /api/* sont dirigées ici.
 * Réponses 100% JSON. Architecture API First.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Controllers/ConfigController.php';
require_once __DIR__ . '/../src/Controllers/SlotsController.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/BookingsController.php';
require_once __DIR__ . '/../src/Controllers/PaymentsController.php';

// --- Résolution du chemin de route ----------------------------------------
// Supporte /api/index.php/slots (PATH_INFO) et /api/slots (rewrite .htaccess),
// avec repli sur ?route=/slots pour un maximum de portabilité hébergeur.
$path = $_SERVER['PATH_INFO'] ?? '';
if ($path === '') {
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $path = preg_replace('#^.*/api(/index\.php)?#', '', $uri) ?: '';
}
if ($path === '' && isset($_GET['route'])) {
    $path = $_GET['route'];
}
$path   = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$seg    = $path === '/' ? [] : explode('/', trim($path, '/'));

try {
    switch (true) {

        // ---- Configuration (sports, villes, commission) ----
        case $method === 'GET' && $path === '/config':
            ConfigController::index();
            break;

        // ---- Cours ----
        case $method === 'GET' && $path === '/slots':
            SlotsController::index();
            break;
        case $method === 'GET' && ($seg[0] ?? '') === 'slots' && isset($seg[1]):
            SlotsController::show((int) $seg[1]);
            break;

        // ---- Authentification ----
        case $method === 'POST' && $path === '/auth/register':
            AuthController::register();
            break;
        case $method === 'POST' && $path === '/auth/login':
            AuthController::login();
            break;
        case $method === 'POST' && $path === '/auth/logout':
            AuthController::logout();
            break;
        case $method === 'GET' && $path === '/auth/me':
            AuthController::me();
            break;

        // ---- Réservations ----
        case $method === 'GET' && $path === '/bookings':
            BookingsController::index();
            break;
        case $method === 'POST' && $path === '/bookings':
            BookingsController::create();
            break;
        case $method === 'POST' && $path === '/bookings/validate':
            BookingsController::validate();
            break;

        // ---- Paiement (simulation Stripe) ----
        case $method === 'POST' && $path === '/payments/checkout':
            PaymentsController::checkout();
            break;

        default:
            Response::error("Route introuvable : $method $path", 404);
    }
} catch (Throwable $e) {
    Database::rollback();
    $msg = (APP_ENV === 'dev') ? $e->getMessage() : 'Erreur serveur.';
    Response::error($msg, 500);
}
