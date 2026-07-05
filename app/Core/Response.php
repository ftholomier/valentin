<?php
/** Helpers de réponse HTTP JSON pour l'API. */
class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // Jamais de cache sur l'API (évite les boucles de redirection sur /auth/me).
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok($data = null, string $message = ''): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    public static function error(string $message, int $status = 400, $errors = null): void
    {
        self::json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    /** Corps JSON de la requête, décodé en tableau associatif. */
    public static function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            return $_POST ?: [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Repli formulaire classique (urlencoded / multipart).
        if (!empty($_POST)) {
            return $_POST;
        }
        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }
}
