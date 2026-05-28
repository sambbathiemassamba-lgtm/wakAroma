<?php
session_start();
require_once 'pdo.php';
require_once 'function.php';

// Rediriger si non connecté
if (empty($_SESSION['auth'])) {
    header("Location: login.php");
    exit();
}

$id_user = (int) $_SESSION['auth']['id_user'];

// ──────────────────────────────────────────────────────────────
// TRAITEMENT DE LA COMMANDE (POST)
// ──────────────────────────────────────────────────────────────
$erreurs   = [];
$succes    = false;
$numero_commande = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer'])) {

    // --- Validation basique des champs ---
    $prenom   = trim($_POST['prenom']   ?? '');
    $nom      = trim($_POST['nom']      ?? '');
    $email    = trim($_POST['email']    ?? '');
    $adresse  = trim($_POST['adresse']  ?? '');
    $ville    = trim($_POST['ville']    ?? '');
    $cp       = trim($_POST['cp']       ?? '');
    $pays     = trim($_POST['pays']     ?? '');
    $carte_nom   = trim($_POST['carte_nom']   ?? '');
    $carte_num   = trim($_POST['carte_num']   ?? '');
    $carte_exp   = trim($_POST['carte_exp']   ?? '');
    $carte_cvv   = trim($_POST['carte_cvv']   ?? '');

    if (!$prenom)  $erreurs[] = "Le prénom est obligatoire.";
    if (!$nom)     $erreurs[] = "Le nom est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Adresse e-mail invalide.";
    if (!$adresse) $erreurs[] = "L'adresse est obligatoire.";
    if (!$ville)   $erreurs[] = "La ville est obligatoire.";
    if (!$cp)      $erreurs[] = "Le code postal est obligatoire.";
    if (!$carte_nom) $erreurs[] = "Le nom sur la carte est obligatoire.";
    // Numéro de carte : on vérifie 16 chiffres (espaces tolérés)
    $carte_num_clean = preg_replace('/\s+/', '', $carte_num);
    if (!preg_match('/^\d{16}$/', $carte_num_clean)) $erreurs[] = "Numéro de carte invalide (16 chiffres requis).";
    if (!preg_match('/^\d{2}\/\d{2}$/', $carte_exp)) $erreurs[] = "Date d'expiration invalide (MM/AA).";
    if (!preg_match('/^\d{3,4}$/', $carte_cvv))      $erreurs[] = "CVV invalide.";

    if (empty($erreurs)) {
        // Récupérer les lignes du panier
        $req = $pdo->prepare("
            SELECT lp.id_ligne_panier, lp.quantite, lp.prix_capture,
                   p.id_produit, p.nom, p.reference, p.stock
            FROM paniers pan
            INNER JOIN lignes_panier lp ON lp.id_panier = pan.id_panier
            INNER JOIN produits p       ON p.id_produit = lp.id_produit
            WHERE pan.id_user = :id
        ");
        $req->execute([':id' => $id_user]);
        $lignes = $req->fetchAll(PDO::FETCH_OBJ);

        if (empty($lignes)) {
            $erreurs[] = "Votre panier est vide.";
        } else {
            // Vérifier les stocks
            foreach ($lignes as $l) {
                if ($l->quantite > $l->stock) {
                    $erreurs[] = "Stock insuffisant pour « {$l->nom} » (disponible : {$l->stock}).";
                }
            }

            if (empty($erreurs)) {
                // Calcul du total
                $total = 0;
                foreach ($lignes as $l) $total += $l->prix_capture * $l->quantite;

                // Générer un numéro de commande unique
                $numero_commande = 'WK-' . strtoupper(uniqid());

                try {
                    $pdo->beginTransaction();

                    // Insérer la commande
                    $pdo->prepare("
                        INSERT INTO commandes (id_user, numero_commande, statut, total)
                        VALUES (:u, :n, 'payee', :t)
                    ")->execute([':u' => $id_user, ':n' => $numero_commande, ':t' => $total]);

                    $id_commande = (int) $pdo->lastInsertId();

                    // Insérer les lignes de commande + décrémenter le stock
                    foreach ($lignes as $l) {
                        $pdo->prepare("
                            INSERT INTO lignes_commandes
                                (id_commande, id_produit, nom_produit, reference_produit, quantite, prix)
                            VALUES (:c, :p, :nom, :ref, :q, :prix)
                        ")->execute([
                            ':c'   => $id_commande,
                            ':p'   => $l->id_produit,
                            ':nom' => $l->nom,
                            ':ref' => $l->reference,
                            ':q'   => $l->quantite,
                            ':prix'=> $l->prix_capture,
                        ]);

                        $pdo->prepare("UPDATE produits SET stock = stock - :q WHERE id_produit = :p")
                            ->execute([':q' => $l->quantite, ':p' => $l->id_produit]);
                    }

                    // Vider le panier
                    $pdo->prepare("
                        DELETE lp FROM lignes_panier lp
                        INNER JOIN paniers pan ON pan.id_panier = lp.id_panier
                        WHERE pan.id_user = :u
                    ")->execute([':u' => $id_user]);

                    $pdo->commit();
                    $succes = true;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $erreurs[] = "Une erreur est survenue lors de la commande. Veuillez réessayer.";
                }
            }
        }
    }
}

// ──────────────────────────────────────────────────────────────
// RÉCUPÉRATION DU PANIER POUR L'AFFICHAGE
// ──────────────────────────────────────────────────────────────
$lignes_panier = [];
$total_panier  = 0;

if (!$succes) {
    $req = $pdo->prepare("
        SELECT lp.id_ligne_panier, lp.quantite, lp.prix_capture,
               p.nom, i.url_image
        FROM paniers pan
        INNER JOIN lignes_panier lp ON lp.id_panier = pan.id_panier
        INNER JOIN produits p       ON p.id_produit = lp.id_produit
        LEFT  JOIN images i         ON i.id_produit = p.id_produit
        WHERE pan.id_user = :id
        ORDER BY lp.id_ligne_panier ASC
    ");
    $req->execute([':id' => $id_user]);
    $lignes_panier = $req->fetchAll(PDO::FETCH_OBJ);

    foreach ($lignes_panier as $l) $total_panier += $l->prix_capture * $l->quantite;

    // Panier vide et pas de commande en cours => retour panier
    if (empty($lignes_panier)) {
        header("Location: panier.php");
        exit();
    }
}

$livraison_gratuite = $total_panier >= 50;
$frais_livraison    = $livraison_gratuite ? 0 : 4.90;
$total_final        = $total_panier + $frais_livraison;

// Pré-remplir l'e-mail depuis la session
$email_session = htmlspecialchars($_SESSION['auth']['email'] ?? '');
$nom_session   = htmlspecialchars($_SESSION['auth']['nom']   ?? '');
$prenom_session= htmlspecialchars($_SESSION['auth']['prenom']?? '');
?>
<?php require_once 'headear.php'; ?>

<style>
/* ── VARIABLES ── */
:root {
    --or:       #C9943A;
    --or-clair: #E8BA6B;
    --brun:     #2C1A0E;
    --creme:    #FAF6F0;
    --texte:    #3D2B1A;
    --gris:     #8C7A6B;
    --rouge:    #C0392B;
    --vert:     #27AE60;
    --ombre:    0 4px 24px rgba(44,26,14,.10);
}

/* ── LAYOUT ── */
.paiement-page {
    background: var(--creme);
    min-height: 100vh;
    padding: 2.5rem 1rem 4rem;
    font-family: 'Georgia', serif;
}

.paiement-header {
    max-width: 1100px;
    margin: 0 auto 2.5rem;
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.paiement-retour {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: var(--or);
    text-decoration: none;
    font-size: .92rem;
    letter-spacing: .03em;
    transition: opacity .2s;
}
.paiement-retour:hover { opacity: .75; }
.paiement-retour svg  { width: 18px; height: 18px; }

.paiement-titre {
    font-size: 1.9rem;
    color: var(--brun);
    font-weight: 700;
    letter-spacing: .04em;
    flex: 1;
    text-align: right;
}

/* ── GRID ── */
.paiement-grid {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
    align-items: start;
}
@media (max-width: 860px) {
    .paiement-grid { grid-template-columns: 1fr; }
}

/* ── CARTE (section) ── */
.paiement-section {
    background: #fff;
    border-radius: 16px;
    padding: 2rem 2.2rem;
    box-shadow: var(--ombre);
    margin-bottom: 1.6rem;
}

.section-titre {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--brun);
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 2px solid var(--or-clair);
    padding-bottom: .6rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.section-titre .icone { font-size: 1.1rem; }

/* ── FORMULAIRE ── */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.form-row.trois { grid-template-columns: 1fr 1fr 1fr; }
.form-row.un    { grid-template-columns: 1fr; }

@media (max-width: 540px) {
    .form-row, .form-row.trois { grid-template-columns: 1fr; }
}

.form-groupe {
    display: flex;
    flex-direction: column;
    gap: .35rem;
}

.form-groupe label {
    font-size: .82rem;
    color: var(--gris);
    letter-spacing: .04em;
    text-transform: uppercase;
    font-family: Arial, sans-serif;
    font-weight: 600;
}

.form-groupe input,
.form-groupe select {
    border: 1.5px solid #E0D5CC;
    border-radius: 8px;
    padding: .7rem .95rem;
    font-size: .97rem;
    color: var(--texte);
    background: #FDFAF7;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    font-family: Arial, sans-serif;
}
.form-groupe input:focus,
.form-groupe select:focus {
    border-color: var(--or);
    box-shadow: 0 0 0 3px rgba(201,148,58,.15);
}

/* ── CARTE BANCAIRE ── */
.carte-visuelle {
    background: linear-gradient(135deg, var(--brun) 0%, #5C3317 60%, #8B5A2B 100%);
    border-radius: 14px;
    padding: 1.5rem 1.8rem;
    color: #fff;
    margin-bottom: 1.8rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(44,26,14,.28);
    min-height: 170px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.carte-visuelle::before {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(201,148,58,.18);
}
.carte-visuelle::after {
    content: '';
    position: absolute;
    right: 30px; top: 40px;
    width: 100px; height: 100px;
    border-radius: 50%;
    background: rgba(201,148,58,.10);
}
.carte-puce {
    width: 38px; height: 28px;
    background: linear-gradient(135deg, #E8BA6B, #C9943A);
    border-radius: 5px;
    margin-bottom: .6rem;
}
.carte-numero-affiche {
    letter-spacing: .22em;
    font-size: 1.15rem;
    font-family: 'Courier New', monospace;
    margin-bottom: .8rem;
}
.carte-bas {
    display: flex;
    justify-content: space-between;
    font-size: .8rem;
    opacity: .85;
}
.carte-bas span { display: flex; flex-direction: column; gap: .1rem; }
.carte-bas small { font-size: .7rem; opacity: .7; text-transform: uppercase; letter-spacing: .05em; }

/* ── RÉCAP ── */
.recap-commande {
    background: #fff;
    border-radius: 16px;
    padding: 1.8rem 2rem;
    box-shadow: var(--ombre);
    position: sticky;
    top: 1.5rem;
}

.recap-titre {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--brun);
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom: 2px solid var(--or-clair);
    padding-bottom: .6rem;
    margin-bottom: 1.4rem;
}

.recap-articles { margin-bottom: 1.2rem; }

.recap-article {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: .6rem 0;
    border-bottom: 1px solid #F0E9E0;
}
.recap-article:last-child { border-bottom: none; }

.recap-article img {
    width: 46px; height: 46px;
    object-fit: contain;
    border-radius: 8px;
    background: var(--creme);
    padding: 3px;
}
.recap-article-nom   { flex: 1; font-size: .9rem; color: var(--texte); font-weight: 600; }
.recap-article-qte   { font-size: .82rem; color: var(--gris); }
.recap-article-prix  { font-size: .92rem; color: var(--brun); font-weight: 700; }

.recap-totaux { margin-top: 1rem; }
.recap-ligne-total {
    display: flex;
    justify-content: space-between;
    font-size: .9rem;
    color: var(--gris);
    margin-bottom: .5rem;
    font-family: Arial, sans-serif;
}
.recap-ligne-total.grand {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--brun);
    border-top: 2px solid var(--or-clair);
    padding-top: .8rem;
    margin-top: .5rem;
}
.gratuit { color: var(--vert); font-weight: 700; }

/* ── BOUTON PAYER ── */
.btn-payer {
    display: block;
    width: 100%;
    margin-top: 1.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, var(--or), var(--or-clair));
    color: var(--brun);
    border: none;
    border-radius: 10px;
    font-size: 1.08rem;
    font-weight: 700;
    letter-spacing: .05em;
    cursor: pointer;
    text-align: center;
    transition: opacity .2s, transform .1s;
    font-family: 'Georgia', serif;
}
.btn-payer:hover  { opacity: .9; }
.btn-payer:active { transform: scale(.98); }

.securite-mention {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    font-size: .78rem;
    color: var(--gris);
    margin-top: .8rem;
    font-family: Arial, sans-serif;
}

/* ── ERREURS ── */
.erreurs-bloc {
    background: #FDF0EF;
    border: 1.5px solid #E8A89E;
    border-radius: 10px;
    padding: 1rem 1.3rem;
    margin-bottom: 1.5rem;
    color: var(--rouge);
    font-family: Arial, sans-serif;
    font-size: .9rem;
}
.erreurs-bloc ul { margin: .4rem 0 0 1rem; padding: 0; }
.erreurs-bloc li { margin-bottom: .3rem; }

/* ── SUCCÈS ── */
.succes-page {
    max-width: 580px;
    margin: 4rem auto;
    text-align: center;
    background: #fff;
    border-radius: 20px;
    padding: 3.5rem 2.5rem;
    box-shadow: var(--ombre);
}
.succes-icone { font-size: 4rem; margin-bottom: 1rem; }
.succes-page h2 { font-size: 1.7rem; color: var(--brun); margin-bottom: .8rem; }
.succes-page p  { color: var(--gris); font-family: Arial, sans-serif; font-size: .97rem; line-height: 1.6; }
.succes-num {
    display: inline-block;
    background: var(--creme);
    border: 1.5px solid var(--or-clair);
    border-radius: 8px;
    padding: .5rem 1.2rem;
    font-family: 'Courier New', monospace;
    color: var(--or);
    font-size: 1.05rem;
    letter-spacing: .1em;
    margin: 1rem 0;
    font-weight: 700;
}
.btn-retour-boutique {
    display: inline-block;
    margin-top: 1.5rem;
    padding: .85rem 2rem;
    background: linear-gradient(135deg, var(--or), var(--or-clair));
    color: var(--brun);
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: .97rem;
    letter-spacing: .04em;
    transition: opacity .2s;
}
.btn-retour-boutique:hover { opacity: .88; }

/* ── ÉTAPES ── */
.paiement-etapes {
    display: flex;
    justify-content: center;
    gap: 0;
    margin-bottom: 2.5rem;
    max-width: 1100px;
    margin-left: auto;
    margin-right: auto;
}
.etape {
    display: flex;
    align-items: center;
    font-family: Arial, sans-serif;
    font-size: .82rem;
    color: var(--gris);
    text-transform: uppercase;
    letter-spacing: .06em;
}
.etape-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #E0D5CC;
    color: var(--gris);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: .85rem;
    margin-right: .5rem;
    flex-shrink: 0;
}
.etape.active .etape-num { background: var(--or); color: #fff; }
.etape.active { color: var(--brun); font-weight: 700; }
.etape-separateur {
    width: 40px; height: 2px;
    background: #E0D5CC;
    margin: 0 .6rem;
}

/* Formatting de numéro carte */
.input-carte { font-family: 'Courier New', monospace; letter-spacing: .15em; }
</style>

<main class="paiement-page">

<?php if ($succes): ?>
<!-- ══════════════════════════════════════════════════════ -->
<!-- PAGE CONFIRMATION                                       -->
<!-- ══════════════════════════════════════════════════════ -->
<div class="succes-page">
    <div class="succes-icone">🎉</div>
    <h2>Commande confirmée !</h2>
    <p>Merci pour votre commande. Vous recevrez un e-mail de confirmation sous peu.</p>
    <div class="succes-num"><?= htmlspecialchars($numero_commande) ?></div>
    <p>Conservez ce numéro pour suivre votre livraison.</p>
    <a href="index.php" class="btn-retour-boutique">← Retour à la boutique</a>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════ -->
<!-- FORMULAIRE PAIEMENT                                    -->
<!-- ══════════════════════════════════════════════════════ -->

    <!-- Étapes -->
    <div class="paiement-etapes">
        <div class="etape">
            <span class="etape-num">✓</span> Panier
        </div>
        <div class="etape-separateur"></div>
        <div class="etape active">
            <span class="etape-num">2</span> Livraison & Paiement
        </div>
        <div class="etape-separateur"></div>
        <div class="etape">
            <span class="etape-num">3</span> Confirmation
        </div>
    </div>

    <!-- En-tête -->
    <div class="paiement-header">
        <a href="panier.php" class="paiement-retour">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Retour au panier
        </a>
        <h1 class="paiement-titre">Finaliser la commande</h1>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($erreurs)): ?>
    <div class="erreurs-bloc" style="max-width:1100px;margin:0 auto 1.5rem;">
        <strong>Veuillez corriger les erreurs suivantes :</strong>
        <ul>
            <?php foreach ($erreurs as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" id="form-paiement">

    <div class="paiement-grid">

        <!-- ── COLONNE GAUCHE : formulaire ── -->
        <div>

            <!-- Section 1 : Adresse de livraison -->
            <div class="paiement-section">
                <div class="section-titre">
                    <span class="icone">📦</span> Adresse de livraison
                </div>

                <div class="form-row">
                    <div class="form-groupe">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom"
                               value="<?= $prenom_session ?>"
                               placeholder="Amina" required>
                    </div>
                    <div class="form-groupe">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom"
                               value="<?= $nom_session ?>"
                               placeholder="Durand" required>
                    </div>
                </div>

                <div class="form-row un" style="margin-top:1rem;">
                    <div class="form-groupe">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email"
                               value="<?= $email_session ?>"
                               placeholder="vous@exemple.fr" required>
                    </div>
                </div>

                <div class="form-row un" style="margin-top:1rem;">
                    <div class="form-groupe">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse"
                               placeholder="12 rue des Épices" required>
                    </div>
                </div>

                <div class="form-row trois" style="margin-top:1rem;">
                    <div class="form-groupe">
                        <label for="cp">Code postal</label>
                        <input type="text" id="cp" name="cp"
                               placeholder="75001" required>
                    </div>
                    <div class="form-groupe">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville"
                               placeholder="Paris" required>
                    </div>
                    <div class="form-groupe">
                        <label for="pays">Pays</label>
                        <select id="pays" name="pays">
                            <option value="FR" selected>France</option>
                            <option value="BE">Belgique</option>
                            <option value="CH">Suisse</option>
                            <option value="LU">Luxembourg</option>
                            <option value="CA">Canada</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2 : Paiement -->
            <div class="paiement-section">
                <div class="section-titre">
                    <span class="icone">💳</span> Informations de paiement
                </div>

                <!-- Carte visuelle -->
                <div class="carte-visuelle" id="carte-preview">
                    <div class="carte-puce"></div>
                    <div class="carte-numero-affiche" id="prev-numero">•••• •••• •••• ••••</div>
                    <div class="carte-bas">
                        <span>
                            <small>Titulaire</small>
                            <span id="prev-nom">VOTRE NOM</span>
                        </span>
                        <span style="align-items:flex-end;">
                            <small>Expire</small>
                            <span id="prev-exp">MM/AA</span>
                        </span>
                    </div>
                </div>

                <div class="form-row un">
                    <div class="form-groupe">
                        <label for="carte_nom">Nom sur la carte</label>
                        <input type="text" id="carte_nom" name="carte_nom"
                               placeholder="AMINA DURAND"
                               style="text-transform:uppercase;"
                               required>
                    </div>
                </div>

                <div class="form-row un" style="margin-top:1rem;">
                    <div class="form-groupe">
                        <label for="carte_num">Numéro de carte</label>
                        <input type="text" id="carte_num" name="carte_num"
                               class="input-carte"
                               placeholder="0000 0000 0000 0000"
                               maxlength="19"
                               autocomplete="cc-number"
                               required>
                    </div>
                </div>

                <div class="form-row" style="margin-top:1rem;">
                    <div class="form-groupe">
                        <label for="carte_exp">Date d'expiration</label>
                        <input type="text" id="carte_exp" name="carte_exp"
                               class="input-carte"
                               placeholder="MM/AA"
                               maxlength="5"
                               autocomplete="cc-exp"
                               required>
                    </div>
                    <div class="form-groupe">
                        <label for="carte_cvv">CVV</label>
                        <input type="password" id="carte_cvv" name="carte_cvv"
                               placeholder="•••"
                               maxlength="4"
                               autocomplete="cc-csc"
                               required>
                    </div>
                </div>

                <!-- Logos CB -->
                <div style="display:flex;gap:.6rem;align-items:center;margin-top:1.2rem;opacity:.55;font-family:Arial,sans-serif;font-size:.78rem;color:var(--gris);">
                    <span style="border:1px solid #ddd;border-radius:5px;padding:3px 8px;font-weight:700;font-size:.85rem;">VISA</span>
                    <span style="border:1px solid #ddd;border-radius:5px;padding:3px 8px;font-weight:700;font-size:.85rem;">MC</span>
                    <span style="border:1px solid #ddd;border-radius:5px;padding:3px 8px;font-weight:700;font-size:.75rem;">CB</span>
                    <span style="margin-left:auto;">🔒 Connexion SSL sécurisée</span>
                </div>
            </div>

        </div>

        <!-- ── COLONNE DROITE : récap commande ── -->
        <aside>
            <div class="recap-commande">
                <div class="recap-titre">Votre commande</div>

                <div class="recap-articles">
                    <?php foreach ($lignes_panier as $l): ?>
                    <div class="recap-article">
                        <img src="<?= htmlspecialchars($l->url_image ?? 'images/placeholder.png') ?>"
                             alt="<?= htmlspecialchars($l->nom) ?>">
                        <div class="recap-article-nom">
                            <?= htmlspecialchars($l->nom) ?>
                            <div class="recap-article-qte">× <?= (int)$l->quantite ?></div>
                        </div>
                        <div class="recap-article-prix">
                            <?= number_format($l->prix_capture * $l->quantite, 2, ',', '') ?> €
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="recap-totaux">
                    <div class="recap-ligne-total">
                        <span>Sous-total</span>
                        <span><?= number_format($total_panier, 2, ',', '') ?> €</span>
                    </div>
                    <div class="recap-ligne-total">
                        <span>Livraison</span>
                        <span>
                            <?php if ($livraison_gratuite): ?>
                                <span class="gratuit">Gratuite 🎉</span>
                            <?php else: ?>
                                <?= number_format($frais_livraison, 2, ',', '') ?> €
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!$livraison_gratuite): ?>
                    <p style="font-size:.78rem;color:var(--gris);font-family:Arial,sans-serif;margin:.3rem 0 0;">
                        Encore <strong><?= number_format(50 - $total_panier, 2, ',', '') ?> €</strong> pour la livraison offerte
                    </p>
                    <?php endif; ?>
                    <div class="recap-ligne-total grand">
                        <span>Total TTC</span>
                        <span><?= number_format($total_final, 2, ',', '') ?> €</span>
                    </div>
                </div>

                <button type="submit" name="confirmer" class="btn-payer" id="btn-payer">
                    🔒 Payer <?= number_format($total_final, 2, ',', '') ?> €
                </button>

                <div class="securite-mention">
                    <span>🔒</span>
                    <span>Paiement 100 % sécurisé — données chiffrées</span>
                </div>
            </div>
        </aside>

    </div>
    </form>

<?php endif; ?>

</main>

<?php require_once 'footer.php'; ?>

<script>
/* ── Formatage numéro carte ── */
const inputNum = document.getElementById('carte_num');
const inputNom = document.getElementById('carte_nom');
const inputExp = document.getElementById('carte_exp');

if (inputNum) {
    inputNum.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').slice(0, 16);
        this.value = v.match(/.{1,4}/g)?.join(' ') || v;
        // Mise à jour carte visuelle
        const affiche = v.padEnd(16, '•').match(/.{1,4}/g).join(' ');
        document.getElementById('prev-numero').textContent = affiche;
    });
}

if (inputNom) {
    inputNom.addEventListener('input', function () {
        document.getElementById('prev-nom').textContent = this.value.toUpperCase() || 'VOTRE NOM';
    });
}

if (inputExp) {
    inputExp.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').slice(0, 4);
        if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
        this.value = v;
        document.getElementById('prev-exp').textContent = v || 'MM/AA';
    });
}

/* ── Feedback bouton payer ── */
const form = document.getElementById('form-paiement');
const btn  = document.getElementById('btn-payer');
if (form && btn) {
    form.addEventListener('submit', function () {
        btn.textContent = '⏳ Traitement en cours…';
        btn.disabled = true;
        btn.style.opacity = '.7';
    });
}
</script>
