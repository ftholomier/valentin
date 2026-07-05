<?php
/** Cours disponibles : liste filtrable et fiche détaillée. */
class SlotsController
{
    /**
     * GET /api/slots
     * Query : ville, sport, date (ce-soir|aujourdhui|demain|week-end), q, lat, lng
     * Renvoie chaque cours enrichi (salle, distance, % de réduction).
     */
    public static function index(): void
    {
        $where  = ["s.statut != 'termine'"];
        $params = [];

        if (!empty($_GET['ville'])) {
            $where[]  = 'p.ville = ?';
            $params[] = Helpers::str($_GET['ville']);
        }
        if (!empty($_GET['sport'])) {
            $where[]  = 's.sport = ?';
            $params[] = Helpers::str($_GET['sport']);
        }
        if (!empty($_GET['q'])) {
            $like     = '%' . Helpers::str($_GET['q']) . '%';
            $where[]  = '(s.titre LIKE ? OR p.nom LIKE ? OR s.sport LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (!empty($_GET['date'])) {
            [$from, $to] = self::dateRange(Helpers::str($_GET['date']));
            if ($from !== null) {
                $where[]  = 's.date_debut BETWEEN ? AND ?';
                array_push($params, $from, $to);
            }
        }

        $sql = 'SELECT s.*, p.nom AS salle, p.ville, p.adresse, p.lat, p.lng, p.note
                FROM slots s
                JOIN partners p ON p.id = s.partner_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.date_debut ASC';

        $rows = Database::all($sql, $params);

        $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : DEFAULT_LAT;
        $lng = isset($_GET['lng']) ? (float) $_GET['lng'] : DEFAULT_LNG;

        $data = array_map(fn($r) => self::present($r, $lat, $lng), $rows);
        Response::ok($data);
    }

    /** GET /api/slots/{id} */
    public static function show(int $id): void
    {
        $row = Database::one(
            'SELECT s.*, p.nom AS salle, p.description AS salle_description, p.ville,
                    p.adresse, p.lat, p.lng, p.equipements, p.note
             FROM slots s JOIN partners p ON p.id = s.partner_id
             WHERE s.id = ?',
            [$id]
        );
        if ($row === null) {
            Response::error('Cours introuvable.', 404);
        }

        $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : DEFAULT_LAT;
        $lng = isset($_GET['lng']) ? (float) $_GET['lng'] : DEFAULT_LNG;

        $data = self::present($row, $lat, $lng);
        $data['salle_description'] = $row['salle_description'] ?? null;
        $data['equipements']       = $row['equipements']
            ? array_map('trim', explode(',', $row['equipements'])) : [];

        Response::ok($data);
    }

    /** Mise en forme commune d'une ligne de cours pour l'API. */
    private static function present(array $r, float $lat, float $lng): array
    {
        $distance = ($r['lat'] !== null && $r['lng'] !== null)
            ? Helpers::distanceKm($lat, $lng, (float) $r['lat'], (float) $r['lng'])
            : null;

        return [
            'id'               => (int) $r['id'],
            'sport'            => $r['sport'],
            'titre'            => $r['titre'],
            'coach'            => $r['coach'],
            'salle'            => $r['salle'],
            'ville'            => $r['ville'],
            'adresse'          => $r['adresse'] ?? null,
            'lat'              => $r['lat'] !== null ? (float) $r['lat'] : null,
            'lng'              => $r['lng'] !== null ? (float) $r['lng'] : null,
            'note'             => (float) $r['note'],
            'date_debut'       => $r['date_debut'],
            'duree_min'        => (int) $r['duree_min'],
            'prix_initial'     => (float) $r['prix_initial'],
            'prix_reduit'      => (float) $r['prix_reduit'],
            'reduction'        => Helpers::discountPercent((float) $r['prix_initial'], (float) $r['prix_reduit']),
            'places_totales'   => (int) $r['places_totales'],
            'places_restantes' => (int) $r['places_restantes'],
            'image_url'        => $r['image_url'],
            'statut'           => $r['statut'],
            'distance_km'      => $distance,
        ];
    }

    /** Traduit un mot-clé de date en intervalle [début, fin]. */
    private static function dateRange(string $key): array
    {
        $today = date('Y-m-d');
        switch ($key) {
            case 'ce-soir':
            case 'aujourdhui':
                return ["$today 00:00:00", "$today 23:59:59"];
            case 'demain':
                $d = date('Y-m-d', strtotime('+1 day'));
                return ["$d 00:00:00", "$d 23:59:59"];
            case 'week-end':
                $sat = date('Y-m-d', strtotime('saturday this week'));
                $sun = date('Y-m-d', strtotime('sunday this week'));
                return ["$sat 00:00:00", "$sun 23:59:59"];
            default:
                return [null, null];
        }
    }
}
