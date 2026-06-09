<?php
// ============================================================
//  avis.php  —  API AJAX pour les avis / notes produits
// ============================================================
session_start();
require_once 'function.php';   // contient déjà getDB() / la connexion PDO

header('Content-Type: application/json');

// Identifiant de session stable (créé si absent)
if (empty($_SESSION['visitor_id'])) {
    $_SESSION['visitor_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['visitor_id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Connexion ──────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB : ' . $e->getMessage()]);
    exit;
}

// ── Migration automatique (crée la table si elle n'existe pas) ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS avis (
        id          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
        id_produit  INT UNSIGNED     NOT NULL,
        id_session  VARCHAR(128)     NOT NULL,
        note        TINYINT UNSIGNED NOT NULL,
        created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_vote (id_produit, id_session),
        INDEX idx_produit (id_produit)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ============================================================
switch ($action) {

    // ── Voter ──────────────────────────────────────────────
    case 'voter':
        $idProduit = (int)($_POST['id_produit'] ?? 0);
        $note      = (int)($_POST['note']       ?? 0);

        if ($idProduit <= 0 || $note < 1 || $note > 5) {
            echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
            exit;
        }

        // INSERT ou mise à jour si le visiteur revote
        $stmt = $pdo->prepare("
            INSERT INTO avis (id_produit, id_session, note)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE note = VALUES(note)
        ");
        $stmt->execute([$idProduit, $sessionId, $note]);

        // Recalculer les stats de ce produit
        $stats = getStats($pdo, $idProduit);
        echo json_encode(['success' => true, 'stats' => $stats, 'ma_note' => $note]);
        break;

    // ── Récupérer les stats d'un produit ──────────────────
    case 'get_stats':
        $idProduit = (int)($_GET['id_produit'] ?? $_POST['id_produit'] ?? 0);
        if ($idProduit <= 0) {
            echo json_encode(['success' => false, 'error' => 'id_produit manquant']);
            exit;
        }
        $stats  = getStats($pdo, $idProduit);
        $maNote = getMaNote($pdo, $idProduit, $sessionId);
        echo json_encode(['success' => true, 'stats' => $stats, 'ma_note' => $maNote]);
        break;

    // ── Récupérer les stats de TOUS les produits (bulk) ───
    case 'get_all_stats':
        $stmt = $pdo->query("
            SELECT
                id_produit,
                ROUND(AVG(note), 2)                        AS moyenne,
                COUNT(*)                                   AS nb_votes,
                ROUND((AVG(note) / 5) * 100, 1)           AS pct
            FROM avis
            GROUP BY id_produit
        ");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['id_produit']] = [
                'moyenne'  => (float)$r['moyenne'],
                'nb_votes' => (int)$r['nb_votes'],
                'pct'      => (float)$r['pct'],
            ];
        }
        // Récupérer aussi la note du visiteur courant
        $stmtMoi = $pdo->prepare("SELECT id_produit, note FROM avis WHERE id_session = ?");
        $stmtMoi->execute([$sessionId]);
        $mesNotes = [];
        foreach ($stmtMoi->fetchAll() as $r) {
            $mesNotes[$r['id_produit']] = (int)$r['note'];
        }
        echo json_encode(['success' => true, 'stats' => $result, 'mes_notes' => $mesNotes]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}

// ============================================================
//  Helpers
// ============================================================
function getStats(PDO $pdo, int $idProduit): array {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(ROUND(AVG(note), 2), 0)          AS moyenne,
            COUNT(*)                                   AS nb_votes,
            COALESCE(ROUND((AVG(note) / 5) * 100, 1), 0) AS pct
        FROM avis
        WHERE id_produit = ?
    ");
    $stmt->execute([$idProduit]);
    $r = $stmt->fetch();
    return [
        'moyenne'  => (float)$r['moyenne'],
        'nb_votes' => (int)$r['nb_votes'],
        'pct'      => (float)$r['pct'],
    ];
}

function getMaNote(PDO $pdo, int $idProduit, string $sessionId): int {
    $stmt = $pdo->prepare("SELECT note FROM avis WHERE id_produit = ? AND id_session = ?");
    $stmt->execute([$idProduit, $sessionId]);
    $r = $stmt->fetch();
    return $r ? (int)$r['note'] : 0;
}