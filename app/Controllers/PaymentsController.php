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

        // Libère les réservations abandonnées avant de traiter le paiement.
        BookingsController::expireStale();

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
            Response::error('Réservation expirée ou annulée — merci de réserver à nouveau.', 409);
        }

        // --- Simulation du refus (numéro de test façon Stripe) ---
        if ($card !== '' && $card === STRIPE_TEST_DECLINE) {
            BookingsController::cancelPending($bookingId, (int) $booking['slot_id']);
            Response::error('Paiement refusé par la banque (carte de test).', 402);
        }

        // --- Paiement accepté : montant FIGÉ à la réservation (commission + part salle) ---
        $montant = round((float) $booking['commission'] + (float) $booking['montant_salle'], 2);
        $token   = Helpers::qrToken();

        // CAS atomique : deux paiements simultanés du même billet → un seul passe.
        $upd = Database::run(
            "UPDATE bookings
             SET statut_paiement = 'paye', montant_paye = ?, qr_token = ?
             WHERE id = ? AND statut_paiement = 'en_attente'",
            [$montant, $token, $bookingId]
        );
        if ($upd->rowCount() !== 1) {
            Response::error("Cette réservation vient déjà d'être payée.", 409);
        }

        Response::ok([
            'booking_id'      => $bookingId,
            'statut_paiement' => 'paye',
            'montant_paye'    => $montant,
            'qr_token'        => $token,
            'transaction_id'  => 'sim_' . strtoupper(bin2hex(random_bytes(6))),
        ], 'Paiement accepté.');
    }
}
