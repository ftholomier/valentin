<?php
/** Inscription, connexion, session. */
class AuthController
{
    public static function register(): void
    {
        $b     = Response::body();
        $nom   = Helpers::str($b['nom'] ?? '');
        $email = strtolower(Helpers::str($b['email'] ?? ''));
        $pass  = (string) ($b['password'] ?? '');
        $ville = Helpers::str($b['ville'] ?? '');

        $errors = [];
        if ($nom === '')                 $errors['nom']      = 'Nom requis.';
        if (!Helpers::isEmail($email))   $errors['email']    = 'Email invalide.';
        if (strlen($pass) < 6)           $errors['password'] = '6 caractères minimum.';
        if ($errors) {
            Response::error('Formulaire invalide.', 422, $errors);
        }

        $exists = Database::one('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            Response::error('Un compte existe déjà avec cet email.', 409);
        }

        Database::run(
            'INSERT INTO users (role, nom, email, password_hash, ville, created_at) VALUES (?,?,?,?,?,?)',
            ['sportif', $nom, $email, Auth::hash($pass), $ville, date('Y-m-d H:i:s')]
        );
        $id = (int) Database::lastId();
        Auth::login($id);

        Response::ok(Auth::user(), 'Compte créé.');
    }

    public static function login(): void
    {
        $b     = Response::body();
        $email = strtolower(Helpers::str($b['email'] ?? ''));
        $pass  = (string) ($b['password'] ?? '');

        $user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user || !Auth::verify($pass, $user['password_hash'])) {
            Response::error('Email ou mot de passe incorrect.', 401);
        }

        Auth::login((int) $user['id']);
        Response::ok(Auth::user(), 'Connecté.');
    }

    public static function logout(): void
    {
        Auth::logout();
        Response::ok(null, 'Déconnecté.');
    }

    public static function me(): void
    {
        Response::ok(Auth::user());
    }
}
