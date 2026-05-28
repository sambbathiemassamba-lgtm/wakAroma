<?php
// ==========================================
// CONFIGURATION BASE DE DONNÉES
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Modifier selon votre config phpMyAdmin
define('DB_PASS', '');             // Modifier selon votre config phpMyAdmin
define('DB_NAME', 'wakaroma'); // Modifier si nécessaire

// ==========================================
// SEUIL D'ALERTE PAR DÉFAUT (modifiable ici ou via l'interface)
// ==========================================
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
            // Migration automatique : ajouter seuil_alerte si elle n'existe pas
            $pdo->exec("ALTER TABLE produits ADD COLUMN IF NOT EXISTS seuil_alerte INT NOT NULL DEFAULT " . SEUIL_ALERTE_DEFAULT);
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

        case 'get_stocks':
            $stmt = $pdo->query("
                SELECT
                    p.id_produit        AS id,
                    p.nom,
                    c.nom               AS categorie,
                    p.stock,
                    COALESCE(p.seuil_alerte, 10) AS seuil_alerte,
                    COALESCE(
                        (SELECT car.valeur FROM caracteristiques car
                         WHERE car.id_produit = p.id_produit AND car.nom = 'Poids' LIMIT 1),
                        'g'
                    ) AS unite,
                    p.prix
                FROM produits p
                INNER JOIN categories c ON c.id_categorie = p.id_categorie
                ORDER BY c.nom, p.nom
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'update_stock':
            $id    = (int)$_POST['id'];
            $stock = (int)$_POST['stock'];
            $stmt  = $pdo->prepare("UPDATE produits SET stock = ? WHERE id_produit = ?");
            $stmt->execute([$stock, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_seuil':
            $id    = (int)$_POST['id'];
            $seuil = (int)$_POST['seuil'];
            $stmt  = $pdo->prepare("UPDATE produits SET seuil_alerte = ? WHERE id_produit = ?");
            $stmt->execute([$seuil, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_seuil_global':
            $seuil = (int)$_POST['seuil'];
            $stmt  = $pdo->prepare("UPDATE produits SET seuil_alerte = ?");
            $stmt->execute([$seuil]);
            echo json_encode(['success' => true, 'message' => 'Seuil global mis à jour']);
            break;

        case 'add_produit':
            $nom       = trim($_POST['nom']);
            $categorie = trim($_POST['categorie']);
            $stock     = (int)$_POST['stock'];
            $seuil     = (int)$_POST['seuil'];
            $unite     = trim($_POST['unite']) ?: 'g';
            $prix      = (float)$_POST['prix'];

            if (empty($nom)) {
                echo json_encode(['success' => false, 'error' => 'Le nom est obligatoire']);
                break;
            }

            // Récupérer id_categorie depuis le nom, ou créer la catégorie si elle n'existe pas
            $stmtCat = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom = ?");
            $stmtCat->execute([$categorie]);
            $cat = $stmtCat->fetch(PDO::FETCH_OBJ);
            if ($cat) {
                $id_categorie = $cat->id_categorie;
            } else {
                $stmtNewCat = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                $stmtNewCat->execute([$categorie]);
                $id_categorie = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO produits (id_categorie, nom, stock, seuil_alerte, prix) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$id_categorie, $nom, $stock, $seuil, $prix]);
            $newId = (int)$pdo->lastInsertId();

            // Insérer l'unité dans caracteristiques (Poids)
            if (!empty($unite)) {
                $stmtCar = $pdo->prepare("INSERT INTO caracteristiques (id_produit, nom, valeur) VALUES (?, 'Poids', ?)");
                $stmtCar->execute([$newId, $unite]);
            }

            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'delete_produit':
            $id   = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id_produit = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT nom FROM categories ORDER BY nom");
            $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['success' => true, 'data' => $cats]);
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
<title>Gestion des Stocks — Épicerie d'Épices</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* ==========================================
   VARIABLES & RESET
   ========================================== */
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

/* ==========================================
   HEADER
   ========================================== */
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
.header-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}
.header-icon {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.header-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.header-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    color: var(--cream);
    font-weight: 700;
}
.header-sub {
    font-size: .8rem;
    color: var(--text-dim);
    margin-top: 2px;
}
.header-stats {
    display: flex;
    gap: 20px;
}
.stat-badge {
    text-align: center;
    padding: 8px 18px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
}
.stat-badge .num {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--gold);
    display: block;
}
.stat-badge .label {
    font-size: .7rem;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: .06em;
}
.stat-badge.alert-badge .num { color: var(--red); }

/* ==========================================
   TOOLBAR
   ========================================== */
.toolbar {
    padding: 20px 32px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
}
.search-wrap {
    position: relative;
    flex: 1;
    min-width: 200px;
}
.search-wrap input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    outline: none;
    transition: border-color .2s;
}
.search-wrap input:focus { border-color: var(--gold); }
.search-wrap .ico {
    position: absolute;
    left: 12px; top: 50%;
    transform: translateY(-50%);
    color: var(--text-dim);
    font-size: 16px;
}
select.filter-select {
    padding: 10px 14px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    outline: none;
    cursor: pointer;
    transition: border-color .2s;
}
select.filter-select:focus { border-color: var(--gold); }
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all .2s;
}
.btn-primary {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
    color: #1a1200;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(201,150,59,.4); }
.btn-danger {
    background: var(--red-bg);
    color: #e74c3c;
    border: 1px solid var(--red-border);
}
.btn-danger:hover { background: #3d1208; }
.btn-ghost {
    background: var(--surface2);
    color: var(--text);
    border: 1px solid var(--border);
}
.btn-ghost:hover { border-color: var(--gold); color: var(--gold); }

/* Seuil global */
.seuil-global {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
}
.seuil-global label { font-size: .82rem; color: var(--text-dim); white-space: nowrap; }
.seuil-global input {
    width: 64px;
    padding: 6px 10px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--gold);
    font-weight: 600;
    font-size: .9rem;
    text-align: center;
    outline: none;
}
.seuil-global input:focus { border-color: var(--gold); }

/* ==========================================
   MAIN CONTENT
   ========================================== */
.main { padding: 28px 32px; }

/* ==========================================
   TABLE
   ========================================== */
.table-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}
.table-wrap { overflow-x: auto; }
table {
    width: 100%;
    border-collapse: collapse;
}
thead tr {
    background: var(--surface2);
    border-bottom: 2px solid var(--gold-dim);
}
thead th {
    padding: 14px 18px;
    text-align: left;
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--gold);
    white-space: nowrap;
}
tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .15s;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(201,150,59,.04); }
tbody tr.row-alert {
    background: rgba(192,57,43,.06);
    border-left: 3px solid var(--red);
}
tbody tr.row-alert:hover { background: rgba(192,57,43,.1); }
td {
    padding: 14px 18px;
    font-size: .9rem;
    vertical-align: middle;
}

/* Cellule nom */
.td-nom { font-weight: 500; color: var(--cream); }
.td-cat {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .75rem;
    background: var(--surface2);
    border: 1px solid var(--border2);
    color: var(--cream-dim);
}

/* Stock editable */
.stock-cell { display: flex; align-items: center; gap: 10px; }
.stock-input {
    width: 80px;
    padding: 7px 10px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text);
    font-size: .9rem;
    text-align: center;
    outline: none;
    transition: border-color .2s;
}
.stock-input:focus { border-color: var(--gold); }
.row-alert .stock-input { border-color: var(--red-border); color: #e74c3c; }
.save-btn {
    padding: 6px 12px;
    background: var(--green-bg);
    border: 1px solid #1e6b40;
    border-radius: 6px;
    color: var(--green);
    font-size: .8rem;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.save-btn:hover { background: #0d3520; }

/* Seuil editable */
.seuil-input {
    width: 70px;
    padding: 7px 10px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--gold-dim);
    font-size: .9rem;
    text-align: center;
    outline: none;
    transition: border-color .2s;
}
.seuil-input:focus { border-color: var(--gold); color: var(--gold); }

/* Badge alerte */
.alert-icon {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: var(--red-bg);
    border: 1px solid var(--red-border);
    border-radius: 20px;
    color: #e74c3c;
    font-size: .78rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .65; }
}
.ok-icon {
    color: var(--green);
    font-size: 1.1rem;
}

/* Actions colonne */
.action-btn {
    padding: 6px 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: transparent;
    color: var(--text-dim);
    cursor: pointer;
    transition: all .15s;
    font-size: .85rem;
}
.action-btn:hover { border-color: var(--red-border); color: #e74c3c; background: var(--red-bg); }

/* ==========================================
   MODAL AJOUT PRODUIT
   ========================================== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.7);
    backdrop-filter: blur(4px);
    z-index: 200;
    display: none;
    align-items: center;
    justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal {
    background: var(--surface);
    border: 1px solid var(--border2);
    border-radius: 16px;
    padding: 32px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
    animation: modalIn .25s ease;
}
@keyframes modalIn {
    from { opacity: 0; transform: translateY(20px) scale(.97); }
    to   { opacity: 1; transform: none; }
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.modal-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: var(--cream);
}
.modal-close {
    width: 32px; height: 32px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text-dim);
    font-size: 18px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.modal-close:hover { color: var(--cream); background: var(--border); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label {
    font-size: .78rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-dim);
}
.form-group input,
.form-group select {
    padding: 10px 14px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    outline: none;
    transition: border-color .2s;
}
.form-group input:focus,
.form-group select:focus { border-color: var(--gold); }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
}

/* ==========================================
   TOAST
   ========================================== */
.toast-container {
    position: fixed;
    bottom: 24px; right: 24px;
    z-index: 300;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.toast {
    padding: 12px 20px;
    border-radius: 10px;
    font-size: .88rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.4);
    animation: toastIn .3s ease;
}
@keyframes toastIn {
    from { opacity: 0; transform: translateX(30px); }
    to   { opacity: 1; transform: none; }
}
.toast.success { background: var(--green-bg); border: 1px solid #1e6b40; color: #4ecb78; }
.toast.error   { background: var(--red-bg);   border: 1px solid var(--red-border); color: #e74c3c; }

/* ==========================================
   EMPTY STATE
   ========================================== */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-dim);
}
.empty-state .icon { font-size: 3rem; margin-bottom: 12px; }
.empty-state p { font-size: .95rem; }

/* ==========================================
   BARRE DE PROGRESSION STOCK
   ========================================== */
.stock-bar-wrap { display: flex; align-items: center; gap: 10px; }
.stock-bar {
    flex: 1;
    height: 5px;
    background: var(--border);
    border-radius: 3px;
    overflow: hidden;
}
.stock-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width .4s ease;
}
.stock-bar-fill.ok   { background: var(--green); }
.stock-bar-fill.warn { background: var(--gold); }
.stock-bar-fill.danger { background: var(--red); }

/* ==========================================
   RESPONSIVE
   ========================================== */
@media (max-width: 768px) {
    .header { padding: 14px 16px; flex-wrap: wrap; gap: 12px; }
    .header-stats { gap: 10px; }
    .toolbar { padding: 14px 16px; }
    .main { padding: 16px; }
    thead th:nth-child(4),
    td:nth-child(4) { display: none; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-brand">
        <div class="header-icon"><img src="logo/logo.jpeg" alt="Logo WakAroma"></div>
        <div>
            <div class="header-title">Gestion des Stocks</div>
            <div class="header-sub">Épicerie d'Épices</div>
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
    </div>
</header>

<!-- TOOLBAR -->
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
    <button class="btn btn-primary" onclick="openModal()">
        <span>＋</span> Ajouter un produit
    </button>
</div>

<!-- MAIN TABLE -->
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
                    <tr><td colspan="9" class="empty-state">
                        <div class="icon">⏳</div>
                        <p>Chargement des données…</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- MODAL AJOUT PRODUIT -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">✦ Nouveau produit</div>
            <button class="modal-close" onclick="closeModal()">✕</button>
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
            <button class="btn btn-ghost" onclick="closeModal()">Annuler</button>
            <button class="btn btn-primary" onclick="addProduit()">✦ Ajouter le produit</button>
        </div>
    </div>
</div>

<!-- TOASTS -->
<div class="toast-container" id="toasts"></div>

<script>
// ==========================================
// DATA & STATE
// ==========================================
let allProducts = [];
const SCRIPT_URL = window.location.href.split('?')[0];

// ==========================================
// INIT
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    loadStocks();
    loadCategories();
});

// ==========================================
// CHARGEMENT DES STOCKS
// ==========================================
async function loadStocks() {
    try {
        const res  = await post({ action: 'get_stocks' });
        allProducts = res.data || [];
        renderTable(allProducts);
        updateStats(allProducts);
    } catch (e) {
        showToast('Erreur de chargement : ' + e.message, 'error');
    }
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

// ==========================================
// RENDU TABLE
// ==========================================
function renderTable(products) {
    const tbody = document.getElementById('tbody');

    if (!products.length) {
        tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state">
            <div class="icon">📦</div>
            <p>Aucun produit trouvé.</p>
        </div></td></tr>`;
        return;
    }

    tbody.innerHTML = products.map(p => {
        const seuil   = parseInt(p.seuil_alerte) || 0;
        const stock   = parseInt(p.stock) || 0;
        const isAlert = stock <= seuil;
        const max     = Math.max(stock * 1.5, seuil * 2, 50);
        const pct     = Math.min(100, Math.round((stock / max) * 100));
        const barClass = stock === 0 ? 'danger' : isAlert ? 'warn' : 'ok';

        return `<tr class="${isAlert ? 'row-alert' : ''}" data-id="${p.id}" data-nom="${p.nom.toLowerCase()}" data-cat="${(p.categorie||'').toLowerCase()}" data-alert="${isAlert ? 'alert' : 'ok'}">
            <td class="td-nom">🌿 ${escHtml(p.nom)}</td>
            <td><span class="td-cat">${escHtml(p.categorie || 'Sans catégorie')}</span></td>
            <td>
                <div class="stock-cell">
                    <input class="stock-input" type="number" value="${stock}" min="0"
                        onchange="markDirty(this)" data-original="${stock}" id="stock-${p.id}">
                    <button class="save-btn" onclick="saveStock(${p.id})">💾 Sauver</button>
                </div>
            </td>
            <td>
                <div class="stock-bar-wrap">
                    <div class="stock-bar"><div class="stock-bar-fill ${barClass}" style="width:${pct}%"></div></div>
                    <span style="font-size:.8rem;color:var(--text-dim);min-width:30px;">${pct}%</span>
                </div>
            </td>
            <td>
                <input class="seuil-input" type="number" value="${seuil}" min="0"
                    onchange="saveSeuil(${p.id}, this.value)" id="seuil-${p.id}">
            </td>
            <td style="color:var(--text-dim)">${escHtml(p.unite || 'g')}</td>
            <td style="color:var(--gold)">${parseFloat(p.prix || 0).toFixed(2)} €</td>
            <td>
                ${isAlert
                    ? `<span class="alert-icon">⚠ Stock faible</span>`
                    : `<span class="ok-icon">✓</span>`}
            </td>
            <td>
                <button class="action-btn" onclick="deleteProduit(${p.id}, '${escHtml(p.nom)}')" title="Supprimer">🗑</button>
            </td>
        </tr>`;
    }).join('');
}

function markDirty(input) {
    input.style.borderColor = 'var(--gold)';
}

// ==========================================
// STATS
// ==========================================
function updateStats(products) {
    const alertCount = products.filter(p => parseInt(p.stock) <= parseInt(p.seuil_alerte)).length;
    document.getElementById('stat-total').textContent  = products.length;
    document.getElementById('stat-ok').textContent     = products.length - alertCount;
    document.getElementById('stat-alert').textContent  = alertCount;
}

// ==========================================
// FILTRES
// ==========================================
function filterTable() {
    const search = document.getElementById('search').value.toLowerCase();
    const cat    = document.getElementById('filter-cat').value.toLowerCase();
    const status = document.getElementById('filter-status').value;

    const rows = document.querySelectorAll('#tbody tr[data-id]');
    rows.forEach(row => {
        const nomMatch    = row.dataset.nom.includes(search);
        const catMatch    = !cat || row.dataset.cat === cat;
        const statusMatch = !status || row.dataset.alert === status;
        row.style.display = (nomMatch && catMatch && statusMatch) ? '' : 'none';
    });
}

// ==========================================
// SAUVEGARDE STOCK
// ==========================================
async function saveStock(id) {
    const input = document.getElementById('stock-' + id);
    const stock = parseInt(input.value);
    if (isNaN(stock) || stock < 0) { showToast('Valeur invalide', 'error'); return; }
    try {
        await post({ action: 'update_stock', id, stock });
        showToast('Stock mis à jour ✓', 'success');
        input.style.borderColor = '';
        loadStocks();
    } catch (e) {
        showToast('Erreur : ' + e.message, 'error');
    }
}

// ==========================================
// SAUVEGARDE SEUIL INDIVIDUEL
// ==========================================
async function saveSeuil(id, seuil) {
    try {
        await post({ action: 'update_seuil', id, seuil: parseInt(seuil) });
        showToast('Seuil mis à jour ✓', 'success');
        loadStocks();
    } catch (e) {
        showToast('Erreur : ' + e.message, 'error');
    }
}

// ==========================================
// SEUIL GLOBAL
// ==========================================
async function applySeuilGlobal() {
    const seuil = parseInt(document.getElementById('seuil-global-input').value);
    if (isNaN(seuil) || seuil < 0) { showToast('Valeur invalide', 'error'); return; }
    if (!confirm(`Appliquer le seuil de ${seuil} à TOUS les produits ?`)) return;
    try {
        await post({ action: 'update_seuil_global', seuil });
        showToast(`Seuil global ${seuil} appliqué à tous les produits ✓`, 'success');
        loadStocks();
    } catch (e) {
        showToast('Erreur : ' + e.message, 'error');
    }
}

// ==========================================
// AJOUT PRODUIT
// ==========================================
function openModal() {
    document.getElementById('modal').classList.add('open');
    document.getElementById('f-nom').focus();
}
function closeModal() {
    document.getElementById('modal').classList.remove('open');
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
        showToast(`"${nom}" ajouté avec succès ✓`, 'success');
        closeModal();
        // Réinitialiser
        ['f-nom','f-cat','f-stock','f-seuil','f-prix'].forEach(id => document.getElementById(id).value = id === 'f-seuil' ? '10' : id === 'f-stock' || id === 'f-prix' ? '0' : '');
        loadStocks();
        loadCategoriesRefresh();
    } catch (e) {
        showToast('Erreur : ' + e.message, 'error');
    }
}

async function loadCategoriesRefresh() {
    try {
        const res = await post({ action: 'get_categories' });
        const dl = document.getElementById('cat-list');
        dl.innerHTML = '';
        (res.data || []).forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat;
            dl.appendChild(opt);
        });
    } catch(e) {}
}

// ==========================================
// SUPPRESSION
// ==========================================
async function deleteProduit(id, nom) {
    if (!confirm(`Supprimer "${nom}" définitivement ?`)) return;
    try {
        await post({ action: 'delete_produit', id });
        showToast(`"${nom}" supprimé`, 'success');
        loadStocks();
    } catch (e) {
        showToast('Erreur : ' + e.message, 'error');
    }
}

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
    const ct   = document.getElementById('toasts');
    const el   = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${type === 'success' ? '✓' : '✕'}</span> ${msg}`;
    ct.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// Fermer modal si clic hors
document.getElementById('modal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});
</script>
</body>
</html>