<?php
/**
 * Routeur pour le serveur PHP intégré (développement uniquement) :
 *   php -S localhost:8000 router.php
 * Mappe /api/* vers api/index.php (PATH_INFO) et sert les fichiers statiques.
 * En production Apache, ce fichier n'est pas utilisé (voir les .htaccess).
 */

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = __DIR__;

// Routes API -> front controller
if (preg_match('#^/api(/.*)?$#', $uri, $m)) {
    $_SERVER['PATH_INFO'] = $m[1] ?? '';
    require __DIR__ . '/api/index.php';
    return true;
}

// Fichier statique existant : laisser le serveur intégré le servir
$file = realpath($root . $uri);
if ($file && is_file($file) && strpos($file, $root) === 0) {
    return false;
}

// Racine -> accueil
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.html';
    return true;
}

// Sinon 404
http_response_code(404);
echo '404 Not Found';
return true;
