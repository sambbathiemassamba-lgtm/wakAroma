<?php
session_start();

require_once "pdo.php";
require_once "function.php";

// Redirection si non connecté
if (empty($_SESSION['auth'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['auth']['id_user'];

// Annulation du changement email en cours
if (isset($_GET['cancel_email'])) {
    unset($_SESSION['email_change']);
    header("Location: profil.php");
    exit();
}
$errors  = [];
$success = [];

// ── Récupération des données utilisateur ──────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id");
$stmt->execute([':id' => $id_user]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

// ── Traitement formulaires POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1a. Demande de changement email : envoi du code de confirmation
    if (isset($_POST['action']) && $_POST['action'] === 'update_email') {
        $new_email = trim($_POST['new_email'] ?? '');
        $pwd_check = $_POST['pwd_email'] ?? '';

        if (empty($new_email) || empty($pwd_check)) {
            $errors['email'] = "Veuillez remplir tous les champs.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Adresse email invalide.";
        } elseif (!password_verify($pwd_check, $user->password_hash)) {
            $errors['email'] = "Mot de passe incorrect.";
        } else {
            $chk = $pdo->prepare("SELECT id_user FROM users WHERE email = :email AND id_user != :id");
            $chk->execute([':email' => $new_email, ':id' => $id_user]);
            if ($chk->fetch()) {
                $errors['email'] = "Cette adresse email est deja utilisee.";
            } else {
                // Generer code 6 chiffres et stocker en session
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['email_change'] = [
                    'new_email'  => $new_email,
                    'code'       => $code,
                    'expires_at' => time() + 600,
                ];

                // Envoyer le mail avec PHPMailer
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->SMTPDebug  = 0;
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'samzosamb123@gmail.com';
                $mail->Password   = 'oxwcjqcvmoettpkx';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->setFrom('mail@gmail.com', 'WakAroma');
                $mail->addAddress($new_email);
                $mail->addReplyTo('noreply@wakaroma.com', 'No Reply');
                $mail->isHTML(true);
                $mail->Subject = "Confirmation de changement d'email - WakAroma";
                $codeHtml = $code;
                $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body{background:#000;color:#fff;font-family:Arial,sans-serif;margin:0;padding:0;}
.container{background:rgba(0,0,0,0.7);margin:40px auto;padding:40px;max-width:600px;text-align:center;border-radius:10px;border:1px solid #333;}
h1{font-size:22px;color:#fff;}p{font-size:16px;color:#ccc;}
.code{background:rgba(0,0,0,0.9);padding:20px;font-size:38px;color:#c8943a;font-weight:bold;letter-spacing:10px;margin:20px 0;border-radius:8px;border:2px solid #c8943a;}
.footer{margin-top:30px;font-size:13px;color:#888;}
</style></head>
<body><div class="container">
  <h1>Confirmation de changement d'email</h1>
  <p>Voici votre code de confirmation (valable 10 minutes) :</p>
  <div class="code">{$codeHtml}</div>
  <p>Si vous n'avez pas demande ce changement, ignorez cet email.</p>
  <div class="footer">WakAroma - Les saveurs d'Afrique</div>
</div></body></html>
HTML;
                $mail->AltBody = "Code de confirmation WakAroma : {$code} (valable 10 minutes)";

                try {
                    $mail->send();
                    $success['email'] = "Un code de confirmation a ete envoye a {$new_email}. Valable 10 minutes.";
                } catch (Exception $e) {
                    unset($_SESSION['email_change']);
                    $errors['email'] = "Impossible d'envoyer l'email. Verifiez l'adresse saisie.";
                }
            }
        }
    }

    // 1b. Verification du code et validation du changement email
    if (isset($_POST['action']) && $_POST['action'] === 'confirm_email') {
        $code_saisi = trim($_POST['email_code'] ?? '');

        if (empty($_SESSION['email_change'])) {
            $errors['email'] = "Aucune demande en cours. Recommencez.";
        } elseif (time() > $_SESSION['email_change']['expires_at']) {
            unset($_SESSION['email_change']);
            $errors['email'] = "Le code a expire (10 min). Veuillez recommencer.";
        } elseif ($code_saisi !== $_SESSION['email_change']['code']) {
            $errors['email'] = "Code incorrect. Verifiez votre boite mail.";
        } else {
            $pdo->prepare("UPDATE users SET email = :email, updated_at = NOW() WHERE id_user = :id")
                ->execute([':email' => $_SESSION['email_change']['new_email'], ':id' => $id_user]);
            unset($_SESSION['email_change']);
            $success['email'] = "Adresse email mise a jour avec succes !";
            $stmt->execute([':id' => $id_user]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
        }
    }

    // ── 2. Mise à jour téléphone ──────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'update_phone') {
        $new_phone = trim($_POST['new_phone'] ?? '');

        if (empty($new_phone)) {
            $errors['phone'] = "Veuillez saisir un numéro de téléphone.";
        } elseif (!preg_match('/^[\d\s\+\-\(\)]{6,20}$/', $new_phone)) {
            $errors['phone'] = "Format de numéro invalide.";
        } else {
            $pdo->prepare("UPDATE users SET numero = :phone, updated_at = NOW() WHERE id_user = :id")
                ->execute([':phone' => $new_phone, ':id' => $id_user]);
            $success['phone'] = "Numéro de téléphone mis à jour.";
            $stmt->execute([':id' => $id_user]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
        }
    }

    // ── 3. Mise à jour mot de passe ───────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $current_pwd = $_POST['current_password'] ?? '';
        $new_pwd     = $_POST['new_password']     ?? '';
        $confirm_pwd = $_POST['confirm_password'] ?? '';

        if (empty($current_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
            $errors['password'] = "Veuillez remplir tous les champs.";
        } elseif (!password_verify($current_pwd, $user->password_hash)) {
            $errors['password'] = "Le mot de passe actuel est incorrect.";
        } elseif (strlen($new_pwd) < 8) {
            $errors['password'] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } elseif ($new_pwd !== $confirm_pwd) {
            $errors['password'] = "Les mots de passe ne correspondent pas.";
        } else {
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id_user = :id")
                ->execute([':hash' => $hash, ':id' => $id_user]);
            $success['password'] = "Mot de passe modifié avec succès.";
        }
    }
}
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
   SIDEBAR (identique à compte.php)
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
  width: 72px; height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold), var(--color-primary));
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display);
  font-size: 2rem; font-weight: 700; color: #fff;
  box-shadow: 0 0 0 4px rgba(200,148,58,0.35);
  letter-spacing: -0.03em;
}
.sidebar-avatar__name {
  font-family: var(--font-display);
  font-size: 1.25rem; font-weight: 700;
  color: #fff; text-align: center; letter-spacing: -0.01em;
}
.sidebar-avatar__badge {
  font-size: 0.65rem; letter-spacing: 0.2em;
  text-transform: uppercase; font-weight: 600;
  color: var(--color-gold);
  background: rgba(200,148,58,0.18);
  padding: 0.25rem 0.75rem; border-radius: 999px;
  border: 1px solid rgba(200,148,58,0.35);
}
.sidebar-nav__label {
  font-size: 0.62rem; letter-spacing: 0.2em;
  text-transform: uppercase; color: rgba(255,255,255,0.35);
  font-weight: 600; padding: 0.6rem 0.75rem 0.3rem;
  margin-top: 0.5rem;
}
.sidebar-nav__item {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 0.8rem 1rem; border-radius: 12px;
  text-decoration: none; color: rgba(255,255,255,0.7);
  font-size: 0.88rem; font-weight: 500; cursor: pointer;
  transition: all var(--transition);
  border: none; background: transparent;
  width: 100%; text-align: left;
}
.sidebar-nav__item:hover,
.sidebar-nav__item--active {
  color: #fff; background: rgba(255,255,255,0.1);
}
.sidebar-nav__item--active {
  background: rgba(200,148,58,0.25) !important;
  color: var(--color-gold) !important;
  font-weight: 600;
}
.sidebar-nav__icon {
  width: 36px; height: 36px; border-radius: 9px;
  background: rgba(255,255,255,0.08);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 1rem;
  transition: background var(--transition);
}
.sidebar-nav__item--active .sidebar-nav__icon {
  background: rgba(200,148,58,0.3);
}
.sidebar-nav__pill {
  margin-left: auto; font-size: 0.62rem;
  background: var(--color-gold); color: var(--color-green);
  font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px;
}
.sidebar-bottom {
  margin-top: auto; padding-top: 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}

/* ══════════════════════════════════════════
   MAIN CONTENT
   ══════════════════════════════════════════ */
.boutique-main {
  padding: 2.5rem 3rem;
  display: flex; flex-direction: column; gap: 2.5rem;
}

/* ── Topbar ─────────────────────────────── */
.boutique-topbar {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 1rem; flex-wrap: wrap;
}
.boutique-topbar__greeting {
  font-family: var(--font-display);
  font-size: 2.4rem; font-weight: 700;
  color: var(--color-green); letter-spacing: -0.03em; line-height: 1.1;
}
.boutique-topbar__greeting em {
  font-style: italic; color: var(--color-gold);
  font-family: var(--font-italic);
}
.boutique-topbar__sub {
  font-size: 0.9rem; color: var(--color-muted); margin-top: 0.4rem;
}

/* ── Boutons ─────────────────────────────── */
.btn-outline {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.65rem 1.2rem;
  border: 1.5px solid var(--color-border); border-radius: var(--radius-btn);
  background: var(--color-bg-card); color: var(--color-text);
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  text-decoration: none; transition: all var(--transition);
}
.btn-outline:hover {
  border-color: var(--color-primary); color: var(--color-green);
  box-shadow: 0 4px 14px rgba(212,165,116,0.2); transform: translateY(-1px);
}
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.65rem 1.35rem;
  background: var(--color-green); color: #fff;
  border: none; border-radius: var(--radius-btn);
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  text-decoration: none; transition: all var(--transition);
}
.btn-primary:hover {
  background: var(--color-green-mid); transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(31,79,46,0.25);
}

/* ── Section title ───────────────────────── */
.section-title {
  display: flex; align-items: center;
  justify-content: space-between; gap: 1rem; margin-bottom: 1.2rem;
}
.section-title__h {
  font-family: var(--font-display);
  font-size: 1.45rem; font-weight: 700;
  color: var(--color-green); letter-spacing: -0.02em;
}
.section-title__eyebrow {
  font-size: 0.65rem; letter-spacing: 0.18em;
  text-transform: uppercase; color: var(--color-gold);
  font-weight: 600; margin-bottom: 0.2rem;
}

/* ══════════════════════════════════════════
   PROFIL — CARDS DE MODIFICATION
   ══════════════════════════════════════════ */
.profil-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

.profil-card {
  background: var(--color-bg-card, #fff);
  border: 1.5px solid var(--color-border, #e8e0d4);
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.04);
  transition: box-shadow var(--transition), transform var(--transition);
}
.profil-card:hover {
  box-shadow: 0 6px 28px rgba(31,79,46,0.1);
}

/* En-tête de carte */
.profil-card__header {
  display: flex; align-items: center; gap: 1rem;
  padding: 1.4rem 1.8rem;
  background: linear-gradient(135deg, rgba(31,79,46,0.04) 0%, rgba(200,148,58,0.06) 100%);
  border-bottom: 1px solid var(--color-border, #e8e0d4);
  cursor: pointer;
  user-select: none;
}
.profil-card__icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; flex-shrink: 0;
}
.profil-card__icon--email    { background: rgba(200,148,58,0.12); }
.profil-card__icon--phone    { background: rgba(31,79,46,0.1); }
.profil-card__icon--password { background: rgba(80,50,120,0.09); }

.profil-card__title-group { flex: 1; }
.profil-card__title {
  font-family: var(--font-display);
  font-size: 1.05rem; font-weight: 700;
  color: var(--color-green); letter-spacing: -0.01em;
}
.profil-card__subtitle {
  font-size: 0.78rem; color: var(--color-muted); margin-top: 0.15rem;
}
.profil-card__current {
  font-size: 0.82rem; font-weight: 600;
  color: var(--color-text);
  background: rgba(0,0,0,0.04);
  padding: 0.3rem 0.8rem; border-radius: 8px;
  max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.profil-card__chevron {
  margin-left: 0.5rem; color: var(--color-muted);
  font-size: 1.1rem; transition: transform 0.3s ease;
}
.profil-card--open .profil-card__chevron {
  transform: rotate(180deg);
}

/* Corps de carte (formulaire) */
.profil-card__body {
  padding: 0 1.8rem;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.4s cubic-bezier(0.22,1,0.36,1), padding 0.3s ease;
}
.profil-card--open .profil-card__body {
  max-height: 600px;
  padding: 1.6rem 1.8rem;
}

/* Champs */
.form-row {
  display: grid; gap: 1rem;
  margin-bottom: 1rem;
}
.form-row--2 { grid-template-columns: 1fr 1fr; }

.form-group label {
  display: block; font-size: 0.75rem; font-weight: 600;
  letter-spacing: 0.08em; text-transform: uppercase;
  color: var(--color-muted); margin-bottom: 0.45rem;
}
.form-input {
  width: 100%; padding: 0.75rem 1rem;
  border: 1.5px solid var(--color-border, #e8e0d4);
  border-radius: 10px;
  background: var(--color-bg, #fdfaf6);
  color: var(--color-text);
  font-size: 0.88rem; font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}
.form-input:focus {
  outline: none;
  border-color: var(--color-gold);
  box-shadow: 0 0 0 3px rgba(200,148,58,0.15);
}
.form-input--error { border-color: #e74c3c !important; }

/* Password toggle */
.input-pw-wrap { position: relative; }
.input-pw-wrap .form-input { padding-right: 2.8rem; }
.btn-toggle-pw {
  position: absolute; right: 0.9rem; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: var(--color-muted); font-size: 1rem; padding: 0;
  transition: color 0.2s;
}
.btn-toggle-pw:hover { color: var(--color-green); }

/* Strength indicator */
.pw-strength { margin-top: 0.5rem; }
.pw-strength__bar {
  height: 4px; border-radius: 99px;
  background: #e0e0e0; overflow: hidden;
}
.pw-strength__fill {
  height: 100%; border-radius: 99px;
  width: 0; transition: width 0.3s ease, background 0.3s ease;
}
.pw-strength__label {
  font-size: 0.7rem; color: var(--color-muted);
  margin-top: 0.3rem;
}

/* Alertes */
.alert {
  padding: 0.8rem 1rem; border-radius: 10px;
  font-size: 0.82rem; font-weight: 500;
  display: flex; align-items: center; gap: 0.6rem;
  margin-bottom: 1rem;
}
.alert--success {
  background: rgba(39,174,96,0.1); color: #1e8449;
  border: 1px solid rgba(39,174,96,0.25);
}
.alert--error {
  background: rgba(231,76,60,0.08); color: #c0392b;
  border: 1px solid rgba(231,76,60,0.2);
}

/* Footer de form */
.form-footer {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 0.75rem; padding-top: 0.5rem;
  border-top: 1px solid var(--color-border, #e8e0d4);
  margin-top: 1rem;
}
.btn-save {
  display: inline-flex; align-items: center; gap: 0.45rem;
  padding: 0.72rem 1.5rem;
  background: var(--color-green); color: #fff;
  border: none; border-radius: var(--radius-btn);
  font-size: 0.85rem; font-weight: 600; cursor: pointer;
  transition: all var(--transition);
}
.btn-save:hover {
  background: var(--color-green-mid); transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(31,79,46,0.3);
}
.btn-cancel {
  padding: 0.72rem 1.2rem;
  background: transparent; color: var(--color-muted);
  border: 1.5px solid var(--color-border); border-radius: var(--radius-btn);
  font-size: 0.85rem; font-weight: 500; cursor: pointer;
  transition: all var(--transition);
}
.btn-cancel:hover {
  border-color: var(--color-muted); color: var(--color-text);
}

/* ── Carte identité rapide ───────────────── */
.profil-identity {
  display: flex; align-items: center; gap: 1.5rem;
  background: linear-gradient(135deg, var(--color-green) 0%, #2d6a42 100%);
  border-radius: 18px; padding: 1.8rem 2rem;
  color: #fff;
  box-shadow: 0 8px 30px rgba(31,79,46,0.25);
  position: relative; overflow: hidden;
}
.profil-identity::before {
  content: '';
  position: absolute; top: -40px; right: -40px;
  width: 180px; height: 180px; border-radius: 50%;
  background: rgba(255,255,255,0.05);
}
.profil-identity__avatar {
  width: 80px; height: 80px; border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold), #e8b86d);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display);
  font-size: 2.2rem; font-weight: 700; color: var(--color-green);
  flex-shrink: 0;
  box-shadow: 0 0 0 4px rgba(255,255,255,0.2);
  position: relative; z-index: 1;
}
.profil-identity__info { position: relative; z-index: 1; }
.profil-identity__name {
  font-family: var(--font-display);
  font-size: 1.6rem; font-weight: 700; letter-spacing: -0.02em;
}
.profil-identity__name em {
  font-style: italic; color: var(--color-gold);
  font-family: var(--font-italic);
}
.profil-identity__meta {
  font-size: 0.82rem; opacity: 0.7; margin-top: 0.25rem;
}
.profil-identity__badge {
  margin-top: 0.6rem; display: inline-flex; align-items: center; gap: 0.4rem;
  background: rgba(200,148,58,0.25);
  border: 1px solid rgba(200,148,58,0.4);
  color: var(--color-gold); font-size: 0.68rem;
  font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase;
  padding: 0.25rem 0.75rem; border-radius: 999px;
}

/* ── Animations ──────────────────────────── */
.fade-up {
  opacity: 0; transform: translateY(18px);
  animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
}
@keyframes fadeUp { to { opacity:1; transform:none; } }
.fade-up:nth-child(1) { animation-delay: 0.05s; }
.fade-up:nth-child(2) { animation-delay: 0.12s; }
.fade-up:nth-child(3) { animation-delay: 0.19s; }
.fade-up:nth-child(4) { animation-delay: 0.26s; }
.fade-up:nth-child(5) { animation-delay: 0.33s; }

/* ── Responsive ──────────────────────────── */
@media (max-width: 900px) {
  .boutique-layout { grid-template-columns: 1fr; }
  .boutique-sidebar { height: auto; position: static; flex-direction: row; flex-wrap: wrap; padding: 1rem; }
  .boutique-main { padding: 1.5rem 1rem; }
  .form-row--2 { grid-template-columns: 1fr; }
  .profil-identity { flex-direction: column; text-align: center; }
  .boutique-topbar__greeting { font-size: 1.7rem; }
}
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

    <a href="compte.php" class="sidebar-nav__item">
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

    <a href="profil.php" class="sidebar-nav__item sidebar-nav__item--active">
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

    <!-- Topbar -->
    <div class="boutique-topbar fade-up">
      <div>
        <h1 class="boutique-topbar__greeting">
          Mon <em>profil</em>
        </h1>
        <p class="boutique-topbar__sub">
          Gérez vos informations personnelles et la sécurité de votre compte.
        </p>
      </div>
      <div>
        <a href="compte.php" class="btn-outline">
          ← Tableau de bord
        </a>
      </div>
    </div>

    <!-- Carte identité -->
    <div class="profil-identity fade-up">
      <div class="profil-identity__avatar">
        <?= strtoupper(mb_substr($user->prenom ?? '', 0, 1)) . strtoupper(mb_substr($user->nom ?? '', 0, 1)) ?>
      </div>
      <div class="profil-identity__info">
        <div class="profil-identity__name">
          <?= htmlspecialchars($user->prenom ?? '') ?> <em><?= htmlspecialchars($user->nom ?? '') ?></em>
        </div>
        <div class="profil-identity__meta">
          <?= htmlspecialchars($user->email ?? '') ?>
          <?php if (!empty($user->numero)): ?>
            &nbsp;·&nbsp; <?= htmlspecialchars($user->numero) ?>
          <?php endif; ?>
        </div>
        <div class="profil-identity__badge">
          ✦ Membre depuis <?= date('Y', strtotime($user->created_at ?? 'now')) ?>
        </div>
      </div>
    </div>

    <!-- Cartes de modification -->
    <div class="profil-grid fade-up">

      <!-- ── Carte Email ─────────────────────────────────────────────────── -->
      <div class="profil-card <?= isset($success['email']) || isset($errors['email']) || !empty($_SESSION['email_change']) ? 'profil-card--open' : '' ?>" id="card-email">
        <div class="profil-card__header" onclick="toggleCard('card-email')" role="button" aria-expanded="false">
          <div class="profil-card__icon profil-card__icon--email">✉️</div>
          <div class="profil-card__title-group">
            <div class="profil-card__title">Adresse email</div>
            <div class="profil-card__subtitle">Modifiez l'email associé à votre compte</div>
          </div>
          <div class="profil-card__current"><?= htmlspecialchars($user->email ?? '') ?></div>
          <span class="profil-card__chevron">▾</span>
        </div>
        <div class="profil-card__body">

          <?php if (isset($success['email'])): ?>
            <div class="alert alert--success">✓ <?= htmlspecialchars($success['email']) ?></div>
          <?php endif; ?>
          <?php if (isset($errors['email'])): ?>
            <div class="alert alert--error">⚠ <?= htmlspecialchars($errors['email']) ?></div>
          <?php endif; ?>

          <?php if (!empty($_SESSION['email_change'])): ?>
            <!-- ETAPE 2 : saisie du code reçu par mail -->
            <div class="alert alert--success" style="margin-bottom:1.2rem;">
              📧 Un code a été envoyé à <strong><?= htmlspecialchars($_SESSION['email_change']['new_email']) ?></strong>. Saisissez-le ci-dessous.
            </div>
            <form method="POST" action="profil.php">
              <input type="hidden" name="action" value="confirm_email">
              <div class="form-row">
                <div class="form-group">
                  <label for="email_code">Code de confirmation (6 chiffres)</label>
                  <input type="text" id="email_code" name="email_code"
                         class="form-input <?= isset($errors['email']) ? 'form-input--error' : '' ?>"
                         placeholder="ex : 482917"
                         maxlength="6" autocomplete="one-time-code"
                         style="font-size:1.4rem; letter-spacing:0.3em; text-align:center; font-weight:700;">
                </div>
              </div>
              <div class="form-footer">
                <a href="profil.php?cancel_email=1" class="btn-cancel" style="text-decoration:none;">Annuler</a>
                <button type="submit" class="btn-save">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Valider le code
                </button>
              </div>
            </form>

          <?php else: ?>
            <!-- ETAPE 1 : saisie nouvel email + mot de passe -->
            <form method="POST" action="profil.php">
              <input type="hidden" name="action" value="update_email">
              <div class="form-row form-row--2">
                <div class="form-group">
                  <label for="new_email">Nouvel email</label>
                  <input type="email" id="new_email" name="new_email"
                         class="form-input <?= isset($errors['email']) ? 'form-input--error' : '' ?>"
                         placeholder="nouveau@exemple.com"
                         value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label for="pwd_email">Mot de passe actuel</label>
                  <div class="input-pw-wrap">
                    <input type="password" id="pwd_email" name="pwd_email" class="form-input"
                           placeholder="Confirmez votre identité">
                    <button type="button" class="btn-toggle-pw" onclick="togglePw('pwd_email', this)" aria-label="Afficher">👁</button>
                  </div>
                </div>
              </div>
              <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="toggleCard('card-email')">Annuler</button>
                <button type="submit" class="btn-save">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  Recevoir un code
                </button>
              </div>
            </form>
          <?php endif; ?>

        </div>
      </div>

      <!-- ── Carte Téléphone ─────────────────────────────────────────────── -->
      <div class="profil-card <?= isset($success['phone']) || isset($errors['phone']) ? 'profil-card--open' : '' ?>" id="card-phone">
        <div class="profil-card__header" onclick="toggleCard('card-phone')" role="button" aria-expanded="false">
          <div class="profil-card__icon profil-card__icon--phone">📱</div>
          <div class="profil-card__title-group">
            <div class="profil-card__title">Numéro de téléphone</div>
            <div class="profil-card__subtitle">Ajoutez ou modifiez votre numéro</div>
          </div>
          <div class="profil-card__current">
            <?= !empty($user->numero) ? htmlspecialchars($user->numero) : 'Non renseigné' ?>
          </div>
          <span class="profil-card__chevron">▾</span>
        </div>
        <div class="profil-card__body">
          <?php if (isset($success['phone'])): ?>
            <div class="alert alert--success">✓ <?= htmlspecialchars($success['phone']) ?></div>
          <?php endif; ?>
          <?php if (isset($errors['phone'])): ?>
            <div class="alert alert--error">⚠ <?= htmlspecialchars($errors['phone']) ?></div>
          <?php endif; ?>
          <form method="POST" action="profil.php">
            <input type="hidden" name="action" value="update_phone">
            <div class="form-row">
              <div class="form-group">
                <label for="new_phone">Numéro de téléphone</label>
                <input type="tel" id="new_phone" name="new_phone" class="form-input <?= isset($errors['phone']) ? 'form-input--error' : '' ?>"
                       placeholder="+33 6 12 34 56 78"
                       value="<?= htmlspecialchars($_POST['new_phone'] ?? $user->numero ?? '') ?>">
              </div>
            </div>
            <div class="form-footer">
              <button type="button" class="btn-cancel" onclick="toggleCard('card-phone')">Annuler</button>
              <button type="submit" class="btn-save">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Carte Mot de passe ──────────────────────────────────────────── -->
      <div class="profil-card <?= isset($success['password']) || isset($errors['password']) ? 'profil-card--open' : '' ?>" id="card-password">
        <div class="profil-card__header" onclick="toggleCard('card-password')" role="button" aria-expanded="false">
          <div class="profil-card__icon profil-card__icon--password">🔒</div>
          <div class="profil-card__title-group">
            <div class="profil-card__title">Mot de passe</div>
            <div class="profil-card__subtitle">Renforcez la sécurité de votre compte</div>
          </div>
          <div class="profil-card__current">••••••••••••</div>
          <span class="profil-card__chevron">▾</span>
        </div>
        <div class="profil-card__body">
          <?php if (isset($success['password'])): ?>
            <div class="alert alert--success">✓ <?= htmlspecialchars($success['password']) ?></div>
          <?php endif; ?>
          <?php if (isset($errors['password'])): ?>
            <div class="alert alert--error">⚠ <?= htmlspecialchars($errors['password']) ?></div>
          <?php endif; ?>
          <form method="POST" action="profil.php">
            <input type="hidden" name="action" value="update_password">
            <div class="form-row">
              <div class="form-group">
                <label for="current_password">Mot de passe actuel</label>
                <div class="input-pw-wrap">
                  <input type="password" id="current_password" name="current_password"
                         class="form-input <?= isset($errors['password']) ? 'form-input--error' : '' ?>"
                         placeholder="Votre mot de passe actuel">
                  <button type="button" class="btn-toggle-pw" onclick="togglePw('current_password', this)" aria-label="Afficher">👁</button>
                </div>
              </div>
            </div>
            <div class="form-row form-row--2">
              <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <div class="input-pw-wrap">
                  <input type="password" id="new_password" name="new_password"
                         class="form-input" placeholder="8 caractères minimum"
                         oninput="updateStrength(this.value)">
                  <button type="button" class="btn-toggle-pw" onclick="togglePw('new_password', this)" aria-label="Afficher">👁</button>
                </div>
                <div class="pw-strength">
                  <div class="pw-strength__bar"><div class="pw-strength__fill" id="pw-fill"></div></div>
                  <div class="pw-strength__label" id="pw-label">Saisissez un mot de passe</div>
                </div>
              </div>
              <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <div class="input-pw-wrap">
                  <input type="password" id="confirm_password" name="confirm_password"
                         class="form-input" placeholder="Répétez le mot de passe">
                  <button type="button" class="btn-toggle-pw" onclick="togglePw('confirm_password', this)" aria-label="Afficher">👁</button>
                </div>
              </div>
            </div>
            <div class="form-footer">
              <button type="button" class="btn-cancel" onclick="toggleCard('card-password')">Annuler</button>
              <button type="submit" class="btn-save">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Mettre à jour
              </button>
            </div>
          </form>
        </div>
      </div>

    </div><!-- /profil-grid -->

  </main>
</div>

<!-- Toast -->
<div id="toast-profil" aria-live="polite" style="
  position:fixed; bottom:2rem; right:2rem; z-index:9999;
  background:var(--color-green); color:#fff;
  padding:.85rem 1.4rem; border-radius:12px;
  font-size:.85rem; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,0.18);
  transform:translateY(120px); opacity:0; transition:all 0.3s cubic-bezier(0.22,1,0.36,1);
  pointer-events:none;
"></div>

<script>
// ── Accordion des cartes ──────────────────────────────────────────────────────
function toggleCard(id) {
  const card = document.getElementById(id);
  const isOpen = card.classList.contains('profil-card--open');
  // Fermer toutes
  document.querySelectorAll('.profil-card--open').forEach(c => c.classList.remove('profil-card--open'));
  // Ouvrir si était fermé
  if (!isOpen) card.classList.add('profil-card--open');
}

// Ouvrir automatiquement les cartes avec erreur/succès (déjà en PHP via classe)
// + Toast si succès
<?php if (!empty($success)): ?>
  window.addEventListener('DOMContentLoaded', () => {
    const msgs = <?= json_encode(array_values($success)) ?>;
    if (msgs.length) showToast('✓ ' + msgs[0]);
  });
<?php endif; ?>

// ── Toggle visibilité mot de passe ────────────────────────────────────────────
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.textContent = isText ? '👁' : '🙈';
}

// ── Indicateur de force du mot de passe ──────────────────────────────────────
function updateStrength(pw) {
  const fill  = document.getElementById('pw-fill');
  const label = document.getElementById('pw-label');
  if (!fill) return;

  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  const levels = [
    { pct: '0%',   color: '#e0e0e0', txt: 'Saisissez un mot de passe' },
    { pct: '20%',  color: '#e74c3c', txt: 'Très faible' },
    { pct: '40%',  color: '#e67e22', txt: 'Faible' },
    { pct: '60%',  color: '#f1c40f', txt: 'Moyen' },
    { pct: '80%',  color: '#2ecc71', txt: 'Fort' },
    { pct: '100%', color: '#27ae60', txt: 'Très fort 💪' },
  ];
  const lvl = pw.length === 0 ? levels[0] : levels[Math.min(score, 5)];
  fill.style.width      = lvl.pct;
  fill.style.background = lvl.color;
  label.textContent     = lvl.txt;
  label.style.color     = lvl.color === '#e0e0e0' ? 'var(--color-muted)' : lvl.color;
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='success') {
  const el = document.getElementById('toast-profil');
  el.textContent = msg;
  el.style.background = type === 'error' ? '#c0392b' : 'var(--color-green)';
  el.style.transform = 'translateY(0)'; el.style.opacity = '1';
  clearTimeout(el._t);
  el._t = setTimeout(() => { el.style.transform = 'translateY(120px)'; el.style.opacity = '0'; }, 3000);
}

// ── Animations fade-up ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.style.animationPlayState = 'running'; observer.unobserve(e.target); }
    });
  }, { threshold: 0.06 });
  document.querySelectorAll('.fade-up').forEach(el => {
    el.style.animationPlayState = 'paused';
    observer.observe(el);
  });
});
</script>

<?php require_once 'footer.php'; ?>