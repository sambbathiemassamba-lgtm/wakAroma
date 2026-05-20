<?php
// Démarrer la session
session_start();

// Connexion à la base de données (à adapter)
// require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // --- Exemple de vérification (à remplacer par votre logique PDO) ---
        // $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        // $stmt->execute([$email]);
        // $user = $stmt->fetch();
        // if ($user && password_verify($password, $user['password_hash'])) {
        //     $_SESSION['user_id']  = $user['id_user'];
        //     $_SESSION['user_nom'] = $user['prenom'];
        //     header('Location: index.php');
        //     exit;
        // } else {
        //     $error = 'Email ou mot de passe incorrect.';
        // }

        // Placeholder de démonstration :
        $error = 'Email ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — WakAroma</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== PAGE LOGIN ===== */
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background:
                radial-gradient(ellipse 80% 60% at 70% 10%, rgba(212,165,116,0.22) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 10% 80%, rgba(255,159,28,0.12) 0%, transparent 55%),
                #f9f4ee;
            position: relative;
            overflow: hidden;
        }

        /* Motif décoratif discret */
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(212,165,116,0.15) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        /* Forme décorative droite */
        .login-page__deco {
            position: absolute;
            right: -80px;
            top: 50%;
            transform: translateY(-50%);
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212,165,116,0.18) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Carte principale */
        .login-card {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: 2rem;
            box-shadow:
                0 4px 6px rgba(0,0,0,0.04),
                0 24px 60px rgba(31,79,46,0.10),
                0 0 0 1px rgba(212,165,116,0.15);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 440px;
            animation: fadeUp 0.55s cubic-bezier(0.22,1,0.36,1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* En-tête de la carte */
        .login-card__brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-card__logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 1rem;
            box-shadow: 0 4px 18px rgba(212,165,116,0.3);
        }

        .login-card__title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #1f4f2e;
            margin-top: 0.5rem;
            letter-spacing: -0.02em;
        }

        .login-card__subtitle {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #c77f2c;
            font-weight: 600;
        }

        /* Séparateur décoratif */
        .login-card__divider {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, #d4a574, #ff9f1c);
            border-radius: 2px;
            margin: 0 auto 1.8rem;
        }

        /* Formulaire */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .form-group label {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a4a4a;
            letter-spacing: 0.02em;
        }

        .form-group .input-wrap {
            position: relative;
        }

        .form-group .input-wrap svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #c8a87a;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1.5px solid #e8ddd0;
            border-radius: 0.85rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.975rem;
            color: #333;
            background: #fdfaf7;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #d4a574;
            background: #fff;
            box-shadow: 0 0 0 3.5px rgba(212,165,116,0.18);
        }

        /* Toggle mot de passe */
        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #b0a090;
            padding: 0;
            line-height: 0;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: #c77f2c; }

        /* Options */
        .login-form__options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            margin-top: -0.3rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            color: #555;
        }

        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #d4a574;
            cursor: pointer;
        }

        .login-form__forgot {
            color: #c77f2c;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-form__forgot:hover { color: #a05a10; text-decoration: underline; }

        /* Bouton soumettre */
        .btn-login {
            margin-top: 0.5rem;
            padding: 1rem;
            background: linear-gradient(135deg, #d4a574 0%, #ff9f1c 100%);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 0.85rem;
            cursor: pointer;
            letter-spacing: 0.04em;
            box-shadow: 0 6px 22px rgba(212,165,116,0.45);
            transition: transform 0.2s, box-shadow 0.2s, filter 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(212,165,116,0.55);
            filter: brightness(1.05);
        }

        .btn-login:active { transform: translateY(0); }

        /* Messages d'erreur / succès */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert--error {
            background: #fff0ef;
            color: #c0392b;
            border: 1px solid #f5c6c2;
        }

        .alert--success {
            background: #edfaf2;
            color: #1e7e45;
            border: 1px solid #b2e8c8;
        }

        /* Lien inscription */
        .login-card__register {
            margin-top: 1.5rem;
            text-align: center;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            color: #777;
        }

        .login-card__register a {
            color: #1f4f2e;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-card__register a:hover { color: #c77f2c; text-decoration: underline; }

        /* Retour au site */
        .login-card__back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 1.5rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            color: #888;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-card__back:hover { color: #c77f2c; }

        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

<div class="login-page">
    <div class="login-page__deco"></div>

    <div class="login-card">

        <!-- Retour -->
        <a href="index.php" class="login-card__back">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
            Retour à la boutique
        </a>

        <!-- Branding -->
        <div class="login-card__brand">
            <img src="logo/logo.jpeg" alt="Logo WakAroma" class="login-card__logo">
            <h1 class="login-card__title">WakAroma</h1>
            <p class="login-card__subtitle">Épices d'Afrique</p>
        </div>

        <div class="login-card__divider"></div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert--error">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert--success">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST" action="login.php" class="login-form">

            <div class="form-group">
                <label for="email">Adresse email</label>
                <div class="input-wrap">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="votre@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Afficher le mot de passe">
                        <svg id="eye-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="login-form__options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember"> Se souvenir de moi
                </label>
                <a href="mot-de-passe-oublie.php" class="login-form__forgot">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn-login">Se connecter</button>

        </form>

        <p class="login-card__register">
            Pas encore de compte ?
            <a href="inscription.php">Créer un compte</a>
        </p>

    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/>
        `;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}
</script>

</body>
</html>
