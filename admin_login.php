<?php
session_start();

// Si déjà connecté en admin → rediriger vers stock
if (isset($_SESSION['admin_auth'])) {
    header("Location: stock.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=wakaroma;charset=utf8",
                "root", "",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_OBJ);

            if ($admin && password_verify($password, $admin->password_hash)) {
                $_SESSION['admin_auth'] = [
                    'id'    => $admin->id,
                    'email' => $admin->email,
                    'nom'   => $admin->nom,
                ];
                header("Location: stock.php");
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur de connexion à la base de données.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administration — WakAroma</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --bg:         #0f0d0a;
    --surface:    #1a1611;
    --surface2:   #231f18;
    --border:     #332d22;
    --border2:    #4a4030;
    --gold:       #c9963b;
    --gold-light: #e8b860;
    --cream:      #f5edd8;
    --cream-dim:  #a89878;
    --red:        #c0392b;
    --red-bg:     #2d0f0a;
    --red-border: #8b2a1f;
    --text:       #e8dcc8;
    --text-dim:   #8a7a62;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-image:
        radial-gradient(ellipse 60% 40% at 20% 10%, rgba(201,150,59,.08) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 80% 90%, rgba(201,150,59,.05) 0%, transparent 60%);
}
.card {
    background: var(--surface);
    border: 1px solid var(--border2);
    border-radius: 20px;
    padding: 48px 44px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 24px 80px rgba(0,0,0,.6);
}
.logo-wrap {
    text-align: center;
    margin-bottom: 32px;
}
.logo-wrap img {
    width: 72px;
    height: 72px;
    object-fit: contain;
    border-radius: 14px;
    margin-bottom: 16px;
}
.login-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.7rem;
    color: var(--cream);
    text-align: center;
    margin-bottom: 6px;
}
.login-sub {
    text-align: center;
    font-size: .85rem;
    color: var(--text-dim);
    margin-bottom: 32px;
}
.badge-admin {
    display: inline-block;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: #1a1200;
    font-size: .7rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}
.alert-error {
    background: var(--red-bg);
    border: 1px solid var(--red-border);
    color: #e74c3c;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: .88rem;
    margin-bottom: 24px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 18px;
}
.form-group label {
    font-size: .78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-dim);
}
.form-group input {
    padding: 12px 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .95rem;
    outline: none;
    transition: border-color .2s;
}
.form-group input:focus { border-color: var(--gold); }
.btn-login {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
    color: #1a1200;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin-top: 8px;
    transition: all .2s;
    letter-spacing: .02em;
}
.btn-login:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,150,59,.4); }
.back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    font-size: .82rem;
    color: var(--text-dim);
    text-decoration: none;
    transition: color .2s;
}
.back-link:hover { color: var(--gold); }
.divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 28px 0 0;
}
</style>
</head>
<body>

<div class="card">
    <div class="logo-wrap">
        <img src="logo/logo.jpeg" alt="WakAroma">
        <div><span class="badge-admin">✦ Espace Admin</span></div>
    </div>

    <h1 class="login-title">Administration</h1>
    <p class="login-sub">Accès réservé — WakAroma</p>

    <?php if ($error): ?>
        <div class="alert-error">✕ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php">
        <div class="form-group">
            <label>Email administrateur</label>
            <input type="email" name="email" placeholder="admin@wakaroma.com" required autofocus
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" placeholder="••••••••••••" required>
        </div>
        <button type="submit" class="btn-login">🔐 Se connecter</button>
    </form>

    <hr class="divider">
    <a href="index.php" class="back-link">← Retour au site</a>
</div>

</body>
</html>
