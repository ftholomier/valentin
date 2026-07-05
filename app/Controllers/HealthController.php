<?php
/**
 * Diagnostic de déploiement — GET /api/health
 * Vérifie en un coup d'œil : version déployée, connexion BDD, sessions.
 * (Ne divulgue aucun secret. À restreindre/retirer après mise au point si besoin.)
 */
class HealthController
{
    public static function index(): void
    {
        // --- Base de données ---
        $dbOk = false; $dbErr = null;
        try {
            Database::pdo()->query('SELECT 1');
            $dbOk = true;
        } catch (Throwable $e) {
            $dbErr = $e->getMessage();
        }

        // --- Sessions (test écriture/lecture) ---
        Auth::start();
        $_SESSION['__health_check'] = 'ok';
        $sessionWrite = (($_SESSION['__health_check'] ?? null) === 'ok') && session_id() !== '';
        $savePath = session_save_path();
        if ($savePath === '' || $savePath === false) {
            $savePath = (string) ini_get('session.save_path');
        }

        $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
              || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

        Response::ok([
            'app'                   => APP_NAME,
            'app_version'           => APP_VERSION,
            'env'                   => APP_ENV,
            'php'                   => PHP_VERSION,
            'https_detecte'         => $https,
            'db_driver'             => DB_DRIVER,
            'db_connectee'          => $dbOk,
            'db_erreur'             => APP_ENV === 'dev' ? $dbErr : ($dbOk ? null : 'connexion impossible'),
            'session_ecriture'      => $sessionWrite,
            'session_id_present'    => session_id() !== '',
            'session_dossier'       => $savePath,
            'session_dossier_ok'    => $savePath ? is_writable($savePath) : false,
            'utilisateur_connecte'  => Auth::check(),
        ]);
    }
}
