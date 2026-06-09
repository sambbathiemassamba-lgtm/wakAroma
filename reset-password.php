<?php
session_start();
require_once "pdo.php";
require_once "function.php";

$error   = null;
$success = null;
$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');

// Vérifier que le token est valide et non expiré
$user = null;
if (!empty($token)) {
    $req = $pdo->prepare("
        SELECT id_user, prenom, email, reset_token_expires
        FROM users
        WHERE reset_token = :token
        LIMIT 1
    ");
    $req->execute([':token' => $token]);
    $user = $req->fetch(PDO::FETCH_OBJ);

    // Token expiré ?
    if ($user && strtotime($user->reset_token_expires) < time()) {
        $user  = null;
        $error = "Ce lien a expiré. Veuillez refaire une demande de réinitialisation.";
    }
}

if (!$user && empty($error)) {
    $error = "Lien invalide ou déjà utilisé. Veuillez refaire une demande.";
}

// Traitement du nouveau mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password      = $_POST['password']      ?? '';
    $password_conf = $_POST['password_conf'] ?? '';

    if (empty($password) || empty($password_conf)) {
        $error = "Veuillez remplir les deux champs.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $password_conf) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->prepare("
            UPDATE users
            SET password_hash = :hash,
                reset_token = NULL,
                reset_token_expires = NULL,
                updated_at = NOW()
            WHERE id_user = :id
        ")->execute([
            ':hash' => $hash,
            ':id'   => $user->id_user
        ]);

        $success = true;
    }
}
?>

<?php require_once 'header_login.php' ?>

<?php if ($success): ?>

    <div style="text-align:center; padding: 12px 0 24px;">
        <div style="font-size:3rem; margin-bottom:16px;">✅</div>
        <h1 class="login-title">Mot de passe modifié !</h1>
        <p class="login-subtitle">Votre mot de passe a bien été réinitialisé. Vous pouvez maintenant vous connecter.</p>
        <a href="login.php" class="btn-login" style="display:block; text-align:center; text-decoration:none; margin-top:24px;">
            Se connecter
        </a>
    </div>

<?php elseif ($user): ?>

    <h1 class="login-title">Nouveau mot de passe</h1>
    <p class="login-subtitle">Choisissez un nouveau mot de passe pour votre compte <strong><?= htmlspecialchars($user->email) ?></strong>.</p>

    <?php if ($error): ?>
        <div class="alert alert--error">
            <?= nl2br(htmlspecialchars($error)) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="reset-password.php" class="login-form">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
            <label>Nouveau mot de passe</label>
            <div class="pw-wrap">
                <input type="password" name="password" placeholder="Minimum 6 caractères" class="pw-input" required>
                <button type="button" class="pw-toggle" aria-label="Afficher le mot de passe" onclick="togglePw(this)">
                    <svg class="pw-eye pw-eye--show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="pw-eye pw-eye--hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <div class="pw-wrap">
                <input type="password" name="password_conf" placeholder="Répétez le mot de passe" class="pw-input" required>
                <button type="button" class="pw-toggle" aria-label="Afficher le mot de passe" onclick="togglePw(this)">
                    <svg class="pw-eye pw-eye--show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="pw-eye pw-eye--hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">Réinitialiser le mot de passe</button>

    </form>

    <p class="login-register">
        Vous vous souvenez de votre mot de passe ?
        <a href="login.php">Se connecter</a>
    </p>

<?php else: ?>

    <div style="text-align:center; padding: 12px 0 24px;">
        <div style="font-size:3rem; margin-bottom:16px;">❌</div>
        <h1 class="login-title">Lien invalide</h1>
        <p class="login-subtitle"><?= htmlspecialchars($error) ?></p>
        <a href="mot-de-passe-oublie.php" class="btn-login" style="display:block; text-align:center; text-decoration:none; margin-top:24px;">
            Refaire une demande
        </a>
    </div>

<?php endif; ?>

</div>
</div>

<?php require_once "footer.php"; ?>

<style>
.pw-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.pw-wrap .pw-input {
    width: 100%;
    padding-right: 2.8rem;
}
.pw-toggle {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #9a9088;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: color 0.2s;
}
.pw-toggle:hover { color: #c8943a; }
</style>

<script>
function togglePw(btn) {
    const input   = btn.closest('.pw-wrap').querySelector('.pw-input');
    const eyeShow = btn.querySelector('.pw-eye--show');
    const eyeHide = btn.querySelector('.pw-eye--hide');
    const visible = input.type === 'text';
    input.type            = visible ? 'password' : 'text';
    eyeShow.style.display = visible ? '' : 'none';
    eyeHide.style.display = visible ? 'none' : '';
    btn.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
}
</script>
