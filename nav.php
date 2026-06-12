<style>
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

    <a href="compte.php" class="mobile-tab" aria-label="Tableau de bord">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Accueil
    </a>

    <a href="commandes.php" class="mobile-tab mobile-tab--active" aria-label="Mes commandes">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M16.5 9.4l-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
        <line x1="12" y1="22.08" x2="12" y2="12"/>
      </svg>
      Commandes
      <?php if($total_commandes_global > 0): ?>
        <span class="mobile-tab__badge"><?= $total_commandes_global ?></span>
      <?php endif; ?>
    </a>

    <a href="favoris.php" class="mobile-tab" aria-label="Mes favoris">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
      </svg>
      Favoris
    </a>

    <a href="profil.php" class="mobile-tab" aria-label="Mon profil">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Profil
    </a>

    <a href="adresses.php" class="mobile-tab" aria-label="Mes adresses">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
        <circle cx="12" cy="10" r="3"/>
      </svg>
      Adresses
    </a>

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
