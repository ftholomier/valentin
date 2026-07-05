<?php
/**
 * Couche d'accès aux données — PDO natif (aucun ORM).
 * Singleton de connexion, supporte SQLite (dev) et MySQL (prod) via config.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if (DB_DRIVER === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } else {
            $dir = dirname(DB_SQLITE_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
            // Intégrité référentielle + accès concurrent plus souple.
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
        }

        return self::$pdo;
    }

    /** Retourne toutes les lignes d'une requête préparée. */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Retourne une seule ligne (ou null). */
    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Exécute une requête (INSERT/UPDATE/DELETE) et renvoie le PDOStatement. */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function lastId(): string
    {
        return self::pdo()->lastInsertId();
    }

    public static function begin(): void { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollback(): void
    {
        // N'utilise PAS pdo() : si la connexion initiale a échoué, retenter la
        // connexion dans un bloc catch masquerait l'erreur d'origine (500 vide).
        if (self::$pdo instanceof PDO && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }
}
