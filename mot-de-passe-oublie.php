<?php
session_start();
require_once "pdo.php";
require_once "function.php";

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez saisir une adresse email valide.";
    } else {
        // Vérifier si l'email existe en base
        $req = $pdo->prepare("SELECT id_user, prenom FROM users WHERE email = :email LIMIT 1");
        $req->execute([':email' => $email]);
        $user = $req->fetch(PDO::FETCH_OBJ);

        if ($user) {
            // Générer un token de réinitialisation
            $token   = str_random(64);
            $expires = date('Y-m-d H:i:s', time() + 3600); // expire dans 1h

            // Sauvegarder le token en base
            $pdo->prepare("
                UPDATE users 
                SET reset_token = :token, reset_token_expires = :expires 
                WHERE id_user = :id
            ")->execute([
                ':token'   => $token,
                ':expires' => $expires,
                ':id'      => $user->id_user
            ]);

            // Envoyer le mail
            $lien = "https://wakaroma.com/reset-password.php?token=" . $token;

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
             $mail->Username   = 'hodanmeg7@gmail.com';
                $mail->Password   = 'fpqquaaaprqciyqb';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('hodanmeg7@gmail.com', 'wakaroma');
                $mail->addAddress($email, $user->prenom);
                $mail->addReplyTo('noreply@wakaroma.com', 'No Reply');
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { background-color:#0a1f0d; color:#fff; font-family:Arial,sans-serif; margin:0; padding:0; }
        .container { background-color:rgba(31,79,46,0.95); margin:40px auto; padding:40px; max-width:600px; text-align:center; border-radius:16px; border:1px solid rgba(200,148,58,0.3); }
        h1 { font-size:24px; color:#fff; margin-bottom:8px; }
        p { font-size:16px; color:rgba(255,255,255,0.8); line-height:1.6; }
        .btn { display:inline-block; margin:28px 0; padding:16px 36px; background:linear-gradient(135deg,#c8943a,#e8b860); color:#fff; text-decoration:none; border-radius:10px; font-size:17px; font-weight:700; letter-spacing:0.02em; }
        .link-fallback { font-size:13px; color:rgba(255,255,255,0.5); word-break:break-all; }
        .footer { margin-top:30px; font-size:13px; color:rgba(255,255,255,0.4); }
        .warning { font-size:13px; color:rgba(200,148,58,0.8); margin-top:16px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Réinitialisation du mot de passe</h1>
        <p>Bonjour <strong>{$user->prenom}</strong>,<br>Vous avez demandé à réinitialiser votre mot de passe WakAroma.</p>
        <a href="{$lien}" class="btn">Réinitialiser mon mot de passe</a>
        <p class="warning">⚠️ Ce lien expire dans <strong>1 heure</strong>.</p>
        <p class="link-fallback">Si le bouton ne fonctionne pas, copiez ce lien : {$lien}</p>
        <div class="footer">Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</div>
    </div>
</body>
</html>
HTML;
                $mail->AltBody = "Réinitialisez votre mot de passe : {$lien}";
                $mail->send();
                $success = "Un email de réinitialisation a été envoyé à <strong>{$email}</strong>. Vérifiez votre boîte mail (et vos spams).";
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi du mail. Veuillez réessayer.";
            }
        } else {
            // On affiche le même message pour ne pas révéler si l'email existe
            $success = "Si cet email est associé à un compte, vous recevrez un lien de réinitialisation.";
        }
    }
}
?>

<?php require_once 'header_login.php' ?>

<h1 class="login-title">Mot de passe oublié</h1>
<p class="login-subtitle">Saisissez votre email, nous vous enverrons un lien de réinitialisation.</p>

<?php if ($error): ?>
    <div class="alert alert--error">
        <?= nl2br(htmlspecialchars($error)) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert--success">
        <?= $success ?>
    </div>
<?php endif; ?>

<?php if (!$success): ?>
<form method="POST" action="mot-de-passe-oublie.php" class="login-form">

    <div class="form-group">
        <label>Adresse email</label>
        <input type="email" name="email" placeholder="nom@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
    </div>

    <button type="submit" class="btn-login">Envoyer le lien</button>

</form>
<?php else: ?>
    <a href="login.php" class="btn-login" style="display:block; text-align:center; text-decoration:none; margin-top:12px;">
        Retour à la connexion
    </a>
<?php endif; ?>

<p class="login-register">
    Vous vous souvenez de votre mot de passe ?
    <a href="login.php">Se connecter</a>
</p>

</div>
</div>

<?php require_once "footer.php"; ?>
