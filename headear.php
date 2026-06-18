<?php
// Vérifier si l'utilisateur est connecté
$estConnecte = !empty($_SESSION['auth']);
$nomUtilisateur = $_SESSION['auth']['prenom'] ?? $_SESSION['auth']['nom'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<title>www.wakAroma.com</title>
<style>
/* ══════════════════════════════════════
           BURGER MENU — WakAroma
        ══════════════════════════════════════ */

/* Bouton burger */
.burger-btn {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    width: 42px;
    height: 42px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    transition: background 0.2s;
    flex-shrink: 0;
    z-index: 1001;
}
.burger-btn:hover {
    background: rgba(31, 79, 46, 0.1);
}
.burger-btn__line {
    display: block;
    width: 100%;
    height: 2px;
    background: #1f4f2e;
    border-radius: 2px;
    transition: transform 0.35s cubic-bezier(0.23, 1, 0.32, 1),
                opacity 0.25s ease,
                width 0.3s ease;
    transform-origin: center;
}

/* Animation burger → croix */
.burger-btn.is-open .burger-btn__line:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.burger-btn.is-open .burger-btn__line:nth-child(2) {
    opacity: 0;
    width: 0;
}
.burger-btn.is-open .burger-btn__line:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

/* Overlay sombre derrière le menu */
.nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5, 20, 7, 0.6);
    backdrop-filter: blur(4px);
    z-index: 999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.35s ease;
}
.nav-overlay.is-open {
    opacity: 1;
    pointer-events: all;
}

/* Drawer latéral */
.nav-drawer {
    position: fixed;
    top: 0;
    left: 0;
    height: 100dvh;
    width: min(340px, 88vw);
    background: #1f4f2e;
    z-index: 1000;
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    display: flex;
    flex-direction: column;
    box-shadow: 8px 0 40px rgba(0,0,0,0.25);
    overflow-y: auto;
}
.nav-drawer.is-open {
    transform: translateX(0);
}

/* Bande dorée décorative en haut */
.nav-drawer::before {
    content: '';
    display: block;
    height: 4px;
    background: linear-gradient(90deg, #c8943a, #e8b860, #c8943a);
    flex-shrink: 0;
}

/* En-tête du drawer */
.nav-drawer__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 24px 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.nav-drawer__brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}
.nav-drawer__brand-logo {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(200, 148, 58, 0.15);
    border: 1px solid rgba(200, 148, 58, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}
.nav-drawer__brand-texts {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.nav-drawer__brand-name {
    font-size: 1.55rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1;
    font-family: 'Cormorant Garamond', serif;
}
.nav-drawer__brand-sub {
    font-size: 0.62rem;
    color: #c8943a;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
}
.nav-drawer__close {
    width: 34px;
    height: 34px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    color: rgba(255,255,255,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.nav-drawer__close:hover {
    background: rgba(255,255,255,0.13);
    color: #fff;
    border-color: rgba(255,255,255,0.25);
}

/* Profil utilisateur connecté */
.nav-drawer__user {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 24px;
    margin: 12px 16px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(200, 148, 58, 0.2);
    border-radius: 14px;
}
.nav-drawer__avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #c8943a, #e8b860);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: 800;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(200,148,58,0.35);
}
.nav-drawer__user-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.nav-drawer__user-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #fff;
}
.nav-drawer__user-status {
    font-size: 0.72rem;
    color: #c8943a;
    font-weight: 500;
}

/* Navigation principale */
.nav-drawer__nav {
    flex: 1;
    padding: 8px 0;
}
.nav-drawer__section-title {
    padding: 16px 24px 6px;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(200, 148, 58, 0.7);
}
.nav-drawer__link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 20px;
    margin: 2px 12px;
    text-decoration: none;
    color: rgba(255,255,255,0.82);
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 10px;
    transition: background 0.18s, color 0.18s, transform 0.18s;
}
.nav-drawer__link:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
    transform: translateX(4px);
}
.nav-drawer__link--active {
    background: rgba(200, 148, 58, 0.15);
    color: #c8943a;
}
.nav-drawer__link-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.nav-drawer__link-icon img {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

/* Séparateur */
.nav-drawer__sep {
    height: 1px;
    background: rgba(255,255,255,0.07);
    margin: 10px 24px;
}

/* Footer du drawer */
.nav-drawer__foot {
    padding: 16px;
    border-top: 1px solid rgba(255,255,255,0.07);
}
.nav-drawer__logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 16px;
    width: 100%;
    background: rgba(224, 92, 92, 0.08);
    border: 1px solid rgba(224, 92, 92, 0.25);
    border-radius: 10px;
    color: rgba(255,120,120,0.9);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.nav-drawer__logout:hover {
    background: rgba(224, 92, 92, 0.16);
    color: #ff8080;
    border-color: rgba(224, 92, 92, 0.45);
}

/* ══════════════════════════════════════
           RESPONSIVE HEADER
        ══════════════════════════════════════ */

/* Mobile : cacher Mon compte + Panier dans le header */
@media (max-width: 768px) {
    .header__actions {
        display: none;
    }
}

/* Desktop : cacher Mon compte + Panier + Se connecter + Créer un compte du drawer
   (ils sont déjà dans le header) */
@media (min-width: 769px) {
    .drawer-mobile-only {
        display: none;
    }
}

/* Très petit écran : barre de recherche passe en dessous */
@media (max-width: 480px) {
    .header {
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px 12px;
    }
    .header__search {
        order: 3;
        width: 100%;
        flex-basis: 100%;
    }
    .search-form {
        width: 100%;
    }
    .search-form__input {
        width: 100%;
    }
}
</style>
</head>
<body>

<!-- ══ Overlay ══ -->
<div class="nav-overlay" id="navOverlay" aria-hidden="true"></div>

<!-- ══ Drawer de navigation ══ -->
<nav class="nav-drawer" id="navDrawer" aria-label="Menu principal" aria-hidden="true">

    <!-- En-tête -->
    <div class="nav-drawer__head">
        <a href="index.php" class="nav-drawer__brand" onclick="fermerMenu()">
            <div class="nav-drawer__brand-texts">
                <span class="nav-drawer__brand-name">WakAroma</span>
                <span class="nav-drawer__brand-sub">Épices d'Afrique</span>
            </div>
        </a>
        <button class="nav-drawer__close" onclick="fermerMenu()" aria-label="Fermer le menu">✕</button>
    </div>

    <?php if ($estConnecte): ?>
    <!-- Utilisateur connecté -->
    <div class="nav-drawer__user">
        <div class="nav-drawer__avatar">
            <?= strtoupper(mb_substr($nomUtilisateur, 0, 1)) ?: '👤' ?>
        </div>
        <div class="nav-drawer__user-info">
            <span class="nav-drawer__user-name"><?= htmlspecialchars($nomUtilisateur) ?></span>
            <span class="nav-drawer__user-status">✓ Connecté</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liens -->
    <div class="nav-drawer__nav">

        <p class="nav-drawer__section-title">Explorer</p>

        <a href="index.php#produits" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon">🌿</span>
            Nos produits
        </a>
        <a href="historique.php" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon">📖</span>
            Notre histoire
        </a>
        <a href="salon.php" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon">🏡</span>
            Nos salons
        </a>

        <div class="nav-drawer__sep"></div>

        <!-- "Mon espace" : toujours visible dans le drawer -->
        <p class="nav-drawer__section-title">Mon espace</p>

        <!-- Panier : toujours dans le drawer -->
        <a href="panier.php" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon"><img src="icones/calebass.png" alt="Panier"></span>
            Mon panier
        </a>

        <?php if ($estConnecte): ?>
        <!-- Mon compte : toujours dans le drawer -->
        <a href="compte.php" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon"><img src="icones/moncompte.png" alt="Mon compte"></span>
            Mon compte
        </a>
        <?php else: ?>
        <a href="login.php" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon">🔑</span>
            Se connecter
        </a>
        <a href="inscription.php" class="nav-drawer__link" onclick="fermerMenu()">
            <span class="nav-drawer__link-icon">✨</span>
            Créer un compte
        </a>
        <?php endif; ?>

    </div>

    <!-- Footer du drawer -->
    <?php if ($estConnecte): ?>
    <div class="nav-drawer__foot">
        <a href="logout.php" class="nav-drawer__logout">
            <span>⇠</span> Se déconnecter
        </a>
    </div>
    <?php endif; ?>

</nav>

<!-- ══ HEADER ══ -->
<header class="header">

    <!-- Burger + Logo -->
    <div class="header__brgitand" style="display:flex; align-items:center; gap:12px;">
        <button
            class="burger-btn"
            id="burgerBtn"
            onclick="toggleMenu()"
            aria-label="Ouvrir le menu"
            aria-expanded="false"
            aria-controls="navDrawer"
        >
            <span class="burger-btn__line"></span>
            <span class="burger-btn__line"></span>
            <span class="burger-btn__line"></span>
        </button>
        <div class="header__title">
            <a href="index.php" style="text-decoration:none;">
                <h1>WakAroma</h1>
                <p class="header__subtitle">Épices d'Afrique</p>
            </a>
        </div>
    </div>

    <!-- Barre de recherche -->
    <div class="header__search">
        <form action="index.php" method="GET" class="search-form">
            <input
                type="search"
                name="q"
                placeholder="Rechercher un produit"
                class="search-form__input"
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
            >
            <button type="submit" class="search-form__button">Rechercher</button>
        </form>
    </div>

    <!-- Actions utilisateur : visibles uniquement sur desktop -->
    <nav class="header__actions">
        <a href="compte.php" class="header__link header__link--login">
            <img src="icones/moncompte.png" alt="Mon compte">
            <span>Mon compte</span>
        </a>
        <a href="panier.php" class="header__link header__link--cart" id="header-cart-link">
            <img src="icones/calebass.png" alt="Panier">
            <span>Panier</span>
        </a>
    </nav>

</header>

<script>
function toggleMenu() {
    const drawer  = document.getElementById('navDrawer');
    const overlay = document.getElementById('navOverlay');
    const btn     = document.getElementById('burgerBtn');
    const isOpen  = drawer.classList.toggle('is-open');
    overlay.classList.toggle('is-open', isOpen);
    btn.classList.toggle('is-open', isOpen);
    btn.setAttribute('aria-expanded', isOpen);
    drawer.setAttribute('aria-hidden', !isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

function fermerMenu() {
    const drawer  = document.getElementById('navDrawer');
    const overlay = document.getElementById('navOverlay');
    const btn     = document.getElementById('burgerBtn');
    drawer.classList.remove('is-open');
    overlay.classList.remove('is-open');
    btn.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

// Fermer avec Échap
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') fermerMenu();
});

// Fermer en cliquant sur l'overlay
document.getElementById('navOverlay').addEventListener('click', fermerMenu);
</script>
