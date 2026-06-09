<?php
// webhook.php — À enregistrer dans le tableau de bord Stripe
// URL : https://votre-site.com/webhook.php
// Événements à écouter : checkout.session.completed

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../pdo.php';   // ← ton fichier de connexion PDO ($pdo)

\Stripe\Stripe::setApiKey('');   // ← remplacer

$webhookSecret = '';             // ← remplacer (Stripe Dashboard → Webhooks)

// ─── Vérification de la signature Stripe ─────────────────────────────────
$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit('Payload invalide');
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit('Signature invalide');
}

// ─── Traitement des événements ────────────────────────────────────────────
switch ($event->type) {

    case 'checkout.session.completed':
        $session = $event->data->object;

        if ($session->payment_status === 'paid') {
            enregistrerCommande($pdo, $session);
        }
        break;

    case 'checkout.session.expired':
        error_log('⚠️ Session Stripe expirée : ' . $event->data->object->id);
        break;

    default:
        // Événement non géré — on ignore silencieusement
        break;
}

http_response_code(200);
echo json_encode(['status' => 'ok']);


// ═════════════════════════════════════════════════════════════════════════════
// FONCTION : enregistrerCommande
// Insère dans commandes + lignes_commandes + décrémente le stock produits
// ═════════════════════════════════════════════════════════════════════════════
function enregistrerCommande(PDO $pdo, $session): void
{
    // ── Récupération des métadonnées passées depuis create-checkout.php ──────
    $idUser      = $session->metadata->id_user      ?? null;  // int ou null si invité
    $lignesJson  = $session->metadata->lignes_panier ?? '[]'; // JSON des articles
    $lignes      = json_decode($lignesJson, true);

    if (empty($lignes)) {
        error_log('⚠️ Webhook : lignes_panier vides pour session ' . $session->id);
        return;
    }

    // ── Calcul du total en euros (Stripe renvoie des centimes) ──────────────
    $total = $session->amount_total / 100;

    // ── Numéro de commande unique ────────────────────────────────────────────
    $numeroCommande = 'CMD-' . strtoupper(substr($session->id, -10));

    try {
        $pdo->beginTransaction();

        // ── 1. Insertion dans commandes ──────────────────────────────────────
        $stmt = $pdo->prepare("
            INSERT INTO commandes (id_user, numero_commande, statut, total)
            VALUES (:id_user, :numero_commande, 'payee', :total)
        ");
        $stmt->execute([
            ':id_user'         => $idUser,
            ':numero_commande' => $numeroCommande,
            ':total'           => $total,
        ]);
        $idCommande = (int) $pdo->lastInsertId();

        // ── 2. Insertion des lignes_commandes + décrémentation stock ─────────
        $stmtLigne = $pdo->prepare("
            INSERT INTO lignes_commandes
                (id_commande, id_produit, nom_produit, reference_produit, quantite, prix)
            VALUES
                (:id_commande, :id_produit, :nom_produit, :reference_produit, :quantite, :prix)
        ");

        $stmtStock = $pdo->prepare("
            UPDATE produits
            SET stock = GREATEST(0, stock - :quantite)
            WHERE id_produit = :id_produit
        ");

        foreach ($lignes as $ligne) {
            // Sécurité : récupérer le prix réel depuis la BDD (ne jamais faire confiance aux métadonnées)
            $stmtPrix = $pdo->prepare("
                SELECT prix, nom, reference FROM produits WHERE id_produit = :id
            ");
            $stmtPrix->execute([':id' => $ligne['id_produit']]);
            $produit = $stmtPrix->fetch(PDO::FETCH_ASSOC);

            if (!$produit) {
                // Produit supprimé entre-temps → on garde les infos de la session
                $prixReel  = $ligne['prix'];
                $nomReel   = $ligne['nom'];
                $refReel   = $ligne['reference'] ?? null;
            } else {
                $prixReel  = $produit['prix'];
                $nomReel   = $produit['nom'];
                $refReel   = $produit['reference'];
            }

            // Insertion ligne commande
            $stmtLigne->execute([
                ':id_commande'      => $idCommande,
                ':id_produit'       => $ligne['id_produit'],
                ':nom_produit'      => $nomReel,
                ':reference_produit'=> $refReel,
                ':quantite'         => (int) $ligne['quantite'],
                ':prix'             => $prixReel,
            ]);

            // Décrémentation du stock
            $stmtStock->execute([
                ':quantite'   => (int) $ligne['quantite'],
                ':id_produit' => $ligne['id_produit'],
            ]);
        }

        $pdo->commit();

        error_log("✅ Commande $numeroCommande enregistrée (id: $idCommande) — total: {$total}€");

    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('❌ Erreur enregistrement commande : ' . $e->getMessage());
        // On ne renvoie pas d'erreur HTTP ici pour éviter que Stripe rejoue indéfiniment
    }
}
