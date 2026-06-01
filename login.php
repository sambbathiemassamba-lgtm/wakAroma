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
        <input type="password" name="password" placeholder="***************">
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