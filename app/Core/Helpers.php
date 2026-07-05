<?php
/** Fonctions utilitaires transverses (distance, tokens, formats). */
class Helpers
{
    /**
     * Distance à vol d'oiseau entre deux points GPS (formule de Haversine), en km.
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371.0; // rayon terrestre moyen en km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($R * $c, 2);
    }

    /** Token unique pour le QR code d'une réservation. */
    public static function qrToken(): string
    {
        return 'LF-' . strtoupper(bin2hex(random_bytes(8)));
    }

    /** Pourcentage de réduction entre prix initial et prix réduit. */
    public static function discountPercent(float $initial, float $reduit): int
    {
        if ($initial <= 0) {
            return 0;
        }
        return (int) round((1 - $reduit / $initial) * 100);
    }

    /**
     * Ventile un montant payé entre commission plateforme et part salle.
     * @return array{commission: float, montant_salle: float}
     */
    public static function splitCommission(float $montant, float $taux): array
    {
        $commission = round($montant * $taux, 2);
        return [
            'commission'    => $commission,
            'montant_salle' => round($montant - $commission, 2),
        ];
    }

    /** Nettoie une chaîne (trim + suppression des caractères de contrôle). */
    public static function str($value): string
    {
        return trim((string) $value);
    }

    public static function isEmail($value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
