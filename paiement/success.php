<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

\Stripe\Stripe::setApiKey('');   // ← remplacer

$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    header('Location: index.php');
    exit;
}

// ─── Vérification du paiement auprès de Stripe ────────────────────────────
try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    if ($session->payment_status !== 'paid') {
        // Paiement non finalisé → retour panier
        header('Location: panier.php?erreur=non_paye');
        exit;
    }

    // ✅ Paiement confirmé : vider le panier
    $panier = $_SESSION['panier'] ?? [];
    unset($_SESSION['panier'], $_SESSION['stripe_session_id']);

    // Ici tu peux : enregistrer la commande en BDD, envoyer un email, etc.

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe success error: ' . $e->getMessage());
    header('Location: panier.php?erreur=verification');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500&display=swap');

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
            border-radius: 16px;
            padding: 3rem 3.5rem;
            text-align: center;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 4px 40px rgba(0,0,0,.08);
        }

        .icon {
            width: 64px;
            height: 64px;
            background: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.9rem;
            margin-bottom: .75rem;
        }

        p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: .75rem 2rem;
            background: #1a1a1a;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background .2s;
        }
        .btn:hover { background: #333; }

        .order-id {
            margin-top: 1.5rem;
            font-size: .8rem;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Merci pour votre commande !</h1>
        <p>Votre paiement a bien été reçu. Vous recevrez une confirmation par e-mail sous peu.</p>
        <a href="../index.php" class="btn">Retour à l'accueil</a>
        <p class="order-id">Référence : <?= htmlspecialchars($session->id) ?></p>
    </div>
</body>
</html>
