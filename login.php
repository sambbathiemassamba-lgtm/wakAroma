<?php
session_start();

require_once "pdo.php";


require_once "function.php";

// souvenir de l'utilisateur
souvenirMoi();

// Si déjà connecté en admin
if(isset($_SESSION['admin_auth']))
{
    header("Location: stock.php");
    exit();
}

// Si déjà connecté en user
if(isset($_SESSION['auth']))
{
    header("Location: boutique.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === "POST")
{
    if (!empty($_POST['email']) && !empty($_POST['password']))
    {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        // -------------------------------------------------------
        // VÉRIFICATION ADMIN EN PREMIER
        // -------------------------------------------------------
        // -------------------------------------------------------
        // VÉRIFICATION ADMIN EN PREMIER
        // -------------------------------------------------------
        try {
            $reqAdmin = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $reqAdmin->execute([':email' => $email]);
            $admin = $reqAdmin->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            $admin = null;
        }

        if ($admin)
        {
            // L'email est un admin : on vérifie le mot de passe et on s'arrête là
            if (password_verify($password, $admin->password_hash))
            {
                session_regenerate_id(true);
                $_SESSION['admin_auth'] = [
                    'id'    => $admin->id,
                    'email' => $admin->email,
                    'nom'   => $admin->nom,
                ];
                header("Location: stock.php");
                exit();
            }
            else
            {
                // Email admin reconnu mais mauvais mot de passe → on n'essaie pas la table users
                $error = "Le mot de passe ou l'email est incorrect.";
            }
        }
        else
        {
        // -------------------------------------------------------
        // CONNEXION UTILISATEUR CLASSIQUE
        // -------------------------------------------------------
        $req = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email
        ");
        $req->execute([':email' => $email]);
        $user = $req->fetch(PDO::FETCH_OBJ);

        if($user)
        {
            if(!empty($user->confirmation_token))
            {
                $error = "Veuillez confirmer votre compte avant de vous connecter.";
            }
            else
            {
                if(password_verify($password, $user->password_hash))
                {
                    // souvenir de moi
                    if(isset($_POST['souvenir']))
                    {
                        $souvenir = str_random(250);

                        $pdo->prepare("
                            UPDATE users
                            SET souvenir_token = :souvenir,
                                updated_at = NOW()
                            WHERE email = :email
                        ")->execute([
                            ':souvenir' => $souvenir,
                            ':email'    => $user->email
                        ]); 

                        setcookie(
                            'souvenir',
                            $user->id_user . '===' . $souvenir . '===' . hash('sha256', $user->id_user . $souvenir),
                            time() + 60 * 60 * 24 * 7,
                            '/'
                        );
                    }

                    $_SESSION['auth'] = [
                        'id_user' => $user->id_user,
                        'prenom'  => $user->prenom
                    ];

                    header("Location: compte.php");
                    exit();

                } else {
                    $error = "Le mot de passe est incorrect.";
                }
            }

        } else {
            $error = "Aucun compte trouvé avec cet email.";
        }
        } // fin else (pas un admin)

    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>

<?php require_once 'header_login.php'?>            

<h1 class="login-title">Bienvenue</h1>
<p class="login-subtitle">Accédez à votre compte WakAroma.</p>

<?php if(!empty($_SESSION['success'])): ?>
    <div class="alert--success">
        <?= $_SESSION['success'] ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert--error">
        <?= nl2br(htmlspecialchars($error)); ?>
    </div>
<?php endif; ?>

<form method="POST" action="login.php" class="login-form">

    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="nom@exemple.com">
    </div>

    <div class="form-group">
        <label>Mot de passe</label>
        <div class="pw-wrap">
            <input type="password" name="password" placeholder="***************" class="pw-input">
            <button type="button" class="pw-toggle" aria-label="Afficher le mot de passe" onclick="togglePw(this)">
                <svg class="pw-eye pw-eye--show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="pw-eye pw-eye--hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
    </div>

    <div class="login-options">
        <label class="checkbox-label">
            <input type="checkbox" name="souvenir">
            <span>Se souvenir de moi</span>
        </label>
        <a href="mot-de-passe-oublie.php" class="forgot-link">Mot de passe oublié ?</a>
    </div>

    <button type="submit" class="btn-login">Se connecter</button>

</form>

<p class="login-register">
    Pas encore de compte ?
    <a href="inscription.php">Créer un compte</a>
</p>

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