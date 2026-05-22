<?php 

session_start();

require_once 'pdo.php';
require_once 'function.php';

$errors = null;

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if (!empty($_POST['conf'])) {

        $code = trim($_POST['conf']);

        $result = confirmation_code($code);

        if ($result === true)
        {
            header("Location: login.php");
            exit();

        } else {
            $errors = $result;
        }
    }
}
?>



<!-- fichier css -->
<link rel="stylesheet" href="style.css">

<?php if(isset($_SESSION['success'])):?>
<div class="login-page">
    <div class="login-card">
        <div class="login-card__brand">
            <img src="logo/logo.jpeg" class="login-card__logo">
            <h1 class="login-card__title">WakAroma</h1>
            <p class="login-card__subtitle">Épices d'Afrique</p>
        </div>
            
        <a href="index.php" class="login-card__back">← Retour</a>

        <!-- message d'erreur -->
        <?php if(isset($errors)):?>
            <div class="alert--error"><?= $errors ?></div>
        <?php endif; ?><br>


        <h1 class="login-title">Bienvenue</h1>
        <p class="login-subtitle">Rejoignez WakAroma et découvrez nos saveurs d’Afrique.</p><br>


        <!-- confirmation du compte -->
        <div class="alert--success">
                Merci de confirmer votre compte.
        </div>
        <br>

        <!-- formulaire de saissi du code -->
        <form action="" method="POST" class="code-form">
            <h2>Confirmation du compte</h2>
            <p>Entrez le code reçu par email</p>

            <div class="code-inputs">
                <input type="text" maxlength="1" class="code-input">
                <input type="text" maxlength="1" class="code-input">
                <input type="text" maxlength="1" class="code-input">
                <input type="text" maxlength="1" class="code-input">
                <input type="text" maxlength="1" class="code-input">
                <input type="text" maxlength="1" class="code-input">
            </div>

            <!-- valeur finale envoyée -->
            <input type="hidden" name="conf" id="conf">

            <button type="submit" class="btn-login">Confirmer</button>
        </form>
    </div>
</div>
    <?php require_once 'footer.php'?>
    <script src="script/script.js"></script>
<?php else: ?>
    <?php 
        header('Location: index.php'); 
        exit();
    ?>
<?php endif?>