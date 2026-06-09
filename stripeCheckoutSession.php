<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../pdo.php';

\Stripe\Stripe::setApiKey('sk_test_51TeEC78DOOsdMMdlQrw705YiE2fc81JWm7Gvp3itV4wTguDkoTCrNto3A6jWp0zhTvkwSiUMeKQ9KVLK2E6b9diH00oLODFkUe');// ─── Utilisateur connecté obligatoire ─────────────────────────────────────
if (empty($_SESSION['auth'])) {
    header('Location: login.php');
    exit;
}

$id_user = (int) $_SESSION['auth']['id_user'];

// ─── Récupération du panier depuis la BDD ─────────────────────────────────
$req = $pdo->prepare("
    SELECT
        lp.quantite,
        lp.prix_capture,
        p.id_produit,
        p.nom,
        p.reference,
        p.stock,
        COALESCE(
            MAX(CASE WHEN i.is_cover = 1 THEN i.url_image END),
            MIN(i.url_image)
        ) AS url_image
    FROM paniers pan
    INNER JOIN lignes_panier lp ON lp.id_panier = pan.id_panier
    INNER JOIN produits p       ON p.id_produit  = lp.id_produit
    LEFT  JOIN images i         ON i.id_produit  = p.id_produit
    WHERE pan.id_user = :id
    GROUP BY lp.id_ligne_panier, lp.quantite, lp.prix_capture, p.id_produit, p.nom, p.reference, p.stock
");
$req->execute([':id' => $id_user]);
$lignes = $req->fetchAll(PDO::FETCH_OBJ);

if (empty($lignes)) {
    header('Location: panier.php');
    exit;
}

// ─── Construction des line_items Stripe + métadonnées webhook ─────────────
$lineItems  = [];
$lignesMeta = [];

foreach ($lignes as $l) {
    if ($l->stock <= 0) {
        header('Location: panier.php?erreur=stock');
        exit;
    }

    $imageUrl = !empty($l->url_image)
        ? 'http://localhost/wakaroma/' . $l->url_image
        : null;

    $lineItems[] = [
        'price_data' => [
            'currency'     => 'eur',
            'unit_amount'  => (int) round($l->prix_capture * 100),
            'product_data' => [
                'name'   => $l->nom,
                'images' => $imageUrl ? [$imageUrl] : [],
            ],
        ],
        'quantity' => (int) $l->quantite,
    ];

    $lignesMeta[] = [
        'i' => $l->id_produit,
        'p' => (float) $l->prix_capture,
        'q' => (int) $l->quantite,
    ];
}

$lignesMetaJson = json_encode($lignesMeta);

// ─── Création de la session Stripe Checkout ────────────────────────────────
try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items'           => $lineItems,
        'mode'                 => 'payment',
        'success_url'          => 'http://localhost/wakaroma/paiement/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'           => 'http://localhost/wakaroma/panier.php',
        'locale'               => 'fr',

        // Optionnel : collecter l'adresse de livraison
        // 'shipping_address_collection' => ['allowed_countries' => ['FR', 'BE', 'CH']],

        // Métadonnées transmises au webhook pour enregistrement BDD
        'metadata' => [
            'id_user'       => $id_user,
            'lignes_panier' => $lignesMetaJson,
        ],
    ]);

    $_SESSION['stripe_session_id'] = $session->id;

    // Redirection vers la page Stripe
    header('Location: ' . $session->url);
    exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe error: ' . $e->getMessage());
    die('Erreur Stripe : ' . $e->getMessage());
}