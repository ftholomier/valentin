<?php
/**
 * Installateur / seed de la base LastFit.
 * Usage (dev, SQLite) :   php database/install.php
 * Crée le schéma puis injecte un jeu de données de démo (Lyon, ce soir).
 *
 * En prod MySQL : importe plutôt database/lastfit_mysql.sql via phpMyAdmin.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$isCli = (php_sapi_name() === 'cli');
function say(string $m, bool $cli): void { echo $m . ($cli ? "\n" : "<br>"); }

// --- Sécurité : en web, on n'autorise l'install qu'en environnement dev ---
if (!$isCli && APP_ENV !== 'dev') {
    http_response_code(403);
    exit('Installation web désactivée en production. Utilisez la CLI ou importez le .sql.');
}

$pdo = Database::pdo();

// 1) Schéma -----------------------------------------------------------------
if (DB_DRIVER === 'sqlite') {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
} else {
    // MySQL : on rejoue le dump complet (schéma + données) et on s'arrête là.
    $pdo->exec(file_get_contents(__DIR__ . '/lastfit_mysql.sql'));
    say('Base MySQL initialisée depuis lastfit_mysql.sql.', $isCli);
    return;
}
say('Schéma créé.', $isCli);

// 2) Configuration ----------------------------------------------------------
$config = [
    'commission_rate' => '0.15',
    'sports'          => json_encode(['Yoga','CrossFit','Pilates','Boxe','Cycling','HIIT','Zumba','Aquagym'], JSON_UNESCAPED_UNICODE),
    'villes'          => json_encode(['Lyon','Paris','Bordeaux','Marseille'], JSON_UNESCAPED_UNICODE),
];
foreach ($config as $cle => $valeur) {
    Database::run('INSERT INTO config (cle, valeur) VALUES (?, ?)', [$cle, $valeur]);
}
say('Configuration insérée.', $isCli);

// 3) Salles partenaires (autour de Lyon) ------------------------------------
$partners = [
    ['Studio Lumen', 'Studio de yoga & Pilates baigné de lumière, en plein cœur de la Presqu\'île.', '12 rue de la République, 69002 Lyon', 'Lyon', 45.7645, 4.8352, 'Tapis fournis, Douches, Vestiaires', 4.9],
    ['CrossBox 12', 'Box CrossFit haute intensité, coachs certifiés, matériel neuf.', '34 cours Lafayette, 69003 Lyon', 'Lyon', 45.7620, 4.8560, 'Rig complet, Assault bikes, Douches', 4.7],
    ['Coreo Pilates', 'Le spécialiste du Reformer à Lyon, cours en petit comité.', '8 rue Auguste Comte, 69002 Lyon', 'Lyon', 45.7560, 4.8300, 'Reformers, Cardio, Vestiaires', 5.0],
    ['Ring Club', 'Salle de boxe anglaise & cardio-boxing, ambiance club.', '21 rue Paul Bert, 69003 Lyon', 'Lyon', 45.7600, 4.8600, 'Rings, Sacs, Douches', 4.8],
    ['Pulse Cycling', 'Studio de cycling indoor immersif, son et lumière.', '5 quai Victor Augagneur, 69003 Lyon', 'Lyon', 45.7580, 4.8450, 'Vélos connectés, Douches, Serviettes', 4.6],
];
$partnerIds = [];
foreach ($partners as $p) {
    Database::run(
        'INSERT INTO partners (nom, description, adresse, ville, lat, lng, equipements, note)
         VALUES (?,?,?,?,?,?,?,?)',
        $p
    );
    $partnerIds[$p[0]] = (int) Database::lastId();
}
say(count($partnerIds) . ' salles insérées.', $isCli);

// 4) Cours à venir (datés relativement à maintenant : toujours réservables) ---
$img = fn($id) => "https://images.unsplash.com/photo-$id?auto=format&fit=crop&w=800&q=80";

$slots = [
    // [salle, sport, titre, coach, +minutes, durée, prix_initial, prix_reduit, total, restantes, image]
    ['Studio Lumen', 'Yoga',     'Vinyasa Flow',        'Camille R.',  90, 60, 18, 8,  14, 5, '1544367567-0f2fcb009e0b'],
    ['CrossBox 12',  'CrossFit', 'WOD du soir',         'Marco T.',    45, 60, 20, 12, 16, 3, '1534438327276-14e5300c3a48'],
    ['Coreo Pilates','Pilates',  'Pilates Reformer',    'Julie B.',   150, 55, 26, 9,  8,  2, '1571902943202-507ec2618e8f'],
    ['Ring Club',    'Boxe',     'Boxing Cardio',       'Sofiane K.',  60, 60, 22, 11, 12, 6, '1549719386-74dfcbf7dbed'],
    ['Pulse Cycling','Cycling',  'Ride Nocturne',       'Ana P.',      75, 45, 19, 10, 20, 8, '1518310383802-640c2de311b2'],
    ['Studio Lumen', 'Yoga',     'Yin & Relaxation',    'Camille R.', 180, 60, 16, 7,  12, 4, '1552196563-55cd4e45efb3'],
    ['CrossBox 12',  'HIIT',     'HIIT Express',        'Marco T.',   105, 45, 18, 9,  15, 7, '1534258936925-c58bed479fcb'],
    ['Coreo Pilates','Pilates',  'Reformer Débutant',   'Julie B.',    30, 55, 24, 10, 8,  1, '1517836357463-d25dfeac3438'],
];
$stmt = 0;
foreach ($slots as $s) {
    [$salle, $sport, $titre, $coach, $plusMin, $duree, $pi, $pr, $tot, $rest, $imgId] = $s;
    Database::run(
        'INSERT INTO slots
           (partner_id, sport, titre, coach, date_debut, duree_min, prix_initial, prix_reduit, places_totales, places_restantes, image_url, statut)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $partnerIds[$salle], $sport, $titre, $coach,
            date('Y-m-d H:i:s', time() + $plusMin * 60), $duree, $pi, $pr, $tot, $rest,
            $img($imgId),
            $rest > 0 ? 'disponible' : 'complet',
        ]
    );
    $stmt++;
}
say("$stmt cours insérés (à venir).", $isCli);

// 5) Comptes de démo --------------------------------------------------------
Database::run(
    'INSERT INTO users (role, nom, email, password_hash, ville) VALUES (?,?,?,?,?)',
    ['sportif', 'Léa Marchand', 'lea@demo.fr', Auth::hash('demo1234'), 'Lyon']
);
Database::run(
    'INSERT INTO users (role, nom, email, password_hash, ville, partner_id) VALUES (?,?,?,?,?,?)',
    ['partner', 'Studio Lumen (pro)', 'pro@demo.fr', Auth::hash('demo1234'), 'Lyon', $partnerIds['Studio Lumen']]
);
Database::run(
    'INSERT INTO users (role, nom, email, password_hash, ville) VALUES (?,?,?,?,?)',
    ['admin', 'Admin LastFit', 'admin@demo.fr', Auth::hash('demo1234'), 'Lyon']
);
say('Comptes de démo créés (lea@demo.fr / pro@demo.fr / admin@demo.fr — mot de passe : demo1234).', $isCli);

say('✅ Installation terminée.', $isCli);
