<?php
/** Backoffice admin : statistiques globales + gestion commission / sports / villes. */
class AdminController
{
    /** GET /api/admin/overview — KPI plateforme + configuration courante. */
    public static function overview(): void
    {
        Auth::requireRole(['admin']);

        $stats = [
            'sportifs'      => (int) Database::one("SELECT COUNT(*) n FROM users WHERE role = 'sportif'")['n'],
            'salles'        => (int) Database::one('SELECT COUNT(*) n FROM partners')['n'],
            'cours'         => (int) Database::one('SELECT COUNT(*) n FROM slots')['n'],
            'reservations'  => (int) Database::one("SELECT COUNT(*) n FROM bookings WHERE statut_paiement IN ('paye','valide')")['n'],
            'ca_commission' => (float) Database::one("SELECT COALESCE(SUM(commission),0) s FROM bookings WHERE statut_paiement IN ('paye','valide')")['s'],
            'ca_total'      => (float) Database::one("SELECT COALESCE(SUM(montant_paye),0) s FROM bookings WHERE statut_paiement IN ('paye','valide')")['s'],
        ];

        $rows = Database::all('SELECT cle, valeur FROM config');
        $cfg = [];
        foreach ($rows as $r) {
            $cfg[$r['cle']] = $r['valeur'];
        }

        Response::ok([
            'stats'  => $stats,
            'config' => [
                'commission_rate' => (float) ($cfg['commission_rate'] ?? 0.15),
                'sports'          => json_decode($cfg['sports'] ?? '[]', true) ?: [],
                'villes'          => json_decode($cfg['villes'] ?? '[]', true) ?: [],
            ],
        ]);
    }

    /**
     * POST /api/admin/config  { commission_rate?, sports?, villes? }
     * Met à jour uniquement les clés fournies.
     */
    public static function updateConfig(): void
    {
        Auth::requireRole(['admin']);
        $b = Response::body();

        // --- Taux de commission (0 à 90 %) ---
        if (array_key_exists('commission_rate', $b)) {
            $rate = (float) $b['commission_rate'];
            if ($rate < 0 || $rate > 0.9) {
                Response::error('Le taux de commission doit être compris entre 0 et 0,9.', 422);
            }
            ConfigController::set('commission_rate', (string) round($rate, 4));
        }

        // --- Liste des sports ---
        if (array_key_exists('sports', $b)) {
            $sports = self::cleanList($b['sports']);
            if (!$sports) {
                Response::error('La liste des sports ne peut pas être vide.', 422);
            }
            ConfigController::set('sports', json_encode($sports, JSON_UNESCAPED_UNICODE));
        }

        // --- Liste des villes ---
        if (array_key_exists('villes', $b)) {
            $villes = self::cleanList($b['villes']);
            if (!$villes) {
                Response::error('La liste des villes ne peut pas être vide.', 422);
            }
            ConfigController::set('villes', json_encode($villes, JSON_UNESCAPED_UNICODE));
        }

        self::overview();
    }

    /** Normalise une liste : trim, non vide, unique, réindexée. */
    private static function cleanList($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '' && !in_array($item, $out, true)) {
                $out[] = $item;
            }
        }
        return $out;
    }
}
