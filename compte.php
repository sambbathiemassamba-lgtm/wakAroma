<?php
session_start();
?>

<?php if(!empty($_SESSION['auth'])): ?>

<?php
require_once 'function.php';
require_once 'pdo.php';

$id_user = (int)$_SESSION['auth']['id_user'];

// ── Filtres ──────────────────────────────────────────────────────
$filtre_statut = $_GET['statut'] ?? 'tous';
$page          = max(1, (int)($_GET['page'] ?? 1));
$par_page      = 10;
$offset        = ($page - 1) * $par_page;

$where_statut = '';
$params       = [$id_user];
$statuts_valides = ['en_attente','en_transit','expediee','livree','annulee'];

if($filtre_statut !== 'tous' && in_array($filtre_statut, $statuts_valides)) {
    $where_statut = ' AND c.statut = ?';
    $params[]     = $filtre_statut;
}

// ── Total pour pagination ────────────────────────────────────────
$stmt_count = $pdo->prepare(
    "SELECT COUNT(*) FROM commandes c WHERE c.id_user = ? $where_statut"
);
$stmt_count->execute($params);
$total_rows  = (int)$stmt_count->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $par_page));

// ── Liste des commandes ──────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT c.id_commande, c.numero_commande, c.statut, c.total, c.created_at,
           COUNT(lc.id_ligne_commande) AS nb_articles,
           GROUP_CONCAT(lc.nom_produit ORDER BY lc.id_ligne_commande SEPARATOR '|||') AS noms_produits
    FROM commandes c
    LEFT JOIN lignes_commandes lc ON lc.id_commande = c.id_commande
    WHERE c.id_user = ? $where_statut
    GROUP BY c.id_commande
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
// Bind explicite en INT — MariaDB rejette les strings pour LIMIT/OFFSET
$bind_pos = 1;
foreach($params as $val) {
    $stmt->bindValue($bind_pos++, (int)$val, PDO::PARAM_INT);
}
$stmt->bindValue($bind_pos++, (int)$par_page, PDO::PARAM_INT);
$stmt->bindValue($bind_pos++, (int)$offset,   PDO::PARAM_INT);
$stmt->execute();
$commandes = $stmt->fetchAll(PDO::FETCH_OBJ);

// ── Helpers ──────────────────────────────────────────────────────
function statutBadgeClass(string $s): string {
    return match($s) {
        'livree','livrée'                  => 'status-badge--livree',
        'en_transit','expediee','expédiée' => 'status-badge--transit',
        'annulee','annulée'                => 'status-badge--annulee',
        default                            => 'status-badge--attente',
    };
}
function statutLabel(string $s): string {
    return match($s) {
        'livree','livrée'    => 'Livrée',
        'en_transit'         => 'En transit',
        'expediee','expédiée'=> 'Expédiée',
        'annulee','annulée'  => 'Annulée',
        'en_attente'         => 'En attente',
        default              => ucfirst(str_replace('_',' ',$s)),
    };
}
function emojiproduit(): string {
    $e = ['🌶️','🫚','🌿','🧂','🫙','🌰','🍃','✨'];
    return $e[array_rand($e)];
}
?>

<?php require_once 'headear.php'; ?>

<style>
:root { --boutique-sidebar-w: 270px; }

.boutique-layout {
  display: grid;
  grid-template-columns: var(--boutique-sidebar-w) 1fr;
  min-height: calc(100vh - 120px);
  background: var(--color-bg);
}

/* ── Sidebar (identique à compte.php) ──────── */
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
  display: flex; flex-direction: column; align-items: center;
  gap: 0.75rem; padding: 1.5rem 1rem 2rem;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  margin-bottom: 1rem;
}
.sidebar-avatar__ring {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold), var(--color-primary));
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-size: 2rem; font-weight: 700;
  color: #fff; box-shadow: 0 0 0 4px rgba(200,148,58,0.35);
}
.sidebar-avatar__name { font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#fff; text-align:center; }
.sidebar-avatar__badge { font-size:0.65rem; letter-spacing:0.2em; text-transform:uppercase; font-weight:600; color:var(--color-gold); background:rgba(200,148,58,0.18); padding:0.25rem 0.75rem; border-radius:999px; border:1px solid rgba(200,148,58,0.35); }
.sidebar-nav__label { font-size:0.62rem; letter-spacing:0.2em; text-transform:uppercase; color:rgba(255,255,255,0.35); font-weight:600; padding:0.6rem 0.75rem 0.3rem; margin-top:0.5rem; }
.sidebar-nav__item { display:flex; align-items:center; gap:0.85rem; padding:0.8rem 1rem; border-radius:12px; text-decoration:none; color:rgba(255,255,255,0.7); font-size:0.88rem; font-weight:500; cursor:pointer; transition:all var(--transition); border:none; background:transparent; width:100%; text-align:left; }
.sidebar-nav__item:hover { color:#fff; background:rgba(255,255,255,0.1); }
.sidebar-nav__item--active { background:rgba(200,148,58,0.25) !important; color:var(--color-gold) !important; font-weight:600; }
.sidebar-nav__icon { width:36px; height:36px; border-radius:9px; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem; }
.sidebar-nav__item--active .sidebar-nav__icon { background:rgba(200,148,58,0.3); }
.sidebar-nav__pill { margin-left:auto; font-size:0.62rem; background:var(--color-gold); color:var(--color-green); font-weight:700; padding:0.15rem 0.55rem; border-radius:999px; }
.sidebar-bottom { margin-top:auto; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.1); }

/* ── Main ────────────────────────────────────── */
.boutique-main { padding: 2.5rem 3rem; display:flex; flex-direction:column; gap:2rem; }

.page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.page-header__title { font-family:var(--font-display); font-size:2.2rem; font-weight:700; color:var(--color-green); letter-spacing:-0.03em; line-height:1.1; }
.page-header__title em { font-style:italic; color:var(--color-gold); font-family:var(--font-italic); }
.page-header__sub { font-size:0.88rem; color:var(--color-muted); margin-top:0.35rem; }

/* ── Filtres ────────────────────────────────── */
.filters-row {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-card);
  padding: 1rem 1.2rem;
  align-items: center;
}
.filters-row__label { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.12em; color:var(--color-muted); font-weight:600; margin-right:0.5rem; }
.filter-pill {
  padding: 0.4rem 1rem;
  border: 1.5px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-bg-card);
  color: var(--color-muted);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all var(--transition);
}
.filter-pill:hover { border-color:var(--color-primary); color:var(--color-green); }
.filter-pill--active { background:var(--color-green); border-color:var(--color-green); color:#fff; }

/* ── Tableau ─────────────────────────────────── */
.orders-table-wrap {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-card);
  overflow: hidden;
}
.orders-table { width:100%; border-collapse:collapse; }
.orders-table thead th {
  padding: 1rem 1.2rem;
  font-size: 0.72rem; letter-spacing:0.12em; text-transform:uppercase;
  color:var(--color-muted); font-weight:600; text-align:left;
  background:var(--color-cream); border-bottom:1px solid var(--color-border);
}
.orders-table tbody tr { border-bottom:1px solid var(--color-border); transition:background var(--transition); }
.orders-table tbody tr:last-child { border-bottom:none; }
.orders-table tbody tr:hover { background:var(--color-cream); cursor:pointer; }
.orders-table td { padding:1rem 1.2rem; font-size:0.86rem; vertical-align:middle; }

.order-id { font-family:'Courier New',monospace; font-size:0.8rem; color:var(--color-muted); font-weight:600; }
.order-products { display:flex; align-items:center; gap:0.5rem; }
.order-product-img { width:36px; height:36px; border-radius:8px; background:var(--color-green-light); display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.order-product-name { font-weight:500; color:var(--color-text); font-size:0.84rem; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.order-product-qty { font-size:0.74rem; color:var(--color-muted); margin-top:0.15rem; }
.order-price { font-weight:700; color:var(--color-green); font-size:0.92rem; }

.status-badge { display:inline-flex; align-items:center; gap:0.35rem; padding:0.3rem 0.75rem; border-radius:999px; font-size:0.73rem; font-weight:600; letter-spacing:0.03em; white-space:nowrap; }
.status-badge--livree  { background:#e6f7ed; color:#1f6e3c; }
.status-badge--transit { background:#fff4e0; color:#a35c00; }
.status-badge--attente { background:#f0f0f0; color:#555; }
.status-badge--annulee { background:#fdecea; color:#c0392b; }
.status-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.status-badge--livree  .status-dot { background:#2d7a44; }
.status-badge--transit .status-dot { background:#f59e0b; animation:pulse 1.4s infinite; }
.status-badge--attente .status-dot { background:#888; }
.status-badge--annulee .status-dot { background:#e74c3c; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }


/* ── Pagination ──────────────────────────────── */
.pagination { display:flex; justify-content:center; gap:0.4rem; flex-wrap:wrap; }
.pag-btn {
  min-width: 40px; height: 40px;
  display: flex; align-items:center; justify-content:center;
  border: 1.5px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-bg-card);
  color: var(--color-muted);
  font-size: 0.85rem; font-weight: 600;
  text-decoration: none;
  transition: all var(--transition);
}
.pag-btn:hover { border-color:var(--color-primary); color:var(--color-green); }
.pag-btn--active { background:var(--color-green); border-color:var(--color-green); color:#fff; }
.pag-btn--disabled { opacity:0.4; pointer-events:none; }

/* ── Empty ───────────────────────────────────── */
.empty-state { padding:3.5rem 2rem; text-align:center; color:var(--color-muted); }
.empty-state__icon { font-size:3.5rem; margin-bottom:0.75rem; }
.empty-state__title { font-family:var(--font-display); font-size:1.3rem; color:var(--color-green); margin-bottom:0.5rem; font-weight:700; }
.empty-state__sub { font-size:0.85rem; margin-bottom:1.4rem; }

/* ── Modal détail ────────────────────────────── */







/* ── Progress commande ─────────────────────── */

/* ── Boutons ──────────────────────────────── */
.btn-primary { display:inline-flex; align-items:center; gap:0.5rem; padding:0.65rem 1.35rem; background:var(--color-green); color:#fff; border:none; border-radius:var(--radius-btn); font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all var(--transition); }
.btn-primary:hover { background:var(--color-green-mid); transform:translateY(-1px); box-shadow:0 6px 20px rgba(31,79,46,0.25); }
.btn-outline { display:inline-flex; align-items:center; gap:0.5rem; padding:0.65rem 1.2rem; border:1.5px solid var(--color-border); border-radius:var(--radius-btn); background:var(--color-bg-card); color:var(--color-text); font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all var(--transition); }
.btn-outline:hover { border-color:var(--color-primary); color:var(--color-green); }

/* ── Animation entrée ─────────────────── */
.fade-up { opacity:0; transform:translateY(18px); animation:fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards; }
@keyframes fadeUp { to{opacity:1;transform:none} }
.fade-up:nth-child(1){animation-delay:0.05s} .fade-up:nth-child(2){animation-delay:0.1s} .fade-up:nth-child(3){animation-delay:0.15s} .fade-up:nth-child(4){animation-delay:0.2s}

/* ── Responsive ─────────────────────── */
@media(max-width:860px){
  .boutique-layout{grid-template-columns:1fr}
  .boutique-sidebar{position:static;height:auto;flex-direction:row;flex-wrap:wrap;padding:1rem;gap:0.5rem}
  .sidebar-avatar{display:none} .sidebar-nav__label{display:none}
  .sidebar-nav__item{flex-direction:column;gap:0.25rem;font-size:0.7rem;padding:0.6rem 0.7rem;text-align:center}
  .sidebar-nav__pill{display:none}
  .boutique-main{padding:1.5rem 1rem}
}
@media(max-width:640px){
  .orders-table thead{display:none}
  .orders-table tbody tr{display:block;padding:0.8rem 1rem}
  .orders-table td{display:block;padding:0.25rem 0}
  .order-product-name{max-width:200px}
}
</style>

<div class="boutique-layout">

  <!-- ══════════ SIDEBAR ══════════ -->
  <aside class="boutique-sidebar" aria-label="Navigation espace client">
    <div class="sidebar-avatar">
      <div class="sidebar-avatar__ring"><?= strtoupper(mb_substr($_SESSION['auth']['prenom'], 0, 1)) ?></div>
      <div class="sidebar-avatar__name"><?= htmlspecialchars($_SESSION['auth']['prenom']) ?></div>
      <span class="sidebar-avatar__badge">✦ Membre Gold</span>
    </div>

    <span class="sidebar-nav__label">Mon espace</span>

    <a href="compte.php" class="sidebar-nav__item">
      <span class="sidebar-nav__icon">🏠</span>
      Tableau de bord
    </a>

    <a href="commandes.php" class="sidebar-nav__item sidebar-nav__item--active">
      <span class="sidebar-nav__icon">📦</span>
      Mes commandes
      <?php if($total_rows > 0): ?>
        <span class="sidebar-nav__pill"><?= $total_rows ?></span>
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
      <a href="logout.php" class="sidebar-nav__item" style="color:rgba(255,120,100,0.85);"
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

    <!-- Header -->
    <div class="page-header fade-up">
      <div>
        <h1 class="page-header__title">Mes <em>commandes</em></h1>
        <p class="page-header__sub">
          <?= $total_rows ?> commande<?= $total_rows > 1 ? 's' : '' ?> au total
        </p>
      </div>
      <a href="index.php#produits" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
        Nouvelle commande
      </a>
    </div>

    <!-- Filtres -->
    <div class="filters-row fade-up">
      <span class="filters-row__label">Filtrer :</span>
      <?php
      $filtres = [
        'tous'        => 'Toutes',
        'en_attente'  => 'En attente',
        'en_transit'  => 'En transit',
        'expediee'    => 'Expédiée',
        'livree'      => 'Livrée',
        'annulee'     => 'Annulée',
      ];
      foreach($filtres as $val => $label):
        $active = ($filtre_statut === $val) ? 'filter-pill--active' : '';
      ?>
        <a href="commandes.php?statut=<?= $val ?>" class="filter-pill <?= $active ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Tableau -->
    <div class="orders-table-wrap fade-up">
      <?php if(count($commandes) > 0): ?>
      <table class="orders-table" aria-label="Liste de mes commandes">
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
          <?php foreach($commandes as $cmd):
            $noms = $cmd->noms_produits ? explode('|||', $cmd->noms_produits) : [];
            $premier = $noms[0] ?? 'Commande';
          ?>
          <tr >
            <td><span class="order-id"><?= htmlspecialchars($cmd->numero_commande) ?></span></td>
            <td>
              <div class="order-products">
                <div class="order-product-img"><?= emojiproduit() ?></div>
                <div>
                  <div class="order-product-name"><?= htmlspecialchars($premier) ?></div>
                  <div class="order-product-qty">
                    × <?= (int)$cmd->nb_articles ?> article<?= (int)$cmd->nb_articles > 1 ? 's' : '' ?>
                    <?php if(count($noms) > 1): ?>
                      <span style="color:var(--color-gold)"> +<?= count($noms)-1 ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>
            <td style="color:var(--color-muted);font-size:0.82rem;white-space:nowrap">
              <?= date('d M Y', strtotime($cmd->created_at)) ?>
            </td>
            <td>
              <span class="status-badge <?= statutBadgeClass($cmd->statut) ?>">
                <span class="status-dot"></span>
                <?= statutLabel($cmd->statut) ?>
              </span>
            </td>
            <td><span class="order-price"><?= number_format((float)$cmd->total, 2, ',', ' ') ?> €</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-state__icon">📭</div>
        <div class="empty-state__title">Aucune commande trouvée</div>
        <div class="empty-state__sub">
          <?= $filtre_statut !== 'tous' ? 'Aucune commande avec ce statut.' : 'Vous n\'avez pas encore passé de commande.' ?>
        </div>
        <a href="index.php#produits" class="btn-primary">Découvrir nos épices</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="pagination fade-up">
      <?php if($page > 1): ?>
        <a href="commandes.php?statut=<?= urlencode($filtre_statut) ?>&page=<?= $page-1 ?>" class="pag-btn">‹</a>
      <?php else: ?>
        <span class="pag-btn pag-btn--disabled">‹</span>
      <?php endif; ?>

      <?php for($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
        <a href="commandes.php?statut=<?= urlencode($filtre_statut) ?>&page=<?= $p ?>"
           class="pag-btn <?= $p === $page ? 'pag-btn--active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>

      <?php if($page < $total_pages): ?>
        <a href="commandes.php?statut=<?= urlencode($filtre_statut) ?>&page=<?= $page+1 ?>" class="pag-btn">›</a>
      <?php else: ?>
        <span class="pag-btn pag-btn--disabled">›</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<!-- Toast -->
<div id="toast-cmd" aria-live="polite" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;background:var(--color-green);color:#fff;padding:.85rem 1.4rem;border-radius:12px;font-size:.85rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.18);transform:translateY(120px);opacity:0;transition:all 0.3s;pointer-events:none;"></div>

<?php require_once 'footer.php'; ?>

<?php else: ?>
<?php header("Location: login.php"); exit(); ?>
<?php endif; ?>