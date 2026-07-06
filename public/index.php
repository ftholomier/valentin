<?php
/**
 * Front controller unique de LastFit.
 * - Sert les pages (vues HTML avec header/footer partagés)
 * - Route les requêtes /api/* vers les contrôleurs (réponses JSON)
 *
 * Le document root du domaine pointe sur ce dossier public/.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path   = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

// ------------------------------------------------------------------ API ----
if ($path === '/api' || strncmp($path, '/api/', 5) === 0) {
    dispatch_api($method, substr($path, 4) ?: '/');
    exit;
}

// ---------------------------------------------------------------- Pages ----
$routes = [
    '/'            => ['view' => 'accueil',     'title' => 'LastFit — Bougez plus. Payez moins.', 'script' => 'accueil.js',   'solid' => false],
    '/resultats'   => ['view' => 'resultats',   'title' => 'Rechercher un cours — LastFit',       'script' => 'resultats.js', 'solid' => true],
    '/cours'       => ['view' => 'cours',        'title' => 'Cours — LastFit',                     'script' => 'cours.js',     'solid' => true],
    '/checkout'    => ['view' => 'checkout',     'title' => 'Paiement — LastFit',                  'script' => 'checkout.js',  'solid' => true],
    '/connexion'   => ['view' => 'connexion',    'title' => 'Connexion — LastFit',                 'script' => 'auth.js',      'solid' => true],
    '/inscription' => ['view' => 'inscription',  'title' => 'Créer un compte — LastFit',           'script' => 'auth.js',      'solid' => true],
    '/mon-compte'  => ['view' => 'compte',       'title' => 'Mon compte — LastFit',                'script' => 'compte.js',    'solid' => true],
    '/pro'         => ['view' => 'pro',          'title' => 'Espace pro — LastFit',                'script' => 'pro.js',       'solid' => true],
    '/admin'       => ['view' => 'admin',        'title' => 'Administration — LastFit',            'script' => 'admin.js',     'solid' => true],
];

if (isset($routes[$path])) {
    render_page($routes[$path]);
} else {
    http_response_code(404);
    render_page(['view' => '404', 'title' => 'Page introuvable — LastFit', 'script' => null, 'solid' => true]);
}

/* ============================ Fonctions ============================ */

/** Rend une vue enveloppée dans head + header + footer partagés. */
function render_page(array $route): void
{
    $title       = $route['title'];
    $pageScript  = $route['script'];
    $headerSolid = $route['solid'];
    $viewFile    = VIEWS_PATH . '/' . $route['view'] . '.php';

    require VIEWS_PATH . '/partials/head.php';
    require VIEWS_PATH . '/partials/header.php';
    if (is_file($viewFile)) {
        require $viewFile;
    }
    require VIEWS_PATH . '/partials/footer.php';
}

/** Routeur de l'API (switch propre). $path est relatif à /api. */
function dispatch_api(string $method, string $path): void
{
    $path = '/' . trim($path, '/');
    $seg  = $path === '/' ? [] : explode('/', trim($path, '/'));

    try {
        switch (true) {
            case $method === 'GET' && $path === '/health':
                HealthController::index(); break;

            case $method === 'GET' && $path === '/config':
                ConfigController::index(); break;

            case $method === 'GET' && $path === '/slots':
                SlotsController::index(); break;
            case $method === 'GET' && ($seg[0] ?? '') === 'slots' && isset($seg[1]):
                SlotsController::show((int) $seg[1]); break;

            case $method === 'POST' && $path === '/auth/register':
                AuthController::register(); break;
            case $method === 'POST' && $path === '/auth/login':
                AuthController::login(); break;
            case $method === 'POST' && $path === '/auth/logout':
                AuthController::logout(); break;
            case $method === 'GET' && $path === '/auth/me':
                AuthController::me(); break;

            case $method === 'GET' && $path === '/bookings':
                BookingsController::index(); break;
            case $method === 'POST' && $path === '/bookings':
                BookingsController::create(); break;
            case $method === 'POST' && $path === '/bookings/validate':
                BookingsController::validate(); break;

            case $method === 'POST' && $path === '/payments/checkout':
                PaymentsController::checkout(); break;

            case $method === 'GET' && $path === '/pro/dashboard':
                ProController::dashboard(); break;

            case $method === 'GET' && $path === '/admin/overview':
                AdminController::overview(); break;
            case $method === 'POST' && $path === '/admin/config':
                AdminController::updateConfig(); break;

            default:
                Response::error("Route introuvable : $method $path", 404);
        }
    } catch (Throwable $e) {
        try {
            Database::rollback();
        } catch (Throwable $ignored) {
            // On garantit la réponse JSON même si le rollback échoue.
        }
        Response::error(APP_ENV === 'dev' ? $e->getMessage() : 'Erreur serveur.', 500);
    }
}
