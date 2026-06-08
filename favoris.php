<?php
session_start();
require_once 'pdo.php';
require_once 'function.php';

// ──────────────────────────────────────────────────────────────
// API AJAX
// ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (empty($_SESSION['auth'])) {
        echo json_encode(['success' => false, 'message' => 'Non connecté']);
        exit();
    }

    $id_user = (int) $_SESSION['auth']['id_user'];
    $action  = $_POST['action'];

    if ($action === 'toggle') {
        $id_produit = (int) ($_POST['id_produit'] ?? 0);
        if (!$id_produit) { echo json_encode(['success' => false]); exit(); }

        $check = $pdo->prepare("SELECT id_favori FROM favoris WHERE id_user = :u AND id_produit = :p");
        $check->execute([':u' => $id_user, ':p' => $id_produit]);

        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM favoris WHERE id_user = :u AND id_produit = :p")
                ->execute([':u' => $id_user, ':p' => $id_produit]);
            $actif = false;
        } else {
            $pdo->prepare("INSERT INTO favoris (id_user, id_produit) VALUES (:u, :p)")
                ->execute([':u' => $id_user, ':p' => $id_produit]);
            $actif = true;
        }

        $nb = (int) $pdo->query("SELECT COUNT(*) FROM favoris WHERE id_user = $id_user")->fetchColumn();
        echo json_encode(['success' => true, 'actif' => $actif, 'nb_favoris' => $nb]);
        exit();
    }

    if ($action === 'get_ids') {
        $req = $pdo->prepare("SELECT id_produit FROM favoris WHERE id_user = :u");
        $req->execute([':u' => $id_user]);
        $ids = $req->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'ids' => array_map('intval', $ids)]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    exit();
}

// ──────────────────────────────────────────────────────────────
// PAGE AFFICHAGE
// ──────────────────────────────────────────────────────────────
if (empty($_SESSION['auth'])) {
    header("Location: login.php");
    exit();
}

$id_user = (int) $_SESSION['auth']['id_user'];

$req = $pdo->prepare("
    SELECT
        p.id_produit, p.nom, p.prix, p.stock,
        COALESCE(
            MAX(CASE WHEN i.is_cover = 1 THEN i.url_image END),
            MIN(i.url_image)
        ) AS url_image,
        f.created_at AS favori_depuis
    FROM favoris f
    INNER JOIN produits p ON p.id_produit = f.id_produit
    LEFT  JOIN images   i ON i.id_produit = p.id_produit
    WHERE f.id_user = :u
    GROUP BY p.id_produit, p.nom, p.prix, p.stock, f.created_at
    ORDER BY f.created_at DESC
");
$req->execute([':u' => $id_user]);
$produits_favoris = $req->fetchAll(PDO::FETCH_OBJ);
?>
<?php require_once 'headear.php'; ?>

<style>
:root { --boutique-sidebar-w: 270px; }
.boutique-layout { display:grid; grid-template-columns:var(--boutique-sidebar-w) 1fr; min-height:calc(100vh - 120px); background:var(--color-bg); }
.boutique-sidebar { background:var(--color-green); padding:2.5rem 1.5rem; display:flex; flex-direction:column; gap:0.4rem; position:sticky; top:0; height:100vh; overflow-y:auto; }
.sidebar-avatar { display:flex; flex-direction:column; align-items:center; gap:0.75rem; padding:1.5rem 1rem 2rem; border-bottom:1px solid rgba(255,255,255,0.12); margin-bottom:1rem; }
.sidebar-avatar__ring { width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg,var(--color-gold),var(--color-primary)); display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-size:2rem; font-weight:700; color:#fff; box-shadow:0 0 0 4px rgba(200,148,58,0.35); }
.sidebar-avatar__name { font-family:var(--font-display); font-size:1.25rem; font-weight:700; color:#fff; text-align:center; }
.sidebar-avatar__badge { font-size:0.65rem; letter-spacing:0.2em; text-transform:uppercase; font-weight:600; color:var(--color-gold); background:rgba(200,148,58,0.18); padding:0.25rem 0.75rem; border-radius:999px; border:1px solid rgba(200,148,58,0.35); }
.sidebar-nav__label { font-size:0.62rem; letter-spacing:0.2em; text-transform:uppercase; color:rgba(255,255,255,0.35); font-weight:600; padding:0.6rem 0.75rem 0.3rem; margin-top:0.5rem; }
.sidebar-nav__item { display:flex; align-items:center; gap:0.85rem; padding:0.8rem 1rem; border-radius:12px; text-decoration:none; color:rgba(255,255,255,0.7); font-size:0.88rem; font-weight:500; transition:all var(--transition); border:none; background:transparent; width:100%; text-align:left; cursor:pointer; }
.sidebar-nav__item:hover,.sidebar-nav__item--active { color:#fff; background:rgba(255,255,255,0.1); }
.sidebar-nav__item--active { background:rgba(200,148,58,0.25)!important; color:var(--color-gold)!important; font-weight:600; }
.sidebar-nav__icon { width:36px; height:36px; border-radius:9px; background:rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem; }
.sidebar-nav__item--active .sidebar-nav__icon { background:rgba(200,148,58,0.3); }
.sidebar-nav__pill { margin-left:auto; font-size:0.62rem; background:var(--color-gold); color:var(--color-green); font-weight:700; padding:0.15rem 0.55rem; border-radius:999px; }
.sidebar-bottom { margin-top:auto; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.1); }
.boutique-main { padding:2.5rem 3rem; display:flex; flex-direction:column; gap:2.5rem; }
.boutique-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.boutique-topbar__greeting { font-family:var(--font-display); font-size:2.4rem; font-weight:700; color:var(--color-green); letter-spacing:-0.03em; line-height:1.1; }
.boutique-topbar__sub { font-size:0.9rem; color:var(--color-muted); margin-top:0.4rem; }
.section-title { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
.section-title__h { font-family:var(--font-display); font-size:1.45rem; font-weight:700; color:var(--color-green); }
.section-title__eyebrow { font-size:0.65rem; letter-spacing:0.18em; text-transform:uppercase; color:var(--color-gold); }
.btn-primary { display:inline-flex; align-items:center; gap:0.5rem; padding:0.65rem 1.35rem; background:var(--color-green); color:#fff; border:none; border-radius:var(--radius-btn); font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all var(--transition); }
.btn-primary:hover { background:var(--color-green-mid); transform:translateY(-1px); }
.btn-outline { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.1rem; border:1.5px solid var(--color-border); border-radius:var(--radius-btn); background:var(--color-bg-card); color:var(--color-text); font-size:0.82rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all var(--transition); }
.btn-outline:hover { border-color:var(--color-primary); color:var(--color-green); }
.fav-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.75rem;
}

.fav-card {
  background: var(--color-bg-card);
  border: 1.5px solid var(--color-border);
  border-radius: 22px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.25s, border-color 0.25s, transform 0.25s;
  padding: 0;
}
.fav-card:hover {
  box-shadow: 0 12px 36px rgba(31,79,46,0.13);
  transform: translateY(-4px);
  border-color: rgba(31,79,46,0.3);
}

/* Zone image sans fond, photo rectangulaire pleine largeur */
.fav-card__img-wrap {
  width: 100%;
  height: 220px;
  overflow: hidden;
  flex-shrink: 0;
}
.fav-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.4s cubic-bezier(0.22,1,0.36,1);
}
.fav-card:hover .fav-card__img {
  transform: scale(1.04);
}
.fav-card__img-placeholder {
  width: 100%;
  height: 220px;
  background: var(--color-green-light);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 4rem;
}

/* Contenu texte */
.fav-card__body {
  padding: 1.25rem 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  flex: 1;
}
.fav-card__cat {
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  color: var(--color-muted);
  font-weight: 600;
}
.fav-card__name {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--color-green);
  line-height: 1.2;
}
.fav-card__price {
  font-weight: 700;
  color: var(--color-gold);
  font-size: 1.15rem;
  margin-top: 0.3rem;
}

.fav-card__actions {
  display: flex;
  gap: 0.6rem;
  padding: 1rem 1.5rem 1.25rem;
  margin-top: auto;
}
.fav-card__btn-cart {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: var(--color-green);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s, transform 0.18s;
}
.fav-card__btn-cart:hover { background: var(--color-green-mid); transform: translateY(-1px); }
.fav-card__btn-remove {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(192,57,43,0.06);
  border: 1.5px solid rgba(192,57,43,0.2);
  border-radius: 12px;
  color: #c0392b;
  cursor: pointer;
  font-size: 1.1rem;
  transition: background 0.18s, border-color 0.18s;
}
.fav-card__btn-remove:hover { background: rgba(192,57,43,0.14); border-color: rgba(192,57,43,0.5); }

.fav-empty { text-align:center; padding:5rem 2rem; color:var(--color-muted); }
.fav-empty__icon { font-size:4rem; margin-bottom:1rem; }
.fav-empty__title { font-family:var(--font-display); font-size:1.6rem; color:var(--color-green); margin-bottom:0.6rem; }
.toast-fav { position:fixed; bottom:2rem; right:2rem; z-index:9999; background:var(--color-green); color:#fff; padding:0.85rem 1.4rem; border-radius:12px; font-size:0.85rem; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,0.18); transform:translateY(100px); opacity:0; transition:all 0.35s cubic-bezier(0.22,1,0.36,1); }
.toast-fav.show { transform:translateY(0); opacity:1; }
.fade-up { opacity:0; transform:translateY(18px); animation:fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards; }
@keyframes fadeUp { to { opacity:1; transform:none; } }
.fade-up:nth-child(1){animation-delay:0.05s} .fade-up:nth-child(2){animation-delay:0.10s}
@media(max-width:900px){ .boutique-layout{grid-template-columns:1fr} .boutique-sidebar{height:auto;position:relative} .boutique-main{padding:1.5rem} }
</style>

<div class="boutique-layout">
  <aside class="boutique-sidebar">
    <div class="sidebar-avatar">
      <div class="sidebar-avatar__ring"><?= strtoupper(mb_substr($_SESSION['auth']['prenom'], 0, 1)) ?></div>
      <div class="sidebar-avatar__name"><?= htmlspecialchars($_SESSION['auth']['prenom']) ?></div>
      <span class="sidebar-avatar__badge">✦ Membre Gold</span>
    </div>
    <span class="sidebar-nav__label">Mon espace</span>
    <a href="compte.php"    class="sidebar-nav__item"><span class="sidebar-nav__icon">🏠</span>Tableau de bord</a>
    <a href="commandes.php" class="sidebar-nav__item"><span class="sidebar-nav__icon">📦</span>Mes commandes<span class="sidebar-nav__pill">3</span></a>
    <a href="favoris.php"   class="sidebar-nav__item sidebar-nav__item--active">
      <span class="sidebar-nav__icon">❤️</span>Mes favoris
      <span class="sidebar-nav__pill"><?= count($produits_favoris) ?></span>
    </a>
    <span class="sidebar-nav__label">Mon compte</span>
    <a href="profil.php"   class="sidebar-nav__item"><span class="sidebar-nav__icon">👤</span>Mon profil</a>
    <a href="adresses.php" class="sidebar-nav__item"><span class="sidebar-nav__icon">📍</span>Mes adresses</a>
    <div class="sidebar-bottom">
      <a href="logout.php" class="sidebar-nav__item" style="color:rgba(255,120,100,0.85);"
         onmouseover="this.style.background='rgba(231,76,60,0.15)';this.style.color='#ff6b5b'"
         onmouseout="this.style.background='transparent';this.style.color='rgba(255,120,100,0.85)'">
        <span class="sidebar-nav__icon" style="background:rgba(231,76,60,0.15);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </span>Se déconnecter
      </a>
    </div>
  </aside>

  <main class="boutique-main">
    <div class="boutique-topbar fade-up">
      <div>
        <h1 class="boutique-topbar__greeting">Mes favoris ❤️</h1>
        <p class="boutique-topbar__sub"><?= count($produits_favoris) ?> produit<?= count($produits_favoris) > 1 ? 's' : '' ?> sauvegardé<?= count($produits_favoris) > 1 ? 's' : '' ?></p>
      </div>
      <a href="index.php#produits" class="btn-outline">← Continuer mes achats</a>
    </div>

    <div class="fade-up">
      <div class="section-title">
        <div>
          <div class="section-title__eyebrow">Sauvegardés</div>
          <h2 class="section-title__h">Mes produits coup de cœur</h2>
        </div>
      </div>

      <?php if (empty($produits_favoris)): ?>
        <div class="fav-empty">
          <div class="fav-empty__icon">🤍</div>
          <div class="fav-empty__title">Aucun favori pour l'instant</div>
          <p>Cliquez sur le ♡ sur les produits pour les retrouver ici.</p>
          <a href="index.php#produits" class="btn-primary" style="margin-top:1.5rem;display:inline-flex;">Découvrir nos produits</a>
        </div>
      <?php else: ?>
        <div class="fav-grid">
          <?php foreach ($produits_favoris as $p): ?>
            <div class="fav-card" id="fav-card-<?= $p->id_produit ?>">
              <div class="fav-card__img-wrap">
                <?php if (!empty($p->url_image)): ?>
                  <img src="<?= htmlspecialchars($p->url_image) ?>" alt="<?= htmlspecialchars($p->nom) ?>" class="fav-card__img">
                <?php else: ?>
                  <div class="fav-card__img-placeholder">🌿</div>
                <?php endif; ?>
              </div>
              <div class="fav-card__body">
                <div class="fav-card__cat">Épice · WakAroma</div>
                <div class="fav-card__name"><?= htmlspecialchars($p->nom) ?></div>
                <div class="fav-card__price"><?= number_format($p->prix, 2, ',', ' ') ?> €</div>
              </div>
              <div class="fav-card__actions">
                <button class="fav-card__btn-cart" onclick="ajouterAuPanier(<?= $p->id_produit ?>, this)">🛒 Ajouter au panier</button>
                <button class="fav-card__btn-remove" onclick="retirerFavori(<?= $p->id_produit ?>)" title="Retirer des favoris">🗑️</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<div class="toast-fav" id="toastFav"></div>

<script>
function showToast(msg, ok=true) {
    const el = document.getElementById('toastFav');
    el.textContent = msg;
    el.style.background = ok ? 'var(--color-green)' : '#c0392b';
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 3000);
}

async function retirerFavori(idProduit) {
    const card = document.getElementById('fav-card-' + idProduit);
    try {
        const res  = await fetch('favoris.php', { method:'POST', body: new URLSearchParams({ action:'toggle', id_produit: idProduit }) });
        const json = await res.json();
        if (json.success) {
            card.style.cssText += 'opacity:0;transform:scale(0.92);transition:all 0.25s ease;';
            setTimeout(() => { card.remove(); showToast('Retiré des favoris'); }, 250);
        }
    } catch(e) { showToast('Erreur', false); }
}

async function ajouterAuPanier(idProduit, btn) {
    const orig = btn.innerHTML;
    btn.innerHTML = '⏳ Ajout…'; btn.disabled = true;
    try {
        const res  = await fetch('panier.php', { method:'POST', body: new URLSearchParams({ action:'ajouter', id_produit: idProduit }) });
        const json = await res.json();
        if (json.success) {
            btn.innerHTML = '✓ Ajouté !';
            showToast('🛒 Ajouté au panier !');
            setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 1800);
        } else {
            showToast(json.message || 'Erreur', false);
            btn.innerHTML = orig; btn.disabled = false;
        }
    } catch(e) { showToast('Erreur', false); btn.innerHTML = orig; btn.disabled = false; }
}
</script>

<?php require_once 'footer.php'; ?>