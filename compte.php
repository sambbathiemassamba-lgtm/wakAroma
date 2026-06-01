<?php
session_start();
?>

<?php if(!empty($_SESSION['auth'])): ?>

<?php require_once 'headear.php'; ?>

<!-- ══════════════════════════════════════════
     BOUTIQUE — PAGE ESPACE CLIENT
     ══════════════════════════════════════════ -->

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

/* ── Page header ──────────────────────────── */
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



/* ── Section title ───────────────────────── */
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

/* ── Commandes récentes ─────────────────── */
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

.status-badge--livree   { background: #e6f7ed; color: #1f6e3c; }
.status-badge--transit  { background: #fff4e0; color: #a35c00; }
.status-badge--attente  { background: #f0f0f0; color: #555; }
.status-badge--annulee  { background: #fdecea; color: #c0392b; }

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-badge--livree  .status-dot { background: #2d7a44; }
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

/* ── Produits favoris ───────────────────── */
.fav-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.1rem;
}

.fav-card {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-card);
  padding: 1.2rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  transition: all var(--transition);
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}

.fav-card:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-card-hover);
  transform: translateY(-2px);
}

.fav-card__thumb {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--color-green-light), var(--color-cream));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  flex-shrink: 0;
}

.fav-card__name {
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-green);
  letter-spacing: -0.01em;
}

.fav-card__cat {
  font-size: 0.7rem;
  color: var(--color-muted);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 500;
  margin-top: 0.15rem;
}

.fav-card__price {
  font-weight: 700;
  color: var(--color-gold);
  font-size: 1rem;
  margin-top: 0.4rem;
}

/* ── Fidélité ──────────────────────────── */
.loyalty-card {
  background: linear-gradient(135deg, var(--color-green) 0%, #2d5a3c 50%, #1a3d25 100%);
  border-radius: var(--radius-card);
  padding: 2rem 2.5rem;
  color: #fff;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 2rem;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.loyalty-card::before {
  content: '🌍';
  position: absolute;
  right: -20px;
  bottom: -20px;
  font-size: 9rem;
  opacity: 0.06;
  pointer-events: none;
}

.loyalty-card__eyebrow {
  font-size: 0.65rem;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--color-gold);
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.loyalty-card__title {
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.1;
  margin-bottom: 0.75rem;
}

.loyalty-bar-wrap {
  background: rgba(255,255,255,0.12);
  border-radius: 999px;
  height: 8px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.loyalty-bar {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--color-gold), var(--color-primary));
  width: 68%;
  position: relative;
  animation: loyaltyGrow 1.2s cubic-bezier(0.22,1,0.36,1) forwards;
  transform-origin: left;
}

@keyframes loyaltyGrow {
  from { width: 0; }
  to   { width: 68%; }
}

.loyalty-bar-label {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.6);
}

.loyalty-bar-label strong { color: #fff; }

.loyalty-points {
  text-align: center;
  background: rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 1.5rem 2rem;
  border: 1px solid rgba(255,255,255,0.12);
  min-width: 140px;
}

.loyalty-points__num {
  font-family: var(--font-display);
  font-size: 3rem;
  font-weight: 700;
  color: var(--color-gold);
  letter-spacing: -0.04em;
  line-height: 1;
}

.loyalty-points__label {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.55);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-top: 0.35rem;
}



/* ── Section tabs ─────────────────────── */
.tab-bar {
  display: flex;
  gap: 0.25rem;
  background: var(--color-cream);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 0.3rem;
}

.tab-btn {
  flex: 1;
  padding: 0.55rem 1rem;
  border: none;
  background: transparent;
  border-radius: 9px;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-muted);
  cursor: pointer;
  transition: all var(--transition);
}

.tab-btn--active {
  background: var(--color-bg-card);
  color: var(--color-green);
  box-shadow: 0 1px 4px rgba(45,42,38,0.1);
}

/* ══════════════════════════════════════════
   CATALOGUE PRODUITS
   ══════════════════════════════════════════ */
.catalogue-filters {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.4rem;
}
.cat-filter {
  padding: 0.45rem 1.1rem;
  border: 1.5px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-bg-card);
  color: var(--color-muted);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition);
  font-family: var(--font-body);
}
.cat-filter:hover { border-color: var(--color-primary); color: var(--color-green); }
.cat-filter--active { background: var(--color-green); border-color: var(--color-green); color: #fff; }

.catalogue-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.3rem;
}

.cat-card {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-card);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all var(--transition);
}
.cat-card:hover {
  box-shadow: var(--shadow-card-hover);
  transform: translateY(-3px);
  border-color: rgba(212,165,116,0.4);
}

.cat-card__img-wrap {
  position: relative;
  aspect-ratio: 4/3;
  overflow: hidden;
  background: var(--color-cream);
}
.cat-card__img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform 0.45s cubic-bezier(0.22,1,0.36,1);
}
.cat-card:hover .cat-card__img { transform: scale(1.06); }

.cat-card__badge {
  position: absolute; top: 10px; left: 10px;
  font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
  padding: 0.25rem 0.65rem; border-radius: 999px;
}
.cat-card__badge--ok  { background:#e6f7ed; color:#1f6e3c; }
.cat-card__badge--low { background:#fff4e0; color:#a35c00; }
.cat-card__badge--out { background:#fdecea; color:#c0392b; }

/* ❤ Bouton favori */
.cat-card__wish {
  position: absolute; top: 10px; right: 10px;
  width: 36px; height: 36px; border-radius: 50%;
  background: rgba(255,255,255,0.92);
  border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  transition: all var(--transition);
  z-index: 2; backdrop-filter: blur(4px);
}
.cat-card__wish:hover { background:#fff; transform:scale(1.12); box-shadow:0 4px 14px rgba(0,0,0,0.15); }
.cat-card__wish .wish-icon { stroke: #bbb; transition: all 0.2s ease; }
.cat-card__wish.actif .wish-icon { stroke: #e74c3c; fill: #e74c3c; }
.cat-card__wish.actif { background: #fff0f0; }

@keyframes heartPop {
  0%{transform:scale(1)} 40%{transform:scale(1.35)} 70%{transform:scale(0.9)} 100%{transform:scale(1)}
}
.cat-card__wish.pop { animation: heartPop 0.35s cubic-bezier(0.22,1,0.36,1); }

.cat-card__body {
  padding: 1rem 1.1rem 0.6rem;
  flex: 1; display: flex; flex-direction: column; gap: 0.3rem;
}
.cat-card__cat { font-size:0.65rem; text-transform:uppercase; letter-spacing:0.12em; color:var(--color-gold); font-weight:700; }
.cat-card__name { font-family:var(--font-display); font-size:1.1rem; font-weight:700; color:var(--color-green); letter-spacing:-0.01em; line-height:1.2; }
.cat-card__stars { font-size:0.82rem; color:var(--color-gold); }
.cat-card__desc { font-size:0.78rem; color:var(--color-muted); line-height:1.5; margin-top:0.2rem; }
.cat-card__stock { display:flex; align-items:center; gap:0.4rem; font-size:0.72rem; font-weight:500; margin-top:auto; padding-top:0.4rem; }
.cat-card__stock--ok  { color: var(--color-green-mid); }
.cat-card__stock--out { color: #c0392b; }
.stock-dot { width:7px; height:7px; border-radius:50%; background:currentColor; flex-shrink:0; }

.cat-card__footer {
  display:flex; align-items:center; justify-content:space-between;
  padding: 0.75rem 1.1rem 1rem;
  border-top: 1px solid var(--color-border);
  margin-top: 0.5rem;
}
.cat-card__price { font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:var(--color-green); letter-spacing:-0.02em; }
.cat-card__add {
  display:flex; align-items:center; gap:0.45rem;
  padding: 0.55rem 1.1rem;
  background: var(--color-green); color:#fff;
  border:none; border-radius:var(--radius-btn);
  font-size:0.78rem; font-weight:600; cursor:pointer;
  transition: all var(--transition); font-family:var(--font-body);
}
.cat-card__add:hover { background:var(--color-green-mid); transform:translateY(-1px); box-shadow:0 4px 14px rgba(31,79,46,0.25); }
.cat-card__add--disabled { background:#e0e0e0; color:#999; cursor:not-allowed; }

.fav-count-badge {
  display:inline-flex !important; align-items:center; justify-content:center;
  min-width:18px; height:18px; border-radius:999px;
  background:#e74c3c; color:#fff;
  font-size:0.65rem; font-weight:700; padding:0 5px; margin-left:2px;
}

.cat-card--hidden { display:none !important; }

/* ── Responsive ─────────────────────── */
@media (max-width: 1100px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .catalogue-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 860px) {
  .boutique-layout {
    grid-template-columns: 1fr;
  }
  .boutique-sidebar {
    position: static;
    height: auto;
    flex-direction: row;
    flex-wrap: wrap;
    padding: 1rem;
    gap: 0.5rem;
  }
  .sidebar-avatar { display: none; }
  .sidebar-nav__label { display: none; }
  .sidebar-nav__item {
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.7rem;
    padding: 0.6rem 0.7rem;
    text-align: center;
  }
  .sidebar-nav__pill { display: none; }
  .boutique-main { padding: 1.5rem 1rem; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .fav-grid { grid-template-columns: 1fr; }
  .address-grid { grid-template-columns: 1fr; }
  .loyalty-card { grid-template-columns: 1fr; }
  .loyalty-points { display: none; }
}

@media (max-width: 540px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .boutique-topbar__greeting { font-size: 1.7rem; }
  .orders-table thead { display: none; }
  .orders-table tbody tr {
    display: block;
    padding: 0.8rem 1rem;
  }
  .orders-table td {
    display: block;
    padding: 0.25rem 0;
  }
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

<div class="boutique-layout">

  <!-- ══════════ SIDEBAR ══════════ -->
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
      <span class="sidebar-nav__pill">3</span>
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
      <a href="logout.php" class="sidebar-nav__item" style="color:rgba(255,120,100,0.85);" onmouseover="this.style.background='rgba(231,76,60,0.15)';this.style.color='#ff6b5b'" onmouseout="this.style.background='transparent';this.style.color='rgba(255,120,100,0.85)'">
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
        <a href="commandes.php" class="btn-outline">Voir tout →</a>
      </div>

      <div class="orders-table-wrap">
        <table class="orders-table" aria-label="Mes commandes récentes">
          <thead>
            <tr>
              <th>Réf.</th>
              <th>Produit(s)</th>
              <th>Date</th>
              <th>Statut</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><span class="order-id">#WK-00124</span></td>
              <td>
                <div class="order-products">
                  <div class="order-product-img">🌶️</div>
                  <div>
                    <div class="order-product-name">Piment Fumé Cameroun</div>
                    <div class="order-product-qty">× 2 articles</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--color-muted); font-size:0.82rem">24 mai 2026</td>
              <td><span class="status-badge status-badge--transit"><span class="status-dot"></span>En transit</span></td>
              <td><span class="order-price">18,90 €</span></td>
              <td><a href="commande-detail.php?id=124" class="btn-text">Détails</a></td>
            </tr>
            <tr>
              <td><span class="order-id">#WK-00118</span></td>
              <td>
                <div class="order-products">
                  <div class="order-product-img">🫚</div>
                  <div>
                    <div class="order-product-name">Huile de Baobab Bio</div>
                    <div class="order-product-qty">× 1 article</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--color-muted); font-size:0.82rem">15 mai 2026</td>
              <td><span class="status-badge status-badge--livree"><span class="status-dot"></span>Livrée</span></td>
              <td><span class="order-price">24,50 €</span></td>
              <td><a href="commande-detail.php?id=118" class="btn-text">Détails</a></td>
            </tr>
            <tr>
              <td><span class="order-id">#WK-00109</span></td>
              <td>
                <div class="order-products">
                  <div class="order-product-img">🌿</div>
                  <div>
                    <div class="order-product-name">Ras el Hanout Premium</div>
                    <div class="order-product-qty">× 3 articles</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--color-muted); font-size:0.82rem">3 mai 2026</td>
              <td><span class="status-badge status-badge--livree"><span class="status-dot"></span>Livrée</span></td>
              <td><span class="order-price">37,20 €</span></td>
              <td><a href="commande-detail.php?id=109" class="btn-text">Détails</a></td>
            </tr>
          </tbody>
        </table>
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
/* ── Toast ─────────────────── */
function showToast(msg, type='success') {
  const el = document.getElementById('toast-boutique');
  el.textContent = msg;
  el.style.background = type === 'error' ? '#c0392b' : 'var(--color-green)';
  el.style.transform = 'translateY(0)'; el.style.opacity = '1';
  clearTimeout(el._t);
  el._t = setTimeout(() => { el.style.transform='translateY(120px)'; el.style.opacity='0'; }, 2800);
}

/* ── Favoris localStorage ─── */
const FAV_KEY = 'waka_favoris';
function getFavoris()   { try { return JSON.parse(localStorage.getItem(FAV_KEY)) || {}; } catch { return {}; } }
function saveFavoris(o) { localStorage.setItem(FAV_KEY, JSON.stringify(o)); }

function updateFavBadge() {
  const nb = Object.keys(getFavoris()).length;
  const b  = document.getElementById('fav-count-badge');
  if (!b) return;
  b.textContent = nb;
  b.style.display = nb > 0 ? 'inline-flex' : 'none';
}

function toggleFavori(btn) {
  const { id, nom, prix, img } = btn.dataset;
  const favs = getFavoris();
  btn.classList.add('pop');
  btn.addEventListener('animationend', () => btn.classList.remove('pop'), { once: true });
  if (favs[id]) {
    delete favs[id];
    btn.classList.remove('actif');
    showToast('❌ Retiré des favoris');
  } else {
    favs[id] = { id, nom, prix, img };
    btn.classList.add('actif');
    showToast('❤️ Ajouté aux favoris !');
  }
  saveFavoris(favs);
  updateFavBadge();
}

function initFavorisBtns() {
  const favs = getFavoris();
  document.querySelectorAll('.cat-card__wish').forEach(btn => {
    if (favs[btn.dataset.id]) btn.classList.add('actif');
  });
  updateFavBadge();
}

/* ── Init ───────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initFavorisBtns();
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.style.animationPlayState='running'; observer.unobserve(e.target); }
    });
  }, { threshold: 0.08 });
  document.querySelectorAll('.fade-up').forEach(el => {
    el.style.animationPlayState = 'paused';
    observer.observe(el);
  });
});
</script>

<?php require_once 'footer.php'; ?>

<?php else: ?>
<?php header("Location: login.php"); exit(); ?>
<?php endif; ?>