<?php
/**
 * Routeur pour le serveur PHP intégré (développement uniquement) :
 *   php -S localhost:8000 -t public router.php
 * Simule Apache avec le document root sur public/ :
 * - sert les fichiers réels de public/ (assets, diag.php…), quel que soit le -t
 * - envoie tout le reste au front controller public/index.php
 * En production ce fichier n'est pas utilisé.
 */

$public = realpath(__DIR__ . '/public');
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$file = realpath($public . $uri);
if ($file && is_file($file) && strpos($file, $public) === 0) {
    // Sert le fichier explicitement : indépendant du docroot passé à php -S.
    if (str_ends_with($file, '.php')) {
        require $file;
        return true;
    }
    $types = [
        'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'webp' => 'image/webp',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'html' => 'text/html',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
}

require $public . '/index.php';
