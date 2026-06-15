<?php
session_start();

// PHPMailer pour envoi newsletter (chargé seulement s'il est présent sur le serveur)
if (file_exists(__DIR__ . '/PHPMailer-master/src/PHPMailer.php')) {
    require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
    require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
// Détection automatique : local (WAMP/XAMPP) ou serveur en ligne (OVH)
$IS_LOCAL = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);

if ($IS_LOCAL) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'wakaroma');
} else {
    define('DB_HOST', 'kgaftzfwakaroma.mysql.db');
    define('DB_USER', 'kgaftzfwakaroma');
    define('DB_PASS', 'Wakaroma1');
    define('DB_NAME', 'kgaftzfwakaroma');
}
define('SEUIL_ALERTE_DEFAULT', 10);

// ==========================================
// CONNEXION PDO
// ==========================================
function addColumnIfMissing($pdo, $table, $column, $definition) {
    try {
        $chk = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column));
        if ($chk !== false && $chk->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    } catch (PDOException $e) { /* table absente ou droits insuffisants : on ignore */ }
}

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
            addColumnIfMissing($pdo, 'produits', 'seuil_alerte', "INT NOT NULL DEFAULT " . SEUIL_ALERTE_DEFAULT);
            addColumnIfMissing($pdo, 'images', 'is_cover', 'TINYINT(1) NOT NULL DEFAULT 0');
            // Table ingrédients internes
            $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                email         VARCHAR(255) NOT NULL UNIQUE,
                nom           VARCHAR(150) DEFAULT '',
                source        ENUM('newsletter','compte','manuel') NOT NULL DEFAULT 'newsletter',
                actif         TINYINT(1) NOT NULL DEFAULT 1,
                subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_campaigns (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                sujet         VARCHAR(255) NOT NULL,
                contenu_html  LONGTEXT NOT NULL,
                destinataires TEXT,
                nb_envoyes    INT DEFAULT 0,
                statut        ENUM('brouillon','envoye') DEFAULT 'brouillon',
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at       TIMESTAMP NULL
            )");
            addColumnIfMissing($pdo, 'users', 'is_entreprise', 'TINYINT(1) DEFAULT 0');
            addColumnIfMissing($pdo, 'users', 'nom_entreprise', "VARCHAR(255) DEFAULT ''");
            addColumnIfMissing($pdo, 'produits', 'prix_entreprise', 'DECIMAL(10,2) DEFAULT NULL');
            addColumnIfMissing($pdo, 'produits', 'qte_pro', 'DECIMAL(10,3) DEFAULT NULL');
            addColumnIfMissing($pdo, 'produits', 'unite_pro', 'VARCHAR(30) DEFAULT NULL');
            $pdo->exec("CREATE TABLE IF NOT EXISTS salons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(255) NOT NULL,
                lieu VARCHAR(255) NOT NULL,
                ville VARCHAR(150) NOT NULL,
                adresse VARCHAR(255) DEFAULT '',
                date_debut DATE NOT NULL,
                date_fin DATE NOT NULL,
                heure_debut VARCHAR(10) DEFAULT '10:00',
                heure_fin VARCHAR(10) DEFAULT '18:00',
                description TEXT DEFAULT '',
                stand VARCHAR(255) DEFAULT '',
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
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

    // Empêcher PHP d'afficher des erreurs/avertissements HTML au milieu du JSON
    ini_set('display_errors', '0');
    ob_start();
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            while (ob_get_level()) ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Erreur PHP : ' . $err['message']]);
        }
    });

    try {
    $pdo = getDB();
    $action = $_POST['action'];

    // Traitement upload image (fichier multipart, hors switch classique)
    if ($action === 'add_image') {
        $id_produit = (int)($_POST['id_produit'] ?? 0);
        if ($id_produit <= 0) { echo json_encode(['success' => false, 'error' => 'ID produit invalide']); exit; }
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu ou erreur upload']); exit;
        }
        $file     = $_FILES['image'];
        $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé (JPG, PNG, WEBP, GIF)']); exit;
        }
        $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
        $dir      = __DIR__ . '/images/produits/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'produit_' . $id_produit . '_' . uniqid() . '.' . $ext;
        $dest     = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'error' => 'Impossible de déplacer le fichier']); exit;
        }
        $url = 'images/produits/' . $filename;
        $pdo->prepare("INSERT INTO images (id_produit, url_image) VALUES (?, ?)")->execute([$id_produit, $url]);
        $new_id = (int)$pdo->lastInsertId();
        echo json_encode(['success' => true, 'id_image' => $new_id, 'url_image' => $url]);
        exit;
    }

    switch ($action) {

        // ---- STOCKS PRODUITS ----
        case 'get_stocks':
            $stmt = $pdo->query("
                SELECT p.id_produit AS id, p.nom, c.nom AS categorie, p.stock,
                    COALESCE(p.seuil_alerte, 10) AS seuil_alerte,
                    COALESCE((SELECT car.valeur FROM caracteristiques car WHERE car.id_produit = p.id_produit AND car.nom = 'Poids' LIMIT 1), 'g') AS unite,
                    p.prix,
                    p.prix_entreprise,
                    p.qte_pro,
                    p.unite_pro
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

        case 'update_unite':
            $id    = (int)$_POST['id'];
            $unite = trim($_POST['unite']);
            if (empty($unite)) { echo json_encode(['success' => false, 'error' => 'Unité vide']); break; }
            $stmtCar = $pdo->prepare("SELECT id_caracteristique FROM caracteristiques WHERE id_produit = ? AND nom = 'Poids' LIMIT 1");
            $stmtCar->execute([$id]);
            $car = $stmtCar->fetch(PDO::FETCH_OBJ);
            if ($car) {
                $pdo->prepare("UPDATE caracteristiques SET valeur = ? WHERE id_caracteristique = ?")->execute([$unite, $car->id_caracteristique]);
            } else {
                $pdo->prepare("INSERT INTO caracteristiques (id_produit, nom, valeur) VALUES (?, 'Poids', ?)")->execute([$id, $unite]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'add_produit':
            $nom = trim($_POST['nom']); $categorie = trim($_POST['categorie']);
            $stock = (int)$_POST['stock']; $seuil = (int)$_POST['seuil'];
            $unite = trim($_POST['unite']) ?: 'g'; $prix = (float)$_POST['prix'];
            $prix_entreprise    = (isset($_POST['prix_entreprise']) && $_POST['prix_entreprise'] !== '') ? (float)$_POST['prix_entreprise'] : null;
            $qte_pro         = (isset($_POST['qte_pro']) && $_POST['qte_pro'] !== '') ? (float)$_POST['qte_pro'] : null;
            $unite_pro       = trim($_POST['unite_pro'] ?? '') ?: null;
            if (empty($nom)) { echo json_encode(['success' => false, 'error' => 'Le nom est obligatoire']); break; }
            $stmtCat = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom = ?");
            $stmtCat->execute([$categorie]);
            $cat = $stmtCat->fetch(PDO::FETCH_OBJ);
            if ($cat) { $id_categorie = $cat->id_categorie; }
            else {
                $pdo->prepare("INSERT INTO categories (nom) VALUES (?)")->execute([$categorie]);
                $id_categorie = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("INSERT INTO produits (id_categorie, nom, stock, seuil_alerte, prix, prix_entreprise, qte_pro, unite_pro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([$id_categorie, $nom, $stock, $seuil, $prix, $prix_entreprise, $qte_pro, $unite_pro]);
            $newId = (int)$pdo->lastInsertId();
            if (!empty($unite)) {
                $pdo->prepare("INSERT INTO caracteristiques (id_produit, nom, valeur) VALUES (?, 'Poids', ?)")->execute([$newId, $unite]);
            }
            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'update_produit':
            $id        = (int)$_POST['id'];
            $nom       = trim($_POST['nom']);
            $categorie = trim($_POST['categorie']);
            $stock     = (int)$_POST['stock'];
            $seuil     = (int)$_POST['seuil'];
            $unite     = trim($_POST['unite']) ?: 'g';
            $prix      = (float)$_POST['prix'];
            $prix_entreprise = (isset($_POST['prix_entreprise']) && $_POST['prix_entreprise'] !== '') ? (float)$_POST['prix_entreprise'] : null;
            $qte_pro         = (isset($_POST['qte_pro']) && $_POST['qte_pro'] !== '') ? (float)$_POST['qte_pro'] : null;
            $unite_pro       = trim($_POST['unite_pro'] ?? '') ?: null;
            if (empty($nom)) { echo json_encode(['success' => false, 'error' => 'Nom obligatoire']); break; }
            $stmtC = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom = ?");
            $stmtC->execute([$categorie]);
            $cat = $stmtC->fetch(PDO::FETCH_OBJ);
            if ($cat) { $id_cat = $cat->id_categorie; }
            else {
                $pdo->prepare("INSERT INTO categories (nom) VALUES (?)")->execute([$categorie]);
                $id_cat = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("UPDATE produits SET nom = ?, id_categorie = ?, stock = ?, seuil_alerte = ?, prix = ?, prix_entreprise = ?, qte_pro = ?, unite_pro = ? WHERE id_produit = ?")
                ->execute([$nom, $id_cat, $stock, $seuil, $prix, $prix_entreprise, $qte_pro, $unite_pro, $id]);
            $stmtCar = $pdo->prepare("SELECT id_caracteristique FROM caracteristiques WHERE id_produit = ? AND nom = 'Poids' LIMIT 1");
            $stmtCar->execute([$id]);
            $car = $stmtCar->fetch(PDO::FETCH_OBJ);
            if ($car) {
                $pdo->prepare("UPDATE caracteristiques SET valeur = ? WHERE id_caracteristique = ?")
                    ->execute([$unite, $car->id_caracteristique]);
            } else {
                $pdo->prepare("INSERT INTO caracteristiques (id_produit, nom, valeur) VALUES (?, 'Poids', ?)")
                    ->execute([$id, $unite]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_produit':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM produits WHERE id_produit = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ---- IMAGES PRODUIT ----
        case 'get_images':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT id_image, url_image, is_cover FROM images WHERE id_produit = ? ORDER BY is_cover DESC, id_image");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'delete_image':
            $id_image = (int)$_POST['id_image'];
            // Récupérer le chemin pour supprimer le fichier physique si uploadé
            $stmt = $pdo->prepare("SELECT url_image FROM images WHERE id_image = ?");
            $stmt->execute([$id_image]);
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            $pdo->prepare("DELETE FROM images WHERE id_image = ?")->execute([$id_image]);
            // Supprimer le fichier physique si c'est un upload (préfixe uploads/)
            if ($row && (strpos($row->url_image, 'uploads/') === 0 || strpos($row->url_image, 'images/') === 0)) {
                $path = __DIR__ . '/' . $row->url_image;
                if (file_exists($path)) @unlink($path);
            }
            echo json_encode(['success' => true]);
            break;

        case 'set_cover':
            $id_image   = (int)$_POST['id_image'];
            $id_produit = (int)$_POST['id_produit'];
            // Retirer l'ancienne couverture pour ce produit
            $pdo->prepare("UPDATE images SET is_cover = 0 WHERE id_produit = ?")->execute([$id_produit]);
            // Définir la nouvelle
            $pdo->prepare("UPDATE images SET is_cover = 1 WHERE id_image = ?")->execute([$id_image]);
            echo json_encode(['success' => true]);
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT nom FROM categories ORDER BY nom");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
            break;

        // ---- GESTION DES CATÉGORIES ----
        case 'get_categories_full':
            $stmt = $pdo->query("
                SELECT c.id_categorie, c.nom,
                    (SELECT COUNT(*) FROM produits p WHERE p.id_categorie = c.id_categorie) AS nb_produits
                FROM categories c
                ORDER BY c.nom
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'add_categorie':
            $nom = trim($_POST['nom'] ?? '');
            if ($nom === '') { echo json_encode(['success' => false, 'error' => 'Le nom de la catégorie est obligatoire']); break; }
            $chk = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom = ?");
            $chk->execute([$nom]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'error' => "La catégorie \"$nom\" existe déjà"]); break; }
            $pdo->prepare("INSERT INTO categories (nom) VALUES (?)")->execute([$nom]);
            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'nom' => $nom]);
            break;

        case 'rename_categorie':
            $id  = (int)($_POST['id'] ?? 0);
            $nom = trim($_POST['nom'] ?? '');
            if ($id <= 0 || $nom === '') { echo json_encode(['success' => false, 'error' => 'Données invalides']); break; }
            $chk = $pdo->prepare("SELECT id_categorie FROM categories WHERE nom = ? AND id_categorie != ?");
            $chk->execute([$nom, $id]);
            if ($chk->fetch()) { echo json_encode(['success' => false, 'error' => "Une catégorie \"$nom\" existe déjà"]); break; }
            $pdo->prepare("UPDATE categories SET nom = ? WHERE id_categorie = ?")->execute([$nom, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_categorie':
            $id = (int)($_POST['id'] ?? 0);
            $chk = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie = ?");
            $chk->execute([$id]);
            $nb = (int)$chk->fetchColumn();
            if ($nb > 0) {
                echo json_encode(['success' => false, 'error' => "Impossible : $nb produit(s) utilisent encore cette catégorie. Changez d'abord leur catégorie."]);
                break;
            }
            $pdo->prepare("DELETE FROM categories WHERE id_categorie = ?")->execute([$id]);
            echo json_encode(['success' => true]);
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
            $stmt = $pdo->query("SELECT id_user AS id, nom, prenom, email, numero, created_at, is_entreprise, nom_entreprise FROM users ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'delete_user':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM users WHERE id_user = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ---- NEWSLETTER : ABONNÉS ----
        case 'nl_get_subscribers':
            $stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'nl_add_subscriber':
            $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
            $nom   = trim($_POST['nom'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email invalide']); break;
            }
            $stmtChk = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $stmtChk->execute([$email]);
            if ($stmtChk->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà enregistré']); break;
            }
            $pdo->prepare("INSERT INTO newsletter_subscribers (email, nom, source) VALUES (?, ?, 'manuel')")
                ->execute([$email, $nom]);
            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;

        case 'nl_toggle_subscriber':
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE newsletter_subscribers SET actif = NOT actif WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'nl_delete_subscriber':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'nl_sync_clients':
            $stmt  = $pdo->query("SELECT email, CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) AS nom FROM users WHERE email IS NOT NULL AND email != ''");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $added = 0;
            foreach ($users as $u) {
                try {
                    $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email, nom, source) VALUES (?, ?, 'compte')")
                        ->execute([trim($u['email']), trim($u['nom'])]);
                    if ((int)$pdo->lastInsertId() > 0) $added++;
                } catch (PDOException $e) {}
            }
            echo json_encode(['success' => true, 'added' => $added]);
            break;

        // ---- NEWSLETTER : CAMPAGNES ----
        case 'nl_get_campaigns':
            $stmt = $pdo->query("SELECT id, sujet, nb_envoyes, statut, created_at, sent_at FROM newsletter_campaigns ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'nl_get_campaign':
            $id   = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT * FROM newsletter_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        case 'nl_save_campaign':
            $id    = (int)($_POST['id'] ?? 0);
            $sujet = trim($_POST['sujet'] ?? '');
            $html  = $_POST['contenu_html'] ?? '';
            $dest  = $_POST['destinataires'] ?? null;
            if (empty($sujet)) { echo json_encode(['success' => false, 'error' => 'Le sujet est obligatoire']); break; }
            if (empty($html))  { echo json_encode(['success' => false, 'error' => 'Le contenu est vide']); break; }
            if ($id > 0) {
                $pdo->prepare("UPDATE newsletter_campaigns SET sujet=?, contenu_html=?, destinataires=?, statut='brouillon' WHERE id=?")
                    ->execute([$sujet, $html, $dest, $id]);
                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                $pdo->prepare("INSERT INTO newsletter_campaigns (sujet, contenu_html, destinataires) VALUES (?,?,?)")
                    ->execute([$sujet, $html, $dest]);
                echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            break;

        case 'nl_delete_campaign':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM newsletter_campaigns WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'nl_get_all_recipients':
            // Fusion abonnés newsletter + clients (users), dédupliqués par email
            $subs  = $pdo->query("SELECT CONCAT('sub_',id) AS uid, email, nom, 'newsletter' AS source, actif FROM newsletter_subscribers ORDER BY email")->fetchAll(PDO::FETCH_ASSOC);
            $users = $pdo->query("SELECT CONCAT('usr_',id_user) AS uid, email, CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) AS nom, 'client' AS source, 1 AS actif FROM users WHERE email IS NOT NULL AND email != '' ORDER BY email")->fetchAll(PDO::FETCH_ASSOC);
            // Fusionner en dédupliquant par email (priorité aux abonnés)
            $seen    = [];
            $merged  = [];
            foreach ($subs as $s)  { $seen[strtolower(trim($s['email']))] = true; $merged[] = $s; }
            foreach ($users as $u) { if (!isset($seen[strtolower(trim($u['email']))])) { $merged[] = $u; } }
            usort($merged, fn($a,$b) => strcasecmp($a['nom'].$a['email'], $b['nom'].$b['email']));
            echo json_encode(['success' => true, 'data' => $merged]);
            break;

        case 'nl_send_campaign':
            $campId  = (int)$_POST['campaign_id'];
            $destRaw = $_POST['destinataires'] ?? 'tous';
            $stmtC   = $pdo->prepare("SELECT * FROM newsletter_campaigns WHERE id = ?");
            $stmtC->execute([$campId]);
            $campaign = $stmtC->fetch(PDO::FETCH_ASSOC);
            if (!$campaign) { echo json_encode(['success' => false, 'error' => 'Campagne introuvable']); break; }

            $recipients = [];

            if ($destRaw === 'tous') {
                // Tous abonnés actifs
                $rows = $pdo->query("SELECT email, nom FROM newsletter_subscribers WHERE actif = 1")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) $recipients[strtolower(trim($r['email']))] = ['email' => $r['email'], 'nom' => trim($r['nom'])];
            } elseif ($destRaw === 'tous_clients') {
                // Tous les clients (users)
                $rows = $pdo->query("SELECT email, CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) AS nom FROM users WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) $recipients[strtolower(trim($r['email']))] = ['email' => $r['email'], 'nom' => trim($r['nom'])];
            } elseif ($destRaw === 'tous_les_deux') {
                // Abonnés actifs + tous clients, dédupliqués
                $subs2  = $pdo->query("SELECT email, nom FROM newsletter_subscribers WHERE actif = 1")->fetchAll(PDO::FETCH_ASSOC);
                $users2 = $pdo->query("SELECT email, CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) AS nom FROM users WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($subs2  as $r) $recipients[strtolower(trim($r['email']))] = ['email' => $r['email'], 'nom' => trim($r['nom'])];
                foreach ($users2 as $r) $recipients[strtolower(trim($r['email']))] ?? $recipients[strtolower(trim($r['email']))] = ['email' => $r['email'], 'nom' => trim($r['nom'])];
            } else {
                // Sélection manuelle : UIDs préfixés sub_X ou usr_X
                $uids  = json_decode($destRaw, true);
                if (!is_array($uids) || empty($uids)) { echo json_encode(['success' => false, 'error' => 'Aucun destinataire']); break; }
                $subIds = []; $usrIds = [];
                foreach ($uids as $uid) {
                    if (str_starts_with($uid, 'sub_')) $subIds[] = (int)substr($uid, 4);
                    if (str_starts_with($uid, 'usr_')) $usrIds[] = (int)substr($uid, 4);
                }
                if (!empty($subIds)) {
                    $ph  = implode(',', array_fill(0, count($subIds), '?'));
                    $stS = $pdo->prepare("SELECT email, nom FROM newsletter_subscribers WHERE id IN ($ph)");
                    $stS->execute($subIds);
                    foreach ($stS->fetchAll(PDO::FETCH_ASSOC) as $r) $recipients[strtolower(trim($r['email']))] = ['email' => $r['email'], 'nom' => trim($r['nom'])];
                }
                if (!empty($usrIds)) {
                    $ph  = implode(',', array_fill(0, count($usrIds), '?'));
                    $stU = $pdo->prepare("SELECT email, CONCAT(COALESCE(prenom,''),' ',COALESCE(nom,'')) AS nom FROM users WHERE id_user IN ($ph)");
                    $stU->execute($usrIds);
                    foreach ($stU->fetchAll(PDO::FETCH_ASSOC) as $r) $recipients[strtolower(trim($r['email']))] ?? $recipients[strtolower(trim($r['email']))] = ['email' => $r['email'], 'nom' => trim($r['nom'])];
                }
            }

            if (empty($recipients)) { echo json_encode(['success' => false, 'error' => 'Aucun destinataire trouvé']); break; }

            $sent = 0; $errors = [];
            foreach ($recipients as $rec) {
                $to      = $rec['email'];
                $nomDest = trim($rec['nom']) ?: 'Cher client';
                $body    = str_replace(
                    ['{{NOM}}', '{{EMAIL}}'],
                    [htmlspecialchars($nomDest), htmlspecialchars($to)],
                    $campaign['contenu_html']
                );
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'samzosamb123@gmail.com';
                    $mail->Password   = 'oxwcjqcvmoettpkx';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet    = 'UTF-8';
                    $mail->setFrom('samzosamb123@gmail.com', 'WakAroma');
                    $mail->addAddress($to, $nomDest);
                    $mail->addReplyTo('noreply@wakaroma.com', 'No Reply');
                    $mail->isHTML(true);
                    $mail->Subject = $campaign['sujet'];
                    $mail->Body    = $body;
                    $mail->AltBody = strip_tags($body);
                    $mail->send();
                    $sent++;
                } catch (Exception $e) {
                    $errors[] = $to . ' (' . $e->getMessage() . ')';
                }
            }
            $pdo->prepare("UPDATE newsletter_campaigns SET nb_envoyes=?, statut='envoye', sent_at=NOW() WHERE id=?")
                ->execute([$sent, $campId]);
            echo json_encode(['success' => true, 'sent' => $sent, 'total' => count($recipients), 'errors' => $errors]);
            break;


        // ---- SALONS ----
        case 'get_salons':
            $stmt = $pdo->query("SELECT * FROM salons ORDER BY date_debut ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'add_salon':
            $sNom   = trim($_POST['nom']   ?? '');
            $sLieu  = trim($_POST['lieu']  ?? '');
            $sVille = trim($_POST['ville'] ?? '');
            if (empty($sNom)||empty($sLieu)||empty($sVille)||empty($_POST['date_debut'])||empty($_POST['date_fin'])) {
                echo json_encode(['success'=>false,'error'=>'Champs obligatoires manquants']); break;
            }
            $pdo->prepare("INSERT INTO salons (nom,lieu,ville,adresse,date_debut,date_fin,heure_debut,heure_fin,description,stand,actif) VALUES (?,?,?,?,?,?,?,?,?,?,1)")
                ->execute([$sNom,$sLieu,$sVille,trim($_POST['adresse']??''),trim($_POST['date_debut']),trim($_POST['date_fin']),trim($_POST['heure_debut']??'10:00'),trim($_POST['heure_fin']??'18:00'),trim($_POST['description']??''),trim($_POST['stand']??'')]);
            echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]);
            break;

        case 'update_salon':
            $sId = (int)$_POST['id'];
            $pdo->prepare("UPDATE salons SET nom=?,lieu=?,ville=?,adresse=?,date_debut=?,date_fin=?,heure_debut=?,heure_fin=?,description=?,stand=?,actif=? WHERE id=?")
                ->execute([trim($_POST['nom']),trim($_POST['lieu']),trim($_POST['ville']),trim($_POST['adresse']??''),trim($_POST['date_debut']),trim($_POST['date_fin']),trim($_POST['heure_debut']??'10:00'),trim($_POST['heure_fin']??'18:00'),trim($_POST['description']??''),trim($_POST['stand']??''),(int)($_POST['actif']??1),$sId]);
            echo json_encode(['success'=>true]);
            break;

        case 'delete_salon':
            $sId = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM salons WHERE id=?")->execute([$sId]);
            echo json_encode(['success'=>true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
    } catch (Throwable $e) {
        while (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
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
.unite-input { width: 70px; padding: 7px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text-dim); font-size: .9rem; text-align: center; outline: none; transition: border-color .2s; }
.unite-input:focus { border-color: var(--gold); color: var(--cream); }
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
.modal--wide { max-width: 600px; max-height: 90vh; overflow-y: auto; }
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

/* =============================================
   NEWSLETTER
   ============================================= */
.nl-tabs {
    display: flex; gap: 4px; margin-bottom: 24px;
    border-bottom: 1px solid var(--border); padding-bottom: 0;
}
.nl-tab {
    padding: 10px 20px; background: transparent; border: none;
    border-bottom: 3px solid transparent; color: var(--text-dim);
    font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 500;
    cursor: pointer; transition: all .2s; margin-bottom: -1px;
    display: flex; align-items: center; gap: 6px;
}
.nl-tab:hover { color: var(--cream); }
.nl-tab.active { color: var(--gold); border-bottom-color: var(--gold); }
.nl-page { display: none; }
.nl-page.active { display: block; }

/* Stats newsletter */
.nl-stats {
    display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap;
}
.nl-stat {
    flex: 1; min-width: 120px; padding: 14px 18px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 10px; display: flex; align-items: center; gap: 12px;
}
.nl-stat .ico { font-size: 1.5rem; }
.nl-stat .num { font-size: 1.4rem; font-weight: 700; color: var(--gold); font-family: 'Playfair Display', serif; line-height: 1; }
.nl-stat .lbl { font-size: .72rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }

/* Subscriber table extras */
.sub-avatar-sm {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, var(--gold-dim), var(--gold));
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .72rem; color: #1a1200; flex-shrink: 0;
}
.source-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 20px; font-size: .72rem; font-weight: 600; }
.source-nl   { background: rgba(201,150,59,.12); border: 1px solid var(--gold-dim); color: var(--gold); }
.source-compte { background: var(--blue-bg); border: 1px solid var(--blue-border); color: #6fa3e8; }
.source-manuel { background: var(--surface2); border: 1px solid var(--border); color: var(--text-dim); }

/* Bulk action bar */
.bulk-bar {
    display: none; align-items: center; gap: 12px; flex-wrap: wrap;
    margin-top: 14px; padding: 12px 16px;
    background: rgba(201,150,59,.06); border: 1px solid var(--gold-dim);
    border-radius: 10px;
}
.bulk-bar.visible { display: flex; }

/* Campaign cards */
.campaign-list { display: flex; flex-direction: column; gap: 10px; }
.campaign-card {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 10px; padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
    transition: border-color .2s;
}
.campaign-card:hover { border-color: var(--border2); }
.campaign-card .camp-icon { font-size: 1.6rem; flex-shrink: 0; }
.campaign-card .camp-info { flex: 1; min-width: 0; }
.camp-sujet { font-weight: 600; color: var(--cream); font-size: .92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.camp-meta  { font-size: .76rem; color: var(--text-dim); margin-top: 4px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
.camp-actions { display: flex; gap: 6px; flex-shrink: 0; }
.badge-sent    { background: var(--green-bg); border: 1px solid #1e6b40; color: #4ecb78; display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: .72rem; font-weight: 600; }
.badge-draft   { background: var(--surface); border: 1px solid var(--border); color: var(--text-dim); display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: .72rem; font-weight: 600; }

/* Rich editor */
.nl-editor-toolbar {
    display: flex; flex-wrap: wrap; gap: 3px;
    padding: 10px 12px; background: var(--surface2);
    border: 1px solid var(--border); border-bottom: none;
    border-radius: 8px 8px 0 0;
}
.nl-editor-btn {
    padding: 5px 9px; background: transparent;
    border: 1px solid var(--border); border-radius: 5px;
    color: var(--text-dim); font-size: .8rem; cursor: pointer;
    transition: all .15s; font-family: 'DM Sans', sans-serif;
}
.nl-editor-btn:hover { background: var(--border); color: var(--cream); }
.nl-editor-sep { width: 1px; background: var(--border); margin: 2px 3px; }
#nlRichEditor {
    min-height: 240px; max-height: 380px; overflow-y: auto;
    padding: 16px; background: var(--bg);
    border: 1px solid var(--border); border-radius: 0 0 8px 8px;
    color: var(--text); font-family: 'DM Sans', sans-serif;
    font-size: .9rem; line-height: 1.6; outline: none;
}
#nlRichEditor:focus { border-color: var(--gold); }
#nlRichEditor:empty::before { content: 'Rédigez votre message… Utilisez {{NOM}} pour personnaliser.'; color: var(--text-dim); pointer-events: none; }
#nlRichEditor a { color: var(--gold); }
#nlRichEditor h1, #nlRichEditor h2 { color: var(--cream); font-family: 'Playfair Display', serif; }

/* Recipient selector */
.dest-option {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; background: var(--bg);
    border: 1px solid var(--border); border-radius: 8px;
    cursor: pointer; transition: all .2s; margin-bottom: 8px;
}
.dest-option:hover  { border-color: var(--gold-dim); }
.dest-option.active { border-color: var(--gold); background: rgba(201,150,59,.06); }
.dest-option input[type=radio] { accent-color: var(--gold); width: 15px; height: 15px; }
.dest-lbl { font-weight: 500; color: var(--cream); font-size: .88rem; }
.dest-sub { font-size: .76rem; color: var(--text-dim); margin-top: 2px; }

/* Subscriber checklist for modal */
.nl-sub-list { max-height: 260px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); }
.nl-sub-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; border-bottom: 1px solid var(--border); transition: background .15s; }
.nl-sub-item:last-child { border-bottom: none; }
.nl-sub-item:hover { background: rgba(201,150,59,.04); }
.nl-sub-item input[type=checkbox] { accent-color: var(--gold); width: 15px; height: 15px; flex-shrink: 0; }

/* Template chips */
.tpl-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.tpl-chip { padding: 7px 14px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; color: var(--text-dim); font-size: .82rem; cursor: pointer; transition: all .15s; }
.tpl-chip:hover { border-color: var(--gold-dim); color: var(--gold); }

/* Preview iframe */
.nl-preview-frame { width: 100%; min-height: 400px; border: 1px solid var(--border); border-radius: 8px; background: #fff; }

/* Modals newsletter */
.nl-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.75);
    backdrop-filter: blur(6px); z-index: 250;
    display: none; align-items: center; justify-content: center; padding: 16px;
}
.nl-modal-overlay.open { display: flex; }
.nl-modal {
    background: var(--surface); border: 1px solid var(--border2);
    border-radius: 16px; width: 100%; max-width: 520px;
    max-height: 92vh; overflow-y: auto;
    box-shadow: 0 24px 80px rgba(0,0,0,.7);
    animation: modalIn .25s ease;
}
.nl-modal--lg  { max-width: 780px; }
.nl-modal--xl  { max-width: 960px; }
.nl-mheader { display: flex; align-items: center; justify-content: space-between; padding: 22px 26px 0; }
.nl-mtitle  { font-family: 'Playfair Display', serif; font-size: 1.2rem; color: var(--cream); }
.nl-mbody   { padding: 18px 26px; }
.nl-mfooter { padding: 14px 26px 22px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); margin-top: 4px; }

/* IMAGES PRODUIT dans la modale */
.img-section { margin-top: 20px; }
.img-section-title {
    font-size: .78rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .06em; color: var(--text-dim); margin-bottom: 12px;
}
.img-grid {
    display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;
    min-height: 48px;
}
.img-thumb {
    position: relative; width: 72px; height: 72px;
    border-radius: 8px; overflow: hidden;
    border: 2px solid var(--border2); background: var(--bg);
    flex-shrink: 0;
}
.img-thumb img { width: 100%; height: 100%; object-fit: contain; }
.img-thumb__del {
    position: absolute; top: 3px; right: 3px;
    width: 20px; height: 20px; border-radius: 50%;
    background: rgba(192,57,43,.85); border: none;
    color: #fff; font-size: 11px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s; line-height: 1;
}
.img-thumb__del:hover { background: var(--red); }
.img-thumb__cover-btn {
    position: absolute; bottom: 3px; left: 3px;
    width: 20px; height: 20px; border-radius: 50%;
    background: rgba(30,30,20,.7); border: none;
    color: #888; font-size: 12px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s; line-height: 1;
    title: "Définir comme photo de couverture (index)";
}
.img-thumb__cover-btn:hover { background: rgba(201,150,59,.9); color: #fff; }
.img-thumb.is-cover { border-color: var(--gold); }
.img-thumb.is-cover .img-thumb__cover-btn { background: var(--gold); color: #1a1200; }
.img-cover-badge {
    position: absolute; bottom: 2px; right: 2px;
    background: var(--gold); color: #1a1200;
    font-size: 8px; font-weight: 700;
    padding: 1px 4px; border-radius: 3px;
    letter-spacing: .03em; pointer-events: none;
    line-height: 1.4;
}
.img-empty { color: var(--text-dim); font-size: .82rem; font-style: italic; align-self: center; }

/* Zone d'upload */
.img-upload-zone {
    border: 2px dashed var(--border2); border-radius: 10px;
    padding: 14px 16px; text-align: center;
    cursor: pointer; transition: border-color .2s, background .2s;
    background: var(--bg);
}
.img-upload-zone:hover, .img-upload-zone.drag-over {
    border-color: var(--gold); background: rgba(201,150,59,.05);
}
.img-upload-zone input[type="file"] { display: none; }
.img-upload-label {
    font-size: .82rem; color: var(--text-dim); cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.img-upload-label span { color: var(--gold); font-weight: 600; }
.img-upload-preview {
    display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; justify-content: center;
}
.img-upload-preview img {
    width: 56px; height: 56px; object-fit: contain;
    border-radius: 6px; border: 1px solid var(--border2); background: var(--bg);
}
.img-uploading { font-size: .8rem; color: var(--gold-dim); margin-top: 6px; }


/* =============================================
   RESPONSIVE MOBILE
   ============================================= */

/* --- Tablette (< 900px) --- */
@media (max-width: 900px) {
    .header { padding: 14px 20px; gap: 12px; }
    .header-stats { gap: 10px; }
    .stat-badge { padding: 6px 12px; }
    .stat-badge .num { font-size: 1.1rem; }
    .toolbar { padding: 14px 20px; gap: 10px; }
    .main { padding: 20px; }
    .nav-tabs { padding: 0 20px; }
}

/* --- Mobile (< 640px) --- */
@media (max-width: 640px) {

    /* ── HEADER ── */
    .header { padding: 10px 14px; gap: 10px; }
    .header-icon { width: 38px; height: 38px; }
    .header-title { font-size: 1.05rem; }
    .header-sub { display: none; }
    /* On masque seulement les badges de stats, pour garder le bouton Déconnexion visible */
    .header-stats { gap: 0; }
    .header-stats .stat-badge { display: none; }
    .btn-logout span { display: none; } /* juste l'icône 🚪 */
    .btn-logout { padding: 8px 11px; font-size: 1rem; }

    /* ── ONGLETS : barre fixe en bas type app mobile ── */
    .nav-tabs {
        position: fixed;
        bottom: 0; left: 0; right: 0; top: auto;
        padding: 0; gap: 0;
        background: var(--surface);
        border-top: 1px solid var(--border2);
        border-bottom: none;
        z-index: 150;
        box-shadow: 0 -4px 20px rgba(0,0,0,.5);
    }
    .nav-tab {
        flex: 1; min-width: 0;
        flex-direction: column;
        align-items: center; justify-content: center;
        padding: 8px 2px calc(10px + env(safe-area-inset-bottom));
        font-size: .58rem; gap: 3px;
        border-bottom: none;
        border-top: 3px solid transparent;
        margin-bottom: 0;
        text-align: center;
        position: relative;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .nav-tab .tab-icon { font-size: 1.25rem; line-height: 1; display: block; }
    .nav-tab.active { border-top-color: var(--gold); border-bottom-color: transparent; }
    .nav-tab .tab-badge {
        position: absolute; top: 4px; right: calc(50% - 24px);
        font-size: .58rem; padding: 1px 5px;
    }
    /* Espace en bas pour ne rien masquer derrière la barre d'onglets */
    body { padding-bottom: calc(76px + env(safe-area-inset-bottom)); }

    /* ── TOOLBAR : en colonne, tout pleine largeur ── */
    .toolbar {
        padding: 12px 14px;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .search-wrap { min-width: unset; width: 100%; }
    select.filter-select { width: 100%; }
    .btn { width: 100%; justify-content: center; }
    .seuil-global { width: 100%; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .seuil-global label { font-size: .76rem; }
    .seuil-global .btn { width: auto; flex: 1; }

    /* ── MAIN ── */
    .main { padding: 12px 14px; }
    .table-card { background: transparent; border: none; box-shadow: none; border-radius: 0; }

    /* ══ TABLES → CARTES ══ */
    .table-wrap { overflow-x: unset; }
    table, tbody { display: block; width: 100%; }
    thead { display: none; }
    tbody { display: flex; flex-direction: column; gap: 12px; }
    tbody tr {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 14px;
        align-items: start;
        background: var(--surface2);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 14px;
    }
    tbody tr.row-alert {
        border-color: var(--red-border);
        border-left: 3px solid var(--red);
        background: rgba(192,57,43,.06);
    }
    tbody tr:hover { background: var(--surface2); }
    td { padding: 0; font-size: .88rem; min-width: 0; }
    /* Cellule vide / chargement : pleine largeur */
    td[colspan] { grid-column: 1 / -1; }

    /* Étiquette au-dessus de chaque valeur — uniquement si data-label existe */
    td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: .66rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--text-dim);
        margin-bottom: 4px;
    }

    /* ── CARTES STOCKS (12 colonnes) ──
       Rangée 1 : Produit
       Rangée 2 : Catégorie | Statut
       Rangée 3 : Stock + bouton sauver
       Rangée 4 : Barre de niveau
       Rangée 5 : Seuil | Unité
       Rangée 6 : Prix public | Prix pro
       Rangée 7 : Qté pro | Unité pro
       Rangée 8 : Actions                                   */
    #stockTable tbody tr[data-id] { grid-template-columns: 1fr 1fr; }
    #stockTable tr[data-id] td:nth-child(1)  { grid-area: 1 / 1 / 2 / -1; font-size: 1rem; }
    #stockTable tr[data-id] td:nth-child(2)  { grid-area: 2 / 1; }
    #stockTable tr[data-id] td:nth-child(11) { grid-area: 2 / 2; justify-self: end; }
    #stockTable tr[data-id] td:nth-child(11)::before { display: none; } /* le badge se suffit */
    #stockTable tr[data-id] td:nth-child(3)  { grid-area: 3 / 1 / 4 / -1; }
    #stockTable tr[data-id] td:nth-child(4)  { grid-area: 4 / 1 / 5 / -1; }
    #stockTable tr[data-id] td:nth-child(5)  { grid-area: 5 / 1; }
    #stockTable tr[data-id] td:nth-child(6)  { grid-area: 5 / 2; }
    #stockTable tr[data-id] td:nth-child(7)  { grid-area: 6 / 1; }
    #stockTable tr[data-id] td:nth-child(8)  { grid-area: 6 / 2; }
    #stockTable tr[data-id] td:nth-child(9)  { grid-area: 7 / 1; }
    #stockTable tr[data-id] td:nth-child(10) { grid-area: 7 / 2; }
    #stockTable tr[data-id] td:nth-child(12) { grid-area: 8 / 1 / 9 / -1; }

    /* Champs et boutons des cartes */
    .stock-cell { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .stock-input { flex: 1; min-width: 80px; max-width: 120px; }
    .save-btn { flex: 1; text-align: center; padding: 9px 12px; font-size: .82rem; }
    .seuil-input, .unite-input { width: 100%; max-width: 110px; }

    /* Ligne Actions (le td a un style inline display:flex) */
    #stockTable td[data-label="Actions"],
    #ingrTable  td[data-label="Actions"] {
        display: flex !important;
        gap: 8px;
        align-items: stretch;
    }
    #stockTable td[data-label="Actions"]::before,
    #ingrTable  td[data-label="Actions"]::before { display: none; }
    #stockTable td[data-label="Actions"] .save-btn,
    #stockTable td[data-label="Actions"] .action-btn,
    #ingrTable  td[data-label="Actions"] .save-btn,
    #ingrTable  td[data-label="Actions"] .action-btn {
        flex: 1;
        display: flex; align-items: center; justify-content: center;
        padding: 9px 12px; font-size: .82rem;
    }

    /* ── CARTES INGRÉDIENTS (7 colonnes) ── */
    #ingrTable tr[data-id] td:nth-child(1) { grid-column: 1 / -1; font-size: 1rem; }
    #ingrTable tr[data-id] td:nth-child(2) { grid-column: 1 / -1; }
    #ingrTable tr[data-id] td:nth-child(6) { justify-self: end; }
    #ingrTable tr[data-id] td:nth-child(6)::before { display: none; }
    #ingrTable tr[data-id] td:last-child   { grid-column: 1 / -1; }

    /* ── UTILISATEURS : carte compacte avatar / infos / action ── */
    #usersTable tbody tr {
        grid-template-columns: auto 1fr;
        gap: 6px 12px;
        align-items: center;
    }
    #usersTable td:nth-child(1) { grid-column: 1 / -1; }
    #usersTable td:nth-child(2) { grid-column: 1 / -1; word-break: break-all; }
    #usersTable td:nth-child(3) { grid-column: 1; }
    #usersTable td:nth-child(4) { grid-column: 2; justify-self: end; }
    #usersTable td:nth-child(5) { grid-column: 1 / -1; margin-top: 4px; }

    /* ── NEWSLETTER : tableau abonnés en défilement horizontal ──
       (trop de colonnes interactives pour des cartes lisibles) */
    #nlSubTable { display: table; min-width: 700px; }
    #nlSubTable thead { display: table-header-group; }
    #nlSubTable tbody { display: table-row-group; }
    #nlSubTable tbody tr {
        display: table-row;
        background: transparent;
        border: none; border-radius: 0; padding: 0;
    }
    #nlSubTable td { display: table-cell; padding: 12px 14px; }
    .page#page-newsletter .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .nl-stats { gap: 8px; }
    .nl-stat { min-width: calc(50% - 8px); padding: 10px 12px; }
    .nl-tabs { flex-wrap: wrap; }

    /* ── SALONS : cartes empilées ── */
    #salon-list > div[style*="grid-template-columns"] {
        grid-template-columns: 56px 1fr !important;
        gap: 12px !important;
        padding: 16px !important;
    }
    #salon-list > div[style*="grid-template-columns"] > div:last-child {
        grid-column: 1 / -1;
        flex-direction: row !important;
    }
    #salon-list > div[style*="grid-template-columns"] > div:last-child .action-btn { flex: 1; }

    /* ── MODALS : plein écran depuis le bas ── */
    .modal-overlay { align-items: flex-end; }
    .modal {
        border-radius: 20px 20px 0 0;
        max-width: 100%;
        max-height: 92vh;
        overflow-y: auto;
        padding: 24px 18px calc(28px + env(safe-area-inset-bottom));
        animation: modalUp .3s ease;
    }
    @keyframes modalUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full { grid-column: 1; }
    /* Sous-grilles "tarification entreprise" en colonne */
    .form-group.full > div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
    .nl-modal { max-height: 92vh; }
    .nl-mfooter .btn { flex: 1; }
    .modal-footer { flex-wrap: wrap; }
    .modal-footer .btn { flex: 1; }

    /* ── TOASTS au-dessus de la barre d'onglets ── */
    .toast-container { bottom: calc(84px + env(safe-area-inset-bottom)); right: 14px; left: 14px; }
    .toast { font-size: .82rem; }

    /* ── Stats mini en haut de page stocks ── */
    .mobile-stats {
        display: flex !important;
        gap: 10px;
        padding: 12px 14px;
        background: var(--surface);
        border-bottom: 1px solid var(--border);
    }
    .mobile-stats .stat-badge { flex: 1; padding: 8px 6px; }
    .mobile-stats .stat-badge .num { font-size: 1.2rem; }
}

/* Desktop : masquer mobile-stats */
.mobile-stats { display: none; }

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
        <span class="tab-icon">📦</span> Stocks
    </button>
    <button class="nav-tab" onclick="switchPage('ingredients', this)" id="tab-ingredients">
        <span class="tab-icon">🌿</span> Ingrédients
        <span class="tab-badge" id="badge-ingr" style="display:none">!</span>
    </button>
    <button class="nav-tab" onclick="switchPage('users', this)" id="tab-users">
        <span class="tab-icon">👥</span> Clients
    </button>
    <button class="nav-tab" onclick="switchPage('newsletter', this)" id="tab-newsletter">
        <span class="tab-icon">✉️</span> Newsletter
        <span class="tab-badge" id="badge-nl" style="display:none">0</span>
    </button>
    <button class="nav-tab" onclick="switchPage('salons', this)" id="tab-salons">
        <span class="tab-icon">🏪</span> Salons
    </button>
</nav>


<!-- ===================== PAGE SALONS ===================== -->
<div class="page" id="page-salons">
<div class="toolbar">
    <div style="flex:1">
        <span style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--cream)">🏪 Gestion des salons</span>
        <span style="font-size:.8rem;color:var(--text-dim);margin-left:10px">Planning visible sur salon.php</span>
    </div>
    <button class="btn btn-primary" onclick="salonOpenModal()">＋ Ajouter un salon</button>
</div>

<div class="main">
  <div id="salon-list" style="display:flex;flex-direction:column;gap:14px;max-width:900px;margin:0 auto;padding-top:8px;">
    <div style="text-align:center;color:var(--text-dim);padding:3rem;">Chargement…</div>
  </div>
</div>

<!-- Modal salon -->
<div class="modal-overlay" id="salon-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9000;display:flex !important;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s">
  <div class="modal" style="background:#1c1610;border:1px solid var(--border);border-radius:18px;padding:32px;width:min(600px,95vw);max-height:90vh;overflow-y:auto;transform:scale(.96);transition:transform .25s">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
      <h3 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--cream)" id="salon-modal-title">Nouveau salon</h3>
      <button onclick="salonCloseModal()" style="background:none;border:none;color:var(--text-dim);font-size:1.4rem;cursor:pointer">✕</button>
    </div>
    <input type="hidden" id="salon-id">
    <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="form-group full" style="grid-column:1/-1">
        <label class="form-label">Nom du salon *</label>
        <input class="form-input" type="text" id="salon-nom" placeholder="Ex : Salon Saveurs d'Afrique 2025">
      </div>
      <div class="form-group">
        <label class="form-label">Lieu / Espace *</label>
        <input class="form-input" type="text" id="salon-lieu" placeholder="Ex : Parc des Expositions">
      </div>
      <div class="form-group">
        <label class="form-label">Ville *</label>
        <input class="form-input" type="text" id="salon-ville" placeholder="Ex : Paris">
      </div>
      <div class="form-group full" style="grid-column:1/-1">
        <label class="form-label">Adresse complète</label>
        <input class="form-input" type="text" id="salon-adresse" placeholder="Ex : 1 Place de la Porte de Versailles">
      </div>
      <div class="form-group">
        <label class="form-label">Date de début *</label>
        <input class="form-input" type="date" id="salon-date-debut">
      </div>
      <div class="form-group">
        <label class="form-label">Date de fin *</label>
        <input class="form-input" type="date" id="salon-date-fin">
      </div>
      <div class="form-group">
        <label class="form-label">Heure d'ouverture</label>
        <input class="form-input" type="time" id="salon-heure-debut" value="10:00">
      </div>
      <div class="form-group">
        <label class="form-label">Heure de fermeture</label>
        <input class="form-input" type="time" id="salon-heure-fin" value="18:00">
      </div>
      <div class="form-group">
        <label class="form-label">N° / Nom du stand</label>
        <input class="form-input" type="text" id="salon-stand" placeholder="Ex : Stand B42">
      </div>
      <div class="form-group">
        <label class="form-label">Actif (visible sur le site)</label>
        <select class="form-input" id="salon-actif">
          <option value="1">✅ Oui — visible</option>
          <option value="0">❌ Non — masqué</option>
        </select>
      </div>
      <div class="form-group full" style="grid-column:1/-1">
        <label class="form-label">Description</label>
        <textarea class="form-input" id="salon-desc" rows="3" placeholder="Quelques mots pour donner envie…" style="resize:vertical"></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:24px;justify-content:flex-end">
      <button class="btn" onclick="salonCloseModal()" style="background:var(--surface2);border:1px solid var(--border);color:var(--text-dim)">Annuler</button>
      <button class="btn btn-primary" onclick="salonSave()" id="salon-save-btn">Enregistrer</button>
    </div>
  </div>
</div>
</div>

<!-- ===================== PAGE STOCKS ===================== -->
<div class="page active" id="page-stocks">
    <!-- Stats mini visible uniquement sur mobile -->
    <div class="mobile-stats">
        <div class="stat-badge">
            <span class="num" id="stat-total-m">—</span>
            <span class="label">Produits</span>
        </div>
        <div class="stat-badge">
            <span class="num" id="stat-ok-m">—</span>
            <span class="label">OK</span>
        </div>
        <div class="stat-badge alert-badge">
            <span class="num" id="stat-alert-m">—</span>
            <span class="label">⚠ Alertes</span>
        </div>
    </div>
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
        <button class="btn btn-ghost" onclick="openCatManager()">
            🏷 Catégories
        </button>
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
                            <th>Prix public</th>
                            <th>Prix pro 🏢</th>
                            <th>Qté pro</th>
                            <th>Unité pro</th>
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

<!-- ===================== PAGE NEWSLETTER ===================== -->
<div class="page" id="page-newsletter">
    <!-- Sous-onglets -->
    <div class="toolbar" style="border-bottom:none; padding-bottom:0;">
        <div class="nl-tabs">
            <button class="nl-tab active" onclick="nlSwitchTab('abonnes', this)">📋 Abonnés</button>
            <button class="nl-tab" onclick="nlSwitchTab('campagnes', this)">✉️ Campagnes</button>
            <button class="nl-tab" onclick="nlSwitchTab('rediger', this)">✍️ Rédiger</button>
        </div>
    </div>

    <!-- ---- SOUS-PAGE : ABONNÉS ---- -->
    <div class="nl-page active" id="nl-page-abonnes">
        <main class="main" style="padding-top:0;">
            <!-- Stats -->
            <div class="nl-stats" id="nl-stats-wrap">
                <div class="nl-stat"><div class="ico">📧</div><div><div class="num" id="nl-stat-total">—</div><div class="lbl">Total abonnés</div></div></div>
                <div class="nl-stat"><div class="ico">✅</div><div><div class="num" id="nl-stat-actifs">—</div><div class="lbl">Actifs</div></div></div>
                <div class="nl-stat"><div class="ico">📤</div><div><div class="num" id="nl-stat-camps">—</div><div class="lbl">Campagnes</div></div></div>
                <div class="nl-stat"><div class="ico">📨</div><div><div class="num" id="nl-stat-sent">—</div><div class="lbl">Emails envoyés</div></div></div>
            </div>
            <!-- Toolbar abonnés -->
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; align-items:center;">
                <div class="search-wrap" style="max-width:280px;">
                    <span class="ico">🔍</span>
                    <input type="text" id="nl-search-sub" placeholder="Rechercher…" oninput="nlFilterSubs()">
                </div>
                <select id="nl-filter-source" class="filter-select" onchange="nlFilterSubs()">
                    <option value="">Toutes sources</option>
                    <option value="newsletter">Newsletter</option>
                    <option value="compte">Compte client</option>
                    <option value="manuel">Manuel</option>
                </select>
                <select id="nl-filter-actif" class="filter-select" onchange="nlFilterSubs()">
                    <option value="">Tous statuts</option>
                    <option value="1">Actifs</option>
                    <option value="0">Inactifs</option>
                </select>
                <button class="btn btn-ghost" onclick="nlSyncClients(this)">🔄 Sync. clients</button>
                <button class="btn btn-primary" onclick="nlOpenAddSub()">＋ Ajouter</button>
            </div>
            <!-- Table abonnés -->
            <div class="table-card">
                <div class="table-wrap">
                    <table id="nlSubTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="nl-check-all" onchange="nlToggleAllSubs(this)" style="accent-color:var(--gold);width:15px;height:15px;cursor:pointer;"></th>
                                <th>Email</th>
                                <th>Nom</th>
                                <th>Source</th>
                                <th>Statut</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="nl-tbody-sub">
                            <tr><td colspan="7"><div class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Bulk bar -->
            <div class="bulk-bar" id="nl-bulk-bar">
                <span id="nl-bulk-count" style="color:var(--gold);font-weight:600;font-size:.88rem;"></span>
                <button class="btn btn-primary" style="padding:7px 14px;font-size:.82rem;" onclick="nlSendToSelected()">📤 Envoyer une campagne</button>
                <button class="btn btn-danger"  style="padding:7px 14px;font-size:.82rem;" onclick="nlDeleteSelected()">🗑 Supprimer</button>
            </div>
        </main>
    </div>

    <!-- ---- SOUS-PAGE : CAMPAGNES ---- -->
    <div class="nl-page" id="nl-page-campagnes">
        <main class="main" style="padding-top:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                <span style="color:var(--text-dim);font-size:.85rem;" id="nl-camp-count"></span>
                <button class="btn btn-primary" onclick="nlSwitchTab('rediger', document.querySelectorAll('.nl-tab')[2])">✍️ Nouvelle campagne</button>
            </div>
            <div id="nl-camp-list-wrap">
                <div class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></div>
            </div>
        </main>
    </div>

    <!-- ---- SOUS-PAGE : RÉDIGER ---- -->
    <div class="nl-page" id="nl-page-rediger">
        <main class="main" style="padding-top:0; max-width:900px;">
            <!-- Sujet + actions -->
            <div class="table-card" style="padding:22px 26px; margin-bottom:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                    <h3 style="font-family:'Playfair Display',serif;color:var(--cream);font-size:1.1rem;margin:0;">✍️ Rédiger une campagne</h3>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-ghost" style="padding:7px 14px;font-size:.82rem;" onclick="nlOpenPreview()">👁 Aperçu</button>
                        <button class="btn btn-ghost" style="padding:7px 14px;font-size:.82rem;" onclick="nlSaveDraft()">💾 Brouillon</button>
                        <button class="btn btn-primary" style="padding:7px 14px;font-size:.82rem;" onclick="nlOpenSendModal()">📤 Envoyer</button>
                    </div>
                </div>
                <input type="hidden" id="nl-compose-id">
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);">Sujet *</label>
                    <input type="text" id="nl-compose-sujet" placeholder="Ex : 🌿 Nos nouveautés de printemps sont arrivées !" style="padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;width:100%;transition:border-color .2s;">
                </div>
                <!-- Éditeur riche -->
                <div>
                    <label style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);display:block;margin-bottom:8px;">Contenu *</label>
                    <div class="nl-editor-toolbar">
                        <button class="nl-editor-btn" onclick="nlCmd('bold')"><b>B</b></button>
                        <button class="nl-editor-btn" onclick="nlCmd('italic')"><i>I</i></button>
                        <button class="nl-editor-btn" onclick="nlCmd('underline')"><u>U</u></button>
                        <div class="nl-editor-sep"></div>
                        <button class="nl-editor-btn" onclick="nlCmd('formatBlock','h1')">H1</button>
                        <button class="nl-editor-btn" onclick="nlCmd('formatBlock','h2')">H2</button>
                        <button class="nl-editor-btn" onclick="nlCmd('formatBlock','p')">¶</button>
                        <div class="nl-editor-sep"></div>
                        <button class="nl-editor-btn" onclick="nlCmd('insertUnorderedList')">• Liste</button>
                        <button class="nl-editor-btn" onclick="nlCmd('insertOrderedList')">1. Liste</button>
                        <button class="nl-editor-btn" onclick="nlCmd('formatBlock','blockquote')">❝</button>
                        <div class="nl-editor-sep"></div>
                        <button class="nl-editor-btn" onclick="nlCmd('justifyLeft')">⬅</button>
                        <button class="nl-editor-btn" onclick="nlCmd('justifyCenter')">↔</button>
                        <button class="nl-editor-btn" onclick="nlCmd('justifyRight')">➡</button>
                        <div class="nl-editor-sep"></div>
                        <label style="display:flex;align-items:center;gap:5px;padding:4px 8px;border:1px solid var(--border);border-radius:5px;cursor:pointer;color:var(--text-dim);font-size:.78rem;">
                            A <input type="color" value="#f5edd8" onchange="nlCmd('foreColor',this.value)" style="width:16px;height:16px;padding:0;border:none;background:none;cursor:pointer;">
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;padding:4px 8px;border:1px solid var(--border);border-radius:5px;cursor:pointer;color:var(--text-dim);font-size:.78rem;">
                            🖌 <input type="color" value="#c9963b" onchange="nlCmd('hiliteColor',this.value)" style="width:16px;height:16px;padding:0;border:none;background:none;cursor:pointer;">
                        </label>
                        <div class="nl-editor-sep"></div>
                        <button class="nl-editor-btn" onclick="nlInsertLink()">🔗 Lien</button>
                        <button class="nl-editor-btn" onclick="nlInsertImg()">🖼 Image</button>
                        <button class="nl-editor-btn" onclick="nlInsertVar('{{NOM}}')" style="color:var(--gold);">{{NOM}}</button>
                        <div class="nl-editor-sep"></div>
                        <button class="nl-editor-btn" onclick="nlCmd('removeFormat')">✕ Format</button>
                        <button class="nl-editor-btn" onclick="nlClearEditor()" style="color:#e74c3c;">🗑 Vider</button>
                    </div>
                    <div id="nlRichEditor" contenteditable="true" spellcheck="true"></div>
                </div>
            </div>
            <!-- Templates -->
            <div class="table-card" style="padding:18px 22px;">
                <div style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--gold);margin-bottom:10px;">✦ Templates rapides</div>
                <div class="tpl-chips">
                    <button class="tpl-chip" onclick="nlLoadTemplate('promo')">🎁 Promotion</button>
                    <button class="tpl-chip" onclick="nlLoadTemplate('nouveaute')">🌿 Nouveauté</button>
                    <button class="tpl-chip" onclick="nlLoadTemplate('bienvenue')">👋 Bienvenue</button>
                    <button class="tpl-chip" onclick="nlLoadTemplate('recette')">🍽 Recette</button>
                    <button class="tpl-chip" onclick="nlLoadTemplate('evenement')">📅 Événement</button>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ===================== MODALS NEWSLETTER ===================== -->

<!-- Modal : Ajouter abonné -->
<div class="nl-modal-overlay" id="nl-modal-add-sub">
    <div class="nl-modal">
        <div class="nl-mheader">
            <span class="nl-mtitle">➕ Ajouter un abonné</span>
            <button class="modal-close" onclick="nlCloseModal('add-sub')">✕</button>
        </div>
        <div class="nl-mbody">
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div class="form-group"><label>Email *</label><input type="email" id="nl-add-email" placeholder="email@exemple.com"></div>
                <div class="form-group"><label>Nom (facultatif)</label><input type="text" id="nl-add-nom" placeholder="Nom affiché dans les emails"></div>
            </div>
        </div>
        <div class="nl-mfooter">
            <button class="btn btn-ghost" onclick="nlCloseModal('add-sub')">Annuler</button>
            <button class="btn btn-primary" onclick="nlSubmitAddSub()">Ajouter</button>
        </div>
    </div>
</div>

<!-- Modal : Envoi -->
<div class="nl-modal-overlay" id="nl-modal-send">
    <div class="nl-modal nl-modal--xl">
        <div class="nl-mheader">
            <span class="nl-mtitle">📤 Envoyer la campagne</span>
            <button class="modal-close" onclick="nlCloseModal('send')">✕</button>
        </div>
        <div class="nl-mbody">
            <!-- Résumé campagne -->
            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:18px;">
                <div style="font-size:.72rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Campagne</div>
                <div style="font-weight:600;color:var(--cream);" id="nl-send-sujet"></div>
            </div>

            <!-- Choix du type de destinataires -->
            <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);margin-bottom:10px;">Qui doit recevoir cet email ?</div>

            <div class="dest-option active" id="nl-dest-tous" onclick="nlSelectDest('tous')">
                <input type="radio" name="nl-dest" value="tous" checked>
                <div>
                    <div class="dest-lbl">📋 Abonnés newsletter actifs</div>
                    <div class="dest-sub" id="nl-dest-tous-count">—</div>
                </div>
            </div>
            <div class="dest-option" id="nl-dest-tous_clients" onclick="nlSelectDest('tous_clients')">
                <input type="radio" name="nl-dest" value="tous_clients">
                <div>
                    <div class="dest-lbl">👥 Tous les clients (comptes)</div>
                    <div class="dest-sub" id="nl-dest-clients-count">—</div>
                </div>
            </div>
            <div class="dest-option" id="nl-dest-tous_les_deux" onclick="nlSelectDest('tous_les_deux')">
                <input type="radio" name="nl-dest" value="tous_les_deux">
                <div>
                    <div class="dest-lbl">✦ Abonnés + Clients (fusionnés, sans doublons)</div>
                    <div class="dest-sub" id="nl-dest-both-count">—</div>
                </div>
            </div>
            <div class="dest-option" id="nl-dest-selection" onclick="nlSelectDest('selection')">
                <input type="radio" name="nl-dest" value="selection">
                <div>
                    <div class="dest-lbl">🎯 Sélection manuelle</div>
                    <div class="dest-sub">Choisissez précisément les destinataires parmi abonnés et clients</div>
                </div>
            </div>

            <!-- Sélection manuelle -->
            <div id="nl-sub-selector" style="display:none;margin-top:14px;">
                <!-- Filtres -->
                <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                    <input type="text" id="nl-search-send" placeholder="🔍 Filtrer par nom ou email…" oninput="nlFilterSendSubs()" style="flex:1;min-width:180px;padding:8px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;color:var(--text);font-size:.83rem;outline:none;">
                    <select id="nl-filter-send-type" onchange="nlFilterSendSubs()" style="padding:8px 12px;background:var(--bg);border:1px solid var(--border);border-radius:7px;color:var(--text-dim);font-size:.83rem;outline:none;cursor:pointer;">
                        <option value="">Tous types</option>
                        <option value="newsletter">📋 Abonnés</option>
                        <option value="client">👥 Clients</option>
                    </select>
                </div>
                <div class="nl-sub-list">
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(201,150,59,.05);border-bottom:1px solid var(--border);cursor:pointer;font-size:.8rem;color:var(--gold);" onclick="nlToggleAllSend()">
                        <input type="checkbox" id="nl-check-all-send" style="accent-color:var(--gold);width:14px;height:14px;"> Tout sélectionner / désélectionner
                    </div>
                    <div id="nl-send-sub-list">
                        <div style="padding:20px;text-align:center;color:var(--text-dim);font-size:.85rem;">⏳ Chargement…</div>
                    </div>
                </div>
                <div style="margin-top:8px;font-size:.8rem;color:var(--gold);font-weight:500;" id="nl-send-sel-count">0 sélectionné(s)</div>
            </div>
        </div>
        <div class="nl-mfooter">
            <button class="btn btn-ghost" onclick="nlCloseModal('send')">Annuler</button>
            <button class="btn btn-primary" id="nl-btn-confirm-send" onclick="nlConfirmSend()">📤 Confirmer l'envoi</button>
        </div>
    </div>
</div>

<!-- Modal : Aperçu -->
<div class="nl-modal-overlay" id="nl-modal-preview">
    <div class="nl-modal nl-modal--lg">
        <div class="nl-mheader">
            <span class="nl-mtitle">👁 Aperçu</span>
            <button class="modal-close" onclick="nlCloseModal('preview')">✕</button>
        </div>
        <div class="nl-mbody">
            <div style="background:var(--surface2);border-radius:6px;padding:8px 12px;margin-bottom:12px;font-size:.83rem;color:var(--text-dim);">
                Sujet : <span id="nl-preview-sujet" style="color:var(--cream);font-weight:500;"></span>
            </div>
            <iframe id="nlPreviewFrame" class="nl-preview-frame" frameborder="0" style="width:100%;min-height:420px;border-radius:8px;"></iframe>
        </div>
        <div class="nl-mfooter">
            <button class="btn btn-ghost" onclick="nlCloseModal('preview')">Fermer</button>
            <button class="btn btn-primary" onclick="nlCloseModal('preview');nlOpenSendModal();">📤 Envoyer</button>
        </div>
    </div>
</div>

<!-- MODAL ÉDITION PRODUIT -->
<div class="modal-overlay" id="modal-edit-produit">
    <div class="modal modal--wide">
        <div class="modal-header">
            <div class="modal-title">✏ Modifier le produit</div>
            <button class="modal-close" onclick="closeModal('edit-produit')">✕</button>
        </div>
        <div class="form-grid">
            <div class="form-group full">
                <label>Nom du produit *</label>
                <input type="text" id="ep-nom" placeholder="Nom du produit">
            </div>
            <div class="form-group">
                <label>Catégorie</label>
                <select id="ep-cat">
                    <option value="">— Choisir une catégorie —</option>
                </select>
                <button type="button" onclick="openCatManager()" style="background:none;border:none;color:var(--gold);font-size:.76rem;cursor:pointer;text-align:left;padding:2px 0 0;font-family:'DM Sans',sans-serif;">＋ Créer / gérer les catégories</button>
            </div>
            <div class="form-group">
                <label>Unité</label>
                <select id="ep-unite">
                    <option value="g">Grammes (g)</option>
                    <option value="kg">Kilogrammes (kg)</option>
                    <option value="pièce">Pièce</option>
                    <option value="sachet">Sachet</option>
                    <option value="boîte">Boîte</option>
                </select>
            </div>
            <div class="form-group">
                <label>Stock actuel</label>
                <input type="number" id="ep-stock" min="0">
            </div>
            <div class="form-group">
                <label>Seuil d'alerte</label>
                <input type="number" id="ep-seuil" min="0">
            </div>
            <div class="form-group">
                <label>Prix public (€)</label>
                <input type="number" id="ep-prix" step="0.01" min="0">
            </div>
            <div class="form-group full" style="background:rgba(201,150,59,.07);border:1px solid var(--gold-dim);border-radius:10px;padding:14px 16px;">
                <label style="color:var(--gold);font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:12px;">🏢 Tarification entreprise</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group"><label>Prix pro (€)</label><input type="number" id="ep-prix-ent" placeholder="ex: 8.50" step="0.01" min="0"></div>
                    <div class="form-group"><label>Quantité pro</label><input type="number" id="ep-qte-pro" placeholder="ex: 500" min="0" step="0.001"></div>
                    <div class="form-group"><label>Unité pro</label><select id="ep-unite-pro"><option value="">— Aucune —</option><option value="g">g</option><option value="kg">kg</option><option value="L">L</option><option value="ml">ml</option><option value="pièce">Pièce</option><option value="sachet">Sachet</option><option value="boîte">Boîte</option><option value="carton">Carton</option></select></div>
                </div>
            </div>
        </div>

        <!-- ── Section images ── -->
        <div class="img-section">
            <div class="img-section-title">🖼 Images du carousel (page Découvrir)</div>

            <!-- Images existantes -->
            <div class="img-grid" id="ep-img-grid">
                <span class="img-empty">Chargement…</span>
            </div>

            <!-- Zone d'upload de nouvelles images -->
            <div class="img-upload-zone" id="ep-upload-zone"
                 ondragover="event.preventDefault();this.classList.add('drag-over')"
                 ondragleave="this.classList.remove('drag-over')"
                 ondrop="handleImgDrop(event)">
                <label class="img-upload-label" for="ep-img-input">
                    📁 <span>Choisir des images</span> ou glisser-déposer ici
                </label>
                <input type="file" id="ep-img-input" accept="image/jpeg,image/png,image/webp,image/gif"
                       multiple onchange="handleImgSelect(this.files)">
                <div class="img-upload-preview" id="ep-img-preview"></div>
                <div class="img-uploading" id="ep-img-status"></div>
            </div>
        </div>

        <input type="hidden" id="ep-id">
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('edit-produit')">Annuler</button>
            <button class="btn btn-primary" onclick="saveEditProduit()">💾 Enregistrer</button>
        </div>
    </div>
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
                <select id="f-cat">
                    <option value="">— Choisir une catégorie —</option>
                </select>
                <button type="button" onclick="openCatManager()" style="background:none;border:none;color:var(--gold);font-size:.76rem;cursor:pointer;text-align:left;padding:2px 0 0;font-family:'DM Sans',sans-serif;">＋ Créer / gérer les catégories</button>
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
                <label>Prix public (€)</label>
                <input type="number" id="f-prix" placeholder="0.00" step="0.01" min="0" value="0">
            </div>
            <div class="form-group full" style="background:rgba(201,150,59,.07);border:1px solid var(--gold-dim);border-radius:10px;padding:14px 16px;">
                <label style="color:var(--gold);font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;display:block;margin-bottom:12px;">🏢 Tarification entreprise</label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group"><label>Prix pro (€)</label><input type="number" id="f-prix-ent" placeholder="ex: 8.50" step="0.01" min="0"></div>
                    <div class="form-group"><label>Quantité pro</label><input type="number" id="f-qte-pro" placeholder="ex: 500" min="0" step="0.001"></div>
                    <div class="form-group"><label>Unité pro</label><select id="f-unite-pro"><option value="">— Aucune —</option><option value="g">g</option><option value="kg">kg</option><option value="L">L</option><option value="ml">ml</option><option value="pièce">Pièce</option><option value="sachet">Sachet</option><option value="boîte">Boîte</option><option value="carton">Carton</option></select></div>
                </div>
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

<!-- MODAL GESTION CATÉGORIES -->
<div class="modal-overlay" id="modal-categories">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">🏷 Gérer les catégories</div>
            <button class="modal-close" onclick="closeModal('categories')">✕</button>
        </div>
        <!-- Ajout d'une catégorie -->
        <div style="display:flex;gap:8px;margin-bottom:18px;">
            <input type="text" id="cat-new-nom" placeholder="Nouvelle catégorie (ex: Café, Thé, Épices…)"
                onkeydown="if(event.key==='Enter')addCategorie()"
                style="flex:1;padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;">
            <button class="btn btn-primary" style="width:auto;flex-shrink:0;" onclick="addCategorie()">＋ Ajouter</button>
        </div>
        <!-- Liste des catégories -->
        <div id="cat-list-wrap" style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto;">
            <span style="color:var(--text-dim);font-size:.85rem;">Chargement…</span>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('categories')">Fermer</button>
        </div>
    </div>
</div>

<!-- TOASTS -->
<div class="toast-container" id="toasts"></div>

<script>
// Toujours cibler stock.php directement (même si l'URL affichée est "/stock" sans extension)
const SCRIPT_URL = (function () {
    let base = window.location.href.split('?')[0].split('#')[0];
    // Si l'URL se termine par "/stock" (URL propre), on ajoute ".php"
    if (/\/stock$/.test(base)) base += '.php';
    // Si elle ne finit ni par .php ni par /stock (ex: dossier), on pointe vers stock.php du dossier courant
    else if (!/\.php$/.test(base)) base = base.replace(/\/?$/, '/') + 'stock.php';
    return base;
})();
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
    if (page === 'newsletter'  && nlAllSubs.length === 0)      nlLoadSubs();
    if (page === 'salons'       && allSalons.length === 0)      loadSalons();
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

let allCategoriesNames = [];

async function loadCategories() {
    try {
        const res = await post({ action: 'get_categories' });
        allCategoriesNames = res.data || [];
        refreshCategorySelects();
    } catch(e) {}
}

// Reconstruit les 3 menus déroulants (filtre + ajout + édition)
// en conservant la valeur sélectionnée
function refreshCategorySelects() {
    const filterSel = document.getElementById('filter-cat');
    const fSel      = document.getElementById('f-cat');
    const epSel     = document.getElementById('ep-cat');

    const filterVal = filterSel.value;
    const fVal      = fSel.value;
    const epVal     = epSel.value;

    filterSel.innerHTML = '<option value="">Toutes les catégories</option>';
    fSel.innerHTML      = '<option value="">— Choisir une catégorie —</option>';
    epSel.innerHTML     = '<option value="">— Choisir une catégorie —</option>';

    allCategoriesNames.forEach(cat => {
        [filterSel, fSel, epSel].forEach(sel => {
            const o = document.createElement('option');
            o.value = cat; o.textContent = cat;
            sel.appendChild(o);
        });
    });

    filterSel.value = filterVal;
    fSel.value      = fVal;
    epSel.value     = epVal;
}

// ==========================================
// GESTIONNAIRE DE CATÉGORIES
// ==========================================
function openCatManager() {
    openModal('categories');
    loadCatManager();
}

async function loadCatManager() {
    const wrap = document.getElementById('cat-list-wrap');
    wrap.innerHTML = '<span style="color:var(--text-dim);font-size:.85rem;">Chargement…</span>';
    try {
        const res  = await post({ action: 'get_categories_full' });
        const cats = res.data || [];
        if (!cats.length) {
            wrap.innerHTML = '<span style="color:var(--text-dim);font-size:.85rem;font-style:italic;">Aucune catégorie. Ajoutez-en une ci-dessus (ex: Café, Thé, Épices…).</span>';
            return;
        }
        wrap.innerHTML = cats.map(c => `
            <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;">
                <span style="flex:1;font-weight:500;color:var(--cream);">🏷 ${escHtml(c.nom)}</span>
                <span style="font-size:.72rem;color:var(--text-dim);white-space:nowrap;">${c.nb_produits} produit${c.nb_produits > 1 ? 's' : ''}</span>
                <button class="save-btn" style="background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue);padding:5px 10px;"
                    onclick="renameCategorie(${c.id_categorie}, '${escHtml(c.nom)}')" title="Renommer">✏</button>
                <button class="action-btn" style="padding:5px 10px;"
                    onclick="deleteCategorie(${c.id_categorie}, '${escHtml(c.nom)}', ${c.nb_produits})" title="Supprimer">🗑</button>
            </div>`).join('');
    } catch(e) {
        wrap.innerHTML = '<span style="color:#e74c3c;font-size:.85rem;">Erreur de chargement</span>';
    }
}

async function addCategorie() {
    const input = document.getElementById('cat-new-nom');
    const nom   = input.value.trim();
    if (!nom) { showToast('Saisissez un nom de catégorie', 'error'); return; }
    try {
        await post({ action: 'add_categorie', nom });
        showToast(`Catégorie "${nom}" créée ✓`, 'success');
        input.value = '';
        loadCatManager();
        await loadCategories();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function renameCategorie(id, nomActuel) {
    const nom = prompt('Nouveau nom de la catégorie :', nomActuel);
    if (nom === null) return;
    if (!nom.trim()) { showToast('Le nom ne peut pas être vide', 'error'); return; }
    try {
        await post({ action: 'rename_categorie', id, nom: nom.trim() });
        showToast('Catégorie renommée ✓', 'success');
        loadCatManager();
        await loadCategories();
        loadStocks(); // met à jour les noms affichés dans le tableau
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function deleteCategorie(id, nom, nbProduits) {
    if (nbProduits > 0) {
        showToast(`Impossible : ${nbProduits} produit(s) utilisent "${nom}". Changez d'abord leur catégorie.`, 'error');
        return;
    }
    if (!confirm(`Supprimer la catégorie "${nom}" ?`)) return;
    try {
        await post({ action: 'delete_categorie', id });
        showToast(`Catégorie "${nom}" supprimée`, 'success');
        loadCatManager();
        await loadCategories();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
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
            <td class="td-nom" data-label="Produit">🌿 ${escHtml(p.nom)}</td>
            <td data-label="Catégorie"><span class="td-cat">${escHtml(p.categorie || 'Sans catégorie')}</span></td>
            <td data-label="Stock actuel">
                <div class="stock-cell">
                    <input class="stock-input" type="number" value="${stock}" min="0" onchange="markDirty(this)" data-original="${stock}" id="stock-${p.id}">
                    <button class="save-btn" onclick="saveStock(${p.id})">💾 Sauver</button>
                </div>
            </td>
            <td data-label="Niveau">
                <div class="stock-bar-wrap">
                    <div class="stock-bar"><div class="stock-bar-fill ${barClass}" style="width:${pct}%"></div></div>
                    <span style="font-size:.8rem;color:var(--text-dim);min-width:30px;">${pct}%</span>
                </div>
            </td>
            <td data-label="Seuil alerte"><input class="seuil-input" type="number" value="${seuil}" min="0" onchange="saveSeuil(${p.id}, this.value)" id="seuil-${p.id}"></td>
            <td data-label="Unité"><input class="unite-input" type="text" value="${escHtml(p.unite || 'g')}" onchange="saveUnite(${p.id}, this.value)" id="unite-${p.id}" title="Ex: 100g, 250g, 1kg…"></td>
            <td data-label="Prix public" style="color:var(--gold)">${parseFloat(p.prix || 0).toFixed(2)} €</td>
            <td data-label="Prix pro 🏢" style="color:#e8b860">${p.prix_entreprise != null && p.prix_entreprise !== '' ? parseFloat(p.prix_entreprise).toFixed(2)+' €' : '<span style="color:var(--text-dim);font-size:.78rem">—</span>'}</td>
            <td data-label="Qté pro" style="color:var(--text-dim);font-size:.85rem">${p.qte_pro != null && p.qte_pro !== '' ? parseFloat(p.qte_pro)+' '+(escHtml(p.unite_pro||'')) : '—'}</td>
            <td data-label="Unité pro" style="color:var(--text-dim);font-size:.85rem">${escHtml(p.unite_pro||'—')}</td>
            <td data-label="Statut">${isAlert ? `<span class="alert-icon">⚠ Stock faible</span>` : `<span class="ok-icon">✓</span>`}</td>
            <td data-label="Actions" style="display:flex;gap:6px;">
                <button class="save-btn" style="background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue)" onclick="openEditProduit(${p.id})">✏ Modifier</button>
                <button class="action-btn" onclick="deleteProduit(${p.id}, '${escHtml(p.nom)}')" title="Supprimer">🗑</button>
            </td>
        </tr>`;
    }).join('');
}

function markDirty(input) { input.style.borderColor = 'var(--gold)'; }

function updateStats(products) {
    const alertCount = products.filter(p => parseInt(p.stock) <= parseInt(p.seuil_alerte)).length;
    document.getElementById('stat-total').textContent = products.length;
    document.getElementById('stat-ok').textContent    = products.length - alertCount;
    document.getElementById('stat-alert').textContent = alertCount;
    // Badges mobiles
    document.getElementById('stat-total-m').textContent = products.length;
    document.getElementById('stat-ok-m').textContent    = products.length - alertCount;
    document.getElementById('stat-alert-m').textContent = alertCount;
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

async function saveUnite(id, unite) {
    unite = unite.trim();
    if (!unite) { showToast('L\'unité ne peut pas être vide', 'error'); return; }
    try {
        await post({ action: 'update_unite', id, unite });
        showToast('Unité mise à jour : ' + unite + ' ✓', 'success');
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
    const prix     = document.getElementById('f-prix').value;
    const prixEnt  = document.getElementById('f-prix-ent').value;
    const qtePro   = document.getElementById('f-qte-pro').value;
    const unitePro = document.getElementById('f-unite-pro').value;
    if (!nom) { showToast('Le nom est obligatoire', 'error'); return; }
    try {
        await post({ action: 'add_produit', nom, categorie: cat, stock, seuil, unite, prix, prix_entreprise: prixEnt, qte_pro: qtePro, unite_pro: unitePro });
        showToast(`"${nom}" ajouté ✓`, 'success');
        closeModal('produit');
        ['f-nom','f-cat','f-stock','f-seuil','f-prix','f-prix-ent','f-qte-pro'].forEach(id => document.getElementById(id).value = id === 'f-seuil' ? '10' : (id === 'f-stock' || id === 'f-prix') ? '0' : '');
        document.getElementById('f-unite-pro').value = '';
        loadStocks();
        loadCategoriesRefresh();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function loadCategoriesRefresh() {
    // Recharge les catégories dans tous les menus déroulants
    await loadCategories();
}

async function deleteProduit(id, nom) {
    if (!confirm(`Supprimer "${nom}" définitivement ?`)) return;
    try {
        await post({ action: 'delete_produit', id });
        showToast(`"${nom}" supprimé`, 'success');
        loadStocks();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

function openEditProduit(id) {
    const p = allProducts.find(x => x.id == id);
    if (!p) return;
    document.getElementById('ep-id').value    = p.id;
    document.getElementById('ep-nom').value   = p.nom;
    document.getElementById('ep-stock').value = p.stock;
    document.getElementById('ep-seuil').value = p.seuil_alerte;
    document.getElementById('ep-prix').value      = parseFloat(p.prix || 0).toFixed(2);
    document.getElementById('ep-prix-ent').value   = (p.prix_entreprise != null && p.prix_entreprise !== '') ? parseFloat(p.prix_entreprise).toFixed(2) : '';
    document.getElementById('ep-qte-pro').value    = (p.qte_pro != null && p.qte_pro !== '') ? p.qte_pro : '';
    const selUP = document.getElementById('ep-unite-pro');
    for (let o of selUP.options) { o.selected = (o.value === (p.unite_pro||'')); }
    // Unité
    const sel = document.getElementById('ep-unite');
    const val = p.unite || 'g';
    let found = false;
    for (let opt of sel.options) { if (opt.value === val) { opt.selected = true; found = true; break; } }
    if (!found) {
        const o = document.createElement('option'); o.value = val; o.textContent = val; o.selected = true;
        sel.appendChild(o);
    }
    // Catégorie : sélectionner celle du produit (l'ajouter si absente de la liste)
    const epSel = document.getElementById('ep-cat');
    if (p.categorie && ![...epSel.options].some(o => o.value === p.categorie)) {
        const o = document.createElement('option');
        o.value = p.categorie; o.textContent = p.categorie;
        epSel.appendChild(o);
    }
    epSel.value = p.categorie || '';
    // Reset zone upload
    document.getElementById('ep-img-preview').innerHTML = '';
    document.getElementById('ep-img-status').textContent = '';
    document.getElementById('ep-img-input').value = '';
    // Charger les images existantes
    loadProduitImages(p.id);
    openModal('edit-produit');
}

// ==========================================
// GESTION IMAGES PRODUIT
// ==========================================
async function loadProduitImages(id_produit) {
    const grid = document.getElementById('ep-img-grid');
    grid.innerHTML = '<span class="img-empty">Chargement…</span>';
    try {
        const res = await post({ action: 'get_images', id: id_produit });
        const imgs = res.data || [];
        if (!imgs.length) {
            grid.innerHTML = '<span class="img-empty">Aucune image — ajoutez-en ci-dessous</span>';
            return;
        }
        grid.innerHTML = imgs.map(img => {
            const isCover = parseInt(img.is_cover) === 1;
            return `
            <div class="img-thumb ${isCover ? 'is-cover' : ''}" id="imgthumb-${img.id_image}">
                <img src="${escHtml(img.url_image)}" alt="Image produit" onerror="this.src='images/placeholder.png'">
                <button class="img-thumb__cover-btn"
                    onclick="setCover(${img.id_image}, ${id_produit})"
                    title="${isCover ? 'Photo de couverture (index)' : 'Définir comme couverture index'}">★</button>
                ${isCover ? '<span class="img-cover-badge">INDEX</span>' : ''}
                <button class="img-thumb__del" onclick="deleteProduitImage(${img.id_image}, ${id_produit})" title="Supprimer cette image">✕</button>
            </div>`;
        }).join('');
    } catch(e) {
        grid.innerHTML = '<span class="img-empty" style="color:var(--red)">Erreur de chargement</span>';
    }
}

async function setCover(id_image, id_produit) {
    try {
        await post({ action: 'set_cover', id_image, id_produit });
        showToast('Photo de couverture mise à jour ✓', 'success');
        loadProduitImages(id_produit);
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function deleteProduitImage(id_image, id_produit) {
    if (!confirm('Supprimer cette image ?')) return;
    try {
        await post({ action: 'delete_image', id_image });
        loadProduitImages(id_produit);
        showToast('Image supprimée ✓', 'success');
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

function handleImgDrop(event) {
    event.preventDefault();
    document.getElementById('ep-upload-zone').classList.remove('drag-over');
    const files = event.dataTransfer.files;
    if (files.length) handleImgSelect(files);
}

async function handleImgSelect(files) {
    const id_produit = document.getElementById('ep-id').value;
    if (!id_produit) return;

    const preview  = document.getElementById('ep-img-preview');
    const statusEl = document.getElementById('ep-img-status');
    const allowed  = ['image/jpeg','image/png','image/webp','image/gif'];

    // Prévisualisation immédiate
    for (const file of files) {
        if (!allowed.includes(file.type)) { showToast(`"${file.name}" : format non supporté`, 'error'); continue; }
        const url = URL.createObjectURL(file);
        const img = document.createElement('img');
        img.src = url; preview.appendChild(img);
    }

    // Upload un par un
    let uploaded = 0;
    for (const file of files) {
        if (!allowed.includes(file.type)) continue;
        statusEl.textContent = `Upload en cours… (${uploaded + 1}/${files.length})`;
        try {
            const formData = new FormData();
            formData.append('action', 'add_image');
            formData.append('id_produit', id_produit);
            formData.append('image', file);
            const res  = await fetch(SCRIPT_URL, { method: 'POST', body: formData });
            const json = await res.json();
            if (!json.success) throw new Error(json.error || 'Erreur upload');
            uploaded++;
        } catch(e) { showToast(`Erreur upload "${file.name}" : ` + e.message, 'error'); }
    }

    if (uploaded > 0) {
        statusEl.textContent = `${uploaded} image${uploaded > 1 ? 's' : ''} ajoutée${uploaded > 1 ? 's' : ''} ✓`;
        showToast(`${uploaded} image${uploaded > 1 ? 's' : ''} ajoutée${uploaded > 1 ? 's' : ''} au carousel ✓`, 'success');
        // Recharger la grille et vider la preview
        setTimeout(() => {
            loadProduitImages(id_produit);
            preview.innerHTML = '';
            document.getElementById('ep-img-input').value = '';
            statusEl.textContent = '';
        }, 800);
    } else {
        statusEl.textContent = '';
    }
}

async function saveEditProduit() {
    const id        = document.getElementById('ep-id').value;
    const nom       = document.getElementById('ep-nom').value.trim();
    const categorie = document.getElementById('ep-cat').value.trim();
    const stock     = document.getElementById('ep-stock').value;
    const seuil     = document.getElementById('ep-seuil').value;
    const unite     = document.getElementById('ep-unite').value;
    const prix      = document.getElementById('ep-prix').value;
    const prixEnt   = document.getElementById('ep-prix-ent').value;
    const qtePro    = document.getElementById('ep-qte-pro').value;
    const unitePro  = document.getElementById('ep-unite-pro').value;
    if (!nom) { showToast('Le nom est obligatoire', 'error'); return; }
    try {
        await post({ action: 'update_produit', id, nom, categorie, stock, seuil, unite, prix, prix_entreprise: prixEnt, qte_pro: qtePro, unite_pro: unitePro });
        showToast(`"${nom}" mis à jour ✓`, 'success');
        closeModal('edit-produit');
        loadStocks();
        loadCategoriesRefresh();
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
            <td class="td-nom" data-label="Ingrédient">🌶 ${escHtml(i.nom)}</td>
            <td data-label="Quantité en stock">
                <div class="stock-cell">
                    <input class="stock-input" type="number" value="${qty}" min="0" step="0.1"
                        onchange="markDirty(this)" id="ingr-qty-${i.id}">
                    <button class="save-btn" onclick="saveIngredient(${i.id})">💾 Sauver</button>
                </div>
            </td>
            <td data-label="Unité" style="color:var(--text-dim)">${escHtml(i.unite || 'g')}</td>
            <td data-label="Prix achat" style="color:var(--gold)">${parseFloat(i.prix_achat || 0).toFixed(2)} €</td>
            <td data-label="Seuil alerte"><input class="seuil-input" type="number" value="${seuil}" min="0"
                onchange="saveIngrSeuil(${i.id}, this.value)" id="ingr-seuil-${i.id}"></td>
            <td data-label="Statut">${isAlert ? `<span class="alert-icon">⚠ Stock faible</span>` : `<span class="ok-icon">✓</span>`}</td>
            <td data-label="Actions" style="display:flex;gap:6px;">
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
        const initials   = ((u.prenom||'?')[0] + (u.nom||'?')[0]).toUpperCase();
        const dateStr    = u.created_at ? new Date(u.created_at).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' }) : '—';
        const isEntreprise = u.is_entreprise == 1;
        const rowStyle   = isEntreprise ? 'border-left:3px solid #e74c3c;background:rgba(231,76,60,.06);' : '';
        const avatarStyle= isEntreprise ? 'background:linear-gradient(135deg,#c0392b,#e74c3c);' : '';
        const entrepriseBadge = isEntreprise
            ? `<span style="display:inline-flex;align-items:center;gap:4px;font-size:.62rem;padding:2px 8px;border-radius:999px;background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.4);color:#e74c3c;margin-left:6px">🏢 ${escHtml(u.nom_entreprise || 'Entreprise')}</span>`
            : '';
        const searchStr = (u.nom+' '+u.prenom+' '+u.email+' '+(u.nom_entreprise||'')).toLowerCase();
        return `<tr data-id="${u.id}" data-search="${searchStr}" style="${rowStyle}">
            <td>
                <div class="user-name-cell">
                    <div class="user-avatar" style="${avatarStyle}">${isEntreprise ? '🏢' : escHtml(initials)}</div>
                    <div>
                        <div style="font-weight:500;color:${isEntreprise ? '#e74c3c' : 'var(--cream)'}">
                            ${escHtml(u.prenom || '')} ${escHtml(u.nom || '')}
                            ${entrepriseBadge}
                        </div>
                    </div>
                </div>
            </td>
            <td class="user-email">${escHtml(u.email || '—')}</td>
            <td class="user-tel">${escHtml(u.numero || '—')}</td>
            <td class="user-date">${dateStr}</td>
            <td><button class="action-btn" style="width:100%;justify-content:center;" onclick="deleteUser(${u.id}, '${escHtml((u.prenom||'')+' '+(u.nom||''))}')" title="Supprimer">🗑 Supprimer</button></td>
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
document.getElementById('modal-edit-produit').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal('edit-produit'); });
document.getElementById('modal-produit').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal('produit'); });
document.getElementById('modal-ingredient').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal('ingredient'); });
document.getElementById('modal-categories').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal('categories'); });

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
// NEWSLETTER
// ==========================================
let nlAllSubs  = [];
let nlAllCamps = [];
let nlDestType = 'tous';

// --- Navigation sous-onglets ---
function nlSwitchTab(tab, btn) {
    document.querySelectorAll('.nl-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.nl-page').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.getElementById('nl-page-' + tab).classList.add('active');
    if (tab === 'campagnes') nlLoadCampaigns();
}

// --- Chargement abonnés ---
async function nlLoadSubs() {
    try {
        const res = await post({ action: 'nl_get_subscribers' });
        nlAllSubs = res.data || [];
        nlRenderSubs(nlAllSubs);
        nlUpdateStats();
    } catch(e) { showToast('Erreur newsletter : ' + e.message, 'error'); }
}

function nlRenderSubs(list) {
    const tbody = document.getElementById('nl-tbody-sub');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="icon">📭</div><p>Aucun abonné pour l'instant.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = list.map(s => {
        const init = s.nom ? s.nom.trim().split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2) : s.email[0].toUpperCase();
        const date = s.subscribed_at ? new Date(s.subscribed_at).toLocaleDateString('fr-FR',{day:'2-digit',month:'short',year:'numeric'}) : '—';
        const src  = { newsletter:'<span class="source-badge source-nl">Newsletter</span>', compte:'<span class="source-badge source-compte">Compte</span>', manuel:'<span class="source-badge source-manuel">Manuel</span>' }[s.source] || '';
        const st   = s.actif==1 ? '<span class="alert-icon" style="background:var(--green-bg);border-color:#1e6b40;color:#4ecb78;animation:none;">● Actif</span>' : '<span class="td-cat" style="color:#e74c3c;border-color:var(--red-border);">● Inactif</span>';
        return `<tr data-id="${s.id}" data-search="${escHtml((s.email+' '+(s.nom||'')).toLowerCase())}" data-actif="${s.actif}" data-source="${s.source}">
            <td><input type="checkbox" class="nl-sub-check" value="${s.id}" onchange="nlUpdateBulkBar()" style="accent-color:var(--gold);width:15px;height:15px;cursor:pointer;"></td>
            <td style="font-family:monospace;color:var(--cream);font-size:.85rem;">${escHtml(s.email)}</td>
            <td><div style="display:flex;align-items:center;gap:10px;"><div class="sub-avatar-sm">${escHtml(init)}</div><span>${escHtml(s.nom||'—')}</span></div></td>
            <td>${src}</td>
            <td>${st}</td>
            <td style="font-size:.78rem;color:var(--text-dim);">${date}</td>
            <td><div style="display:flex;gap:6px;">
                <button class="save-btn" style="background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue);" onclick="nlToggleSub(${s.id})" title="${s.actif==1?'Désactiver':'Réactiver'}">${s.actif==1?'⏸':'▶'}</button>
                <button class="action-btn" onclick="nlDeleteSub(${s.id},'${escHtml(s.email)}')" title="Supprimer">🗑</button>
            </div></td>
        </tr>`;
    }).join('');
}

function nlFilterSubs() {
    const search = document.getElementById('nl-search-sub').value.toLowerCase();
    const src    = document.getElementById('nl-filter-source').value;
    const actif  = document.getElementById('nl-filter-actif').value;
    const filt   = nlAllSubs.filter(s =>
        (s.email+' '+(s.nom||'')).toLowerCase().includes(search) &&
        (!src   || s.source   === src) &&
        (!actif || String(s.actif) === actif)
    );
    nlRenderSubs(filt);
}

async function nlToggleSub(id) {
    await post({ action: 'nl_toggle_subscriber', id });
    showToast('Statut mis à jour', 'success');
    nlLoadSubs();
}

async function nlDeleteSub(id, email) {
    if (!confirm(`Supprimer "${email}" de la newsletter ?`)) return;
    await post({ action: 'nl_delete_subscriber', id });
    showToast(`${email} supprimé`, 'success');
    nlLoadSubs();
}

async function nlSyncClients(btn) {
    btn.disabled = true; btn.textContent = '⏳ Sync…';
    try {
        const res = await post({ action: 'nl_sync_clients' });
        showToast(`${res.added} nouveau(x) abonné(s) importé(s)`, 'success');
        nlLoadSubs();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
    btn.disabled = false; btn.textContent = '🔄 Sync. clients';
}

function nlUpdateBulkBar() {
    const n   = document.querySelectorAll('.nl-sub-check:checked').length;
    const bar = document.getElementById('nl-bulk-bar');
    document.getElementById('nl-bulk-count').textContent = n + ' abonné(s) sélectionné(s)';
    bar.classList.toggle('visible', n > 0);
}

function nlToggleAllSubs(cb) {
    document.querySelectorAll('.nl-sub-check').forEach(c => c.checked = cb.checked);
    nlUpdateBulkBar();
}

function nlSendToSelected() {
    const ids = [...document.querySelectorAll('.nl-sub-check:checked')].map(c => c.value);
    if (!ids.length) { showToast('Aucun abonné sélectionné', 'error'); return; }
    nlSwitchTab('rediger', document.querySelectorAll('.nl-tab')[2]);
    setTimeout(() => nlOpenSendModalWithIds(ids), 200);
}

async function nlDeleteSelected() {
    const ids = [...document.querySelectorAll('.nl-sub-check:checked')].map(c => c.value);
    if (!ids.length || !confirm(`Supprimer ${ids.length} abonné(s) ?`)) return;
    for (const id of ids) await post({ action: 'nl_delete_subscriber', id });
    showToast(`${ids.length} abonné(s) supprimé(s)`, 'success');
    nlLoadSubs();
}

// --- Ajouter manuellement ---
function nlOpenAddSub() { nlOpenModal('add-sub'); }
async function nlSubmitAddSub() {
    const email = document.getElementById('nl-add-email').value.trim();
    const nom   = document.getElementById('nl-add-nom').value.trim();
    if (!email) { showToast('Email obligatoire', 'error'); return; }
    try {
        await post({ action: 'nl_add_subscriber', email, nom });
        showToast(`"${email}" ajouté ✓`, 'success');
        nlCloseModal('add-sub');
        document.getElementById('nl-add-email').value = '';
        document.getElementById('nl-add-nom').value   = '';
        nlLoadSubs();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

// --- Stats ---
function nlUpdateStats() {
    const total  = nlAllSubs.length;
    const actifs = nlAllSubs.filter(s => s.actif==1).length;
    const camps  = nlAllCamps.length;
    const sent   = nlAllCamps.reduce((a,c) => a + (parseInt(c.nb_envoyes)||0), 0);
    document.getElementById('nl-stat-total').textContent  = total;
    document.getElementById('nl-stat-actifs').textContent = actifs;
    document.getElementById('nl-stat-camps').textContent  = camps;
    document.getElementById('nl-stat-sent').textContent   = sent;
    // Badge onglet
    const badge = document.getElementById('badge-nl');
    badge.textContent   = total;
    badge.style.display = total > 0 ? '' : 'none';
}

// --- Campagnes ---
async function nlLoadCampaigns() {
    try {
        const res = await post({ action: 'nl_get_campaigns' });
        nlAllCamps = res.data || [];
        nlRenderCampaigns(nlAllCamps);
        nlUpdateStats();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

function nlRenderCampaigns(list) {
    const wrap = document.getElementById('nl-camp-list-wrap');
    document.getElementById('nl-camp-count').textContent = list.length + ' campagne(s)';
    if (!list.length) {
        wrap.innerHTML = `<div class="empty-state"><div class="icon">📭</div><p>Aucune campagne. Rédigez votre première !</p></div>`;
        return;
    }
    wrap.innerHTML = '<div class="campaign-list">' + list.map(c => {
        const date  = new Date(c.sent_at || c.created_at).toLocaleDateString('fr-FR',{day:'2-digit',month:'short',year:'numeric'});
        const badge = c.statut === 'envoye' ? '<span class="badge-sent">✓ Envoyée</span>' : '<span class="badge-draft">💾 Brouillon</span>';
        return `<div class="campaign-card">
            <div class="camp-icon">${c.statut==='envoye'?'📨':'📝'}</div>
            <div class="camp-info">
                <div class="camp-sujet">${escHtml(c.sujet)}</div>
                <div class="camp-meta">${badge}<span>${date}</span>${c.nb_envoyes>0?`<span>📤 ${c.nb_envoyes} envoyé(s)</span>`:''}</div>
            </div>
            <div class="camp-actions">
                <button class="save-btn" style="background:var(--blue-bg);border-color:var(--blue-border);color:var(--blue);" onclick="nlEditCampaign(${c.id})">✏ Modifier</button>
                ${c.statut==='brouillon'?`<button class="save-btn" onclick="nlEditCampaign(${c.id},true)">📤 Envoyer</button>`:''}
                <button class="action-btn" onclick="nlDeleteCampaign(${c.id})" title="Supprimer">🗑</button>
            </div>
        </div>`;
    }).join('') + '</div>';
}

async function nlEditCampaign(id, sendDirect=false) {
    const res = await post({ action: 'nl_get_campaign', id });
    const c   = res.data;
    document.getElementById('nl-compose-id').value    = c.id;
    document.getElementById('nl-compose-sujet').value = c.sujet;
    document.getElementById('nlRichEditor').innerHTML  = c.contenu_html || '';
    nlSwitchTab('rediger', document.querySelectorAll('.nl-tab')[2]);
    if (sendDirect) setTimeout(nlOpenSendModal, 300);
}

async function nlDeleteCampaign(id) {
    if (!confirm('Supprimer cette campagne ?')) return;
    await post({ action: 'nl_delete_campaign', id });
    showToast('Campagne supprimée', 'success');
    nlLoadCampaigns();
}

// --- Éditeur ---
function nlCmd(cmd, val=null) { document.getElementById('nlRichEditor').focus(); document.execCommand(cmd, false, val); }
function nlInsertLink()  { const u=prompt('URL :','https://'); if(u) document.execCommand('createLink',false,u); }
function nlInsertImg()   { const u=prompt('URL image :','https://'); if(u) document.execCommand('insertImage',false,u); }
function nlInsertVar(v)  { document.getElementById('nlRichEditor').focus(); document.execCommand('insertText',false,v); }
function nlClearEditor() { if(confirm('Effacer le contenu ?')){ document.getElementById('nlRichEditor').innerHTML=''; document.getElementById('nl-compose-sujet').value=''; document.getElementById('nl-compose-id').value=''; } }

// --- Templates ---
const NL_TEMPLATES = {
    promo: {
        sujet: '🎁 Offre exclusive — -15% sur votre prochaine commande',
        html: `<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;"><div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;"><h1 style="color:#c9963b;font-size:2rem;margin:0 0 8px;">WakAroma</h1><p style="color:#a89878;margin:0;font-size:.9rem;">Des épices d'exception</p></div><div style="padding:32px;"><p style="color:#5c4a2a;">Bonjour {{NOM}},</p><h2 style="color:#1a1611;">Une offre rien que pour vous ✦</h2><p style="color:#5c4a2a;line-height:1.7;">Bénéficiez de <strong style="color:#c9963b;">-15% sur toute votre prochaine commande</strong>.</p><div style="margin:28px 0;text-align:center;"><a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Profiter de l'offre →</a></div><p style="color:#8a7a62;font-size:.82rem;">Offre valable jusqu'au [DATE]. Non cumulable.</p></div><div style="background:#2a2218;padding:20px 32px;text-align:center;"><p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma</p></div></div>`
    },
    nouveaute: {
        sujet: '🌿 Nouvelle collection — Découvrez nos dernières épices',
        html: `<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;"><div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;"><h1 style="color:#c9963b;font-size:2rem;margin:0 0 8px;">WakAroma</h1><p style="color:#a89878;margin:0;">Les saveurs du monde</p></div><div style="padding:32px;"><p style="color:#5c4a2a;">Bonjour {{NOM}},</p><h2 style="color:#1a1611;">Nos nouvelles épices sont arrivées 🌿</h2><p style="color:#5c4a2a;line-height:1.7;">Nous avons sélectionné pour vous de nouvelles épices rares directement sourced auprès de producteurs passionnés.</p><div style="background:#fff;border:1px solid #e8dcc8;border-radius:8px;padding:20px;margin:20px 0;"><p style="margin:0;color:#5c4a2a;font-style:italic;">✦ [Épice] — [Description]</p></div><div style="text-align:center;margin:24px 0;"><a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Découvrir →</a></div></div><div style="background:#2a2218;padding:20px 32px;text-align:center;"><p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma</p></div></div>`
    },
    bienvenue: {
        sujet: '👋 Bienvenue dans la communauté WakAroma !',
        html: `<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;"><div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:48px 32px;text-align:center;"><h1 style="color:#c9963b;font-size:2.2rem;margin:0 0 8px;">Bienvenue ✦</h1><p style="color:#a89878;margin:0;">Vous faites partie de la famille WakAroma</p></div><div style="padding:32px;"><p style="color:#5c4a2a;font-size:1.05rem;">Bonjour {{NOM}},</p><p style="color:#5c4a2a;line-height:1.7;">Merci de nous rejoindre ! Vous recevrez désormais nos actualités, recettes exclusives et offres en avant-première.</p><div style="border-left:3px solid #c9963b;padding-left:16px;margin:20px 0;color:#8a7a62;font-style:italic;">« Les épices ne sont pas seulement des ingrédients, ce sont des histoires. »</div><div style="text-align:center;margin:28px 0;"><a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Visiter la boutique →</a></div></div><div style="background:#2a2218;padding:20px 32px;text-align:center;"><p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma</p></div></div>`
    },
    recette: {
        sujet: '🍽 Recette du mois — [Titre de la recette]',
        html: `<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;"><div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;"><h1 style="color:#c9963b;font-size:2rem;margin:0 0 8px;">La recette du mois</h1><p style="color:#a89878;margin:0;">[Titre]</p></div><div style="padding:32px;"><p style="color:#5c4a2a;">Bonjour {{NOM}},</p><h3 style="color:#1a1611;margin:16px 0 8px;">Ingrédients</h3><ul style="color:#5c4a2a;line-height:2;padding-left:18px;"><li>[Ingrédient 1]</li><li>[Épice WakAroma]</li></ul><h3 style="color:#1a1611;margin:16px 0 8px;">Préparation</h3><p style="color:#5c4a2a;line-height:1.7;">[Étapes…]</p><div style="text-align:center;margin:24px 0;"><a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Commander les épices →</a></div></div><div style="background:#2a2218;padding:20px 32px;text-align:center;"><p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma</p></div></div>`
    },
    evenement: {
        sujet: '📅 Événement — [Nom]',
        html: `<div style="font-family:Georgia,serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;"><div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;"><p style="color:#a89878;margin:0 0 8px;font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;">[DATE]</p><h1 style="color:#c9963b;font-size:1.8rem;margin:0;">[Nom de l'événement]</h1></div><div style="padding:32px;"><p style="color:#5c4a2a;">Bonjour {{NOM}},</p><p style="color:#5c4a2a;line-height:1.7;">Nous vous invitons à [description].</p><div style="background:#fff;border:1px solid #e8dcc8;border-radius:8px;padding:20px;margin:20px 0;display:flex;gap:20px;flex-wrap:wrap;"><div><strong style="color:#c9963b;">📅</strong> [Date]</div><div><strong style="color:#c9963b;">📍</strong> [Lieu]</div><div><strong style="color:#c9963b;">⏰</strong> [Heure]</div></div><div style="text-align:center;margin:24px 0;"><a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Je participe →</a></div></div><div style="background:#2a2218;padding:20px 32px;text-align:center;"><p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma</p></div></div>`
    }
};

function nlLoadTemplate(key) {
    if (!confirm('Charger ce template ? Le contenu actuel sera remplacé.')) return;
    const t = NL_TEMPLATES[key];
    document.getElementById('nl-compose-sujet').value = t.sujet;
    document.getElementById('nlRichEditor').innerHTML  = t.html;
    document.getElementById('nl-compose-id').value    = '';
    showToast('Template chargé ✓', 'success');
}

// --- Brouillon ---
async function nlSaveDraft() {
    const sujet = document.getElementById('nl-compose-sujet').value.trim();
    const html  = document.getElementById('nlRichEditor').innerHTML.trim();
    const id    = document.getElementById('nl-compose-id').value;
    if (!sujet) { showToast('Le sujet est obligatoire', 'error'); return; }
    if (!html)  { showToast('Le contenu est vide', 'error'); return; }
    try {
        const res = await post({ action: 'nl_save_campaign', id, sujet, contenu_html: html });
        document.getElementById('nl-compose-id').value = res.id;
        showToast('Brouillon sauvegardé ✓', 'success');
        nlLoadCampaigns();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

// --- Aperçu ---
function nlOpenPreview() {
    const sujet = document.getElementById('nl-compose-sujet').value || '(sans sujet)';
    const html  = document.getElementById('nlRichEditor').innerHTML;
    document.getElementById('nl-preview-sujet').textContent = sujet;
    document.getElementById('nlPreviewFrame').srcdoc = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{margin:0;padding:16px;background:#fff;font-family:Georgia,serif;}</style></head><body>${html}</body></html>`;
    nlOpenModal('preview');
}

// --- Envoi modal ---
let nlAllRecipients = []; // liste fusionnée abonnés + clients

async function nlOpenSendModal() {
    const sujet = document.getElementById('nl-compose-sujet').value.trim();
    const html  = document.getElementById('nlRichEditor').innerHTML.trim();
    if (!sujet) { showToast('Le sujet est obligatoire', 'error'); return; }
    if (!html)  { showToast('Le contenu est vide', 'error'); return; }
    document.getElementById('nl-send-sujet').textContent = sujet;

    // Charger tous les destinataires (fusionnés)
    try {
        const res = await post({ action: 'nl_get_all_recipients' });
        nlAllRecipients = res.data || [];
    } catch(e) { nlAllRecipients = []; }

    const subs    = nlAllRecipients.filter(r => r.source === 'newsletter' && r.actif == 1);
    const clients = nlAllRecipients.filter(r => r.source === 'client');
    const both    = nlAllRecipients; // déjà dédupliqués côté serveur

    document.getElementById('nl-dest-tous-count').textContent    = `${subs.length} abonné(s) actif(s)`;
    document.getElementById('nl-dest-clients-count').textContent = `${clients.length} client(s) avec un compte`;
    document.getElementById('nl-dest-both-count').textContent    = `${both.length} destinataire(s) au total (sans doublons)`;

    nlSelectDest('tous');
    nlPopulateSendList(nlAllRecipients);
    nlOpenModal('send');
}

function nlOpenSendModalWithIds(ids) {
    nlOpenSendModal().then ? nlOpenSendModal().then(() => {
        nlSelectDest('selection');
        setTimeout(() => {
            document.querySelectorAll('.nl-send-check').forEach(cb => cb.checked = ids.includes(cb.value));
            nlUpdateSendCount();
        }, 150);
    }) : setTimeout(() => {
        nlSelectDest('selection');
        setTimeout(() => {
            document.querySelectorAll('.nl-send-check').forEach(cb => cb.checked = ids.map(String).includes(cb.value));
            nlUpdateSendCount();
        }, 150);
    }, 400);
}

function nlSelectDest(type) {
    nlDestType = type;
    document.querySelectorAll('.dest-option').forEach(el => el.classList.remove('active'));
    const el = document.getElementById('nl-dest-' + type);
    if (el) el.classList.add('active');
    document.querySelectorAll('.dest-option input[type=radio]').forEach(r => r.checked = (r.value === type));
    document.getElementById('nl-sub-selector').style.display = type === 'selection' ? 'block' : 'none';
}

function nlPopulateSendList(list) {
    const wrap = document.getElementById('nl-send-sub-list');
    if (!list.length) {
        wrap.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-dim);font-size:.85rem;">Aucun destinataire disponible</div>';
        return;
    }
    wrap.innerHTML = list.map(s => {
        const init   = s.nom ? s.nom.trim().split(' ').filter(Boolean).map(w=>w[0]).join('').toUpperCase().slice(0,2) : s.email[0].toUpperCase();
        const isNews = s.source === 'newsletter';
        const srcBadge = isNews
            ? '<span class="source-badge source-nl" style="font-size:.65rem;">📋 Abonné</span>'
            : '<span class="source-badge source-compte" style="font-size:.65rem;">👥 Client</span>';
        const inactive = s.actif == 0 ? ' (inactif)' : '';
        return `<div class="nl-sub-item" data-source="${s.source}" data-search="${(s.email+' '+(s.nom||'')).toLowerCase()}">
            <input type="checkbox" class="nl-send-check" value="${s.uid}" onchange="nlUpdateSendCount()" style="accent-color:var(--gold);width:14px;height:14px;flex-shrink:0;">
            <div class="sub-avatar-sm" style="width:28px;height:28px;font-size:.68rem;flex-shrink:0;">${escHtml(init)}</div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span style="font-size:.85rem;color:var(--cream);font-weight:500;">${escHtml((s.nom||'').trim()||'—')}${inactive}</span>
                    ${srcBadge}
                </div>
                <div style="font-size:.75rem;color:var(--text-dim);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(s.email)}</div>
            </div>
        </div>`;
    }).join('');
    nlUpdateSendCount();
}

function nlFilterSendSubs() {
    const q    = document.getElementById('nl-search-send').value.toLowerCase();
    const type = document.getElementById('nl-filter-send-type').value;
    document.querySelectorAll('#nl-send-sub-list .nl-sub-item').forEach(item => {
        const matchQ = !q || item.dataset.search.includes(q);
        const matchT = !type || item.dataset.source === type;
        item.style.display = (matchQ && matchT) ? '' : 'none';
    });
}

function nlToggleAllSend() {
    const cb = document.getElementById('nl-check-all-send');
    cb.checked = !cb.checked;
    document.querySelectorAll('#nl-send-sub-list .nl-sub-item:not([style*="display: none"]) .nl-send-check').forEach(c => c.checked = cb.checked);
    nlUpdateSendCount();
}

function nlUpdateSendCount() {
    const n = document.querySelectorAll('.nl-send-check:checked').length;
    document.getElementById('nl-send-sel-count').textContent = n + ' sélectionné(s)';
}

async function nlConfirmSend() {
    let campId = document.getElementById('nl-compose-id').value;
    const sujet = document.getElementById('nl-compose-sujet').value.trim();
    const html  = document.getElementById('nlRichEditor').innerHTML.trim();

    if (!campId) {
        try {
            const res = await post({ action: 'nl_save_campaign', id: '', sujet, contenu_html: html });
            campId = res.id;
            document.getElementById('nl-compose-id').value = campId;
        } catch(e) { showToast('Erreur sauvegarde : ' + e.message, 'error'); return; }
    }

    let destinataires = nlDestType; // 'tous', 'tous_clients', 'tous_les_deux'
    let nbDest = 0;

    if (nlDestType === 'selection') {
        const uids = [...document.querySelectorAll('.nl-send-check:checked')].map(c => c.value);
        if (!uids.length) { showToast('Sélectionnez au moins un destinataire', 'error'); return; }
        destinataires = JSON.stringify(uids);
        nbDest = uids.length;
    } else if (nlDestType === 'tous') {
        nbDest = nlAllRecipients.filter(r => r.source === 'newsletter' && r.actif == 1).length;
    } else if (nlDestType === 'tous_clients') {
        nbDest = nlAllRecipients.filter(r => r.source === 'client').length;
    } else if (nlDestType === 'tous_les_deux') {
        nbDest = nlAllRecipients.length;
    }

    if (!confirm(`Envoyer cet email à ${nbDest} destinataire(s) ?`)) return;

    const btn = document.getElementById('nl-btn-confirm-send');
    btn.disabled = true; btn.textContent = '⏳ Envoi en cours…';

    try {
        const res = await post({ action: 'nl_send_campaign', campaign_id: campId, destinataires });
        nlCloseModal('send');
        showToast(`✓ ${res.sent}/${res.total} email(s) envoyé(s) avec succès !`, 'success');
        if (res.errors?.length) showToast(`⚠ ${res.errors.length} échec(s) d'envoi`, 'error');
        nlLoadCampaigns();
        nlLoadSubs();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
    btn.disabled = false; btn.textContent = '📤 Confirmer l\'envoi';
}

// --- Modals ---
function nlOpenModal(id)  { document.getElementById('nl-modal-' + id).classList.add('open'); }
function nlCloseModal(id) { document.getElementById('nl-modal-' + id).classList.remove('open'); }
document.querySelectorAll('.nl-modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

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

// ==========================================
// GESTION DES SALONS
// ==========================================
let allSalons = [];

async function loadSalons() {
    try {
        const res = await post({ action: 'get_salons' });
        allSalons = res.data || [];
        renderSalons();
    } catch(e) { showToast('Erreur chargement salons', 'error'); }
}

function renderSalons() {
    const wrap = document.getElementById('salon-list');
    if (!allSalons.length) {
        wrap.innerHTML = `<div style="text-align:center;color:var(--text-dim);padding:3rem;background:var(--surface2);border:1px solid var(--border);border-radius:14px">
            <div style="font-size:2.5rem;margin-bottom:12px">🏪</div>
            <div style="font-size:1rem;font-weight:600;color:var(--cream)">Aucun salon programmé</div>
            <div style="font-size:.85rem;margin-top:6px">Cliquez sur "Ajouter un salon" pour commencer</div>
        </div>`;
        return;
    }
    const months = ['','Janv','Févr','Mars','Avr','Mai','Juin','Juil','Août','Sept','Oct','Nov','Déc'];
    wrap.innerHTML = allSalons.map(s => {
        const d = new Date(s.date_debut + 'T00:00:00');
        const dFin = new Date(s.date_fin + 'T00:00:00');
        const today = new Date(); today.setHours(0,0,0,0);
        const isPast   = dFin < today;
        const isToday  = d <= today && dFin >= today;
        const isFuture = d > today;
        const statusBadge = isPast
            ? `<span style="font-size:.65rem;padding:3px 9px;border-radius:999px;background:rgba(192,57,43,.2);border:1px solid var(--red-border);color:#e74c3c">Terminé</span>`
            : isToday
            ? `<span style="font-size:.65rem;padding:3px 9px;border-radius:999px;background:rgba(39,174,96,.15);border:1px solid rgba(39,174,96,.4);color:#27ae60">En cours</span>`
            : `<span style="font-size:.65rem;padding:3px 9px;border-radius:999px;background:rgba(201,150,59,.15);border:1px solid rgba(201,150,59,.3);color:var(--gold)">À venir</span>`;
        const activeBadge = s.actif == 0
            ? `<span style="font-size:.65rem;padding:2px 8px;border-radius:999px;background:rgba(192,57,43,.15);color:#e74c3c;margin-left:6px">Masqué</span>` : '';
        const sameDay = s.date_debut === s.date_fin;
        return `<div style="background:var(--surface2);border:1px solid var(--border);border-radius:16px;padding:20px 24px;display:grid;grid-template-columns:70px 1fr auto;gap:18px;align-items:center;${isPast?'opacity:.55':''}">
            <div style="background:rgba(201,150,59,.12);border:1px solid rgba(201,150,59,.25);border-radius:12px;padding:10px;text-align:center">
                <div style="font-family:'Playfair Display',serif;font-size:1.7rem;font-weight:700;color:var(--gold);line-height:1">${d.getDate()}</div>
                <div style="font-size:.62rem;letter-spacing:.08em;text-transform:uppercase;color:var(--text-dim);margin-top:3px">${months[d.getMonth()+1]}</div>
                <div style="font-size:.6rem;color:var(--text-dim)">${d.getFullYear()}</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:5px">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;color:var(--cream)">${escHtml(s.nom)}</span>
                    ${statusBadge}${activeBadge}
                </div>
                <div style="font-size:.83rem;color:var(--text-dim)">📍 ${escHtml(s.lieu)}, ${escHtml(s.ville)}${s.adresse ? ' — '+escHtml(s.adresse) : ''}</div>
                ${!sameDay ? `<div style="font-size:.78rem;color:var(--text-dim)">📅 Du ${s.date_debut} au ${s.date_fin}</div>` : ''}
                ${s.heure_debut ? `<div style="font-size:.78rem;color:var(--text-dim)">🕙 ${escHtml(s.heure_debut)} – ${escHtml(s.heure_fin)}</div>` : ''}
                ${s.stand ? `<div style="font-size:.75rem;padding:2px 10px;background:rgba(31,79,46,.3);border:1px solid rgba(45,122,68,.3);border-radius:999px;color:#6fd98a;width:fit-content">🏷 Stand : ${escHtml(s.stand)}</div>` : ''}
                ${s.description ? `<div style="font-size:.8rem;color:var(--text-dim);font-style:italic">${escHtml(s.description)}</div>` : ''}
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <button class="action-btn" onclick="salonEdit(${s.id})" style="background:var(--surface);border:1px solid var(--border)">✏️ Modifier</button>
                <button class="action-btn" onclick="salonDelete(${s.id},'${escHtml(s.nom)}')" style="background:var(--red-bg);border:1px solid var(--red-border);color:#e74c3c">🗑 Suppr.</button>
            </div>
        </div>`;
    }).join('');
}

function salonOpenModal(data = null) {
    document.getElementById('salon-modal-title').textContent = data ? 'Modifier le salon' : 'Nouveau salon';
    document.getElementById('salon-id').value          = data?.id ?? '';
    document.getElementById('salon-nom').value         = data?.nom ?? '';
    document.getElementById('salon-lieu').value        = data?.lieu ?? '';
    document.getElementById('salon-ville').value       = data?.ville ?? '';
    document.getElementById('salon-adresse').value     = data?.adresse ?? '';
    document.getElementById('salon-date-debut').value  = data?.date_debut ?? '';
    document.getElementById('salon-date-fin').value    = data?.date_fin ?? '';
    document.getElementById('salon-heure-debut').value = data?.heure_debut ?? '10:00';
    document.getElementById('salon-heure-fin').value   = data?.heure_fin ?? '18:00';
    document.getElementById('salon-stand').value       = data?.stand ?? '';
    document.getElementById('salon-actif').value       = data ? String(data.actif) : '1';
    document.getElementById('salon-desc').value        = data?.description ?? '';
    const m = document.getElementById('salon-modal');
    m.style.pointerEvents = 'all';
    m.style.opacity = '1';
    setTimeout(() => m.querySelector('.modal').style.transform = 'scale(1)', 10);
}

function salonCloseModal() {
    const m = document.getElementById('salon-modal');
    m.style.opacity = '0';
    m.style.pointerEvents = 'none';
    m.querySelector('.modal').style.transform = 'scale(.96)';
}

function salonEdit(id) {
    const s = allSalons.find(x => x.id == id);
    if (s) salonOpenModal(s);
}

async function salonDelete(id, nom) {
    if (!confirm(`Supprimer le salon "${nom}" ?`)) return;
    try {
        await post({ action: 'delete_salon', id });
        showToast('Salon supprimé', 'success');
        loadSalons();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
}

async function salonSave() {
    const id  = document.getElementById('salon-id').value;
    const nom = document.getElementById('salon-nom').value.trim();
    const lieu = document.getElementById('salon-lieu').value.trim();
    const ville = document.getElementById('salon-ville').value.trim();
    const dDebut = document.getElementById('salon-date-debut').value;
    const dFin   = document.getElementById('salon-date-fin').value;
    if (!nom || !lieu || !ville || !dDebut || !dFin) {
        showToast('Remplissez tous les champs obligatoires (*)', 'error'); return;
    }
    const btn = document.getElementById('salon-save-btn');
    btn.disabled = true; btn.textContent = '⏳ Enregistrement…';
    try {
        await post({
            action: id ? 'update_salon' : 'add_salon',
            id, nom, lieu, ville,
            adresse:     document.getElementById('salon-adresse').value.trim(),
            date_debut:  dDebut,
            date_fin:    dFin,
            heure_debut: document.getElementById('salon-heure-debut').value,
            heure_fin:   document.getElementById('salon-heure-fin').value,
            stand:       document.getElementById('salon-stand').value.trim(),
            actif:       document.getElementById('salon-actif').value,
            description: document.getElementById('salon-desc').value.trim(),
        });
        showToast(id ? 'Salon mis à jour ✓' : 'Salon ajouté ✓', 'success');
        salonCloseModal();
        loadSalons();
    } catch(e) { showToast('Erreur : ' + e.message, 'error'); }
    btn.disabled = false; btn.textContent = 'Enregistrer';
}

// Fermer modal en cliquant l'overlay
document.getElementById('salon-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('salon-modal')) salonCloseModal();
});

</script>
</body>
</html>