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

// ── Récupération des 3 dernières commandes ──────────────────
$stmtCmd = $pdo->prepare("
    SELECT
        c.id_commande,
        c.numero_commande,
        c.statut,
        c.total,
        c.created_at,
        GROUP_CONCAT(lc.nom_produit ORDER BY lc.id_ligne_commande SEPARATOR ', ') AS produits,
        SUM(lc.quantite) AS nb_articles,
        (SELECT i.url_image FROM images i
         INNER JOIN lignes_commandes lc2 ON lc2.id_produit = i.id_produit
         WHERE lc2.id_commande = c.id_commande LIMIT 1) AS premiere_image
    FROM commandes c
    INNER JOIN lignes_commandes lc ON lc.id_commande = c.id_commande
    WHERE c.id_user = :id
    GROUP BY c.id_commande
    ORDER BY c.created_at DESC
    LIMIT 3
");
$stmtCmd->execute([':id' => $id_user]);
$commandes_recentes = $stmtCmd->fetchAll(PDO::FETCH_OBJ);

// ── Nombre total de commandes (badge sidebar) ───────────────
$stmtNb = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE id_user = :id");
$stmtNb->execute([':id' => $id_user]);
$nb_commandes = (int) $stmtNb->fetchColumn();
?>

<?php require_once 'headear.php'; ?>

<style>
/* ── Variables locales ─────────────────────── */
:root {
  --boutique-sidebar-w: 270px;
}

/* ── Layout principal ──────────────────────── */
.boutique-layout {
  display: grid;
  grid-template-columns: var(--boutique-sidebar-w) 1fr;
  min-height: calc(100vh - 120px);
  background: var(--color-bg);
}

/* ══════════════════════════════════════════
   SIDEBAR
   ══════════════════════════════════════════ */
.boutique-sidebar {
  background: var(--color-green);
  padding: 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}

.sidebar-avatar {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem 1rem 2rem;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  margin-bottom: 1rem;
}

.sidebar-avatar__ring {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold), var(--color-primary));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  color: #fff;
  box-shadow: 0 0 0 4px rgba(200,148,58,0.35);
  letter-spacing: -0.03em;
}

.sidebar-avatar__name {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 700;
  color: #fff;
  text-align: center;
  letter-spacing: -0.01em;
}

.sidebar-avatar__badge {
  font-size: 0.65rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  font-weight: 600;
  color: var(--color-gold);
  background: rgba(200,148,58,0.18);
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  border: 1px solid rgba(200,148,58,0.35);
}

.sidebar-nav__label {
  font-size: 0.62rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.35);
  font-weight: 600;
  padding: 0.6rem 0.75rem 0.3rem;
  margin-top: 0.5rem;
}

.sidebar-nav__item {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.8rem 1rem;
  border-radius: 12px;
  text-decoration: none;
  color: rgba(255,255,255,0.7);
  font-size: 0.88rem;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--transition);
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
}

.sidebar-nav__item:hover,
.sidebar-nav__item--active {
  color: #fff;
  background: rgba(255,255,255,0.1);
}

.sidebar-nav__item--active {
  background: rgba(200,148,58,0.25) !important;
  color: var(--color-gold) !important;
  font-weight: 600;
}

.sidebar-nav__icon {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: rgba(255,255,255,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1rem;
  transition: background var(--transition);
}

.sidebar-nav__item--active .sidebar-nav__icon {
  background: rgba(200,148,58,0.3);
}

.sidebar-nav__pill {
  margin-left: auto;
  font-size: 0.62rem;
  background: var(--color-gold);
  color: var(--color-green);
  font-weight: 700;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
}

.sidebar-bottom {
  margin-top: auto;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}

/* ══════════════════════════════════════════
   MAIN CONTENT
   ══════════════════════════════════════════ */
.boutique-main {
  padding: 2.5rem 3rem;
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.boutique-topbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.boutique-topbar__greeting {
  font-family: var(--font-display);
  font-size: 2.4rem;
  font-weight: 700;
  color: var(--color-green);
  letter-spacing: -0.03em;
  line-height: 1.1;
}

.boutique-topbar__greeting em {
  font-style: italic;
  color: var(--color-gold);
  font-family: var(--font-italic);
}

.boutique-topbar__sub {
  font-size: 0.9rem;
  color: var(--color-muted);
  margin-top: 0.4rem;
}

.boutique-topbar__actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.2rem;
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-btn);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all var(--transition);
}

.btn-outline:hover {
  border-color: var(--color-primary);
  color: var(--color-green);
  box-shadow: 0 4px 14px rgba(212,165,116,0.2);
  transform: translateY(-1px);
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.35rem;
  background: var(--color-green);
  color: #fff;
  border: none;
  border-radius: var(--radius-btn);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all var(--transition);
}

.btn-primary:hover {
  background: var(--color-green-mid);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(31,79,46,0.25);
}

.section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.2rem;
}

.section-title__h {
  font-family: var(--font-display);
  font-size: 1.45rem;
  font-weight: 700;
  color: var(--color-green);
  letter-spacing: -0.02em;
}

.section-title__eyebrow {
  font-size: 0.65rem;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--color-gold);
  font-weight: 700;
  margin-bottom: 0.2rem;
}

/* ── Commandes ─────────────────────────────── */
.orders-table-wrap {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-card);
  overflow: hidden;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
}

.orders-table thead th {
  padding: 1rem 1.2rem;
  font-size: 0.72rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-muted);
  font-weight: 600;
  text-align: left;
  background: var(--color-cream);
  border-bottom: 1px solid var(--color-border);
}

.orders-table tbody tr {
  border-bottom: 1px solid var(--color-border);
  transition: background var(--transition);
}

.orders-table tbody tr:last-child { border-bottom: none; }
.orders-table tbody tr:hover { background: var(--color-cream); }

.orders-table td {
  padding: 1rem 1.2rem;
  font-size: 0.86rem;
  vertical-align: middle;
}

.order-id {
  font-family: 'Courier New', monospace;
  font-size: 0.8rem;
  color: var(--color-muted);
  font-weight: 600;
}

.order-products {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.order-product-img {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--color-green-light);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
  overflow: hidden;
}

.order-product-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}

.order-product-name {
  font-weight: 500;
  color: var(--color-text);
  font-size: 0.84rem;
}

.order-product-qty {
  font-size: 0.74rem;
  color: var(--color-muted);
  margin-top: 0.15rem;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.75rem;
  border-radius: 999px;
  font-size: 0.73rem;
  font-weight: 600;
  letter-spacing: 0.03em;
}

.status-badge--livree  { background: #e6f7ed; color: #1f6e3c; }
.status-badge--payee   { background: #e6f7ed; color: #1f6e3c; }
.status-badge--transit { background: #fff4e0; color: #a35c00; }
.status-badge--attente { background: #f0f0f0; color: #555; }
.status-badge--annulee { background: #fdecea; color: #c0392b; }

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-badge--livree  .status-dot,
.status-badge--payee   .status-dot { background: #2d7a44; }
.status-badge--transit .status-dot { background: #f59e0b; animation: pulse 1.4s infinite; }
.status-badge--attente .status-dot { background: #888; }
.status-badge--annulee .status-dot { background: #e74c3c; }

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(1.3); }
}

.order-price {
  font-weight: 700;
  color: var(--color-green);
  font-size: 0.92rem;
}

.btn-text {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-gold);
  text-decoration: none;
  border-bottom: 1px solid transparent;
  transition: border-color var(--transition);
}
.btn-text:hover { border-color: var(--color-gold); }

.empty-orders {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--color-muted);
}
.empty-orders__icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-orders p { margin-bottom: 1.2rem; font-size: 0.9rem; }

/* ══════════════════════════════════════════
   BARRE DE NAVIGATION MOBILE (EN HAUT)
   ══════════════════════════════════════════ */
.mobile-top-nav {
  display: none;
  position: sticky;
  top: 0;
  left: 0;
  right: 0;
  background: var(--color-green);
  border-bottom: 1px solid rgba(255,255,255,0.12);
  z-index: 200;
}

.mobile-tabs {
  display: flex;
  align-items: stretch;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.mobile-tabs::-webkit-scrollbar { display: none; }

.mobile-tab {
  flex: 0 0 auto;
  min-width: 68px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 10px 12px 12px;
  color: rgba(255,255,255,0.4);
  font-size: 0.58rem;
  font-weight: 500;
  text-decoration: none;
  position: relative;
  transition: color 0.15s;
  cursor: pointer;
  border: none;
  background: transparent;
  letter-spacing: 0.02em;
  text-align: center;
  white-space: nowrap;
}

.mobile-tab svg {
  width: 22px;
  height: 22px;
  stroke: currentColor;
  stroke-width: 1.8;
  fill: none;
  stroke-linecap: round;
  stroke-linejoin: round;
  flex-shrink: 0;
}

.mobile-tab--active {
  color: var(--color-gold) !important;
}

/* Indicateur : barre en BAS de l'onglet (visible sur fond vert) */
.mobile-tab--active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 15%;
  right: 15%;
  height: 2px;
  background: var(--color-gold);
  border-radius: 2px 2px 0 0;
}

.mobile-tab--logout {
  color: rgba(255,120,100,0.65);
}

.mobile-tab--logout:hover,
.mobile-tab--logout:focus {
  color: #ff6b5b;
}

.mobile-tab__badge {
  position: absolute;
  top: 7px;
  right: calc(50% - 22px);
  min-width: 16px;
  height: 16px;
  border-radius: 999px;
  background: var(--color-gold);
  color: var(--color-green);
  font-size: 0.55rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3px;
  line-height: 1;
}

/* ── Responsive desktop intermédiaire ──────── */
@media (max-width: 860px) and (min-width: 641px) {
  .boutique-layout {
    grid-template-columns: 220px 1fr;
  }
  .boutique-main {
    padding: 2rem 1.5rem;
  }
  .sidebar-avatar__ring {
    width: 56px;
    height: 56px;
    font-size: 1.5rem;
  }
}

/* ── Responsive mobile ──────────────────────── */
@media (max-width: 640px) {

  /* Cache la sidebar, affiche la navbar en haut */
  .boutique-sidebar  { display: none !important; }
  .mobile-top-nav    { display: block; }

  /* Layout pleine largeur — pas de padding-bottom pour une navbar fixe */
  .boutique-layout   { grid-template-columns: 1fr; }
  .boutique-main     { padding: 1.25rem 1rem 2rem; gap: 1.5rem; }

  /* Topbar */
  .boutique-topbar__greeting { font-size: 1.6rem; }

  /* Tableau responsive : sans en-têtes, cellules en bloc avec libellé */
  .orders-table thead { display: none; }

  .orders-table tbody tr {
    display: block;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--color-border);
  }
  .orders-table tbody tr:last-child { border-bottom: none; }
  .orders-table tbody tr:hover      { background: var(--color-cream); }

  .orders-table td {
    display: block;
    padding: 0.22rem 0;
    font-size: 0.83rem;
  }

  /* Libellés automatiques via data-label */
  .orders-table td[data-label]::before {
    content: attr(data-label);
    display: block;
    font-size: 0.62rem;
    color: var(--color-muted);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 2px;
    margin-top: 6px;
  }

  /* Pas de libellé pour la colonne Réf. (première) */
  .orders-table td:first-child::before { display: none; }
}

/* ── Animation entrée ─────────────────── */
.fade-up {
  opacity: 0;
  transform: translateY(18px);
  animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
}

@keyframes fadeUp {
  to { opacity: 1; transform: none; }
}

.fade-up:nth-child(1) { animation-delay: 0.05s; }
.fade-up:nth-child(2) { animation-delay: 0.10s; }
.fade-up:nth-child(3) { animation-delay: 0.15s; }
.fade-up:nth-child(4) { animation-delay: 0.20s; }
.fade-up:nth-child(5) { animation-delay: 0.25s; }
.fade-up:nth-child(6) { animation-delay: 0.30s; }
</style>

<!-- ══════════ BARRE DE NAVIGATION MOBILE (EN HAUT) ══════════ -->
<nav class="mobile-top-nav" aria-label="Navigation principale">
  <div class="mobile-tabs">

    <!-- Tableau de bord -->
    <a href="compte.php" class="mobile-tab mobile-tab--active" aria-label="Tableau de bord">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Accueil
    </a>

    <!-- Mes commandes -->
    <a href="commandes.php" class="mobile-tab" aria-label="Mes commandes">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
        <line x1="12" y1="22.08" x2="12" y2="12"/>
      </svg>
      Commandes
      <?php if ($nb_commandes > 0): ?>
        <span class="mobile-tab__badge"><?= $nb_commandes ?></span>
      <?php endif; ?>
    </a>

    <!-- Mes favoris -->
    <a href="favoris.php" class="mobile-tab" aria-label="Mes favoris">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
      </svg>
      Favoris
    </a>

    <!-- Mon profil -->
    <a href="profil.php" class="mobile-tab" aria-label="Mon profil">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Profil
    </a>

    <!-- Mes adresses -->
    <a href="adresses.php" class="mobile-tab" aria-label="Mes adresses">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
        <circle cx="12" cy="10" r="3"/>
      </svg>
      Adresses
    </a>

    <!-- Se déconnecter -->
    <a href="logout.php" class="mobile-tab mobile-tab--logout" aria-label="Se déconnecter">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Quitter
    </a>

  </div>
</nav>

<div class="boutique-layout">

  <!-- ══════════ SIDEBAR (desktop) ══════════ -->
  <aside class="boutique-sidebar" aria-label="Navigation espace client">

    <div class="sidebar-avatar">
      <div class="sidebar-avatar__ring">
        <?= strtoupper(mb_substr($_SESSION['auth']['prenom'], 0, 1)) ?>
      </div>
      <div class="sidebar-avatar__name"><?= htmlspecialchars($_SESSION['auth']['prenom']) ?></div>
      <span class="sidebar-avatar__badge">✦ Membre Gold</span>
    </div>

    <span class="sidebar-nav__label">Mon espace</span>

    <a href="compte.php" class="sidebar-nav__item sidebar-nav__item--active">
      <span class="sidebar-nav__icon">🏠</span>
      Tableau de bord
    </a>

    <a href="commandes.php" class="sidebar-nav__item">
      <span class="sidebar-nav__icon">📦</span>
      Mes commandes
      <?php if ($nb_commandes > 0): ?>
        <span class="sidebar-nav__pill"><?= $nb_commandes ?></span>
      <?php endif; ?>
    </a>

    <a href="favoris.php" class="sidebar-nav__item">
      <span class="sidebar-nav__icon">❤️</span>
      Mes favoris
    </a>

    <span class="sidebar-nav__label">Mon compte</span>

    <a href="profil.php" class="sidebar-nav__item">
      <span class="sidebar-nav__icon">👤</span>
      Mon profil
    </a>

    <a href="adresses.php" class="sidebar-nav__item">
      <span class="sidebar-nav__icon">📍</span>
      Mes adresses
    </a>

    <div class="sidebar-bottom">
      <a href="logout.php" class="sidebar-nav__item"
         style="color:rgba(255,120,100,0.85);"
         onmouseover="this.style.background='rgba(231,76,60,0.15)';this.style.color='#ff6b5b'"
         onmouseout="this.style.background='transparent';this.style.color='rgba(255,120,100,0.85)'">
        <span class="sidebar-nav__icon" style="background:rgba(231,76,60,0.15);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </span>
        Se déconnecter
      </a>
    </div>
  </aside>

  <!-- ══════════ MAIN ══════════ -->
  <main class="boutique-main">

    <!-- Topbar -->
    <div class="boutique-topbar fade-up">
      <div>
        <h1 class="boutique-topbar__greeting">
          Bonjour, <em><?= htmlspecialchars($_SESSION['auth']['prenom']) ?></em> 👋
        </h1>
        <p class="boutique-topbar__sub">
          Voici un aperçu de votre espace WakAroma — <?= date('l d F Y') ?>
        </p>
      </div>
      <div class="boutique-topbar__actions">
        <a href="panier.php" class="btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Mon panier
        </a>
      </div>
    </div>

    <!-- Commandes récentes -->
    <div class="fade-up">
      <div class="section-title">
        <div>
          <div class="section-title__eyebrow">Suivi</div>
          <h2 class="section-title__h">Commandes récentes</h2>
        </div>
      </div>

      <div class="orders-table-wrap">
        <?php if (empty($commandes_recentes)): ?>
          <div class="empty-orders">
            <div class="empty-orders__icon">📦</div>
            <p>Vous n'avez pas encore passé de commande.</p>
            <a href="index.php" class="btn-primary">Découvrir nos produits</a>
          </div>
        <?php else: ?>
          <table class="orders-table" aria-label="Mes commandes récentes">
            <thead>
              <tr>
                <th>Réf.</th>
                <th>Produit(s)</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($commandes_recentes as $cmd):
                $statutClass = match($cmd->statut) {
                  'payee'      => 'payee',
                  'livree'     => 'livree',
                  'en_transit' => 'transit',
                  'en_attente' => 'attente',
                  'annulee'    => 'annulee',
                  default      => 'attente',
                };
                $statutLabel = match($cmd->statut) {
                  'payee'      => 'Payée',
                  'livree'     => 'Livrée',
                  'en_transit' => 'En transit',
                  'en_attente' => 'En attente',
                  'annulee'    => 'Annulée',
                  default      => ucfirst($cmd->statut),
                };
              ?>
              <tr>
                <td>
                  <span class="order-id"><?= htmlspecialchars($cmd->numero_commande) ?></span>
                </td>
                <td data-label="Produit(s)">
                  <div class="order-products">
                    <div class="order-product-img">
                      <?php if (!empty($cmd->premiere_image)): ?>
                        <img src="<?= htmlspecialchars($cmd->premiere_image) ?>" alt="">
                      <?php else: ?>
                        🛍️
                      <?php endif; ?>
                    </div>
                    <div>
                      <div class="order-product-name">
                        <?= htmlspecialchars(mb_substr($cmd->produits, 0, 45)) ?><?= mb_strlen($cmd->produits) > 45 ? '…' : '' ?>
                      </div>
                      <div class="order-product-qty">
                        × <?= (int)$cmd->nb_articles ?> article<?= $cmd->nb_articles > 1 ? 's' : '' ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td data-label="Date" style="color:var(--color-muted); font-size:0.82rem">
                  <?= date('d M Y', strtotime($cmd->created_at)) ?>
                </td>
                <td data-label="Statut">
                  <span class="status-badge status-badge--<?= $statutClass ?>">
                    <span class="status-dot"></span>
                    <?= $statutLabel ?>
                  </span>
                </td>
                <td data-label="Total">
                  <span class="order-price"><?= number_format($cmd->total, 2) ?> €</span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<!-- Toast notification -->
<div id="toast-boutique" aria-live="polite" style="
  position:fixed; bottom:2rem; right:2rem; z-index:9999;
  background:var(--color-green); color:#fff;
  padding:.85rem 1.4rem; border-radius:12px;
  font-size:.85rem; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,0.18);
  transform:translateY(120px); opacity:0; transition:all 0.3s cubic-bezier(0.22,1,0.36,1);
  pointer-events:none;
"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Animation fade-up au scroll
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.animationPlayState = 'running';
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.08 });

  document.querySelectorAll('.fade-up').forEach(el => {
    el.style.animationPlayState = 'paused';
    observer.observe(el);
  });

  // Onglet actif dans la barre mobile selon l'URL courante
  const currentPage = window.location.pathname.split('/').pop() || 'compte.php';
  document.querySelectorAll('.mobile-tab').forEach(tab => {
    const href = tab.getAttribute('href');
    if (href && href !== 'logout.php') {
      tab.classList.remove('mobile-tab--active');
      if (href === currentPage) {
        tab.classList.add('mobile-tab--active');
      }
    }
  });
});
</script>

<?php require_once 'footer.php'; ?>
