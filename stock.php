<?php
session_start();

// ==========================================
// PROTECTION ADMIN — redirection si non connecté
// ==========================================
if (!isset($_SESSION['admin_auth'])) {
    header("Location: admin_login.php");
    exit();
}
$admin = $_SESSION['admin_auth'];

// ==========================================
// CONFIGURATION BASE DE DONNÉES
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wakaroma');
define('SEUIL_ALERTE_DEFAULT', 10);

// ==========================================
// CONNEXION PDO
// ==========================================
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("ALTER TABLE produits ADD COLUMN IF NOT EXISTS seuil_alerte INT NOT NULL DEFAULT " . SEUIL_ALERTE_DEFAULT);
            // Table ingrédients internes
            $pdo->exec("CREATE TABLE IF NOT EXISTS ingredients_internes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(150) NOT NULL,
                quantite DECIMAL(10,2) NOT NULL DEFAULT 0,
                unite VARCHAR(30) DEFAULT 'g',
                prix_achat DECIMAL(10,2) NOT NULL DEFAULT 0,
                seuil_alerte INT NOT NULL DEFAULT 10,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion impossible : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ==========================================
// TRAITEMENT DES REQUÊTES AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $pdo = getDB();
    $action = $_POST['action'];

    switch ($action) {

        // ---- STOCKS PRODUITS ----
        case 'get_stocks':
            $stmt = $pdo->query("
                SELECT p.id_produit AS id, p.nom, c.nom AS categorie, p.stock,
                    COALESCE(p.seuil_alerte, 10) AS seuil_alerte,
                    COALESCE((SELECT car.valeur FROM caracteristiques car WHERE car.id_produit = p.id_produit AND car.nom = 'Poids' LIMIT 1), 'g') AS unite,
                    p.prix
                FROM produits p
                INNER JOIN categories c ON c.id_categorie = p.id_categorie
                ORDER BY c.nom, p.nom
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'update_stock':
            $id = (int)$_POST['id']; $stock = (int)$_POST['stock'];
            $pdo->prepare("UPDATE produits SET stock = ? WHERE id_produit = ?")->execute([$stock, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_seuil':
            $id = (int)$_POST['id']; $seuil = (int)$_POST['seuil'];
            $pdo->prepare("UPDATE produits SET seuil_alerte = ? WHERE id_produit = ?")->execute([$seuil, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_seuil_global':
            $seuil = (int)$_POST['seuil'];
            $pdo->prepare("UPDATE produits SET seuil_alerte = ?")->execute([$seuil]);
            echo json_encode(['success' => true, 'message' => 'Seuil global mis à jour']);
            break;

        case 'add_produit':
            $nom = trim($_POST['nom']); $categorie = trim($_POST['categorie']);
            $stock = (int)$_POST['stock']; $seuil = (int)$_POST['seuil'];
            $unite = trim($_POST['unite']) ?: 'g'; $prix = (float)$_POST['prix'];
            if (empty($nom)) { echo json_encode(['success' => false, 'error' => 'Le nom est obligatoire']); break; }
            $stmtCat = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom = ?");
            $stmtCat->execute([$categorie]);
            $cat = $stmtCat->fetch(PDO::FETCH_OBJ);
            if ($cat) { $id_categorie = $cat->id_categorie; }
            else {
                $pdo->prepare("INSERT INTO categories (nom) VALUES (?)")->execute([$categorie]);
                $id_categorie = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("INSERT INTO produits (id_categorie, nom, stock, seuil_alerte, prix) VALUES (?, ?, ?, ?, ?)")->execute([$id_categorie, $nom, $stock, $seuil, $prix]);
            $newId = (int)$pdo->lastInsertId();
            if (!empty($unite)) {
                $pdo->prepare("INSERT INTO caracteristiques (id_produit, nom, valeur) VALUES (?, 'Poids', ?)")->execute([$newId, $unite]);
            }
            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'delete_produit':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM produits WHERE id_produit = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT nom FROM categories ORDER BY nom");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
            break;

        // ---- INGRÉDIENTS INTERNES ----
        case 'get_ingredients':
            $stmt = $pdo->query("SELECT * FROM ingredients_internes ORDER BY nom");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'add_ingredient':
            $nom       = trim($_POST['nom']);
            $quantite  = (float)$_POST['quantite'];
            $unite     = trim($_POST['unite']) ?: 'g';
            $prix      = (float)$_POST['prix_achat'];
            $seuil     = (int)$_POST['seuil'];
            if (empty($nom)) { echo json_encode(['success' => false, 'error' => 'Le nom est obligatoire']); break; }
            $pdo->prepare("INSERT INTO ingredients_internes (nom, quantite, unite, prix_achat, seuil_alerte) VALUES (?, ?, ?, ?, ?)")->execute([$nom, $quantite, $unite, $prix, $seuil]);
            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;

        case 'update_ingredient':
            $id       = (int)$_POST['id'];
            $quantite = (float)$_POST['quantite'];
            $prix     = (float)$_POST['prix_achat'];
            $seuil    = (int)$_POST['seuil'];
            $pdo->prepare("UPDATE ingredients_internes SET quantite = ?, prix_achat = ?, seuil_alerte = ? WHERE id = ?")->execute([$quantite, $prix, $seuil, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_ingredient':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM ingredients_internes WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ---- UTILISATEURS ----
        case 'get_users':
            $stmt = $pdo->query("SELECT id_user AS id, nom, prenom, email, numero, created_at FROM users ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'delete_user':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM users WHERE id_user = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administration — WakAroma</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg:         #0f0d0a;
    --surface:    #1a1611;
    --surface2:   #231f18;
    --border:     #332d22;
    --border2:    #4a4030;
    --gold:       #c9963b;
    --gold-light: #e8b860;
    --gold-dim:   #7a5a22;
    --cream:      #f5edd8;
    --cream-dim:  #a89878;
    --red:        #c0392b;
    --red-bg:     #2d0f0a;
    --red-border: #8b2a1f;
    --green:      #27ae60;
    --green-bg:   #0a1f12;
    --blue:       #3a7bd5;
    --blue-bg:    #0a1525;
    --blue-border:#1e4080;
    --text:       #e8dcc8;
    --text-dim:   #8a7a62;
    --radius:     12px;
    --shadow:     0 8px 32px rgba(0,0,0,.5);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    background-image:
        radial-gradient(ellipse 60% 40% at 20% 10%, rgba(201,150,59,.07) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 80% 90%, rgba(201,150,59,.05) 0%, transparent 60%);
}

/* HEADER */
.header {
    background: linear-gradient(135deg, var(--surface) 0%, #1f1a12 100%);
    border-bottom: 1px solid var(--border);
    padding: 20px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 24px rgba(0,0,0,.4);
}
.header-brand { display: flex; align-items: center; gap: 14px; }
.header-icon { width: 52px; height: 52px; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.header-icon img { width: 100%; height: 100%; object-fit: contain; }
.header-title { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--cream); font-weight: 700; }
.header-sub { font-size: .8rem; color: var(--text-dim); margin-top: 2px; }
.header-stats { display: flex; gap: 20px; }
.stat-badge { text-align: center; padding: 8px 18px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; }
.stat-badge .num { font-size: 1.4rem; font-weight: 700; color: var(--gold); display: block; }
.stat-badge .label { font-size: .7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: .06em; }
.stat-badge.alert-badge .num { color: var(--red); }
.btn-logout {
    padding: 9px 18px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-dim);
    font-family: 'DM Sans', sans-serif;
    font-size: .85rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all .2s;
    white-space: nowrap;
}
.btn-logout:hover { border-color: var(--red-border); color: #e74c3c; background: var(--red-bg); }

/* NAV TABS */
.nav-tabs {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    display: flex;
    gap: 4px;
}
.nav-tab {
    padding: 14px 24px;
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    font-weight: 500;
    color: var(--text-dim);
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: -1px;
}
.nav-tab:hover { color: var(--cream); }
.nav-tab.active { color: var(--gold); border-bottom-color: var(--gold); }
.nav-tab .tab-badge {
    background: var(--red-bg);
    border: 1px solid var(--red-border);
    color: #e74c3c;
    font-size: .68rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
}

/* PAGES */
.page { display: none; }
.page.active { display: block; }

/* TOOLBAR */
.toolbar {
    padding: 20px 32px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
}
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-wrap input { width: 100%; padding: 10px 14px 10px 40px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; transition: border-color .2s; }
.search-wrap input:focus { border-color: var(--gold); }
.search-wrap .ico { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 16px; }
select.filter-select { padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; cursor: pointer; transition: border-color .2s; }
select.filter-select:focus { border-color: var(--gold); }
.btn { padding: 10px 20px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: .9rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; }
.btn-primary { background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%); color: #1a1200; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(201,150,59,.4); }
.btn-danger { background: var(--red-bg); color: #e74c3c; border: 1px solid var(--red-border); }
.btn-danger:hover { background: #3d1208; }
.btn-ghost { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
.btn-ghost:hover { border-color: var(--gold); color: var(--gold); }
.seuil-global { display: flex; align-items: center; gap: 10px; padding: 8px 14px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; }
.seuil-global label { font-size: .82rem; color: var(--text-dim); white-space: nowrap; }
.seuil-global input { width: 64px; padding: 6px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--gold); font-weight: 600; font-size: .9rem; text-align: center; outline: none; }
.seuil-global input:focus { border-color: var(--gold); }

/* TABLE */
.main { padding: 28px 32px; }
.table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: var(--surface2); border-bottom: 2px solid var(--gold-dim); }
thead th { padding: 14px 18px; text-align: left; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--gold); white-space: nowrap; }
tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(201,150,59,.04); }
tbody tr.row-alert { background: rgba(192,57,43,.06); border-left: 3px solid var(--red); }
tbody tr.row-alert:hover { background: rgba(192,57,43,.1); }
td { padding: 14px 18px; font-size: .9rem; vertical-align: middle; }
.td-nom { font-weight: 500; color: var(--cream); }
.td-cat { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .75rem; background: var(--surface2); border: 1px solid var(--border2); color: var(--cream-dim); }
.stock-cell { display: flex; align-items: center; gap: 10px; }
.stock-input { width: 80px; padding: 7px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: .9rem; text-align: center; outline: none; transition: border-color .2s; }
.stock-input:focus { border-color: var(--gold); }
.row-alert .stock-input { border-color: var(--red-border); color: #e74c3c; }
.save-btn { padding: 6px 12px; background: var(--green-bg); border: 1px solid #1e6b40; border-radius: 6px; color: var(--green); font-size: .8rem; cursor: pointer; transition: all .15s; white-space: nowrap; }
.save-btn:hover { background: #0d3520; }
.seuil-input { width: 70px; padding: 7px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--gold-dim); font-size: .9rem; text-align: center; outline: none; transition: border-color .2s; }
.seuil-input:focus { border-color: var(--gold); color: var(--gold); }
.alert-icon { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background: var(--red-bg); border: 1px solid var(--red-border); border-radius: 20px; color: #e74c3c; font-size: .78rem; font-weight: 600; animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .65; } }
.ok-icon { color: var(--green); font-size: 1.1rem; }
.action-btn { padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; background: transparent; color: var(--text-dim); cursor: pointer; transition: all .15s; font-size: .85rem; }
.action-btn:hover { border-color: var(--red-border); color: #e74c3c; background: var(--red-bg); }
.stock-bar-wrap { display: flex; align-items: center; gap: 10px; }
.stock-bar { flex: 1; height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
.stock-bar-fill { height: 100%; border-radius: 3px; transition: width .4s ease; }
.stock-bar-fill.ok { background: var(--green); }
.stock-bar-fill.warn { background: var(--gold); }
.stock-bar-fill.danger { background: var(--red); }

/* USERS TABLE */
.user-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--gold-dim), var(--gold));
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .85rem; color: #1a1200;
    flex-shrink: 0;
}
.user-name-cell { display: flex; align-items: center; gap: 12px; }
.user-email { color: var(--text-dim); font-size: .85rem; }
.user-tel { font-family: monospace; color: var(--cream-dim); font-size: .88rem; }
.user-date { font-size: .78rem; color: var(--text-dim); }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); backdrop-filter: blur(4px); z-index: 200; display: none; align-items: center; justify-content: center; }
.modal-overlay.open { display: flex; }
.modal { background: var(--surface); border: 1px solid var(--border2); border-radius: 16px; padding: 32px; width: 100%; max-width: 480px; box-shadow: 0 24px 80px rgba(0,0,0,.6); animation: modalIn .25s ease; }
@keyframes modalIn { from { opacity: 0; transform: translateY(20px) scale(.97); } to { opacity: 1; transform: none; } }
.modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.modal-title { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--cream); }
.modal-close { width: 32px; height: 32px; background: var(--surface2); border: 1px solid var(--border); border-radius: 6px; color: var(--text-dim); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.modal-close:hover { color: var(--cream); background: var(--border); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: .78rem; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: var(--text-dim); }
.form-group input, .form-group select { padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; transition: border-color .2s; }
.form-group input:focus, .form-group select:focus { border-color: var(--gold); }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }

/* TOAST */
.toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 300; display: flex; flex-direction: column; gap: 10px; }
.toast { padding: 12px 20px; border-radius: 10px; font-size: .88rem; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.4); animation: toastIn .3s ease; }
@keyframes toastIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: none; } }
.toast.success { background: var(--green-bg); border: 1px solid #1e6b40; color: #4ecb78; }
.toast.error { background: var(--red-bg); border: 1px solid var(--red-border); color: #e74c3c; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-dim); }
.empty-state .icon { font-size: 3rem; margin-bottom: 12px; }
.empty-state p { font-size: .95rem; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .header { padding: 14px 16px; flex-wrap: wrap; gap: 12px; }
    .header-stats { gap: 10px; }
    .toolbar { padding: 14px 16px; }
    .main { padding: 16px; }
    .nav-tabs { padding: 0 16px; }
    .nav-tab { padding: 12px 16px; font-size: .82rem; }
    thead th:nth-child(4), td:nth-child(4) { display: none; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-brand">
        <div class="header-icon"><img src="logo/logo.jpeg" alt="Logo WakAroma"></div>
        <div>
            <div class="header-title">Administration WakAroma</div>
            <div class="header-sub">Connecté : <?= htmlspecialchars($admin['nom']) ?></div>
        </div>
    </div>
    <div class="header-stats">
        <div class="stat-badge">
            <span class="num" id="stat-total">—</span>
            <span class="label">Produits</span>
        </div>
        <div class="stat-badge">
            <span class="num" id="stat-ok">—</span>
            <span class="label">En stock</span>
        </div>
        <div class="stat-badge alert-badge">
            <span class="num" id="stat-alert">—</span>
            <span class="label">⚠ Alertes</span>
        </div>
        <a href="admin_logout.php" class="btn-logout" onclick="return confirm('Se déconnecter ?')">
            🚪 Déconnexion
        </a>
    </div>
</header>

<!-- NAVIGATION PAR ONGLETS -->
<nav class="nav-tabs">
    <button class="nav-tab active" onclick="switchPage('stocks', this)" id="tab-stocks">
        📦 Stocks produits
    </button>
    <button class="nav-tab" onclick="switchPage('ingredients', this)" id="tab-ingredients">
        🌿 Ingrédients internes
        <span class="tab-badge" id="badge-ingr" style="display:none">!</span>
    </button>
    <button class="nav-tab" onclick="switchPage('users', this)" id="tab-users">
        👥 Utilisateurs
    </button>
</nav>

<!-- ===================== PAGE STOCKS ===================== -->
<div class="page active" id="page-stocks">
    <div class="toolbar">
        <div class="search-wrap">
            <span class="ico">🔍</span>
            <input type="text" id="search" placeholder="Rechercher un produit…" oninput="filterTable()">
        </div>
        <select class="filter-select" id="filter-cat" onchange="filterTable()">
            <option value="">Toutes les catégories</option>
        </select>
        <select class="filter-select" id="filter-status" onchange="filterTable()">
            <option value="">Tous les statuts</option>
            <option value="alert">⚠ En alerte</option>
            <option value="ok">✓ En stock</option>
        </select>
        <div class="seuil-global">
            <label>⚙ Seuil alerte global :</label>
            <input type="number" id="seuil-global-input" value="<?= SEUIL_ALERTE_DEFAULT ?>" min="0">
            <button class="btn btn-ghost" style="padding:6px 12px;font-size:.82rem;" onclick="applySeuilGlobal()">Appliquer</button>
        </div>
        <button class="btn btn-primary" onclick="openModal('produit')">
            <span>＋</span> Ajouter un produit
        </button>
    </div>
    <main class="main">
        <div class="table-card">
            <div class="table-wrap">
                <table id="stockTable">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Stock actuel</th>
                            <th>Niveau</th>
                            <th>Seuil alerte</th>
                            <th>Unité</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <tr><td colspan="9" class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ===================== PAGE INGRÉDIENTS ===================== -->
<div class="page" id="page-ingredients">
    <div class="toolbar">
        <div class="search-wrap">
            <span class="ico">🔍</span>
            <input type="text" id="search-ingr" placeholder="Rechercher un ingrédient…" oninput="filterIngredients()">
        </div>
        <select class="filter-select" id="filter-ingr-status" onchange="filterIngredients()">
            <option value="">Tous les statuts</option>
            <option value="alert">⚠ Stock faible</option>
            <option value="ok">✓ OK</option>
        </select>
        <button class="btn btn-primary" onclick="openModal('ingredient')">
            <span>＋</span> Ajouter un ingrédient
        </button>
    </div>
    <main class="main">
        <div class="table-card">
            <div class="table-wrap">
                <table id="ingrTable">
                    <thead>
                        <tr>
                            <th>Ingrédient</th>
                            <th>Quantité en stock</th>
                            <th>Unité</th>
                            <th>Prix d'achat</th>
                            <th>Seuil alerte</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-ingr">
                        <tr><td colspan="7" class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ===================== PAGE UTILISATEURS ===================== -->
<div class="page" id="page-users">
    <div class="toolbar">
        <div class="search-wrap">
            <span class="ico">🔍</span>
            <input type="text" id="search-users" placeholder="Rechercher un utilisateur…" oninput="filterUsers()">
        </div>
        <span style="color:var(--text-dim);font-size:.85rem;margin-left:auto;" id="user-count-label"></span>
    </div>
    <main class="main">
        <div class="table-card">
            <div class="table-wrap">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Inscrit le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-users">
                        <tr><td colspan="5" class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- MODAL AJOUT PRODUIT -->
<div class="modal-overlay" id="modal-produit">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">✦ Nouveau produit</div>
            <button class="modal-close" onclick="closeModal('produit')">✕</button>
        </div>
        <div class="form-grid">
            <div class="form-group full">
                <label>Nom du produit *</label>
                <input type="text" id="f-nom" placeholder="Ex: Curcuma bio">
            </div>
            <div class="form-group">
                <label>Catégorie</label>
                <input type="text" id="f-cat" list="cat-list" placeholder="Ex: Épices douces">
                <datalist id="cat-list"></datalist>
            </div>
            <div class="form-group">
                <label>Unité</label>
                <select id="f-unite">
                    <option value="g">Grammes (g)</option>
                    <option value="kg">Kilogrammes (kg)</option>
                    <option value="pièce">Pièce</option>
                    <option value="sachet">Sachet</option>
                    <option value="boîte">Boîte</option>
                </select>
            </div>
            <div class="form-group">
                <label>Stock initial</label>
                <input type="number" id="f-stock" placeholder="100" min="0" value="0">
            </div>
            <div class="form-group">
                <label>Seuil d'alerte</label>
                <input type="number" id="f-seuil" placeholder="10" min="0" value="10">
            </div>
            <div class="form-group">
                <label>Prix (€)</label>
                <input type="number" id="f-prix" placeholder="0.00" step="0.01" min="0" value="0">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('produit')">Annuler</button>
            <button class="btn btn-primary" onclick="addProduit()">✦ Ajouter le produit</button>
        </div>
    </div>
</div>

<!-- MODAL AJOUT INGRÉDIENT -->
<div class="modal-overlay" id="modal-ingredient">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-ingr-title">🌿 Nouvel ingrédient</div>
            <button class="modal-close" onclick="closeModal('ingredient')">✕</button>
        </div>
        <div class="form-grid">
            <div class="form-group full">
                <label>Nom de l'ingrédient *</label>
                <input type="text" id="fi-nom" placeholder="Ex: Ail en poudre">
            </div>
            <div class="form-group">
                <label>Quantité en stock</label>
                <input type="number" id="fi-quantite" placeholder="0" min="0" step="0.1" value="0">
            </div>
            <div class="form-group">
                <label>Unité</label>
                <select id="fi-unite">
                    <option value="g">Grammes (g)</option>
                    <option value="kg">Kilogrammes (kg)</option>
                    <option value="ml">Millilitres (ml)</option>
                    <option value="L">Litres (L)</option>
                    <option value="pièce">Pièce</option>
                    <option value="sachet">Sachet</option>
                </select>
            </div>
            <div class="form-group">
                <label>Prix d'achat (€)</label>
                <input type="number" id="fi-prix" placeholder="0.00" step="0.01" min="0" value="0">
            </div>
            <div class="form-group">
                <label>Seuil d'alerte</label>
                <input type="number" id="fi-seuil" placeholder="10" min="0" value="10">
            </div>
        </div>
        <input type="hidden" id="fi-edit-id" value="">
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('ingredient')">Annuler</button>
            <button class="btn btn-primary" id="fi-submit-btn" onclick="submitIngredient()">🌿 Ajouter</button>
        </div>
    </div>
</div>

<!-- TOASTS -->
<div class="toast-container" id="toasts"></div>

<script>
const SCRIPT_URL = window.location.href.split('?')[0];
let allProducts    = [];
let allIngredients = [];
let allUsers       = [];

// ==========================================
// NAVIGATION
// ==========================================
function switchPage(page, btn) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    btn.classList.add('active');

    if (page === 'ingredients' && allIngredients.length === 0) loadIngredients();
    if (page === 'users'       && allUsers.length === 0)       loadUsers();
}

// ==========================================
// INIT
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    loadStocks();
    loadCategories();
});

// ==========================================
// STOCKS
// ==========================================
async function loadStocks() {
    try {
        const res = await post({ action: 'get_stocks' });
        allProducts = res.data || [];
        renderTable(allProducts);
        updateStats(allProducts);
    } catch (e) { showToast('Erreur de chargement : ' + e.message, 'error'); }
}

async function loadCategories() {
    try {
        const res = await post({ action: 'get_categories' });
        const sel = document.getElementById('filter-cat');
        const dl  = document.getElementById('cat-list');
        (res.data || []).forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat; opt.textContent = cat;
            sel.appendChild(opt.cloneNode(true));
            dl.appendChild(opt);
        });
    } catch(e) {}
}

function renderTable(products) {
    const tbody = document.getElementById('tbody');
    if (!products.length) {
        tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><div class="icon">📦</div><p>Aucun produit trouvé.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = products.map(p => {
        const seuil = parseInt(p.seuil_alerte) || 0;
        const stock = parseInt(p.stock) || 0;
        const isAlert = stock <= seuil;
        const max   = Math.max(stock * 1.5, seuil * 2, 50);
        const pct   = Math.min(100, Math.round((stock / max) * 100));
        const barClass = stock === 0 ? 'danger' : isAlert ? 'warn' : 'ok';
        return `<tr class="${isAlert ? 'row-alert' : ''}" data-id="${p.id}" data-nom="${p.nom.toLowerCase()}" data-cat="${(p.categorie||'').toLowerCase()}" data-alert="${isAlert ? 'alert' : 'ok'}">
            <td class="td-nom">🌿 ${escHtml(p.nom)}</td>
            <td><span class="td-cat">${escHtml(p.categorie || 'Sans catégorie')}</span></td>
            <td>
                <div class="stock-cell">
                    <input class="stock-input" type="number" value="${stock}" min="0" onchange="markDirty(this)" data-original="${stock}" id="stock-${p.id}">
                    <button class="save-btn" onclick="saveStock(${p.id})">💾 Sauver</button>
                </div>
            </td>
            <td>
                <div class="stock-bar-wrap">
                    <div class="stock-bar"><div class="stock-bar-fill ${barClass}" style="width:${pct}%"></div></div>
                    <span style="font-size:.8rem;color:var(--text-dim);min-width:30px;">${pct}%</span>
                </div>
            </td>
            <td><input class="seuil-input" type="number" value="${seuil}" min="0" onchange="saveSeuil(${p.id}, this.value)" id="seuil-${p.id}"></td>
            <td style="color:var(--text-dim)">${escHtml(p.unite || 'g')}</td>
            <td style="color:var(--gold)">${parseFloat(p.prix || 0).toFixed(2)} €</td>
            <td>${isAlert ? `<span class="alert-icon">⚠ Stock faible</span>` : `<span class="ok-icon">✓</span>`}</td>
            <td><button class="action-btn" onclick="deleteProduit(${p.id}, '${escHtml(p.nom)}')" title="Supprimer">🗑</button></td>
        </tr>`;
    }).join('');
}

function markDirty(input) { input.style.borderColor = 'var(--gold)'; }

function updateStats(products) {
    const alertCount = products.filter(p => parseInt(p.stock) <= parseInt(p.seuil_alerte)).length;
    document.getElementById('stat-total').textContent = products.length;
    document.getElementById('stat-ok').textContent    = products.length - alertCount;
    document.getElementById('stat-alert').textContent = alertCount;
}

function filterTable() {
    const search = document.getElementById('search').value.toLowerCase();
    const cat    = document.getElementById('filter-cat').value.toLowerCase();
    const status = document.getElementById('filter-status').value;
    document.querySelectorAll('#tbody tr[data-id]').forEach(row => {
        const nomMatch    = row.dataset.nom.includes(search);
        const catMatch    = !cat || row.dataset.cat === cat;
        const statusMatch = !status || row.dataset.alert === status;
        row.style.display = (nomMatch && catMatch && statusMatch) ? '' : 'none';
    });
}

async function saveStock(id) {
    const input = document.getElementById('stock-' + id);
    const stock = parseInt(input.value);
    if (isNaN(stock) || stock < 0) { showToast('Valeur invalide', 'error'); return; }
    try {
        await post({ action: 'update_stock', id, stock });
        showToast('Stock mis à jour ✓', 'success');
        input.style.borderColor = '';
        loadStocks();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function saveSeuil(id, seuil) {
    try {
        await post({ action: 'update_seuil', id, seuil: parseInt(seuil) });
        showToast('Seuil mis à jour ✓', 'success');
        loadStocks();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function applySeuilGlobal() {
    const seuil = parseInt(document.getElementById('seuil-global-input').value);
    if (isNaN(seuil) || seuil < 0) { showToast('Valeur invalide', 'error'); return; }
    if (!confirm(`Appliquer le seuil de ${seuil} à TOUS les produits ?`)) return;
    try {
        await post({ action: 'update_seuil_global', seuil });
        showToast(`Seuil global ${seuil} appliqué ✓`, 'success');
        loadStocks();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function addProduit() {
    const nom   = document.getElementById('f-nom').value.trim();
    const cat   = document.getElementById('f-cat').value.trim();
    const stock = document.getElementById('f-stock').value;
    const seuil = document.getElementById('f-seuil').value;
    const unite = document.getElementById('f-unite').value;
    const prix  = document.getElementById('f-prix').value;
    if (!nom) { showToast('Le nom est obligatoire', 'error'); return; }
    try {
        await post({ action: 'add_produit', nom, categorie: cat, stock, seuil, unite, prix });
        showToast(`"${nom}" ajouté ✓`, 'success');
        closeModal('produit');
        ['f-nom','f-cat','f-stock','f-seuil','f-prix'].forEach(id => document.getElementById(id).value = id === 'f-seuil' ? '10' : id === 'f-stock' || id === 'f-prix' ? '0' : '');
        loadStocks();
        loadCategoriesRefresh();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function loadCategoriesRefresh() {
    try {
        const res = await post({ action: 'get_categories' });
        const dl = document.getElementById('cat-list');
        dl.innerHTML = '';
        (res.data || []).forEach(cat => {
            const opt = document.createElement('option'); opt.value = cat; dl.appendChild(opt);
        });
    } catch(e) {}
}

async function deleteProduit(id, nom) {
    if (!confirm(`Supprimer "${nom}" définitivement ?`)) return;
    try {
        await post({ action: 'delete_produit', id });
        showToast(`"${nom}" supprimé`, 'success');
        loadStocks();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

// ==========================================
// INGRÉDIENTS INTERNES
// ==========================================
async function loadIngredients() {
    try {
        const res = await post({ action: 'get_ingredients' });
        allIngredients = res.data || [];
        renderIngredients(allIngredients);
    } catch (e) { showToast('Erreur de chargement ingrédients : ' + e.message, 'error'); }
}

function renderIngredients(list) {
    const tbody = document.getElementById('tbody-ingr');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="icon">🌿</div><p>Aucun ingrédient. Cliquez sur "Ajouter" pour commencer.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = list.map(i => {
        const qty     = parseFloat(i.quantite) || 0;
        const seuil   = parseInt(i.seuil_alerte) || 0;
        const isAlert = qty <= seuil;
        return `<tr class="${isAlert ? 'row-alert' : ''}" data-id="${i.id}" data-nom="${i.nom.toLowerCase()}" data-alert="${isAlert ? 'alert' : 'ok'}">
            <td class="td-nom">🌶 ${escHtml(i.nom)}</td>
            <td>
                <div class="stock-cell">
                    <input class="stock-input" type="number" value="${qty}" min="0" step="0.1"
                        onchange="markDirty(this)" id="ingr-qty-${i.id}">
                    <button class="save-btn" onclick="saveIngredient(${i.id})">💾 Sauver</button>
                </div>
            </td>
            <td style="color:var(--text-dim)">${escHtml(i.unite || 'g')}</td>
            <td style="color:var(--gold)">${parseFloat(i.prix_achat || 0).toFixed(2)} €</td>
            <td><input class="seuil-input" type="number" value="${seuil}" min="0"
                onchange="saveIngrSeuil(${i.id}, this.value)" id="ingr-seuil-${i.id}"></td>
            <td>${isAlert ? `<span class="alert-icon">⚠ Stock faible</span>` : `<span class="ok-icon">✓</span>`}</td>
            <td style="display:flex;gap:6px;">
                <button class="save-btn" style="background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue)" onclick="editIngredient(${i.id})">✏ Modifier</button>
                <button class="action-btn" onclick="deleteIngredient(${i.id}, '${escHtml(i.nom)}')" title="Supprimer">🗑</button>
            </td>
        </tr>`;
    }).join('');

    // Badge alerte sur onglet
    const alertCount = list.filter(i => parseFloat(i.quantite) <= parseInt(i.seuil_alerte)).length;
    const badge = document.getElementById('badge-ingr');
    badge.style.display = alertCount > 0 ? '' : 'none';
    badge.textContent   = alertCount;
}

function filterIngredients() {
    const search = document.getElementById('search-ingr').value.toLowerCase();
    const status = document.getElementById('filter-ingr-status').value;
    document.querySelectorAll('#tbody-ingr tr[data-id]').forEach(row => {
        const nomMatch    = row.dataset.nom.includes(search);
        const statusMatch = !status || row.dataset.alert === status;
        row.style.display = (nomMatch && statusMatch) ? '' : 'none';
    });
}

async function saveIngredient(id) {
    const qtyInput   = document.getElementById('ingr-qty-' + id);
    const seuilInput = document.getElementById('ingr-seuil-' + id);
    const quantite   = parseFloat(qtyInput.value);
    const seuil      = parseInt(seuilInput.value);
    const ingr       = allIngredients.find(i => i.id == id);
    const prix_achat = ingr ? ingr.prix_achat : 0;
    if (isNaN(quantite)) { showToast('Valeur invalide', 'error'); return; }
    try {
        await post({ action: 'update_ingredient', id, quantite, prix_achat, seuil });
        showToast('Ingrédient mis à jour ✓', 'success');
        qtyInput.style.borderColor = '';
        loadIngredients();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function saveIngrSeuil(id, seuil) {
    const ingr = allIngredients.find(i => i.id == id);
    if (!ingr) return;
    try {
        await post({ action: 'update_ingredient', id, quantite: ingr.quantite, prix_achat: ingr.prix_achat, seuil });
        showToast('Seuil mis à jour ✓', 'success');
        loadIngredients();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

function editIngredient(id) {
    const ingr = allIngredients.find(i => i.id == id);
    if (!ingr) return;
    document.getElementById('fi-nom').value      = ingr.nom;
    document.getElementById('fi-quantite').value = ingr.quantite;
    document.getElementById('fi-unite').value    = ingr.unite;
    document.getElementById('fi-prix').value     = ingr.prix_achat;
    document.getElementById('fi-seuil').value    = ingr.seuil_alerte;
    document.getElementById('fi-edit-id').value  = ingr.id;
    document.getElementById('modal-ingr-title').textContent = '✏ Modifier l\'ingrédient';
    document.getElementById('fi-submit-btn').textContent    = '💾 Enregistrer';
    document.getElementById('fi-nom').readOnly = true;
    openModal('ingredient');
}

async function submitIngredient() {
    const editId   = document.getElementById('fi-edit-id').value;
    const nom      = document.getElementById('fi-nom').value.trim();
    const quantite = document.getElementById('fi-quantite').value;
    const unite    = document.getElementById('fi-unite').value;
    const prix     = document.getElementById('fi-prix').value;
    const seuil    = document.getElementById('fi-seuil').value;
    if (!nom) { showToast('Le nom est obligatoire', 'error'); return; }
    try {
        if (editId) {
            await post({ action: 'update_ingredient', id: editId, quantite, prix_achat: prix, seuil });
            showToast('Ingrédient modifié ✓', 'success');
        } else {
            await post({ action: 'add_ingredient', nom, quantite, unite, prix_achat: prix, seuil });
            showToast(`"${nom}" ajouté ✓`, 'success');
        }
        closeModal('ingredient');
        loadIngredients();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function deleteIngredient(id, nom) {
    if (!confirm(`Supprimer "${nom}" définitivement ?`)) return;
    try {
        await post({ action: 'delete_ingredient', id });
        showToast(`"${nom}" supprimé`, 'success');
        loadIngredients();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

// ==========================================
// UTILISATEURS
// ==========================================
async function loadUsers() {
    try {
        const res = await post({ action: 'get_users' });
        allUsers = res.data || [];
        renderUsers(allUsers);
    } catch (e) { showToast('Erreur de chargement utilisateurs : ' + e.message, 'error'); }
}

function renderUsers(list) {
    const tbody = document.getElementById('tbody-users');
    document.getElementById('user-count-label').textContent = list.length + ' utilisateur' + (list.length > 1 ? 's' : '');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state"><div class="icon">👥</div><p>Aucun utilisateur enregistré.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = list.map(u => {
        const initials = ((u.prenom||'?')[0] + (u.nom||'?')[0]).toUpperCase();
        const dateStr  = u.created_at ? new Date(u.created_at).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' }) : '—';
        return `<tr data-id="${u.id}" data-search="${(u.nom+' '+u.prenom+' '+u.email).toLowerCase()}">
            <td>
                <div class="user-name-cell">
                    <div class="user-avatar">${escHtml(initials)}</div>
                    <div>
                        <div style="font-weight:500;color:var(--cream)">${escHtml(u.prenom || '')} ${escHtml(u.nom || '')}</div>
                    </div>
                </div>
            </td>
            <td class="user-email">${escHtml(u.email || '—')}</td>
            <td class="user-tel">${escHtml(u.numero || '—')}</td>
            <td class="user-date">${dateStr}</td>
            <td><button class="action-btn" onclick="deleteUser(${u.id}, '${escHtml((u.prenom||'')+' '+(u.nom||''))}')" title="Supprimer">🗑</button></td>
        </tr>`;
    }).join('');
}

function filterUsers() {
    const search = document.getElementById('search-users').value.toLowerCase();
    document.querySelectorAll('#tbody-users tr[data-id]').forEach(row => {
        row.style.display = row.dataset.search.includes(search) ? '' : 'none';
    });
}

async function deleteUser(id, nom) {
    if (!confirm(`Supprimer l'utilisateur "${nom}" définitivement ?\nCela supprimera aussi ses commandes et son panier.`)) return;
    try {
        await post({ action: 'delete_user', id });
        showToast(`Utilisateur "${nom}" supprimé`, 'success');
        loadUsers();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

// ==========================================
// MODALS
// ==========================================
function openModal(type) {
    if (type === 'ingredient') {
        // Reset si pas en mode édition
        const editId = document.getElementById('fi-edit-id').value;
        if (!editId) {
            document.getElementById('fi-nom').value      = '';
            document.getElementById('fi-quantite').value = '0';
            document.getElementById('fi-prix').value     = '0';
            document.getElementById('fi-seuil').value    = '10';
            document.getElementById('fi-nom').readOnly   = false;
            document.getElementById('modal-ingr-title').textContent = '🌿 Nouvel ingrédient';
            document.getElementById('fi-submit-btn').textContent    = '🌿 Ajouter';
        }
    }
    document.getElementById('modal-' + type).classList.add('open');
}
function closeModal(type) {
    document.getElementById('modal-' + type).classList.remove('open');
    if (type === 'ingredient') {
        document.getElementById('fi-edit-id').value  = '';
        document.getElementById('fi-nom').readOnly   = false;
    }
}

// Fermer modals si clic dehors
document.getElementById('modal-produit').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal('produit'); });
document.getElementById('modal-ingredient').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal('ingredient'); });

// ==========================================
// UTILITAIRES
// ==========================================
async function post(data) {
    const body = new URLSearchParams(data);
    const res  = await fetch(SCRIPT_URL, { method: 'POST', body });
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Erreur inconnue');
    return json;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
    const ct = document.getElementById('toasts');
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${type === 'success' ? '✓' : '✕'}</span> ${msg}`;
    ct.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// ==========================================
// PRÉ-CHARGEMENT INGRÉDIENTS PAR DÉFAUT
// ==========================================
async function initDefaultIngredients() {
    const defaults = [
        'Ail en poudre','Ail semoule','Baies de genièvre','Baies roses',
        'Cannelle bâton','Coriandre','Cumin','Curry de Madras','Curry en poudre',
        'Curry rouge Thaï','Fenugrec','Fleurs d\'hibiscus','Graine de moutarde',
        'Graines de lin','Graines de nigelle','Mélange 4 baies','Muscade en poudre',
        'Oignon semoule','Pétales de roses','Poivre noir','Romarin',
        'Sel rose de l\'Himalaya','Sésame doré','Sumac'
    ];
    try {
        const res = await post({ action: 'get_ingredients' });
        const existing = (res.data || []).map(i => i.nom.toLowerCase());
        if (existing.length === 0) {
            // Première visite : insérer les ingrédients par défaut
            for (const nom of defaults) {
                if (!existing.includes(nom.toLowerCase())) {
                    await post({ action: 'add_ingredient', nom, quantite: 0, unite: 'g', prix_achat: 0, seuil: 10 });
                }
            }
            allIngredients = [];
        }
    } catch(e) {}
}
initDefaultIngredients();
</script>
</body>
</html>