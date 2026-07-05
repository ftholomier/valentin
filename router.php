<?php
/**
 * Routeur pour le serveur PHP intégré (développement uniquement) :
 *   php -S localhost:8000 router.php
 *
 * Simule le comportement d'Apache avec le document root sur public/ :
 * - sert directement les fichiers réels de public/ (assets)
 * - envoie tout le reste au front controller public/index.php
 *
 * En production, ce fichier n'est pas utilisé (le docroot pointe sur public/).
 */

$public = __DIR__ . '/public';
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$file = realpath($public . $uri);
if ($file && is_file($file) && strpos($file, realpath($public)) === 0) {
    return false; // laisser le serveur intégré servir l'asset
}

require $public . '/index.php';
