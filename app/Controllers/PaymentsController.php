<?php
/**
 * Paiement — simulation Stripe (mock).
 * Le paiement finalise une réservation "en_attente" : il la passe "payé"
 * et génère le token QR d'accès. La logique est isolée pour pouvoir être
 * remplacée par un vrai webhook Stripe plus tard sans toucher au frontend.
 */
class PaymentsController
{
    /** POST /api/payments/checkout  { booking_id, card? } */
    public static function checkout(): void
    {
        $user = Auth::requireLogin();
        $b    = Response::body();
        $bookingId = (int) ($b['booking_id'] ?? 0);
        $card      = preg_replace('/\s+/', '', (string) ($b['card'] ?? ''));

        $booking = Database::one(
            'SELECT * FROM bookings WHERE id = ? AND user_id = ?',
            [$bookingId, $user['id']]
        );
        if (!$booking) {
            Response::error('Réservation introuvable.', 404);
        }
        if ($booking['statut_paiement'] === 'paye' || $booking['statut_paiement'] === 'valide') {
            Response::error('Cette réservation est déjà payée.', 409);
        }
        if ($booking['statut_paiement'] !== 'en_attente') {
            Response::error('Réservation non payable.', 409);
        }

        // --- Simulation du refus (numéro de test façon Stripe) ---
        if ($card !== '' && $card === STRIPE_TEST_DECLINE) {
            self::releaseSeat((int) $booking['slot_id'], $bookingId);
            Response::error('Paiement refusé par la banque (carte de test).', 402);
        }

        // --- Paiement accepté : on finalise ---
        $slot    = Database::one('SELECT prix_reduit FROM slots WHERE id = ?', [$booking['slot_id']]);
        $montant = $slot ? (float) $slot['prix_reduit'] : (float) $booking['montant_paye'];
        $token   = Helpers::qrToken();

        Database::run(
            "UPDATE bookings
             SET statut_paiement = 'paye', montant_paye = ?, qr_token = ?
             WHERE id = ?",
            [$montant, $token, $bookingId]
        );

        Response::ok([
            'booking_id'      => $bookingId,
            'statut_paiement' => 'paye',
            'montant_paye'    => $montant,
            'qr_token'        => $token,
            'transaction_id'  => 'sim_' . strtoupper(bin2hex(random_bytes(6))),
        ], 'Paiement accepté.');
    }

    /** Libère la place et annule la réservation (paiement échoué/abandonné). */
    private static function releaseSeat(int $slotId, int $bookingId): void
    {
        Database::begin();
        try {
            Database::run(
                "UPDATE slots SET places_restantes = places_restantes + 1,
                        statut = CASE WHEN statut = 'complet' THEN 'disponible' ELSE statut END
                 WHERE id = ?",
                [$slotId]
            );
            Database::run(
                "UPDATE bookings SET statut_paiement = 'annule' WHERE id = ?",
                [$bookingId]
            );
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }
}
