<?php
/** Expose la configuration dynamique de la plateforme. */
class ConfigController
{
    public static function index(): void
    {
        $rows = Database::all('SELECT cle, valeur FROM config');
        $cfg = [];
        foreach ($rows as $r) {
            $cfg[$r['cle']] = $r['valeur'];
        }

        Response::ok([
            'commission_rate' => (float) ($cfg['commission_rate'] ?? 0.15),
            'sports'          => json_decode($cfg['sports'] ?? '[]', true),
            'villes'          => json_decode($cfg['villes'] ?? '[]', true),
        ]);
    }

    /** Taux de commission courant (utilitaire interne). */
    public static function commissionRate(): float
    {
        $row = Database::one('SELECT valeur FROM config WHERE cle = ?', ['commission_rate']);
        return $row ? (float) $row['valeur'] : 0.15;
    }

    /** Écrit (ou crée) une valeur de configuration de façon portable SQLite/MySQL. */
    public static function set(string $cle, string $valeur): void
    {
        $exists = Database::one('SELECT cle FROM config WHERE cle = ?', [$cle]);
        if ($exists) {
            Database::run('UPDATE config SET valeur = ? WHERE cle = ?', [$valeur, $cle]);
        } else {
            Database::run('INSERT INTO config (cle, valeur) VALUES (?, ?)', [$cle, $valeur]);
        }
    }
}
