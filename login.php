
<?php
session_start();

require_once "pdo.php";
require_once "function.php";

// souvenir de l'utilisateur
souvenirMoi();

// Si déjà connecté
if(isset($_SESSION['auth']))
{
    header("Location: boutique.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === "POST")
{
    // Vérification champs
    if (!empty($_POST['email']) && !empty($_POST['password']))
    {
        // Sécurisation données
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Recherche utilisateur
        $req = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            AND confirmation_token IS NULL
        ");

        $req->execute([
            ':email' => $email
        ]);

        // Récupération utilisateur
        $user = $req->fetch(PDO::FETCH_OBJ);

        // Vérification mot de passe
        if(($user && $user->confirmation_token == NULL) && 
            password_verify($password, $user->password_hash)
        )
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
                    AND confirmation_token IS NULL
                ")->execute([
                    ':souvenir' => $souvenir,
                    ':email' => $user->email
                ]);

                setcookie(
                    'souvenir',
                    $user->id . '===' . $souvenir . '===' . hash('sha256', $user->id . $souvenir),
                    time() + 60 * 60 * 24 * 7,
                    '/'
                );
            }
            
             // Connexion session
                $_SESSION['auth'] = [
                'id_user' => $user->id,
                'prenom'  => $user->prenom
            ];

            // Redirection
            header("Location: boutique.php");
            exit();

        }else{

            $error = "Le mot de passe ou l'email est incorrect ou votre compte n'est pas encore validé.";
        }

    }else{

        $error = "Veuillez remplir tous les champs";
    }
}
?>

<?php require_once 'header_login.php'?>            
        <h1 class="login-title"> Bienvenue</h1>
        <p class="login-subtitle">Accédez à votre compte WakAroma.</p>
        
            <!-- message success apres inscripton -->
        <?php if(!empty($_SESSION['success'])):?>
            <div class="alert--success">
                <?= $_SESSION['success']?>
            </div>
        <?php endif;?>
        
        <!-- ALERTES -->
        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= nl2br(htmlspecialchars($error)); ?>
            </div>
        <?php endif; ?>

    


        <!-- FORMULAIRE -->
        <form method="POST" action="login.php" class="login-form">

            <!-- email -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"  placeholder="nom@exemple.com">
            </div>

            <!-- mot de passe -->
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="***************" >
            </div>


            <!-- pour se souvenir de l'utilisateur -->
            <div class="login-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="souvenir">
                    <span>
                        Se souvenir de moi
                    </span>
                </label>
                <a href="mot-de-passe-oublie.php" class="forgot-link"> Mot de passe oublié ? </a>
            </div>

            <!-- BOUTON -->
            <button type="submit" class="btn-login"> Se connecter </button>

        </form>

        <!-- INSCRIPTION -->
        <p class="login-register"> Pas encore de compte ? 
            <a href="inscription.php">
                Créer un compte
            </a>

        </p>

    </div>

</div>



<!-- FOOTER -->
<?php require_once "footer.php"; ?>