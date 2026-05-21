
<?php
session_start();

require_once "pdo.php";

$error = null;

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    // Vérification champs
    if (!empty($_POST['email']) && !empty($_POST['password'])) {

        // Sécurisation données
        $email = htmlentities(trim($_POST['email']));
        $password = $_POST['password'];

        // Recherche utilisateur
        $req = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email
        ");

        $req->execute([
            ':email' => $email
        ]);

        // Récupération utilisateur
        $user = $req->fetch(PDO::FETCH_OBJ);

        // Vérification mot de passe
        if ($user && password_verify($password, $user->password_hash)) {

            // Redirection
            header("Location: boutique.php");
            exit();

        } else {

            $error = "Le mot de passe ou l'email est incorrect";

        }

    } 
}
?>

<link rel="stylesheet" href="style.css">

<!-- HEADER -->
        <!-- Branding -->
        <div class="login-card__brand">
            <img src="logo/logo.jpeg" alt="Logo WakAroma" class="login-card__logo">
            <h1 class="login-card__title">WakAroma</h1>
            <p class="login-card__subtitle">Épices d'Afrique</p>
        </div>

<div class="login-page">

    <div class="login-page__overlay"></div>

    <div class="login-card">

        <!-- RETOUR -->
        <a href="index.php" class="login-card__back">
            ← Retour 
        </a>
            
            <h1 class="login-title">
                  Bienvenue
            </h1>

            <p class="login-subtitle">
                Accédez à votre compte WakAroma.
            </p><br>

        <!-- ALERTES -->
        <?php if ($error): ?>
            <div class="alert alert--error">
                <?= htmlspecialchars($error); ?>
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


            <!-- OPTIONS pour recuperer le mot dev passe -->
            <div class="login-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember">
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