<?php
session_start();

require_once 'pdo.php';
require_once 'function.php';



// temps
$expire_time = 120;

if (!isset($_SESSION['code_time']) || empty($_SESSION['code_time']))
{
    $_SESSION['code_time'] = time();
}



$remaining_time = getRemainingTime($expire_time);


$errors = [];


if ($_SERVER['REQUEST_METHOD'] === "POST") {

    // recalcul au moment du POST
    $remaining_time = getRemainingTime($expire_time);

    if ($remaining_time <= 0) {

        $errors []= "Le code a expiré. Veuillez demander un nouveau code.";

    } else {

        if (!empty($_POST['conf'])) {

            $code = trim($_POST['conf']);

            $result = confirmation_code($code);

            if ($result === true) {

                // nettoyage session
                unset($_SESSION['code_time']);

                $_SESSION['success'] = "Compte confirmé avec succès.";

                header("Location: login.php");
                exit();

            } else {

                $errors [] = $result;
            }

        } else {

            $errors [] = "Veuillez entrer le code de confirmation.";
        }
    }
}
?>

<link rel="stylesheet" href="style.css">

<?php if(!empty(isset($_SESSION['email']))):?>
    <div class="login-page">

        <div class="login-card">

            <div class="login-card__brand">

                <img src="logo/logo.jpeg" class="login-card__logo">

                <h1 class="login-card__title"> WakAroma </h1>

                <p class="login-card__subtitle"> Épices d'Afrique </p>

            </div>

            <a href="index.php" class="login-card__back"> ← Retour </a>

            <!-- message  succee -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert--success">
                    <?= htmlspecialchars($_SESSION['success']) ?><br>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            
            <!-- message erreur -->
                <div class="alert--error">
                    <?php if (!empty($errors)): ?>
                        <ul>
                            <?php foreach($errors as $error):?>
                                <li><?= htmlspecialchars($error)?></li>
                            <?php endforeach;?>
                        </ul>
                    <?php endif; ?>
                    
                    <!-- session d'erreur lancer dans la page resendCode -->
                    <?php if(!empty($_SESSION['errors'])):?>
                        <?= $_SESSION['errors'] ?>
                    <?php endif;?>
                </div>



            <!-- temps -->
            <?php if ($remaining_time > 0): ?>
                <div class="alert--success"> Code valide pendant : <span id="timer"></span> </div>
            <?php else: ?>
                <div class="alert--error"> Code expiré </div>
            <?php endif; ?>

            <h3 class="login-title"> Confirmation du compte </h3>

            <!-- formulaire -->
            <form method="POST" class="code-form">

                <p>Entrez le code reçu par email</p>

                <div class="code-inputs">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                    <input type="text" maxlength="1" class="code-input">
                </div>

                <!--  -->
                <input type="hidden" name="conf" id="conf">

                <!-- nouveau code -->
                <div id="resend-block">
                    <?php if ($remaining_time <= 0): ?>
                        <a href="resendCode.php"> Envoyer un nouveau code </a>
                    <?php endif; ?>
                </div>

                <!-- button -->
                <button type="submit" id="btn-confirm" class="btn-login" disabled > Confirmer </button>

            </form>

        </div>

    </div>

    <?php require_once 'footer.php'; ?>

    <!-- js -->
    <script>
        const remainingTime = <?= $remaining_time ?>;
    </script>
    <script src="script/confirm.js"></script>


<?php else: ?>
    <?php header("Location: index.php"); exit();?>
<?php endif;?>
