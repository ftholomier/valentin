<?php
/** Espace pro (salle) : cours, remplissage et chiffre d'affaires. */
class ProController
{
    /** GET /api/pro/dashboard — vue d'ensemble de la salle du partenaire connecté. */
    public static function dashboard(): void
    {
        $user = Auth::requireRole(['partner', 'admin']);
        $pid  = (int) ($user['partner_id'] ?? 0);
        if ($pid <= 0) {
            Response::error('Aucune salle rattachée à ce compte.', 400);
        }

        $partner = Database::one('SELECT id, nom, ville, adresse, note FROM partners WHERE id = ?', [$pid]);
        if (!$partner) {
            Response::error('Salle introuvable.', 404);
        }

        // Cours de la salle avec places vendues, billets et CA (part salle).
        $slots = Database::all(
            "SELECT s.id, s.sport, s.titre, s.coach, s.date_debut, s.duree_min,
                    s.prix_reduit, s.places_totales, s.places_restantes, s.statut,
                    (s.places_totales - s.places_restantes) AS vendus,
                    (SELECT COUNT(*) FROM bookings b
                       WHERE b.slot_id = s.id AND b.statut_paiement IN ('paye','valide')) AS billets,
                    (SELECT COUNT(*) FROM bookings b
                       WHERE b.slot_id = s.id AND b.statut_paiement = 'valide') AS valides,
                    (SELECT COALESCE(SUM(b.montant_salle),0) FROM bookings b
                       WHERE b.slot_id = s.id AND b.statut_paiement IN ('paye','valide')) AS ca_salle
             FROM slots s
             WHERE s.partner_id = ?
             ORDER BY s.date_debut ASC",
            [$pid]
        );

        $today = date('Y-m-d');
        $slotsOut = [];
        $placesJour = 0; $venduesJour = 0; $coursJour = 0;
        foreach ($slots as $s) {
            $isToday = strpos($s['date_debut'], $today) === 0;
            if ($isToday) {
                $coursJour++;
                $placesJour  += (int) $s['places_totales'];
                $venduesJour += (int) $s['vendus'];
            }
            $slotsOut[] = [
                'id'               => (int) $s['id'],
                'sport'            => $s['sport'],
                'titre'            => $s['titre'],
                'coach'            => $s['coach'],
                'date_debut'       => $s['date_debut'],
                'duree_min'        => (int) $s['duree_min'],
                'prix_reduit'      => (float) $s['prix_reduit'],
                'places_totales'   => (int) $s['places_totales'],
                'places_restantes' => (int) $s['places_restantes'],
                'vendus'           => (int) $s['vendus'],
                'billets'          => (int) $s['billets'],
                'valides'          => (int) $s['valides'],
                'ca_salle'         => (float) $s['ca_salle'],
                'statut'           => $s['statut'],
                'aujourdhui'       => $isToday,
            ];
        }

        // CA & commission cumulés (tous les billets payés/validés de la salle).
        $totaux = Database::one(
            "SELECT COALESCE(SUM(b.montant_salle),0) AS ca_total,
                    COALESCE(SUM(b.commission),0)    AS commission_total,
                    COUNT(*)                          AS billets_total
             FROM bookings b JOIN slots s ON s.id = b.slot_id
             WHERE s.partner_id = ? AND b.statut_paiement IN ('paye','valide')",
            [$pid]
        );

        Response::ok([
            'partner' => $partner,
            'stats'   => [
                'cours_jour'        => $coursJour,
                'places_jour'       => $placesJour,
                'vendues_jour'      => $venduesJour,
                'remplissage_jour'  => $placesJour > 0 ? (int) round($venduesJour / $placesJour * 100) : 0,
                'ca_total'          => (float) $totaux['ca_total'],
                'commission_total'  => (float) $totaux['commission_total'],
                'billets_total'     => (int) $totaux['billets_total'],
            ],
            'slots'   => $slotsOut,
        ]);
    }
}
