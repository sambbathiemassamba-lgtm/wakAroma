<?php
session_start();

// ==========================================
// PROTECTION ADMIN
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

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            // Créer tables si inexistantes
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
                contenu_texte TEXT,
                destinataires TEXT COMMENT 'JSON: null=tous, sinon array ids',
                nb_envoyes    INT DEFAULT 0,
                statut        ENUM('brouillon','envoye') DEFAULT 'brouillon',
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at       TIMESTAMP NULL
            )");
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion impossible : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ==========================================
// TRAITEMENT AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $pdo    = getDB();
    $action = $_POST['action'];

    switch ($action) {

        // ---- ABONNÉS ----
        case 'get_subscribers':
            $stmt = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'add_subscriber':
            $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
            $nom   = trim($_POST['nom'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email invalide']); break;
            }
            $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà enregistré']); break;
            }
            $pdo->prepare("INSERT INTO newsletter_subscribers (email, nom, source) VALUES (?, ?, 'manuel')")
                ->execute([$email, $nom]);
            echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            break;

        case 'toggle_subscriber':
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE newsletter_subscribers SET actif = NOT actif WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_subscriber':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // Synchroniser les clients (users) avec actif=1 mais pas encore abonnés
        case 'sync_from_clients':
            $stmt = $pdo->query("SELECT email, CONCAT(prenom,' ',nom) AS nom FROM users WHERE email IS NOT NULL AND email != ''");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $added = 0;
            foreach ($users as $u) {
                try {
                    $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email, nom, source) VALUES (?, ?, 'compte')")
                        ->execute([$u['email'], $u['nom']]);
                    if ($pdo->lastInsertId()) $added++;
                } catch (PDOException $e) {}
            }
            echo json_encode(['success' => true, 'added' => $added]);
            break;

        // ---- CAMPAGNES ----
        case 'get_campaigns':
            $stmt = $pdo->query("SELECT id, sujet, nb_envoyes, statut, created_at, sent_at FROM newsletter_campaigns ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'get_campaign':
            $id   = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT * FROM newsletter_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $row]);
            break;

        case 'save_campaign':
            $id      = (int)($_POST['id'] ?? 0);
            $sujet   = trim($_POST['sujet'] ?? '');
            $html    = $_POST['contenu_html'] ?? '';
            $texte   = trim($_POST['contenu_texte'] ?? '');
            $destRaw = $_POST['destinataires'] ?? 'tous';

            if (empty($sujet)) { echo json_encode(['success' => false, 'error' => 'Le sujet est obligatoire']); break; }
            if (empty($html))  { echo json_encode(['success' => false, 'error' => 'Le contenu est obligatoire']); break; }

            $destJson = ($destRaw === 'tous') ? null : $destRaw; // JSON array d'ids ou null

            if ($id > 0) {
                $pdo->prepare("UPDATE newsletter_campaigns SET sujet=?, contenu_html=?, contenu_texte=?, destinataires=?, statut='brouillon' WHERE id=?")
                    ->execute([$sujet, $html, $texte, $destJson, $id]);
                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                $pdo->prepare("INSERT INTO newsletter_campaigns (sujet, contenu_html, contenu_texte, destinataires) VALUES (?, ?, ?, ?)")
                    ->execute([$sujet, $html, $texte, $destJson]);
                echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            }
            break;

        case 'delete_campaign':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM newsletter_campaigns WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ---- ENVOI ----
        case 'send_campaign':
            $campId  = (int)$_POST['campaign_id'];
            $destRaw = $_POST['destinataires'] ?? 'tous'; // 'tous' ou JSON array d'ids

            $stmtC = $pdo->prepare("SELECT * FROM newsletter_campaigns WHERE id = ?");
            $stmtC->execute([$campId]);
            $campaign = $stmtC->fetch(PDO::FETCH_ASSOC);
            if (!$campaign) { echo json_encode(['success' => false, 'error' => 'Campagne introuvable']); break; }

            // Récupérer les destinataires
            if ($destRaw === 'tous') {
                $stmtS = $pdo->query("SELECT * FROM newsletter_subscribers WHERE actif = 1");
            } else {
                $ids = json_decode($destRaw, true);
                if (!is_array($ids) || empty($ids)) {
                    echo json_encode(['success' => false, 'error' => 'Aucun destinataire sélectionné']); break;
                }
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmtS = $pdo->prepare("SELECT * FROM newsletter_subscribers WHERE actif = 1 AND id IN ($placeholders)");
                $stmtS->execute($ids);
            }
            $subscribers = $stmtS->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subscribers)) {
                echo json_encode(['success' => false, 'error' => 'Aucun abonné actif pour cet envoi']); break;
            }

            $sent    = 0;
            $errors  = [];
            $fromEmail = 'newsletter@wakaroma.fr';
            $fromName  = 'WakAroma';

            foreach ($subscribers as $sub) {
                $to      = $sub['email'];
                $nomDest = trim($sub['nom']) ?: 'Cher client';

                // Personnalisation du HTML
                $htmlContent = str_replace(
                    ['{{NOM}}', '{{EMAIL}}'],
                    [htmlspecialchars($nomDest), htmlspecialchars($to)],
                    $campaign['contenu_html']
                );

                // En-têtes mail
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
                $headers .= "Reply-To: $fromEmail\r\n";
                $headers .= "X-Mailer: WakAroma-Newsletter/1.0\r\n";

                $subject = '=?UTF-8?B?' . base64_encode($campaign['sujet']) . '?=';

                if (@mail($to, $subject, $htmlContent, $headers)) {
                    $sent++;
                } else {
                    $errors[] = $to;
                }
            }

            // Mettre à jour la campagne
            $pdo->prepare("UPDATE newsletter_campaigns SET nb_envoyes = ?, statut = 'envoye', sent_at = NOW(), destinataires = ? WHERE id = ?")
                ->execute([$sent, $destRaw === 'tous' ? null : $destRaw, $campId]);

            echo json_encode([
                'success' => true,
                'sent'    => $sent,
                'total'   => count($subscribers),
                'errors'  => $errors
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
    exit;
}

$pdo = getDB();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Newsletter — WakAroma</title>
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

/* ---- HEADER ---- */
.header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-brand { display: flex; align-items: center; gap: 14px; }
.header-icon { width: 48px; height: 48px; border-radius: 10px; overflow: hidden; border: 1px solid var(--gold-dim); }
.header-icon img { width: 100%; height: 100%; object-fit: cover; }
.header-title { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--cream); }
.header-sub { font-size: .78rem; color: var(--text-dim); margin-top: 2px; }
.header-actions { display: flex; align-items: center; gap: 12px; }
.btn-back { padding: 8px 16px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; color: var(--text-dim); font-family: 'DM Sans', sans-serif; font-size: .85rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .2s; }
.btn-back:hover { border-color: var(--gold); color: var(--gold); }

/* ---- STATS HEADER ---- */
.stats-bar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 14px 32px;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}
.stat-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    flex: 1;
    min-width: 140px;
    max-width: 220px;
}
.stat-chip .icon { font-size: 1.4rem; }
.stat-chip .info .num { font-size: 1.5rem; font-weight: 700; color: var(--gold); font-family: 'Playfair Display', serif; line-height: 1; }
.stat-chip .info .lbl { font-size: .73rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: .06em; margin-top: 2px; }

/* ---- NAV TABS ---- */
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

/* ---- PAGES ---- */
.page { display: none; padding: 28px 32px; }
.page.active { display: block; }

/* ---- TOOLBAR ---- */
.toolbar {
    padding: 16px 0 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-wrap input { width: 100%; padding: 10px 14px 10px 40px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; transition: border-color .2s; }
.search-wrap input:focus { border-color: var(--gold); }
.search-wrap .ico { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }

/* ---- BUTTONS ---- */
.btn { padding: 10px 20px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; white-space: nowrap; }
.btn-primary { background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%); color: #1a1200; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(201,150,59,.4); }
.btn-danger { background: var(--red-bg); color: #e74c3c; border: 1px solid var(--red-border); }
.btn-danger:hover { background: #3d1208; }
.btn-ghost { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
.btn-ghost:hover { border-color: var(--gold); color: var(--gold); }
.btn-green { background: var(--green-bg); color: var(--green); border: 1px solid #1e6b40; }
.btn-green:hover { background: #0d3520; }
.btn-blue { background: var(--blue-bg); color: #6fa3e8; border: 1px solid var(--blue-border); }
.btn-blue:hover { background: #0d1e30; }
.btn-sm { padding: 6px 12px; font-size: .78rem; }
.btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; }

/* ---- TABLE ---- */
.table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: var(--surface2); border-bottom: 2px solid var(--gold-dim); }
thead th { padding: 13px 16px; text-align: left; font-size: .73rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--gold); white-space: nowrap; }
tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(201,150,59,.04); }
td { padding: 13px 16px; font-size: .88rem; vertical-align: middle; }

/* ---- BADGES ---- */
.badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: .73rem; font-weight: 600; }
.badge-green { background: var(--green-bg); border: 1px solid #1e6b40; color: #4ecb78; }
.badge-red { background: var(--red-bg); border: 1px solid var(--red-border); color: #e74c3c; }
.badge-gold { background: rgba(201,150,59,.12); border: 1px solid var(--gold-dim); color: var(--gold); }
.badge-blue { background: var(--blue-bg); border: 1px solid var(--blue-border); color: #6fa3e8; }
.badge-grey { background: var(--surface2); border: 1px solid var(--border); color: var(--text-dim); }

/* ---- EMPTY STATE ---- */
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-dim); }
.empty-state .icon { font-size: 3rem; margin-bottom: 12px; }

/* ---- MODAL ---- */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.75); backdrop-filter: blur(6px); z-index: 200; display: none; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.open { display: flex; }
.modal { background: var(--surface); border: 1px solid var(--border2); border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 24px 80px rgba(0,0,0,.6); animation: modalIn .25s ease; display: flex; flex-direction: column; max-height: 92vh; }
.modal--lg { max-width: 820px; }
.modal--xl { max-width: 1000px; }
@keyframes modalIn { from { opacity: 0; transform: translateY(20px) scale(.97); } to { opacity: 1; transform: none; } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 0; flex-shrink: 0; }
.modal-title { font-family: 'Playfair Display', serif; font-size: 1.25rem; color: var(--cream); }
.modal-close { width: 32px; height: 32px; background: var(--surface2); border: 1px solid var(--border); border-radius: 6px; color: var(--text-dim); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; flex-shrink: 0; }
.modal-close:hover { color: var(--cream); background: var(--border); }
.modal-body { padding: 20px 28px; overflow-y: auto; flex: 1; }
.modal-footer { padding: 16px 28px 24px; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; border-top: 1px solid var(--border); }

/* ---- FORM ---- */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-dim); }
.form-group input, .form-group select, .form-group textarea { padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; outline: none; transition: border-color .2s; resize: vertical; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--gold); }

/* ---- RICH EDITOR ---- */
.editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    padding: 10px 12px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-bottom: none;
    border-radius: 8px 8px 0 0;
}
.editor-btn {
    padding: 5px 10px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 5px;
    color: var(--text-dim);
    font-size: .82rem;
    cursor: pointer;
    transition: all .15s;
    font-family: 'DM Sans', sans-serif;
}
.editor-btn:hover { background: var(--border); color: var(--cream); }
.editor-btn.active { background: rgba(201,150,59,.15); border-color: var(--gold-dim); color: var(--gold); }
.editor-sep { width: 1px; background: var(--border); margin: 2px 4px; }
.editor-color { display: flex; align-items: center; gap: 6px; padding: 3px 8px; border: 1px solid var(--border); border-radius: 5px; cursor: pointer; color: var(--text-dim); font-size: .8rem; transition: all .15s; background: transparent; }
.editor-color:hover { border-color: var(--gold-dim); }
.editor-color input[type=color] { width: 18px; height: 18px; padding: 0; border: none; background: none; cursor: pointer; border-radius: 3px; }

#richEditor {
    min-height: 260px;
    max-height: 400px;
    overflow-y: auto;
    padding: 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 0 0 8px 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .92rem;
    line-height: 1.6;
    outline: none;
}
#richEditor:focus { border-color: var(--gold); }
#richEditor:empty::before { content: 'Rédigez votre message ici… Utilisez {{NOM}} pour personnaliser avec le nom du destinataire.'; color: var(--text-dim); pointer-events: none; }
#richEditor a { color: var(--gold); }
#richEditor h1, #richEditor h2, #richEditor h3 { color: var(--cream); font-family: 'Playfair Display', serif; }
#richEditor blockquote { border-left: 3px solid var(--gold-dim); padding-left: 12px; color: var(--cream-dim); margin: 8px 0; }
#richEditor ul, #richEditor ol { padding-left: 20px; }

/* ---- DESTINATAIRES SELECTOR ---- */
.dest-option { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; transition: all .2s; margin-bottom: 8px; }
.dest-option:hover { border-color: var(--gold-dim); }
.dest-option.selected { border-color: var(--gold); background: rgba(201,150,59,.06); }
.dest-option input[type=radio] { accent-color: var(--gold); width: 16px; height: 16px; }
.dest-option .dest-label { font-weight: 500; color: var(--cream); font-size: .9rem; }
.dest-option .dest-sub { font-size: .78rem; color: var(--text-dim); margin-top: 2px; }

/* ---- SUBSCRIBER CHECKBOXES ---- */
.sub-list { max-height: 280px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); }
.sub-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-bottom: 1px solid var(--border); transition: background .15s; }
.sub-item:last-child { border-bottom: none; }
.sub-item:hover { background: rgba(201,150,59,.04); }
.sub-item input[type=checkbox] { accent-color: var(--gold); width: 16px; height: 16px; flex-shrink: 0; }
.sub-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-dim), var(--gold)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .78rem; color: #1a1200; flex-shrink: 0; }
.sub-info .sub-name { font-size: .88rem; color: var(--cream); font-weight: 500; }
.sub-info .sub-email { font-size: .78rem; color: var(--text-dim); }
.sub-search { padding: 10px 14px; background: var(--surface2); border-bottom: 1px solid var(--border); border-radius: 8px 8px 0 0; }
.sub-search input { width: 100%; padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: .85rem; outline: none; }
.sub-search input:focus { border-color: var(--gold); }
.sub-select-all { display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(201,150,59,.05); border-bottom: 1px solid var(--border); font-size: .82rem; color: var(--gold); cursor: pointer; }
.sub-select-all:hover { background: rgba(201,150,59,.1); }

/* ---- PREVIEW ---- */
.preview-frame {
    width: 100%;
    min-height: 400px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fff;
    border: none;
}

/* ---- CAMPAIGN CARD ---- */
.campaign-list { display: flex; flex-direction: column; gap: 12px; }
.campaign-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: border-color .2s;
}
.campaign-card:hover { border-color: var(--border2); }
.campaign-icon { font-size: 1.8rem; flex-shrink: 0; }
.campaign-info { flex: 1; min-width: 0; }
.campaign-sujet { font-weight: 600; color: var(--cream); font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.campaign-meta { font-size: .78rem; color: var(--text-dim); margin-top: 4px; display: flex; gap: 14px; flex-wrap: wrap; }
.campaign-actions { display: flex; gap: 8px; flex-shrink: 0; }

/* ---- TOAST ---- */
.toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 400; display: flex; flex-direction: column; gap: 10px; }
.toast { padding: 12px 20px; border-radius: 10px; font-size: .88rem; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.4); animation: toastIn .3s ease; min-width: 260px; }
@keyframes toastIn { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: none; } }
.toast.success { background: var(--green-bg); border: 1px solid #1e6b40; color: #4ecb78; }
.toast.error   { background: var(--red-bg);   border: 1px solid var(--red-border); color: #e74c3c; }
.toast.info    { background: var(--blue-bg);  border: 1px solid var(--blue-border); color: #6fa3e8; }
.toast .toast-close { margin-left: auto; cursor: pointer; opacity: .7; }
.toast .toast-close:hover { opacity: 1; }

/* ---- RESPONSIVE ---- */
@media (max-width: 640px) {
    .header { padding: 12px 16px; }
    .stats-bar { padding: 12px 16px; gap: 10px; }
    .stat-chip { min-width: 100px; padding: 8px 12px; }
    .page { padding: 16px; }
    .nav-tabs { padding: 0 16px; }
    .nav-tab { padding: 12px 14px; font-size: .82rem; }
    .modal--lg, .modal--xl { max-width: 100%; }
    .form-grid { grid-template-columns: 1fr; }
    .campaign-card { flex-wrap: wrap; }
    .campaign-actions { width: 100%; justify-content: flex-end; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-brand">
        <div class="header-icon"><img src="logo/logo.jpeg" alt="WakAroma"></div>
        <div>
            <div class="header-title">Newsletter — WakAroma</div>
            <div class="header-sub">Connecté : <?= htmlspecialchars($admin['nom'] ?? 'Admin') ?></div>
        </div>
    </div>
    <div class="header-actions">
        <a href="stock.php" class="btn-back">← Retour au stock</a>
    </div>
</header>

<!-- STATS -->
<div class="stats-bar">
    <div class="stat-chip">
        <div class="icon">📧</div>
        <div class="info">
            <div class="num" id="stat-total">—</div>
            <div class="lbl">Abonnés total</div>
        </div>
    </div>
    <div class="stat-chip">
        <div class="icon">✅</div>
        <div class="info">
            <div class="num" id="stat-actif">—</div>
            <div class="lbl">Actifs</div>
        </div>
    </div>
    <div class="stat-chip">
        <div class="icon">📤</div>
        <div class="info">
            <div class="num" id="stat-campaigns">—</div>
            <div class="lbl">Campagnes</div>
        </div>
    </div>
    <div class="stat-chip">
        <div class="icon">📨</div>
        <div class="info">
            <div class="num" id="stat-sent">—</div>
            <div class="lbl">Emails envoyés</div>
        </div>
    </div>
</div>

<!-- NAVIGATION -->
<nav class="nav-tabs">
    <button class="nav-tab active" onclick="switchTab('subscribers', this)">📋 Abonnés</button>
    <button class="nav-tab" onclick="switchTab('campaigns', this)">✉️ Campagnes</button>
    <button class="nav-tab" onclick="switchTab('compose', this)">✍️ Rédiger</button>
</nav>

<!-- ===================== ABONNÉS ===================== -->
<div class="page active" id="page-subscribers">
    <div class="toolbar">
        <div class="search-wrap">
            <span class="ico">🔍</span>
            <input type="text" id="search-sub" placeholder="Rechercher un abonné…" oninput="filterSubscribers()">
        </div>
        <select id="filter-sub-status" class="btn btn-ghost" style="padding:10px 14px;cursor:pointer;" onchange="filterSubscribers()">
            <option value="">Tous les statuts</option>
            <option value="1">Actifs</option>
            <option value="0">Désactivés</option>
        </select>
        <select id="filter-sub-source" class="btn btn-ghost" style="padding:10px 14px;cursor:pointer;" onchange="filterSubscribers()">
            <option value="">Toutes les sources</option>
            <option value="newsletter">Newsletter</option>
            <option value="compte">Compte client</option>
            <option value="manuel">Ajout manuel</option>
        </select>
        <button class="btn btn-blue" onclick="syncFromClients()">🔄 Sync. clients</button>
        <button class="btn btn-primary" onclick="openAddSubscriber()">＋ Ajouter</button>
    </div>
    <div class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="check-all-sub" onchange="toggleAllSubs(this)" style="accent-color:var(--gold);width:16px;height:16px;cursor:pointer;" title="Tout sélectionner"></th>
                        <th>Email</th>
                        <th>Nom</th>
                        <th>Source</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tbody-sub">
                    <tr><td colspan="7"><div class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="bulk-bar" style="display:none; margin-top:14px; padding:12px 16px; background:var(--surface); border:1px solid var(--gold-dim); border-radius:10px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <span id="bulk-count" style="color:var(--gold);font-weight:600;font-size:.9rem;"></span>
        <button class="btn btn-primary btn-sm" onclick="sendToSelected()">📤 Envoyer une campagne</button>
        <button class="btn btn-danger btn-sm" onclick="deleteSelected()">🗑 Supprimer la sélection</button>
    </div>
</div>

<!-- ===================== CAMPAGNES ===================== -->
<div class="page" id="page-campaigns">
    <div class="toolbar">
        <span style="color:var(--text-dim);font-size:.88rem;" id="campaign-count-label"></span>
        <div style="flex:1;"></div>
        <button class="btn btn-primary" onclick="switchTab('compose', document.querySelector('.nav-tab:nth-child(3)'))">✍️ Nouvelle campagne</button>
    </div>
    <div id="campaign-list-wrap">
        <div class="empty-state"><div class="icon">⏳</div><p>Chargement…</p></div>
    </div>
</div>

<!-- ===================== RÉDIGER ===================== -->
<div class="page" id="page-compose">
    <div style="max-width:900px;">
        <!-- En-tête campagne -->
        <div class="table-card" style="padding:24px 28px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                <h2 style="font-family:'Playfair Display',serif; color:var(--cream); font-size:1.2rem;">✍️ Rédiger une campagne</h2>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-ghost btn-sm" id="btn-preview" onclick="openPreview()">👁 Aperçu</button>
                    <button class="btn btn-ghost btn-sm" onclick="saveDraft()">💾 Brouillon</button>
                    <button class="btn btn-primary btn-sm" onclick="openSendModal()">📤 Envoyer</button>
                </div>
            </div>
            <input type="hidden" id="compose-id" value="">
            <div class="form-group full" style="margin-bottom:16px;">
                <label>Sujet de l'email *</label>
                <input type="text" id="compose-sujet" placeholder="Ex : 🌿 Nos nouveautés de printemps sont là !">
            </div>

            <!-- Barre d'outils éditeur -->
            <div class="form-group full">
                <label>Contenu de l'email *</label>
                <div class="editor-toolbar" id="editorToolbar">
                    <button class="editor-btn" onclick="execCmd('bold')" title="Gras"><b>B</b></button>
                    <button class="editor-btn" onclick="execCmd('italic')" title="Italique"><i>I</i></button>
                    <button class="editor-btn" onclick="execCmd('underline')" title="Souligné"><u>U</u></button>
                    <button class="editor-btn" onclick="execCmd('strikeThrough')" title="Barré"><s>S</s></button>
                    <div class="editor-sep"></div>
                    <button class="editor-btn" onclick="execCmd('formatBlock','h1')" title="Titre 1">H1</button>
                    <button class="editor-btn" onclick="execCmd('formatBlock','h2')" title="Titre 2">H2</button>
                    <button class="editor-btn" onclick="execCmd('formatBlock','h3')" title="Titre 3">H3</button>
                    <button class="editor-btn" onclick="execCmd('formatBlock','p')" title="Paragraphe">¶</button>
                    <div class="editor-sep"></div>
                    <button class="editor-btn" onclick="execCmd('insertUnorderedList')" title="Liste à puces">• Liste</button>
                    <button class="editor-btn" onclick="execCmd('insertOrderedList')" title="Liste numérotée">1. Liste</button>
                    <button class="editor-btn" onclick="execCmd('formatBlock','blockquote')" title="Citation">❝</button>
                    <div class="editor-sep"></div>
                    <button class="editor-btn" onclick="execCmd('justifyLeft')" title="Gauche">⬅</button>
                    <button class="editor-btn" onclick="execCmd('justifyCenter')" title="Centre">↔</button>
                    <button class="editor-btn" onclick="execCmd('justifyRight')" title="Droite">➡</button>
                    <div class="editor-sep"></div>
                    <label class="editor-color" title="Couleur du texte">
                        A <input type="color" id="colorText" value="#f5edd8" onchange="execCmd('foreColor',this.value)">
                    </label>
                    <label class="editor-color" title="Couleur de fond">
                        🖌 <input type="color" id="colorBg" value="#c9963b" onchange="execCmd('hiliteColor',this.value)">
                    </label>
                    <div class="editor-sep"></div>
                    <button class="editor-btn" onclick="insertLink()" title="Lien">🔗 Lien</button>
                    <button class="editor-btn" onclick="insertImage()" title="Image">🖼 Image</button>
                    <button class="editor-btn" onclick="insertVar('{{NOM}}')" title="Insérer le nom du destinataire" style="color:var(--gold);">{{NOM}}</button>
                    <div class="editor-sep"></div>
                    <button class="editor-btn" onclick="execCmd('removeFormat')" title="Supprimer la mise en forme">✕ Format</button>
                    <button class="editor-btn" onclick="clearEditor()" title="Tout effacer" style="color:#e74c3c;">🗑</button>
                </div>
                <div id="richEditor" contenteditable="true" spellcheck="true"></div>
            </div>
        </div>

        <!-- Templates prêts à l'emploi -->
        <div class="table-card" style="padding:20px 24px;">
            <div style="font-size:.82rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--gold); margin-bottom:14px;">✦ Templates rapides</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn btn-ghost btn-sm" onclick="loadTemplate('promo')">🎁 Promotion</button>
                <button class="btn btn-ghost btn-sm" onclick="loadTemplate('nouveaute')">🌿 Nouveauté</button>
                <button class="btn btn-ghost btn-sm" onclick="loadTemplate('bienvenue')">👋 Bienvenue</button>
                <button class="btn btn-ghost btn-sm" onclick="loadTemplate('recette')">🍽 Recette</button>
                <button class="btn btn-ghost btn-sm" onclick="loadTemplate('evenement')">📅 Événement</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODALS ===================== -->

<!-- Modal : Ajouter abonné -->
<div class="modal-overlay" id="modal-add-sub">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Ajouter un abonné</span>
            <button class="modal-close" onclick="closeModal('add-sub')">×</button>
        </div>
        <div class="modal-body">
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="add-sub-email" placeholder="email@exemple.com">
                </div>
                <div class="form-group">
                    <label>Nom (facultatif)</label>
                    <input type="text" id="add-sub-nom" placeholder="Nom affiché dans les emails">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('add-sub')">Annuler</button>
            <button class="btn btn-primary" onclick="submitAddSubscriber()">Ajouter</button>
        </div>
    </div>
</div>

<!-- Modal : Sélection destinataires & envoi -->
<div class="modal-overlay" id="modal-send">
    <div class="modal modal--xl">
        <div class="modal-header">
            <span class="modal-title">📤 Envoyer la campagne</span>
            <button class="modal-close" onclick="closeModal('send')">×</button>
        </div>
        <div class="modal-body">
            <!-- Résumé campagne -->
            <div style="background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:14px 18px; margin-bottom:20px;">
                <div style="font-size:.75rem; color:var(--text-dim); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;">Campagne</div>
                <div style="font-weight:600; color:var(--cream);" id="send-sujet-preview">—</div>
            </div>

            <!-- Choix destinataires -->
            <div style="font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-dim); margin-bottom:12px;">Destinataires</div>
            <div class="dest-option selected" id="dest-opt-tous" onclick="selectDestType('tous')">
                <input type="radio" name="dest-type" value="tous" checked>
                <div>
                    <div class="dest-label">Tous les abonnés actifs</div>
                    <div class="dest-sub" id="dest-tous-count">Chargement…</div>
                </div>
            </div>
            <div class="dest-option" id="dest-opt-selection" onclick="selectDestType('selection')">
                <input type="radio" name="dest-type" value="selection">
                <div>
                    <div class="dest-label">Sélection manuelle</div>
                    <div class="dest-sub">Choisissez les destinataires un par un</div>
                </div>
            </div>

            <!-- Liste de sélection -->
            <div id="sub-selector" style="display:none; margin-top:14px;">
                <div class="sub-list">
                    <div class="sub-search">
                        <input type="text" id="search-send-sub" placeholder="Filtrer les abonnés…" oninput="filterSendSubs()">
                    </div>
                    <div class="sub-select-all" onclick="toggleAllSendSubs()">
                        <input type="checkbox" id="check-all-send" style="accent-color:var(--gold);width:14px;height:14px;">
                        Tout sélectionner / désélectionner
                    </div>
                    <div id="send-sub-list"><!-- Injecté dynamiquement --></div>
                </div>
                <div style="margin-top:8px; font-size:.8rem; color:var(--gold);" id="send-selected-count">0 sélectionné(s)</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('send')">Annuler</button>
            <button class="btn btn-primary" id="btn-confirm-send" onclick="confirmSend()">
                📤 Confirmer l'envoi
            </button>
        </div>
    </div>
</div>

<!-- Modal : Aperçu -->
<div class="modal-overlay" id="modal-preview">
    <div class="modal modal--lg">
        <div class="modal-header">
            <span class="modal-title">👁 Aperçu de l'email</span>
            <button class="modal-close" onclick="closeModal('preview')">×</button>
        </div>
        <div class="modal-body" style="padding-top:12px;">
            <div style="background:var(--surface2); border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:.85rem; color:var(--text-dim);">
                Sujet : <span id="preview-sujet" style="color:var(--cream); font-weight:500;"></span>
            </div>
            <iframe id="previewFrame" class="preview-frame" style="width:100%; min-height:450px; border-radius:8px; background:#fff;" frameborder="0"></iframe>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('preview')">Fermer</button>
            <button class="btn btn-primary" onclick="closeModal('preview'); openSendModal();">📤 Envoyer</button>
        </div>
    </div>
</div>

<!-- TOASTS -->
<div class="toast-container" id="toasts"></div>

<script>
const SCRIPT_URL = window.location.href.split('?')[0];
let allSubscribers = [];
let allCampaigns   = [];
let sendDestType   = 'tous';

// ==========================================
// NAVIGATION TABS
// ==========================================
function switchTab(tab, el) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    if (el) el.classList.add('active');
    document.getElementById('page-' + tab).classList.add('active');
    if (tab === 'campaigns') loadCampaigns();
}

// ==========================================
// CHARGEMENT ABONNÉS
// ==========================================
async function loadSubscribers() {
    try {
        const res = await post({ action: 'get_subscribers' });
        allSubscribers = res.data || [];
        renderSubscribers(allSubscribers);
        updateStats();
    } catch (e) { showToast('Erreur chargement abonnés : ' + e.message, 'error'); }
}

function renderSubscribers(list) {
    const tbody = document.getElementById('tbody-sub');
    if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="icon">📭</div><p>Aucun abonné pour l'instant.</p></div></td></tr>`;
        return;
    }
    tbody.innerHTML = list.map(s => {
        const initials  = s.nom ? (s.nom.trim().split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2)) : s.email[0].toUpperCase();
        const dateStr   = s.subscribed_at ? new Date(s.subscribed_at).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric'}) : '—';
        const sourceBadge = {
            newsletter: '<span class="badge badge-gold">Newsletter</span>',
            compte:     '<span class="badge badge-blue">Compte</span>',
            manuel:     '<span class="badge badge-grey">Manuel</span>'
        }[s.source] || '<span class="badge badge-grey">—</span>';
        const statusBadge = s.actif == 1
            ? '<span class="badge badge-green">● Actif</span>'
            : '<span class="badge badge-red">● Inactif</span>';
        return `<tr data-id="${s.id}" data-email="${esc(s.email)}" data-nom="${esc(s.nom)}" data-actif="${s.actif}" data-source="${s.source}">
            <td><input type="checkbox" class="sub-check" value="${s.id}" onchange="updateBulkBar()" style="accent-color:var(--gold);width:16px;height:16px;cursor:pointer;"></td>
            <td style="font-family:monospace;color:var(--cream);">${esc(s.email)}</td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="sub-avatar">${esc(initials)}</div>
                    <span style="color:var(--text);">${esc(s.nom || '—')}</span>
                </div>
            </td>
            <td>${sourceBadge}</td>
            <td>${statusBadge}</td>
            <td style="color:var(--text-dim);font-size:.82rem;">${dateStr}</td>
            <td>
                <div style="display:flex;gap:6px;">
                    <button class="btn btn-ghost btn-sm" onclick="toggleSubscriber(${s.id})" title="${s.actif==1?'Désactiver':'Réactiver'}">${s.actif==1?'⏸':'▶'}</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteSub(${s.id},'${esc(s.email)}')" title="Supprimer">🗑</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function filterSubscribers() {
    const search  = document.getElementById('search-sub').value.toLowerCase();
    const status  = document.getElementById('filter-sub-status').value;
    const source  = document.getElementById('filter-sub-source').value;
    const filtered = allSubscribers.filter(s => {
        const matchSearch = (s.email + ' ' + (s.nom||'')).toLowerCase().includes(search);
        const matchStatus = status === '' || String(s.actif) === status;
        const matchSource = source === '' || s.source === source;
        return matchSearch && matchStatus && matchSource;
    });
    renderSubscribers(filtered);
}

async function toggleSubscriber(id) {
    await post({ action: 'toggle_subscriber', id });
    showToast('Statut mis à jour', 'success');
    loadSubscribers();
}

async function deleteSub(id, email) {
    if (!confirm(`Supprimer "${email}" de la liste newsletter ?`)) return;
    await post({ action: 'delete_subscriber', id });
    showToast(`${email} supprimé`, 'success');
    loadSubscribers();
}

async function syncFromClients() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ Sync…';
    try {
        const res = await post({ action: 'sync_from_clients' });
        showToast(`${res.added} nouveau(x) abonné(s) importé(s) depuis les clients`, 'success');
        loadSubscribers();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
    btn.disabled = false;
    btn.textContent = '🔄 Sync. clients';
}

// ---- Sélection multiple ----
function updateBulkBar() {
    const checked = document.querySelectorAll('.sub-check:checked');
    const bar     = document.getElementById('bulk-bar');
    const cnt     = document.getElementById('bulk-count');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        cnt.textContent   = checked.length + ' abonné(s) sélectionné(s)';
    } else {
        bar.style.display = 'none';
    }
}

function toggleAllSubs(cb) {
    document.querySelectorAll('.sub-check').forEach(c => c.checked = cb.checked);
    updateBulkBar();
}

function sendToSelected() {
    const ids = [...document.querySelectorAll('.sub-check:checked')].map(c => c.value);
    if (!ids.length) { showToast('Aucun abonné sélectionné', 'error'); return; }
    switchTab('compose', document.querySelector('.nav-tab:nth-child(3)'));
    setTimeout(() => { openSendModalWithIds(ids); }, 200);
}

async function deleteSelected() {
    const ids = [...document.querySelectorAll('.sub-check:checked')].map(c => c.value);
    if (!ids.length || !confirm(`Supprimer ${ids.length} abonné(s) ?`)) return;
    for (const id of ids) {
        await post({ action: 'delete_subscriber', id });
    }
    showToast(`${ids.length} abonné(s) supprimé(s)`, 'success');
    loadSubscribers();
}

// ---- Ajouter manuellement ----
function openAddSubscriber() { openModal('add-sub'); }
async function submitAddSubscriber() {
    const email = document.getElementById('add-sub-email').value.trim();
    const nom   = document.getElementById('add-sub-nom').value.trim();
    if (!email) { showToast('Email obligatoire', 'error'); return; }
    try {
        await post({ action: 'add_subscriber', email, nom });
        showToast(`"${email}" ajouté ✓`, 'success');
        closeModal('add-sub');
        document.getElementById('add-sub-email').value = '';
        document.getElementById('add-sub-nom').value = '';
        loadSubscribers();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

// ==========================================
// CAMPAGNES
// ==========================================
async function loadCampaigns() {
    try {
        const res = await post({ action: 'get_campaigns' });
        allCampaigns = res.data || [];
        renderCampaigns(allCampaigns);
        updateStats();
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

function renderCampaigns(list) {
    const wrap = document.getElementById('campaign-list-wrap');
    document.getElementById('campaign-count-label').textContent = list.length + ' campagne(s)';
    if (!list.length) {
        wrap.innerHTML = `<div class="empty-state"><div class="icon">📭</div><p>Aucune campagne pour l'instant. Rédigez votre première !</p></div>`;
        return;
    }
    wrap.innerHTML = '<div class="campaign-list">' + list.map(c => {
        const date     = c.sent_at ? new Date(c.sent_at).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric'}) : new Date(c.created_at).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric'});
        const statusBadge = c.statut === 'envoye'
            ? `<span class="badge badge-green">✓ Envoyée</span>`
            : `<span class="badge badge-grey">💾 Brouillon</span>`;
        return `<div class="campaign-card">
            <div class="campaign-icon">${c.statut === 'envoye' ? '📨' : '📝'}</div>
            <div class="campaign-info">
                <div class="campaign-sujet">${esc(c.sujet)}</div>
                <div class="campaign-meta">
                    ${statusBadge}
                    <span>${date}</span>
                    ${c.nb_envoyes > 0 ? `<span>📤 ${c.nb_envoyes} envoyé(s)</span>` : ''}
                </div>
            </div>
            <div class="campaign-actions">
                <button class="btn btn-ghost btn-sm" onclick="editCampaign(${c.id})" title="Modifier">✏️</button>
                ${c.statut === 'brouillon' ? `<button class="btn btn-primary btn-sm" onclick="editCampaign(${c.id}, true)" title="Envoyer">📤 Envoyer</button>` : ''}
                <button class="btn btn-danger btn-sm" onclick="deleteCampaign(${c.id})" title="Supprimer">🗑</button>
            </div>
        </div>`;
    }).join('') + '</div>';
}

async function editCampaign(id, sendDirect = false) {
    const res = await post({ action: 'get_campaign', id });
    const c   = res.data;
    document.getElementById('compose-id').value    = c.id;
    document.getElementById('compose-sujet').value = c.sujet;
    document.getElementById('richEditor').innerHTML = c.contenu_html || '';
    switchTab('compose', document.querySelector('.nav-tab:nth-child(3)'));
    if (sendDirect) setTimeout(openSendModal, 300);
}

async function deleteCampaign(id) {
    if (!confirm('Supprimer cette campagne définitivement ?')) return;
    await post({ action: 'delete_campaign', id });
    showToast('Campagne supprimée', 'success');
    loadCampaigns();
}

// ==========================================
// ÉDITEUR RICHE
// ==========================================
function execCmd(cmd, val = null) {
    document.getElementById('richEditor').focus();
    document.execCommand(cmd, false, val);
}

function insertLink() {
    const url = prompt('URL du lien :', 'https://');
    if (url) document.execCommand('createLink', false, url);
}

function insertImage() {
    const url = prompt('URL de l\'image :', 'https://');
    if (url) document.execCommand('insertImage', false, url);
}

function insertVar(v) {
    document.getElementById('richEditor').focus();
    document.execCommand('insertText', false, v);
}

function clearEditor() {
    if (confirm('Effacer tout le contenu ?')) {
        document.getElementById('richEditor').innerHTML = '';
        document.getElementById('compose-sujet').value = '';
        document.getElementById('compose-id').value    = '';
    }
}

// ==========================================
// TEMPLATES
// ==========================================
const TEMPLATES = {
    promo: {
        sujet: '🎁 Offre exclusive — -15% sur votre prochaine commande',
        html: `<div style="font-family:'Georgia',serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;">
    <h1 style="color:#c9963b;font-size:2rem;margin:0 0 8px;">WakAroma</h1>
    <p style="color:#a89878;margin:0;font-size:.9rem;">Des épices d'exception</p>
  </div>
  <div style="padding:32px;">
    <p style="color:#5c4a2a;font-size:1rem;margin:0 0 12px;">Bonjour {{NOM}},</p>
    <h2 style="color:#1a1611;font-size:1.5rem;margin:0 0 16px;">Une offre rien que pour vous ✦</h2>
    <p style="color:#5c4a2a;line-height:1.7;">Parce que vous faites partie de notre communauté, nous vous offrons <strong style="color:#c9963b;">-15% sur toute votre prochaine commande</strong>.</p>
    <div style="margin:28px 0;text-align:center;">
      <a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;display:inline-block;">Profiter de l'offre →</a>
    </div>
    <p style="color:#8a7a62;font-size:.82rem;">Offre valable jusqu'au [DATE]. Non cumulable avec d'autres promotions.</p>
  </div>
  <div style="background:#2a2218;padding:20px 32px;text-align:center;">
    <p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma — <a href="#" style="color:#7a5a22;">Se désabonner</a></p>
  </div>
</div>`
    },
    nouveaute: {
        sujet: '🌿 Nouvelle collection — Découvrez nos dernières épices',
        html: `<div style="font-family:'Georgia',serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;">
    <h1 style="color:#c9963b;font-size:2rem;margin:0 0 8px;">WakAroma</h1>
    <p style="color:#a89878;margin:0;font-size:.9rem;">Les saveurs du monde</p>
  </div>
  <div style="padding:32px;">
    <p style="color:#5c4a2a;">Bonjour {{NOM}},</p>
    <h2 style="color:#1a1611;">Nos nouvelles épices sont arrivées 🌿</h2>
    <p style="color:#5c4a2a;line-height:1.7;">Nous avons soigneusement sélectionné pour vous de nouvelles épices rares et parfumées, directement sourcées auprès de producteurs passionnés.</p>
    <div style="background:#fff;border:1px solid #e8dcc8;border-radius:8px;padding:20px;margin:20px 0;">
      <p style="margin:0;color:#5c4a2a;font-style:italic;">✦ [Nom de l'épice] — [Description courte]</p>
      <p style="margin:8px 0 0;color:#5c4a2a;font-style:italic;">✦ [Nom de l'épice] — [Description courte]</p>
    </div>
    <div style="text-align:center;margin:24px 0;">
      <a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Découvrir →</a>
    </div>
  </div>
  <div style="background:#2a2218;padding:20px 32px;text-align:center;">
    <p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma — <a href="#" style="color:#7a5a22;">Se désabonner</a></p>
  </div>
</div>`
    },
    bienvenue: {
        sujet: '👋 Bienvenue dans la communauté WakAroma !',
        html: `<div style="font-family:'Georgia',serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:48px 32px;text-align:center;">
    <h1 style="color:#c9963b;font-size:2.2rem;margin:0 0 8px;">Bienvenue ✦</h1>
    <p style="color:#a89878;margin:0;">Vous faites maintenant partie de la famille WakAroma</p>
  </div>
  <div style="padding:32px;">
    <p style="color:#5c4a2a;font-size:1.05rem;">Bonjour {{NOM}},</p>
    <p style="color:#5c4a2a;line-height:1.7;">Merci de nous rejoindre ! Vous recevrez désormais nos actualités, recettes exclusives et offres en avant-première.</p>
    <div style="border-left:3px solid #c9963b;padding-left:16px;margin:20px 0;color:#8a7a62;font-style:italic;">
      « Les épices ne sont pas seulement des ingrédients, ce sont des histoires. »
    </div>
    <div style="text-align:center;margin:28px 0;">
      <a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Visiter la boutique →</a>
    </div>
  </div>
  <div style="background:#2a2218;padding:20px 32px;text-align:center;">
    <p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma — <a href="#" style="color:#7a5a22;">Se désabonner</a></p>
  </div>
</div>`
    },
    recette: {
        sujet: '🍽 Recette du mois — [Titre de la recette]',
        html: `<div style="font-family:'Georgia',serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;">
    <h1 style="color:#c9963b;font-size:2rem;margin:0 0 8px;">La recette du mois</h1>
    <p style="color:#a89878;margin:0;">[Titre de la recette]</p>
  </div>
  <div style="padding:32px;">
    <p style="color:#5c4a2a;">Bonjour {{NOM}},</p>
    <p style="color:#5c4a2a;line-height:1.7;">Ce mois-ci, nous vous partageons une recette simple et savoureuse qui met en valeur nos épices.</p>
    <h3 style="color:#1a1611;margin:20px 0 8px;">Ingrédients</h3>
    <ul style="color:#5c4a2a;line-height:2;padding-left:18px;">
      <li>[Ingrédient 1]</li>
      <li>[Ingrédient 2]</li>
      <li>[Épice WakAroma]</li>
    </ul>
    <h3 style="color:#1a1611;margin:20px 0 8px;">Préparation</h3>
    <p style="color:#5c4a2a;line-height:1.7;">[Étapes de préparation…]</p>
    <div style="text-align:center;margin:28px 0;">
      <a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Commander les épices →</a>
    </div>
  </div>
  <div style="background:#2a2218;padding:20px 32px;text-align:center;">
    <p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma — <a href="#" style="color:#7a5a22;">Se désabonner</a></p>
  </div>
</div>`
    },
    evenement: {
        sujet: '📅 Événement — [Nom de l\'événement]',
        html: `<div style="font-family:'Georgia',serif;max-width:600px;margin:0 auto;background:#faf8f3;border-radius:12px;overflow:hidden;">
  <div style="background:linear-gradient(135deg,#1a1611,#2a2218);padding:40px 32px;text-align:center;">
    <p style="color:#a89878;margin:0 0 8px;font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;">[DATE]</p>
    <h1 style="color:#c9963b;font-size:1.8rem;margin:0;">[Nom de l'événement]</h1>
  </div>
  <div style="padding:32px;">
    <p style="color:#5c4a2a;">Bonjour {{NOM}},</p>
    <p style="color:#5c4a2a;line-height:1.7;">Nous sommes ravis de vous convier à [description de l'événement].</p>
    <div style="background:#fff;border:1px solid #e8dcc8;border-radius:8px;padding:20px;margin:20px 0;display:flex;gap:20px;">
      <div><strong style="color:#c9963b;">📅</strong> <span style="color:#5c4a2a;">[Date]</span></div>
      <div><strong style="color:#c9963b;">📍</strong> <span style="color:#5c4a2a;">[Lieu]</span></div>
      <div><strong style="color:#c9963b;">⏰</strong> <span style="color:#5c4a2a;">[Heure]</span></div>
    </div>
    <div style="text-align:center;margin:28px 0;">
      <a href="https://wakaroma.fr" style="background:#c9963b;color:#1a1200;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">Je participe →</a>
    </div>
  </div>
  <div style="background:#2a2218;padding:20px 32px;text-align:center;">
    <p style="color:#7a5a22;font-size:.78rem;margin:0;">© WakAroma — <a href="#" style="color:#7a5a22;">Se désabonner</a></p>
  </div>
</div>`
    }
};

function loadTemplate(key) {
    if (!confirm('Charger le template "' + key + '" ? Le contenu actuel sera remplacé.')) return;
    const t = TEMPLATES[key];
    document.getElementById('compose-sujet').value = t.sujet;
    document.getElementById('richEditor').innerHTML = t.html;
    document.getElementById('compose-id').value = '';
    showToast('Template chargé ✓', 'success');
}

// ==========================================
// SAUVEGARDE BROUILLON
// ==========================================
async function saveDraft() {
    const sujet = document.getElementById('compose-sujet').value.trim();
    const html  = document.getElementById('richEditor').innerHTML.trim();
    const id    = document.getElementById('compose-id').value;
    if (!sujet) { showToast('Le sujet est obligatoire', 'error'); return; }
    if (!html)  { showToast('Le contenu est vide', 'error'); return; }
    try {
        const res = await post({ action: 'save_campaign', id, sujet, contenu_html: html });
        document.getElementById('compose-id').value = res.id;
        showToast('Brouillon sauvegardé ✓', 'success');
    } catch (e) { showToast('Erreur : ' + e.message, 'error'); }
}

// ==========================================
// APERÇU
// ==========================================
function openPreview() {
    const sujet = document.getElementById('compose-sujet').value || '(sans sujet)';
    const html  = document.getElementById('richEditor').innerHTML;
    document.getElementById('preview-sujet').textContent = sujet;
    const frame = document.getElementById('previewFrame');
    frame.srcdoc = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{margin:0;padding:16px;background:#fff;}</style></head><body>${html}</body></html>`;
    openModal('preview');
}

// ==========================================
// ENVOI MODAL
// ==========================================
function openSendModal() {
    const sujet = document.getElementById('compose-sujet').value.trim();
    const html  = document.getElementById('richEditor').innerHTML.trim();
    if (!sujet) { showToast('Le sujet est obligatoire avant d\'envoyer', 'error'); return; }
    if (!html)  { showToast('Le contenu est vide', 'error'); return; }
    document.getElementById('send-sujet-preview').textContent = sujet;
    const actifs = allSubscribers.filter(s => s.actif == 1);
    document.getElementById('dest-tous-count').textContent = actifs.length + ' abonné(s) actif(s)';
    selectDestType('tous');
    populateSendSubList();
    openModal('send');
}

function openSendModalWithIds(ids) {
    openSendModal();
    selectDestType('selection');
    setTimeout(() => {
        document.querySelectorAll('.send-sub-check').forEach(cb => {
            cb.checked = ids.includes(cb.value);
        });
        updateSendSelectedCount();
    }, 100);
}

function selectDestType(type) {
    sendDestType = type;
    document.querySelectorAll('.dest-option').forEach(el => el.classList.remove('selected'));
    document.getElementById('dest-opt-' + type)?.classList.add('selected');
    document.querySelectorAll('.dest-option input[type=radio]').forEach(r => r.checked = (r.value === type));
    document.getElementById('sub-selector').style.display = type === 'selection' ? 'block' : 'none';
}

function populateSendSubList() {
    const actifs = allSubscribers.filter(s => s.actif == 1);
    const wrap   = document.getElementById('send-sub-list');
    wrap.innerHTML = actifs.map(s => {
        const initials = s.nom ? s.nom.trim().split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2) : s.email[0].toUpperCase();
        return `<div class="sub-item">
            <input type="checkbox" class="send-sub-check" value="${s.id}" onchange="updateSendSelectedCount()" style="accent-color:var(--gold);width:16px;height:16px;">
            <div class="sub-avatar" style="width:28px;height:28px;font-size:.7rem;">${esc(initials)}</div>
            <div class="sub-info">
                <div class="sub-name">${esc(s.nom || '—')}</div>
                <div class="sub-email">${esc(s.email)}</div>
            </div>
        </div>`;
    }).join('');
    updateSendSelectedCount();
}

function filterSendSubs() {
    const search = document.getElementById('search-send-sub').value.toLowerCase();
    document.querySelectorAll('#send-sub-list .sub-item').forEach(item => {
        const text = item.querySelector('.sub-name').textContent.toLowerCase() + ' ' + item.querySelector('.sub-email').textContent.toLowerCase();
        item.style.display = text.includes(search) ? '' : 'none';
    });
}

function toggleAllSendSubs() {
    const cb    = document.getElementById('check-all-send');
    cb.checked  = !cb.checked;
    document.querySelectorAll('.send-sub-check').forEach(c => c.checked = cb.checked);
    updateSendSelectedCount();
}

function updateSendSelectedCount() {
    const n = document.querySelectorAll('.send-sub-check:checked').length;
    document.getElementById('send-selected-count').textContent = n + ' sélectionné(s)';
}

async function confirmSend() {
    const campId = document.getElementById('compose-id').value;
    const sujet  = document.getElementById('compose-sujet').value.trim();
    const html   = document.getElementById('richEditor').innerHTML.trim();

    // Auto-save avant envoi
    if (!campId) {
        try {
            const res = await post({ action: 'save_campaign', id: '', sujet, contenu_html: html });
            document.getElementById('compose-id').value = res.id;
        } catch (e) { showToast('Erreur sauvegarde : ' + e.message, 'error'); return; }
    }

    const finalId = document.getElementById('compose-id').value;
    let destinataires = 'tous';
    if (sendDestType === 'selection') {
        const ids = [...document.querySelectorAll('.send-sub-check:checked')].map(c => c.value);
        if (!ids.length) { showToast('Sélectionnez au moins un destinataire', 'error'); return; }
        destinataires = JSON.stringify(ids);
    }

    const nbDest = sendDestType === 'tous'
        ? allSubscribers.filter(s => s.actif == 1).length
        : document.querySelectorAll('.send-sub-check:checked').length;

    if (!confirm(`Envoyer cet email à ${nbDest} destinataire(s) ?`)) return;

    const btn = document.getElementById('btn-confirm-send');
    btn.disabled = true;
    btn.textContent = '⏳ Envoi en cours…';

    try {
        const res = await post({ action: 'send_campaign', campaign_id: finalId, destinataires });
        closeModal('send');
        showToast(`✓ ${res.sent}/${res.total} email(s) envoyé(s) avec succès !`, 'success');
        if (res.errors && res.errors.length) {
            showToast(`${res.errors.length} échec(s) : ${res.errors.slice(0,3).join(', ')}`, 'error');
        }
        loadCampaigns();
    } catch (e) { showToast('Erreur envoi : ' + e.message, 'error'); }
    btn.disabled = false;
    btn.innerHTML = '📤 Confirmer l\'envoi';
}

// ==========================================
// STATS
// ==========================================
function updateStats() {
    const total  = allSubscribers.length;
    const actifs = allSubscribers.filter(s => s.actif == 1).length;
    const camps  = allCampaigns.length;
    const sent   = allCampaigns.reduce((acc, c) => acc + (parseInt(c.nb_envoyes) || 0), 0);
    document.getElementById('stat-total').textContent     = total;
    document.getElementById('stat-actif').textContent     = actifs;
    document.getElementById('stat-campaigns').textContent = camps;
    document.getElementById('stat-sent').textContent      = sent;
}

// ==========================================
// MODALS
// ==========================================
function openModal(id)  { document.getElementById('modal-' + id).classList.add('open'); }
function closeModal(id) { document.getElementById('modal-' + id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

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

function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
    const ct  = document.getElementById('toasts');
    const el  = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${type === 'success' ? '✓' : type === 'info' ? 'ℹ' : '✕'}</span> ${msg} <span class="toast-close" onclick="this.parentElement.remove()">×</span>`;
    ct.appendChild(el);
    setTimeout(() => el.remove(), 5000);
}

// ==========================================
// INIT
// ==========================================
loadSubscribers();
</script>
</body>
</html>
