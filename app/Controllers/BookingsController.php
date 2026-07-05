<?php
/** Réservations flash : création (avec réservation d'une place), historique, validation QR. */
class BookingsController
{
    /** Durée de vie d'une réservation non payée avant libération de la place (minutes). */
    private const PENDING_TTL_MIN = 15;

    /**
     * Libère les places des réservations "en_attente" abandonnées (plus vieilles
     * que PENDING_TTL_MIN). Appelé avant les lectures/écritures sensibles pour
     * que le stock reste juste sans dépendre d'un cron.
     */
    public static function expireStale(): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - self::PENDING_TTL_MIN * 60);
        $stale  = Database::all(
            "SELECT id, slot_id FROM bookings
             WHERE statut_paiement = 'en_attente' AND created_at < ?",
            [$cutoff]
        );
        foreach ($stale as $b) {
            self::cancelPending((int) $b['id'], (int) $b['slot_id']);
        }
    }

    /**
     * Annule une réservation "en_attente" et restitue sa place, de façon atomique.
     * Retourne true si l'annulation a bien eu lieu (false si déjà traitée ailleurs).
     */
    public static function cancelPending(int $bookingId, int $slotId): bool
    {
        Database::begin();
        try {
            // CAS : ne s'annule que si toujours en attente (évite les doubles restitutions).
            $upd = Database::run(
                "UPDATE bookings SET statut_paiement = 'annule'
                 WHERE id = ? AND statut_paiement = 'en_attente'",
                [$bookingId]
            );
            if ($upd->rowCount() === 1) {
                Database::run(
                    "UPDATE slots
                     SET places_restantes = places_restantes + 1,
                         statut = CASE WHEN statut = 'complet' THEN 'disponible' ELSE statut END
                     WHERE id = ? AND places_restantes < places_totales",
                    [$slotId]
                );
                Database::commit();
                return true;
            }
            Database::commit();
            return false;
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * POST /api/bookings  { slot_id }
     * Réserve une place de façon atomique et crée une réservation en attente de paiement.
     */
    public static function create(): void
    {
        $user = Auth::requireLogin();
        $b    = Response::body();
        $slotId = (int) ($b['slot_id'] ?? 0);
        if ($slotId <= 0) {
            Response::error('Cours non précisé.', 422);
        }

        self::expireStale();

        Database::begin();
        try {
            $slot = Database::one('SELECT * FROM slots WHERE id = ?', [$slotId]);
            if (!$slot) {
                Database::rollback();
                Response::error('Cours introuvable.', 404);
            }
            if ($slot['statut'] === 'termine' || strtotime($slot['date_debut']) <= time()) {
                Database::rollback();
                Response::error('Ce cours est déjà passé.', 409);
            }

            // Un seul billet actif par utilisateur et par cours.
            $existing = Database::one(
                "SELECT id FROM bookings
                 WHERE user_id = ? AND slot_id = ? AND statut_paiement IN ('en_attente','paye')",
                [$user['id'], $slotId]
            );
            if ($existing) {
                Database::rollback();
                Response::error('Vous avez déjà une réservation pour ce cours.', 409, [
                    'booking_id' => (int) $existing['id'],
                ]);
            }

            // Réservation atomique de la place : ne passe que s'il en reste.
            $upd = Database::run(
                'UPDATE slots SET places_restantes = places_restantes - 1
                 WHERE id = ? AND places_restantes > 0',
                [$slotId]
            );
            if ($upd->rowCount() !== 1) {
                Database::rollback();
                Response::error('Plus de place disponible pour ce cours.', 409);
            }

            // Passe le cours en "complet" s'il ne reste plus rien.
            Database::run(
                "UPDATE slots SET statut = 'complet'
                 WHERE id = ? AND places_restantes = 0",
                [$slotId]
            );

            // Ventilation commission / part salle, figée au moment de la réservation.
            $montant = (float) $slot['prix_reduit'];
            $rate    = ConfigController::commissionRate();
            $split   = Helpers::splitCommission($montant, $rate);

            Database::run(
                'INSERT INTO bookings
                    (user_id, slot_id, statut_paiement, montant_paye, commission, montant_salle, created_at)
                 VALUES (?,?,?,?,?,?,?)',
                [$user['id'], $slotId, 'en_attente', 0, $split['commission'], $split['montant_salle'], date('Y-m-d H:i:s')]
            );
            $bookingId = (int) Database::lastId();

            Database::commit();

            Response::ok([
                'booking_id'    => $bookingId,
                'slot_id'       => $slotId,
                'titre'         => $slot['titre'],
                'montant'       => $montant,
                'commission'    => $split['commission'],
                'montant_salle' => $split['montant_salle'],
                'statut_paiement' => 'en_attente',
            ], 'Place réservée — en attente de paiement.');
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /** GET /api/bookings — historique du sportif connecté. */
    public static function index(): void
    {
        $user = Auth::requireLogin();
        $rows = Database::all(
            'SELECT b.id, b.statut_paiement, b.qr_token, b.montant_paye, b.created_at, b.validated_at,
                    s.titre, s.sport, s.date_debut, s.duree_min,
                    p.nom AS salle, p.adresse, p.ville
             FROM bookings b
             JOIN slots s    ON s.id = b.slot_id
             JOIN partners p ON p.id = s.partner_id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC',
            [$user['id']]
        );

        $data = array_map(fn($r) => [
            'id'              => (int) $r['id'],
            'titre'           => $r['titre'],
            'sport'           => $r['sport'],
            'salle'           => $r['salle'],
            'adresse'         => $r['adresse'],
            'ville'           => $r['ville'],
            'date_debut'      => $r['date_debut'],
            'duree_min'       => (int) $r['duree_min'],
            'montant_paye'    => (float) $r['montant_paye'],
            'statut_paiement' => $r['statut_paiement'],
            'qr_token'        => $r['qr_token'],
            'validated_at'    => $r['validated_at'],
            'created_at'      => $r['created_at'],
        ], $rows);

        Response::ok($data);
    }

    /**
     * POST /api/bookings/validate  { qr_token }
     * Côté salle : valide l'entrée d'un sportif à partir de son token QR.
     */
    public static function validate(): void
    {
        // Réservé aux salles (et admin) : seule la salle propriétaire peut valider.
        $user  = Auth::requireRole(['partner', 'admin']);
        $b     = Response::body();
        $token = Helpers::str($b['qr_token'] ?? '');
        if ($token === '') {
            Response::error('Token QR manquant.', 422);
        }

        $booking = Database::one(
            'SELECT b.*, s.titre, s.date_debut, s.partner_id, u.nom AS client
             FROM bookings b
             JOIN slots s ON s.id = b.slot_id
             JOIN users u ON u.id = b.user_id
             WHERE b.qr_token = ?',
            [$token]
        );
        if (!$booking) {
            Response::error('QR code inconnu.', 404);
        }
        // Un partenaire ne valide que les billets de SA salle.
        if ($user['role'] === 'partner' && (int) $booking['partner_id'] !== (int) $user['partner_id']) {
            Response::error("Ce billet n'appartient pas à votre salle.", 403);
        }
        if ($booking['statut_paiement'] === 'valide') {
            Response::error('Ce billet a déjà été validé.', 409, [
                'validated_at' => $booking['validated_at'],
            ]);
        }
        if ($booking['statut_paiement'] !== 'paye') {
            Response::error('Billet non payé — entrée refusée.', 402);
        }

        // CAS atomique : deux scans simultanés du même billet → un seul passe.
        $upd = Database::run(
            "UPDATE bookings SET statut_paiement = 'valide', validated_at = ?
             WHERE id = ? AND statut_paiement = 'paye'",
            [date('Y-m-d H:i:s'), $booking['id']]
        );
        if ($upd->rowCount() !== 1) {
            Response::error('Ce billet a déjà été validé.', 409);
        }

        Response::ok([
            'client'     => $booking['client'],
            'titre'      => $booking['titre'],
            'date_debut' => $booking['date_debut'],
        ], 'Entrée validée ✅');
    }
}
