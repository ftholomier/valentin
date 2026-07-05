<?php
/**
 * MODÈLE de configuration locale/serveur.
 * -----------------------------------------------------------------
 * 1. Copie ce fichier en "config.local.php" (dans le même dossier).
 * 2. Renseigne tes identifiants ci-dessous.
 * 3. Uploade config.local.php UNE SEULE FOIS sur le serveur.
 *
 * config.local.php est git-ignoré : il n'est pas dans le dépôt, donc
 * un envoi FTP du projet ne l'écrasera jamais.
 */

return [
    // Environnement : 'prod' sur le serveur, 'dev' en local.
    'app_env'   => 'prod',

    // --- Base MySQL (Nuxit / mutualisé) ---
    'db_driver' => 'mysql',
    'db_host'   => 'sql.nuxit.net',   // hôte MySQL fourni par Nuxit (voir ton panel)
    'db_port'   => '3306',
    'db_name'   => 'ta_base',         // nom de la base créée dans le panel
    'db_user'   => 'ton_utilisateur',
    'db_pass'   => 'ton_mot_de_passe',

    // --- Alternative : rester en SQLite (aucune base MySQL à créer) ---
    // 'app_env'   => 'prod',
    // 'db_driver' => 'sqlite',
    // 'db_sqlite_path' => __DIR__ . '/../database/lastfit.sqlite',
];
