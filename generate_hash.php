<?php
/**
 * UTILITAIRE — Générer un hash bcrypt pour l'admin
 * 
 * 1. Place ce fichier à la racine de ton projet
 * 2. Ouvre http://localhost/generate_hash.php dans ton navigateur
 * 3. Saisis ton mot de passe, copie le hash
 * 4. Colle-le dans la requête SQL UPDATE ci-dessous
 * 5. SUPPRIME ce fichier ensuite !
 */

$hash   = null;
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $password = $_POST['password'];
    if (strlen($password) < 8) {
        $error = "Le mot de passe doit faire au moins 8 caractères.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Générer un hash admin</title>
<style>
    body { font-family: monospace; background: #111; color: #eee; padding: 40px; max-width: 600px; margin: 0 auto; }
    h1 { color: #c9963b; margin-bottom: 24px; }
    input[type=password] { width: 100%; padding: 12px; background: #222; border: 1px solid #444; color: #fff; border-radius: 6px; font-size: 1rem; margin-bottom: 12px; box-sizing: border-box; }
    button { padding: 11px 24px; background: #c9963b; color: #111; font-weight: bold; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; }
    .result { margin-top: 28px; background: #1a1a1a; border: 1px solid #c9963b; border-radius: 8px; padding: 20px; }
    .hash { font-size: .85rem; word-break: break-all; color: #4ecb78; margin: 10px 0; background: #0a1f12; padding: 12px; border-radius: 6px; border: 1px solid #1e6b40; }
    .sql { font-size: .82rem; word-break: break-all; color: #e8b860; background: #1a1200; padding: 12px; border-radius: 6px; border: 1px solid #7a5a22; margin-top: 12px; }
    .warning { color: #e74c3c; background: #2d0f0a; border: 1px solid #8b2a1f; padding: 12px; border-radius: 6px; margin-top: 16px; font-size: .85rem; }
    .error { color: #e74c3c; margin-top: 10px; }
</style>
</head>
<body>
<h1>🔑 Générateur de hash admin</h1>
<p style="color:#8a7a62;margin-bottom:24px;">Saisis le mot de passe souhaité pour ton compte administrateur.</p>

<form method="POST">
    <input type="password" name="password" placeholder="Mot de passe (min. 8 caractères)" autofocus>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <button type="submit">Générer le hash</button>
</form>

<?php if ($hash): ?>
<div class="result">
    <strong style="color:#c9963b;">✓ Hash généré :</strong>
    <div class="hash"><?= htmlspecialchars($hash) ?></div>
    <strong style="color:#c9963b;">Requête SQL à exécuter dans phpMyAdmin :</strong>
    <div class="sql">UPDATE admins SET password_hash = '<?= htmlspecialchars($hash) ?>' WHERE email = 'admin@wakaroma.com';</div>
    <div class="warning">
        ⚠ <strong>IMPORTANT</strong> : Supprime ce fichier <code>generate_hash.php</code> après usage !<br>
        Il ne doit jamais rester accessible en ligne.
    </div>
</div>
<?php endif; ?>

</body>
</html>
