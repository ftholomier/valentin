<?php
/**
 * LastFit — Diagnostic de déploiement.
 * Ouvrir : https://ton-domaine/diag.php            (autorisé tant que config.local.php n'existe pas)
 *          https://ton-domaine/diag.php?k=TA_CLE   (ensuite : clé 'diag_key' de config/config.local.php)
 *
 * Sécurité : en production (config.local.php présent), l'accès exige la clé
 * 'diag_key' définie dans config.local.php. Aucune erreur détaillée, aucun
 * chemin serveur ni donnée n'est divulgué.
 * ⚠️ Supprimer ce fichier une fois le site stabilisé.
 */
header('Content-Type: text/plain; charset=utf-8');

$base     = dirname(__DIR__);
$flat     = is_dir(__DIR__ . '/app');
$okStruct = is_dir($base . '/app');
if ($flat && !$okStruct) {
    $base = __DIR__;
}

// --- Contrôle d'accès -------------------------------------------------------
$localFile = $base . '/config/config.local.php';
$cfg = is_file($localFile) ? (require $localFile) : [];
$cfg = is_array($cfg) ? $cfg : [];
if (is_file($localFile)) {
    $key = (string) ($cfg['diag_key'] ?? '');
    if ($key === '' || !hash_equals($key, (string) ($_GET['k'] ?? ''))) {
        http_response_code(404);
        exit("Diagnostic verrouillé.\nDéfinis 'diag_key' => 'une-cle-secrete' dans config/config.local.php\npuis ouvre /diag.php?k=une-cle-secrete\n");
    }
}

function line(string $label, bool $ok, string $detail = ''): void
{
    echo str_pad($label, 40) . ($ok ? '[ OK ]' : '[ PROBLÈME ]') . ($detail !== '' ? "  $detail" : '') . "\n";
}

echo "================= LASTFIT — DIAGNOSTIC =================\n\n";
echo 'PHP : ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . "\n\n";

// --- 1. Structure & fichiers essentiels --------------------------------------
line('structure public/ + projet au-dessus', $okStruct,
    $okStruct ? '' : ($flat ? '→ tout a été uploadé dans le même dossier (à plat)' : '→ app/ INTROUVABLE au-dessus de public/ : uploader app/, config/, database/, storage/ à côté de public/'));
line('public/index.php présent', is_file(__DIR__ . '/index.php'));
line('public/.htaccess présent', is_file(__DIR__ . '/.htaccess'),
    is_file(__DIR__ . '/.htaccess') ? '' : "→ fichier caché non uploadé ? Activer \"Forcer l'affichage des fichiers cachés\" dans FileZilla");
line('public/assets/ présent', is_dir(__DIR__ . '/assets'));
line('app/bootstrap.php présent', is_file($base . '/app/bootstrap.php'));
line('config/config.php présent', is_file($base . '/config/config.php'));

$docroot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$here    = rtrim(str_replace('\\', '/', __DIR__), '/');
line('docroot pointe bien ici', $docroot === '' || $docroot === $here,
    $docroot === $here ? '' : '→ le domaine ne pointe pas sur le dossier public/ contenant ce fichier');

// --- 2. Configuration ----------------------------------------------------------
line('config/config.local.php présent', is_file($localFile),
    is_file($localFile) ? '' : '→ copier config/config.local.example.php en config.local.php et le renseigner');

// --- 3. Sessions ------------------------------------------------------------------
$sessDir = $base . '/storage/sessions';
line('storage/sessions existe', is_dir($sessDir), is_dir($sessDir) ? '' : '→ créer le dossier storage/sessions');
line('storage/sessions inscriptible', is_dir($sessDir) && is_writable($sessDir),
    (is_dir($sessDir) && is_writable($sessDir)) ? '' : '→ chmod 775 sur storage/ ET storage/sessions');

// --- 4. Base de données -------------------------------------------------------------
if (is_file($localFile)) {
    $driver = $cfg['db_driver'] ?? 'sqlite';
    echo "\nDriver BDD configuré : $driver\n";
    try {
        if ($driver === 'mysql') {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $cfg['db_host'] ?? 'localhost', $cfg['db_port'] ?? '3306', $cfg['db_name'] ?? ''),
                $cfg['db_user'] ?? '', $cfg['db_pass'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } else {
            $pdo = new PDO('sqlite:' . ($cfg['db_sqlite_path'] ?? $base . '/database/lastfit.sqlite'));
        }
        $pdo->query('SELECT COUNT(*) FROM users');
        line('connexion base de données', true);
    } catch (Throwable $e) {
        // Pas de message détaillé (il peut contenir l'utilisateur/hôte MySQL).
        $hint = $e instanceof PDOException && str_contains($e->getMessage(), 'Access denied')
            ? '→ identifiants MySQL refusés (vérifier db_user/db_pass/db_name dans config.local.php)'
            : '→ vérifier db_host/db_name dans config.local.php, et que les tables sont importées (lastfit_mysql.sql)';
        line('connexion base de données', false, $hint);
    }
} else {
    line('connexion base de données', false, '→ config.local.php manquant, test impossible');
}

// --- 5. Dernière étape ----------------------------------------------------------------
echo "\nDernière étape : ouvre /api/health — si tu vois du JSON, TOUT fonctionne.\n";
echo "Si /api/health donne 404 alors que tout est OK ci-dessus,\n";
echo "le .htaccess n'est pas lu → demander à Nuxit d'activer mod_rewrite / AllowOverride All.\n";
echo "\n=========================================================\n";
