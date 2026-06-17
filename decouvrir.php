<?php
session_start();
require_once 'function.php';

// Récupération de l'id produit depuis l'URL
$id_produit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produit <= 0) {
    header('Location: index.php');
    exit();
}

// Fonctions à ajouter dans function.php si pas déjà présentes :
// recuperation_produit_by_id($id) → retourne un objet produit avec images, caractéristiques
// recuperation_produits_images() → déjà existante, pour les produits similaires

$produit    = recuperation_produit_by_id($id_produit);   // produit principal
$tous       = recuperation_produits_images();             // tous les produits
$similaires = array_filter($tous, fn($p) => (int)$p->id_produit !== $id_produit);

if (!$produit) {
    header('Location: index.php');
    exit();
}

// ── Récupération de TOUTES les images depuis la table `images` ──
// (indépendamment de ce que retourne recuperation_produit_by_id)
$debug_carousel = []; // TEMPORAIRE : à retirer une fois le bug réglé
$debug_carousel[] = 'SERVER_NAME = ' . ($_SERVER['SERVER_NAME'] ?? '(non défini)');
$debug_carousel[] = 'id_produit demandé = ' . $id_produit;
try {
    $IS_LOCAL_IMGS = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);
    $debug_carousel[] = 'IS_LOCAL_IMGS = ' . ($IS_LOCAL_IMGS ? 'true (mode local)' : 'false (mode OVH)');

    if ($IS_LOCAL_IMGS) {
        $db_host_imgs = 'localhost';
        $db_user_imgs = 'root';
        $db_pass_imgs = '';
        $db_name_imgs = 'wakaroma';
    } else {
        $db_host_imgs = 'kgaftzfwakaroma.mysql.db';
        $db_user_imgs = 'kgaftzfwakaroma';
        $db_pass_imgs = 'Wakaroma1';
        $db_name_imgs = 'kgaftzfwakaroma';
    }
    $debug_carousel[] = 'Connexion ciblee : host=' . $db_host_imgs . ' db=' . $db_name_imgs . ' user=' . $db_user_imgs;

    $pdo_imgs = new PDO(
        "mysql:host=$db_host_imgs;dbname=$db_name_imgs;charset=utf8",
        $db_user_imgs,
        $db_pass_imgs,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $debug_carousel[] = 'Connexion PDO etablie avec succes';

    try {
        $stmt_imgs = $pdo_imgs->prepare("SELECT id_image, url_image, is_cover FROM images WHERE id_produit = ? ORDER BY is_cover DESC, id_image");
        $stmt_imgs->execute([$id_produit]);
        $debug_carousel[] = 'requete avec is_cover OK';
    } catch (PDOException $e) {
        $debug_carousel[] = 'requete avec is_cover a echoue (' . $e->getMessage() . ') -> fallback sans is_cover';
        $stmt_imgs = $pdo_imgs->prepare("SELECT id_image, url_image FROM images WHERE id_produit = ? ORDER BY id_image");
        $stmt_imgs->execute([$id_produit]);
    }

    $images_bdd = $stmt_imgs->fetchAll(PDO::FETCH_OBJ);
    $debug_carousel[] = 'nb lignes trouvees pour ce produit = ' . count($images_bdd);
    if (!empty($images_bdd)) {
        $produit->images = $images_bdd;
        $debug_carousel[] = 'produit->images mis a jour avec ' . count($images_bdd) . ' image(s)';
    } else {
        $debug_carousel[] = 'AUCUNE IMAGE trouvee en base pour id_produit = ' . $id_produit . ' -> fallback sur url_image unique';

        // Vérif supplémentaire : compter le total de lignes dans la table images,
        // pour savoir si la table est vide en général ou juste pour ce produit.
        try {
            $total_imgs = (int)$pdo_imgs->query("SELECT COUNT(*) FROM images")->fetchColumn();
            $debug_carousel[] = 'Nombre TOTAL de lignes dans la table images (tous produits) = ' . $total_imgs;
        } catch (Exception $e2) {
            $debug_carousel[] = 'Impossible de compter le total de la table images : ' . $e2->getMessage();
        }
    }
} catch (Exception $e) {
    // Si la connexion ou la requête échoue, on laisse $produit->images tel quel (fallback natif)
    $debug_carousel[] = 'ERREUR CONNEXION/REQUETE : ' . $e->getMessage();
}
?>
<?php require_once 'headear.php'; ?>

<!-- ══════════════════════════════════════════
     PAGE DÉCOUVRIR — CSS
     ══════════════════════════════════════════ -->
<style>
/* ─── Tokens WakAroma (réutilisés depuis le site) ─── */
:root {
    --or:       #c8943a;
    --or-light: #e8b860;
    --sable:    #f5f0e8;
    --brun:     #3d2b1a;
    --texte:    #fcfcfc;
    --gris:     #b0a898;
    --rouge:    #e74c3c;
    --vert:     #2d7a44;
}

/* ─── Layout principal ─── */
.decouvrir {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 24px 80px;
}

@media (max-width: 768px) {
    .decouvrir {
        padding: 24px 16px 60px;
    }
}

/* Fil d'Ariane */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.82rem;
    color: #111;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .breadcrumb {
        margin-bottom: 24px;
        font-size: 0.78rem;
    }
}
.breadcrumb a {
    color: #111;
    text-decoration: none;
    transition: color 0.2s;
}
.breadcrumb a:hover { color: var(--or); }
.breadcrumb__sep { color: #888; }
.breadcrumb__current { color: #111; font-weight: 600; }

/* ─── Hero produit ─── */
.produit-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: start;
    margin-bottom: 80px;
}
@media (max-width: 768px) {
    .produit-hero {
        grid-template-columns: 1fr;
        gap: 28px;
        margin-bottom: 48px;
    }
}

/* ─── CAROUSEL ─── */
.produit-hero__galerie {
    position: sticky;
    top: 100px;
}

@media (max-width: 768px) {
    .produit-hero__galerie {
        position: static;
    }
}

/* Conteneur principal du carousel */
.carousel {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 20px;
    overflow: hidden;
    background: var(--sable);
    box-shadow: 0 20px 60px rgba(61,43,26,0.12);
    user-select: none;
}

/* Track qui glisse */
.carousel__track {
    display: flex;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
    will-change: transform;
}

/* Chaque slide */
.carousel__slide {
    min-width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--sable);
}
.carousel__slide img {
    width: 82%;
    height: 82%;
    object-fit: contain;
    transition: transform 0.5s cubic-bezier(0.22, 1, 0.36, 1);
    pointer-events: none;
}
.carousel:hover .carousel__slide.active img {
    transform: scale(1.05);
}

/* Badge statut */
.produit-hero__badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    z-index: 10;
}
.produit-hero__badge--new      { background: #e8f5ee; color: var(--vert); }
.produit-hero__badge--low      { background: #fff3e0; color: #e67e22; }
.produit-hero__badge--rupture  { background: #fdecea; color: var(--rouge); }

/* Wishlist btn */
.produit-hero__wishlist {
    position: absolute;
    top: 16px;
    right: 16px;
    background: #fff;
    border: none;
    border-radius: 50%;
    width: 42px;
    height: 42px;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    z-index: 10;
}
.produit-hero__wishlist:hover  { transform: scale(1.1); box-shadow: 0 4px 16px rgba(0,0,0,0.14); }
.produit-hero__wishlist.actif  { color: #e74c3c; }

/* Flèches */
.carousel__arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    background: rgba(255,255,255,0.92);
    border: none;
    border-radius: 50%;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(61,43,26,0.18);
    transition: background 0.2s, transform 0.2s, opacity 0.2s;
    opacity: 0;
}
.carousel:hover .carousel__arrow { opacity: 1; }
.carousel__arrow:hover { background: #fff; transform: translateY(-50%) scale(1.08); }
.carousel__arrow svg { width: 18px; height: 18px; stroke: var(--brun); stroke-width: 2.5; fill: none; }
.carousel__arrow--prev { left: 12px; }
.carousel__arrow--next { right: 12px; }
.carousel__arrow:disabled { opacity: 0.3 !important; cursor: default; }

/* Toujours visibles sur mobile */
@media (max-width: 768px) {
    .carousel__arrow { opacity: 1; width: 36px; height: 36px; }
}

/* Points de navigation */
.carousel__dots {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 7px;
    z-index: 10;
}
.carousel__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(61,43,26,0.25);
    border: none;
    cursor: pointer;
    padding: 0;
    transition: background 0.25s, transform 0.25s;
}
.carousel__dot.active {
    background: var(--or);
    transform: scale(1.3);
}

/* Miniatures sous le carousel */
.carousel__thumbs {
    display: flex;
    gap: 10px;
    margin-top: 14px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
}
.carousel__thumbs::-webkit-scrollbar { display: none; }
.carousel__thumb {
    flex-shrink: 0;
    width: 68px;
    height: 68px;
    border-radius: 10px;
    overflow: hidden;
    background: var(--sable);
    border: 2.5px solid transparent;
    cursor: pointer;
    transition: border-color 0.2s, transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.carousel__thumb img {
    width: 80%;
    height: 80%;
    object-fit: contain;
    pointer-events: none;
}
.carousel__thumb.active { border-color: var(--or); transform: scale(1.05); }
.carousel__thumb:hover:not(.active) { border-color: #d4c4a8; }

/* ─── Infos produit ─── */
.produit-hero__infos {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.produit-hero__categorie {
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--or);
}

.produit-hero__nom {
    font-size: clamp(1.6rem, 6vw, 3rem);
    font-weight: 800;
    color: var(--brun);
    line-height: 1.1;
    margin: 0;
    letter-spacing: -0.02em;
}

.produit-hero__ref {
    font-size: 0.78rem;
    color: #111;
    letter-spacing: 0.06em;
}

/* Séparateur */
.produit-hero__sep {
    width: 48px;
    height: 3px;
    background: linear-gradient(90deg, var(--or), var(--or-light));
    border-radius: 99px;
}

/* Description */
.produit-hero__desc {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--texte);
    font-weight: 400;
}

/* Caractéristiques */
.produit-hero__carac {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

@media (max-width: 768px) {
    .produit-hero__carac {
        gap: 8px;
    }
    .produit-hero__carac-item {
        font-size: 0.78rem;
        padding: 7px 12px;
    }
}
.produit-hero__carac-item {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--sable);
    border: 1px solid #e8dfd0;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 0.82rem;
    color: var(--brun);
    font-weight: 500;
}
.produit-hero__carac-item strong {
    color: var(--or);
    margin-right: 2px;
}

/* Bloc prix + panier */
.produit-hero__acheter {
    background: var(--sable);
    border: 1px solid #e8dfd0;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

@media (max-width: 768px) {
    .produit-hero__acheter {
        padding: 18px;
        border-radius: 14px;
    }
}

.produit-hero__prix-bloc {
    display: flex;
    align-items: baseline;
    gap: 10px;
}
.produit-hero__prix {
    font-size: clamp(1.6rem, 5vw, 2.2rem);
    font-weight: 800;
    color: var(--brun);
    letter-spacing: -0.03em;
}
.produit-hero__prix-note {
    font-size: 0.78rem;
    color: #111;
}

/* Stock indicator */
.produit-hero__stock {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}
.produit-hero__stock--ok  { color: var(--vert); }
.produit-hero__stock--bas { color: #e67e22; }
.produit-hero__stock--off { color: var(--rouge); }

/* Quantité */
.produit-hero__quantite {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1.5px solid #d4c9bb;
    border-radius: 10px;
    overflow: hidden;
    width: fit-content;
}

@media (max-width: 768px) {
    .produit-hero__quantite {
        width: 100%;
        max-width: 200px;
    }
    .qty-btn {
        width: 48px;
        height: 46px;
        font-size: 1.5rem;
    }
    .qty-input {
        flex: 1;
        height: 46px;
        font-size: 1.1rem;
    }
}
.qty-btn {
    background: #fff;
    border: none;
    width: 38px;
    height: 38px;
    font-size: 1.3rem;
    cursor: pointer;
    color: var(--brun);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.qty-btn:hover { background: #f0ebe0; }
.qty-input {
    width: 46px;
    height: 38px;
    border: none;
    border-left: 1.5px solid #d4c9bb;
    border-right: 1.5px solid #d4c9bb;
    text-align: center;
    font-size: 1rem;
    font-weight: 700;
    color: var(--brun);
    background: #fff;
    outline: none;
}
.qty-input::-webkit-inner-spin-button,
.qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }

/* Bouton ajouter au panier */
.decouvrir__panier-btn {
    width: 100%;
    padding: 16px 24px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, var(--brun) 0%, #5a3820 100%);
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
    box-shadow: 0 4px 20px rgba(61,43,26,0.25);
}

@media (max-width: 768px) {
    .decouvrir__panier-btn {
        padding: 18px 24px;
        font-size: 1.05rem;
        border-radius: 14px;
    }
}
.decouvrir__panier-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(61,43,26,0.35);
    background: linear-gradient(135deg, #5a3820 0%, var(--or) 100%);
}
.decouvrir__panier-btn:active:not(:disabled) { transform: translateY(0); }
.decouvrir__panier-btn:disabled {
    background: #c5bdb2;
    cursor: not-allowed;
    box-shadow: none;
}
.decouvrir__panier-btn--added {
    background: linear-gradient(135deg, var(--vert) 0%, #3d9954 100%) !important;
}

/* Bouton aperçu rapide (retour liste) */
.decouvrir__back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s, gap 0.2s;
    padding: 10px 0;
}
.decouvrir__back-btn:hover { color: var(--or); gap: 12px; }

/* ─── Section produits similaires ─── */
.similaires {
    padding-top: 60px;
    border-top: 1px solid #e8dfd0;
}

@media (max-width: 768px) {
    .similaires {
        padding-top: 40px;
    }
    .similaires__titre {
        font-size: 1.4rem;
    }
}
.similaires__header {
    margin-bottom: 36px;
}
.similaires__eyebrow {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--or);
    margin-bottom: 8px;
}
.similaires__titre {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--brun);
    letter-spacing: -0.02em;
}

/* Grille similaires */
.similaires__grille {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
}

@media (max-width: 600px) {
    .similaires__grille {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
}

@media (max-width: 360px) {
    .similaires__grille {
        grid-template-columns: 1fr;
    }
}

/* Carte similaire */
.sim-card {
    background: #fff;
    border: 1px solid #ede5d8;
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.25s, box-shadow 0.25s;
    display: flex;
    flex-direction: column;
}
.sim-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px rgba(61,43,26,0.12);
}
.sim-card__img-wrap {
    background: var(--sable);
    aspect-ratio: 1/1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
.sim-card__img-wrap img {
    width: 75%;
    height: 75%;
    object-fit: contain;
    transition: transform 0.4s ease;
}
.sim-card:hover .sim-card__img-wrap img { transform: scale(1.08); }

.sim-card__badge {
    position: absolute;
    top: 10px; left: 10px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.sim-card__badge--new     { background: #e8f5ee; color: var(--vert); }
.sim-card__badge--low     { background: #fff3e0; color: #e67e22; }
.sim-card__badge--rupture { background: #fdecea; color: var(--rouge); }

.sim-card__body {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.sim-card__nom {
    font-size: 1rem;
    font-weight: 800;
    color: var(--brun);
    letter-spacing: -0.01em;
}
.sim-card__desc {
    font-size: 0.78rem;
    color: #7a6a5a;
    line-height: 1.5;

    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;

    overflow: hidden;
    text-overflow: ellipsis;
}
.sim-card__footer {
    padding: 0 16px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 400px) {
    .sim-card__footer {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .sim-card__actions {
        display: flex;
        gap: 8px;
        width: 100%;
    }
    .sim-card__btn-voir,
    .sim-card__btn-panier {
        flex: 1;
        justify-content: center;
        padding: 10px 8px;
    }
}
.sim-card__prix {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--brun);
}
.sim-card__actions {
    display: flex;
    gap: 8px;
}
.sim-card__btn-voir {
    padding: 8px 14px;
    border-radius: 8px;
    border: 1.5px solid var(--brun);
    background: transparent;
    color: var(--brun);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.2s, color 0.2s;
}
.sim-card__btn-voir:hover { background: var(--brun); color: #fff; }

.sim-card__btn-panier {
    padding: 8px 14px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, var(--brun), #5a3820);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 2px 8px rgba(61,43,26,0.2);
    white-space: nowrap;
}
.sim-card__btn-panier:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(61,43,26,0.28); }
.sim-card__btn-panier:disabled { background: #c5bdb2; cursor: not-allowed; box-shadow: none; }
.sim-card__btn-panier--added { background: linear-gradient(135deg, var(--vert), #3d9954) !important; }

/* Toast */
.toast-decouvrir {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: var(--brun);
    color: #fff;
    padding: 12px 24px;
    border-radius: 30px;
    font-size: 0.88rem;
    font-weight: 600;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s, transform 0.3s;
    z-index: 9999;
    white-space: nowrap;
}
.toast-decouvrir--visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.toast-decouvrir--error { background: #c0392b; }
</style>

<!-- ══════════════════════════════════════════
     CONTENU PRINCIPAL
     ══════════════════════════════════════════ -->
<main class="decouvrir">

    <!-- Fil d'Ariane -->
    <nav class="breadcrumb" aria-label="Fil d'Ariane">
        <a href="index.php">Accueil</a>
        <span class="breadcrumb__sep">›</span>
        <a href="index.php#produits">Produits</a>
        <span class="breadcrumb__sep">›</span>
        <span class="breadcrumb__current"><?= htmlspecialchars($produit->nom) ?></span>
    </nav>

    <!-- ── Hero Produit ── -->
    <div class="produit-hero">

        <!-- Carousel -->
        <div class="produit-hero__galerie">

            <?php
                // Récupération de toutes les images du produit
                $images_produit = [];
                if (!empty($produit->images) && is_array($produit->images)) {
                    $images_produit = $produit->images;
                } elseif (!empty($produit->url_image)) {
                    $images_produit = [(object)['url_image' => $produit->url_image]];
                } elseif (!empty($produit->image_url)) {
                    $images_produit = [(object)['url_image' => $produit->image_url]];
                }
                if (empty($images_produit)) {
                    $images_produit = [(object)['url_image' => 'images/placeholder.png']];
                }
                $nb_images = count($images_produit);
            ?>

            <div class="carousel" id="carousel-produit"
                 data-total="<?= $nb_images ?>"
                 data-autoplay="4000">

                <!-- Track -->
                <div class="carousel__track" id="carousel-track">
                    <?php foreach ($images_produit as $idx => $img): ?>
                    <div class="carousel__slide <?= $idx === 0 ? 'active' : '' ?>">
                        <img
                            src="<?= htmlspecialchars($img->url_image ?? 'images/placeholder.png') ?>"
                            alt="<?= htmlspecialchars($produit->nom) ?> — photo <?= $idx + 1 ?>"
                            loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>"
                        >
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Badge stock -->
                <?php if ((int)$produit->stock <= 5 && (int)$produit->stock > 0): ?>
                    <span class="produit-hero__badge produit-hero__badge--low">Dernières pièces</span>
                <?php elseif ((int)$produit->stock === 0): ?>
                    <span class="produit-hero__badge produit-hero__badge--rupture">Épuisé</span>
                <?php else: ?>
                    <span class="produit-hero__badge produit-hero__badge--new">Disponible</span>
                <?php endif; ?>

                <!-- Wishlist -->
                <button class="produit-hero__wishlist" aria-label="Ajouter aux favoris" onclick="this.classList.toggle('actif')">♡</button>

                <?php if ($nb_images > 1): ?>
                <!-- Flèche gauche -->
                <button class="carousel__arrow carousel__arrow--prev" id="carousel-prev" aria-label="Photo précédente">
                    <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <!-- Flèche droite -->
                <button class="carousel__arrow carousel__arrow--next" id="carousel-next" aria-label="Photo suivante">
                    <svg viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
                </button>

                <!-- Points -->
                <div class="carousel__dots" id="carousel-dots">
                    <?php for ($i = 0; $i < $nb_images; $i++): ?>
                        <button class="carousel__dot <?= $i === 0 ? 'active' : '' ?>"
                                data-index="<?= $i ?>"
                                aria-label="Photo <?= $i + 1 ?>">
                        </button>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            </div><!-- /.carousel -->

            <?php if ($nb_images > 1): ?>
            <!-- Miniatures -->
            <div class="carousel__thumbs" id="carousel-thumbs">
                <?php foreach ($images_produit as $idx => $img): ?>
                <button class="carousel__thumb <?= $idx === 0 ? 'active' : '' ?>"
                        data-index="<?= $idx ?>"
                        aria-label="Voir photo <?= $idx + 1 ?>">
                    <img src="<?= htmlspecialchars($img->url_image ?? 'images/placeholder.png') ?>"
                         alt="Miniature <?= $idx + 1 ?>" loading="lazy">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /.produit-hero__galerie -->

        <!--
        ══ DEBUG TEMPORAIRE CAROUSEL — à retirer une fois réglé ══
        nb_images calculé   : <?= $nb_images ?>
        produit->images existe ? : <?= isset($produit->images) ? 'oui' : 'non' ?>
        Détail :
        <?php foreach ($debug_carousel as $ligne): ?>
        - <?= htmlspecialchars($ligne) ?>

        <?php endforeach; ?>
        ════════════════════════════════════════════════════════
        -->

        <!-- Informations -->
        <div class="produit-hero__infos">

            <p class="produit-hero__categorie">
                <?= htmlspecialchars($produit->nom_categorie ?? 'WakAroma') ?> · WakAroma
            </p>

            <h1 class="produit-hero__nom"><?= htmlspecialchars($produit->nom) ?></h1>

            <p class="produit-hero__ref">Réf. <?= htmlspecialchars($produit->reference ?? '—') ?></p>

            <div class="produit-hero__sep"></div>

            <?php
                $desc_affichee = !empty($produit->description_long) ? $produit->description_long : $produit->description;
                $paragraphes   = array_filter(explode("\n\n", $desc_affichee));
            ?>
            <?php foreach ($paragraphes as $para): ?>
                <p class="produit-hero__desc"><?= nl2br(htmlspecialchars(trim($para))) ?></p>
            <?php endforeach; ?>

            <!-- Caractéristiques -->
            <?php if (!empty($produit->caracteristiques)): ?>
            <div class="produit-hero__carac">
                <?php foreach ($produit->caracteristiques as $carac): ?>
                    <div class="produit-hero__carac-item">
                        <strong><?= htmlspecialchars($carac->nom) ?> :</strong>
                        <?= htmlspecialchars($carac->valeur) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Bloc achat -->
            <div class="produit-hero__acheter">

                <!-- Prix -->
                <div class="produit-hero__prix-bloc">
                    <span class="produit-hero__prix"><?= number_format($produit->prix, 2) ?> €</span>
                    <span class="produit-hero__prix-note">TTC · Livraison offerte dès 50 €</span>
                </div>

                <!-- Stock -->
                <div class="produit-hero__stock <?=
                    (int)$produit->stock === 0 ? 'produit-hero__stock--off' :
                    ((int)$produit->stock <= 5 ? 'produit-hero__stock--bas' : 'produit-hero__stock--ok')
                ?>">
                    <?php if ((int)$produit->stock > 0): ?>
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>
                        En stock (<?= (int)$produit->stock ?> disponibles)
                    <?php else: ?>
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="currentColor"/></svg>
                        Rupture de stock
                    <?php endif; ?>
                </div>

                <!-- Quantité -->
                <?php if ((int)$produit->stock > 0): ?>
                <div class="produit-hero__quantite">
                    <button class="qty-btn" id="qty-minus" aria-label="Diminuer la quantité">−</button>
                    <input class="qty-input" type="number" id="qty-val" value="1" min="1" max="<?= (int)$produit->stock ?>" readonly>
                    <button class="qty-btn" id="qty-plus" aria-label="Augmenter la quantité">+</button>
                </div>
                <?php endif; ?>

                <!-- Bouton panier principal -->
                <button
                    class="decouvrir__panier-btn"
                    id="btn-panier-principal"
                    data-id="<?= (int)$produit->id_produit ?>"
                    onclick="ajouterAuPanierDecouvrir(this)"
                    <?= (int)$produit->stock === 0 ? 'disabled' : '' ?>
                >
                    <?php if ((int)$produit->stock === 0): ?>
                        ✕ Indisponible
                    <?php else: ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Ajouter au panier
                    <?php endif; ?>
                </button>

            </div>

            <!-- Retour -->
            <a href="index.php#produits" class="decouvrir__back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Retour aux produits
            </a>

        </div>
   


</main>

<!-- Toast -->
<div id="toast-decouvrir" class="toast-decouvrir" aria-live="polite"></div>

<!-- Footer -->
<?php require_once 'footer.php'; ?>
<?php require_once 'chatbox.php'; ?>

<!-- ══════════════════════════════════════════
     SCRIPTS
     ══════════════════════════════════════════ -->


<script>
// ══════════════════════════════════════════
// CAROUSEL
// ══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    const carousel  = document.getElementById('carousel-produit');
    if (!carousel) { console.warn('[carousel] #carousel-produit introuvable dans le DOM'); return; }

    const track      = document.getElementById('carousel-track');
    const btnPrev     = document.getElementById('carousel-prev');
    const btnNext     = document.getElementById('carousel-next');
    const dotsWrap    = document.getElementById('carousel-dots');
    const thumbsWrap  = document.getElementById('carousel-thumbs');
    const total       = parseInt(carousel.dataset.total, 10) || 1;
    const delay       = parseInt(carousel.dataset.autoplay, 10) || 4000;

    console.log('[carousel] init — total =', total, 'btnPrev =', btnPrev, 'btnNext =', btnNext);

    if (total <= 1) { console.log('[carousel] une seule image, carousel désactivé'); return; }
    if (!track) { console.warn('[carousel] #carousel-track introuvable'); return; }

    let current   = 0;
    let autoTimer = null;
    let animLock  = false;
    const ANIM_MS = 520;

    // ── Aller à un slide ──────────────────────────
    function goTo(index, userAction = false) {
        if (animLock) return;
        if (index < 0) index = total - 1;
        if (index >= total) index = 0;
        if (index === current) {
            if (userAction) resetAutoplay();
            return;
        }

        animLock = true;
        current = index;

        track.style.transform = 'translateX(-' + (current * 100) + '%)';

        track.querySelectorAll('.carousel__slide').forEach(function(s, i) {
            s.classList.toggle('active', i === current);
        });

        if (dotsWrap) {
            dotsWrap.querySelectorAll('.carousel__dot').forEach(function(d, i) {
                d.classList.toggle('active', i === current);
            });
        }

        if (thumbsWrap) {
            thumbsWrap.querySelectorAll('.carousel__thumb').forEach(function(t, i) {
                t.classList.toggle('active', i === current);
            });
            const activeThumb = thumbsWrap.querySelector('.carousel__thumb.active');
            if (activeThumb) activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        // Filet de sécurité : on libère toujours le verrou, même si une
        // précédente exécution avait laissé un timer en suspens.
        clearTimeout(goTo._unlockTimer);
        goTo._unlockTimer = setTimeout(function() { animLock = false; }, ANIM_MS);

        if (userAction) resetAutoplay();
    }

    // ── Autoplay ──────────────────────────────────
    function startAutoplay() {
        stopAutoplay();
        autoTimer = setInterval(function() { goTo(current + 1); }, delay);
    }
    function stopAutoplay() {
        if (autoTimer) clearInterval(autoTimer);
        autoTimer = null;
    }
    function resetAutoplay() {
        stopAutoplay();
        startAutoplay();
    }

    // ── Flèches ───────────────────────────────────
    if (btnPrev) {
        btnPrev.addEventListener('click', function(e) {
            e.preventDefault();
            goTo(current - 1, true);
        });
    } else {
        console.warn('[carousel] #carousel-prev introuvable — la flèche gauche ne réagira pas');
    }

    if (btnNext) {
        btnNext.addEventListener('click', function(e) {
            e.preventDefault();
            goTo(current + 1, true);
        });
    } else {
        console.warn('[carousel] #carousel-next introuvable — la flèche droite ne réagira pas');
    }

    // ── Dots ──────────────────────────────────────
    if (dotsWrap) {
        dotsWrap.querySelectorAll('.carousel__dot').forEach(function(dot) {
            dot.addEventListener('click', function(e) {
                e.preventDefault();
                goTo(parseInt(dot.dataset.index, 10), true);
            });
        });
    }

    // ── Thumbs ────────────────────────────────────
    if (thumbsWrap) {
        thumbsWrap.querySelectorAll('.carousel__thumb').forEach(function(thumb) {
            thumb.addEventListener('click', function(e) {
                e.preventDefault();
                goTo(parseInt(thumb.dataset.index, 10), true);
            });
        });
    }

    // ── Swipe tactile ─────────────────────────────
    let touchStartX = 0;
    let touchDeltaX = 0;
    carousel.addEventListener('touchstart', e => {
        touchStartX = e.touches[0].clientX;
        stopAutoplay();
    }, { passive: true });
    carousel.addEventListener('touchmove', e => {
        touchDeltaX = e.touches[0].clientX - touchStartX;
    }, { passive: true });
    carousel.addEventListener('touchend', () => {
        if (Math.abs(touchDeltaX) > 50) {
            goTo(touchDeltaX < 0 ? current + 1 : current - 1, true);
        } else {
            resetAutoplay();
        }
        touchDeltaX = 0;
    });

    // ── Pause au survol ───────────────────────────
    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    // ── Keyboard ──────────────────────────────────
    carousel.setAttribute('tabindex', '0');
    carousel.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft')  goTo(current - 1, true);
        if (e.key === 'ArrowRight') goTo(current + 1, true);
    });

    // ── Lancement ─────────────────────────────────
    startAutoplay();
});
</script>

<script>
// ── Toast ──────────────────────────────────────────────
function afficherToastD(msg, type = 'success') {
    const el = document.getElementById('toast-decouvrir');
    el.textContent = msg;
    el.className = 'toast-decouvrir toast-decouvrir--' + type + ' toast-decouvrir--visible';
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('toast-decouvrir--visible'), 3200);
}

// ── Badge panier ──────────────────────────────────────────────
function updateCartBadge(nb, animate = false) {
    const badge = document.getElementById('cart-badge-count');
    if (!badge) return;
    const n = parseInt(nb) || 0;
    badge.textContent = n > 99 ? '99+' : n;
    badge.classList.toggle('visible', n > 0);
    if (animate && n > 0) {
        badge.classList.remove('bump');
        void badge.offsetWidth;
        badge.classList.add('bump');
        setTimeout(() => badge.classList.remove('bump'), 300);
    }
}

// ── Quantité ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const qInput = document.getElementById('qty-val');
    const qMinus = document.getElementById('qty-minus');
    const qPlus  = document.getElementById('qty-plus');
    if (!qInput) return;
    const maxStock = parseInt(qInput.max) || 99;

    qMinus?.addEventListener('click', () => {
        const v = parseInt(qInput.value) || 1;
        if (v > 1) qInput.value = v - 1;
    });
    qPlus?.addEventListener('click', () => {
        const v = parseInt(qInput.value) || 1;
        if (v < maxStock) qInput.value = v + 1;
    });
});

// ── Ajouter au panier (produit principal) ────────────────────
async function ajouterAuPanierDecouvrir(btn) {
    <?php if (empty($_SESSION['auth'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>

    const idProduit = btn.dataset.id;
    const qte       = parseInt(document.getElementById('qty-val')?.value) || 1;
    btn.disabled    = true;
    const original  = btn.innerHTML;

    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Ajout…';

    try {
        const body = new URLSearchParams({ action: 'ajouter', id_produit: idProduit, quantite: qte });
        const res  = await fetch('panier.php', { method: 'POST', body });
        const json = await res.json();

        if (json.success) {
            btn.innerHTML = '✓ Ajouté au panier !';
            btn.classList.add('decouvrir__panier-btn--added');
            afficherToastD('Article ajouté au panier 🛒');
            if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles, true);
            setTimeout(() => {
                btn.innerHTML = original;
                btn.classList.remove('decouvrir__panier-btn--added');
                btn.disabled = false;
            }, 2000);
        } else {
            afficherToastD(json.message || "Erreur lors de l'ajout", 'error');
            btn.innerHTML = original;
            btn.disabled  = false;
        }
    } catch (e) {
        afficherToastD('Erreur de connexion', 'error');
        btn.innerHTML = original;
        btn.disabled  = false;
    }
}

// ── Ajouter au panier (produits similaires) ──────────────────
async function ajouterAuPanierSim(btn) {
    <?php if (empty($_SESSION['auth'])): ?>
        window.location.href = 'login.php';
        return;
    <?php endif; ?>

    const idProduit = btn.dataset.id;
    btn.disabled    = true;
    const original  = btn.innerHTML;
    btn.innerHTML   = '…';

    try {
        const body = new URLSearchParams({ action: 'ajouter', id_produit: idProduit });
        const res  = await fetch('panier.php', { method: 'POST', body });
        const json = await res.json();

        if (json.success) {
            btn.innerHTML = '✓';
            btn.classList.add('sim-card__btn-panier--added');
            afficherToastD('Article ajouté au panier 🛒');
            if (json.nb_articles !== undefined) updateCartBadge(json.nb_articles, true);
            setTimeout(() => {
                btn.innerHTML = original;
                btn.classList.remove('sim-card__btn-panier--added');
                btn.disabled  = false;
            }, 1800);
        } else {
            afficherToastD(json.message || "Erreur lors de l'ajout", 'error');
            btn.innerHTML = original;
            btn.disabled  = false;
        }
    } catch (e) {
        afficherToastD('Erreur de connexion', 'error');
        btn.innerHTML = original;
        btn.disabled  = false;
    }
}

</script>
