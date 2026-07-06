<?php
/**
 * Promeut un utilisateur EXISTANT au rôle admin.
 * Aucun compte admin n'est seedé en production : crée d'abord un compte normal
 * via /inscription, puis promeus-le ici.
 *
 * En CLI :   php database/make_admin.php ton@email.fr
 * En web   :  https://ton-domaine/../  (désactivé — CLI uniquement, par sécurité)
 *
 * ⚠️ Sur hébergement mutualisé sans CLI : utilise plutôt phpMyAdmin :
 *      UPDATE users SET role = 'admin' WHERE email = 'ton@email.fr';
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

$email = $argv[1] ?? '';
if ($email === '') {
    fwrite(STDERR, "Usage : php database/make_admin.php ton@email.fr\n");
    exit(1);
}

require_once __DIR__ . '/../app/bootstrap.php';

$user = Database::one('SELECT id, nom, role FROM users WHERE email = ?', [strtolower(trim($email))]);
if (!$user) {
    fwrite(STDERR, "Aucun utilisateur avec l'email « $email ». Crée d'abord le compte via /inscription.\n");
    exit(1);
}

Database::run('UPDATE users SET role = ? WHERE id = ?', ['admin', $user['id']]);
echo "✅ {$user['nom']} ($email) est désormais administrateur.\n";
