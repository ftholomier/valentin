<?php
/** Authentification simple par session PHP + gestion des mots de passe. */
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]);
            session_start();
        }
    }

    public static function login(int $userId): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function id(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /** Retourne l'utilisateur courant (sans le hash) ou null. */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        $u = Database::one(
            'SELECT id, role, nom, email, ville, created_at FROM users WHERE id = ?',
            [$id]
        );
        return $u ?: null;
    }

    /** Garde-fou : coupe la requête si non authentifié. */
    public static function requireLogin(): array
    {
        $u = self::user();
        if ($u === null) {
            Response::error('Authentification requise.', 401);
        }
        return $u;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
