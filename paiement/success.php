<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../pdo.php';

\Stripe\Stripe::setApiKey('sk_test_51TeEC78DOOsdMMdlQrw705YiE2fc81JWm7Gvp3itV4wTguDkoTCrNto3A6jWp0zhTvkwSiUMeKQ9KVLK2E6b9diH00oLODFkUe');

$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    header('Location: ../index.php');
    exit;
}

// ─── Vérification du paiement auprès de Stripe ───────────────────────────
try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    if ($session->payment_status !== 'paid') {
        header('Location: ../panier.php?erreur=non_paye');
        exit;
    }
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe success error: ' . $e->getMessage());
    header('Location: ../panier.php?erreur=verification');
    exit;
}

// ─── Enregistrement en BDD (si pas déjà fait par le webhook) ─────────────
// On utilise le session_id Stripe comme clé d'idempotence :
// si la commande existe déjà (webhook l'a déjà créée), on ne recrée pas.
$stmtCheck = $pdo->prepare("
    SELECT id_commande, numero_commande FROM commandes
    WHERE numero_commande = ?
");
$numeroCommande = 'CMD-' . strtoupper(substr($session->id, -10));
$stmtCheck->execute([$numeroCommande]);
$commandeExistante = $stmtCheck->fetch(PDO::FETCH_OBJ);

if (!$commandeExistante) {
    // Le webhook n'a pas encore tourné (localhost) → on insère nous-mêmes
    $idUser     = $session->metadata->id_user      ?? (int)($_SESSION['auth']['id_user'] ?? null);
    $lignesJson = $session->metadata->lignes_panier ?? '[]';
    $lignes     = json_decode($lignesJson, true);
    $total      = $session->amount_total / 100;

    if (!empty($lignes)) {
        try {
            $pdo->beginTransaction();

            // 1. Insérer la commande
            $stmtCmd = $pdo->prepare("
                INSERT INTO commandes (id_user, numero_commande, statut, total)
                VALUES (?, ?, 'payee', ?)
            ");
            $stmtCmd->execute([$idUser, $numeroCommande, $total]);
            $idCommande = (int)$pdo->lastInsertId();

            // 2. Insérer les lignes + décrémenter le stock
            $stmtLigne = $pdo->prepare("
                INSERT INTO lignes_commandes
                    (id_commande, id_produit, nom_produit, reference_produit, quantite, prix)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtStock = $pdo->prepare("
                UPDATE produits
                SET stock = GREATEST(0, stock - ?)
                WHERE id_produit = ?
            ");

            foreach ($lignes as $ligne) {
                // Récupérer les vraies infos produit depuis la BDD
                $stmtProd = $pdo->prepare("SELECT nom, reference, prix FROM produits WHERE id_produit = ?");
                $stmtProd->execute([$ligne['i']]);
                $produit = $stmtProd->fetch(PDO::FETCH_OBJ);

                $nomProduit = $produit->nom       ?? ('Produit #' . $ligne['i']);
                $refProduit = $produit->reference ?? null;
                $prixReel   = $produit->prix      ?? $ligne['p'];

                $stmtLigne->execute([
                    $idCommande,
                    $ligne['i'],
                    $nomProduit,
                    $refProduit,
                    (int)$ligne['q'],
                    $prixReel,
                ]);

                $stmtStock->execute([(int)$ligne['q'], $ligne['i']]);
            }

            // 3. Vider le panier en BDD
            if (!empty($idUser)) {
                $stmtViderPanier = $pdo->prepare("
                    DELETE lp FROM lignes_panier lp
                    INNER JOIN paniers pan ON pan.id_panier = lp.id_panier
                    WHERE pan.id_user = ?
                ");
                $stmtViderPanier->execute([$idUser]);
            }

            $pdo->commit();
            error_log("✅ [success.php] Commande $numeroCommande enregistrée — total: {$total}€");

        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('❌ [success.php] Erreur enregistrement : ' . $e->getMessage());
        }
    }
} else {
    // Webhook déjà passé, on vide juste le panier session
    $idUser = (int)($_SESSION['auth']['id_user'] ?? 0);
    if ($idUser) {
        $stmtViderPanier = $pdo->prepare("
            DELETE lp FROM lignes_panier lp
            INNER JOIN paniers pan ON pan.id_panier = lp.id_panier
            WHERE pan.id_user = ?
        ");
        $stmtViderPanier->execute([$idUser]);
    }
}

// Vider la session panier
unset($_SESSION['panier'], $_SESSION['stripe_session_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée — WakAroma</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@400;500;600&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7f5f2;
            font-family: 'DM Sans', sans-serif;
            color: #1a1a1a;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 3rem 3.5rem;
            text-align: center;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 8px 40px rgba(0,0,0,.08);
        }

        .icon-wrap {
            width: 72px; height: 72px;
            background: #e6f7ed;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.2rem;
        }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 2rem;
            margin-bottom: .75rem;
            color: #1f4f2e;
        }

        h1 em { font-style: italic; color: #c8943a; }

        p {
            color: #666;
            line-height: 1.65;
            margin-bottom: 1.8rem;
            font-size: 0.92rem;
        }

        .order-ref {
            display: inline-block;
            background: #f7f5f2;
            border: 1px solid #e8e0d6;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            color: #888;
            margin-bottom: 2rem;
            letter-spacing: 0.05em;
        }

        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }

        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: .75rem 1.6rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all .2s;
        }

        .btn--primary {
            background: #1f4f2e;
            color: #fff;
        }
        .btn--primary:hover { background: #2d7a44; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(31,79,46,.25); }

        .btn--outline {
            background: #fff;
            color: #1f4f2e;
            border: 1.5px solid #d0c9be;
        }
        .btn--outline:hover { border-color: #c8943a; color: #c8943a; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">✅</div>
        <h1>Merci pour votre <em>commande</em> !</h1>
        <p>Votre paiement a bien été reçu et votre commande est enregistrée. Nous préparons vos épices avec soin.</p>
        <div class="order-ref"><?= htmlspecialchars($numeroCommande) ?></div>
        <div class="actions">
            <a href="../commandes.php" class="btn btn--primary">
                📦 Voir mes commandes
            </a>
            <a href="../index.php" class="btn btn--outline">
                Continuer mes achats
            </a>
        </div>
    </div>
</body>
</html>