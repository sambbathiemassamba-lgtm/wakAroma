<?php
session_start();

require_once 'pdo.php';
require_once 'function.php';

// temps
$expire_time = 120;

// Initialiser code_time UNE SEULE FOIS (avant tout traitement POST)
// Si on le fait après, un POST réinitialise le timer et le code expire immédiatement
if (!isset($_SESSION['code_time']) || empty($_SESSION['code_time'])) {
    $_SESSION['code_time'] = time();
}

$remaining_time = getRemainingTime($expire_time);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    // Recalculer le temps restant au moment du POST (code_time est déjà fixé)
    $remaining_time = getRemainingTime($expire_time);

    if ($remaining_time <= 0) {

        $errors[] = "Le code a expiré. Veuillez demander un nouveau code.";

    } else {

        if (!empty($_POST['conf'])) {

            $code = trim($_POST['conf']);

            $result = confirmation_code($code);

            if ($result === true) {

                unset($_SESSION['code_time']);

                $_SESSION['success'] = "Compte confirmé avec succès.";

                header("Location: login.php");
                exit();

            } else {

                $errors[] = $result;
            }

        } else {

            $errors[] = "Veuillez entrer le code de confirmation.";
        }
    }
}
?>

<?php if (!empty($_SESSION['email'])): ?>
    <?php require_once 'header_login.php'?>

            

            <!-- SUCCESS -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert--success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- ERREURS -->
            <?php if (!empty($errors) || !empty($_SESSION['errors'])): ?>
                <div class="alert alert--error">

                    <ul>

                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>

                        <?php if (!empty($_SESSION['errors'])): ?>
                            <li><?= htmlspecialchars($_SESSION['errors']) ?></li>
                            <?php unset($_SESSION['errors']); ?>
                        <?php endif; ?>

                    </ul>

                </div>
            <?php endif; ?>

            <!-- TIMER -->
            <?php if ($remaining_time > 0): ?>

                <div class="alert alert--success">
                    Code valide pendant : <span id="timer"></span>
                </div>

            <?php else: ?>

                <div class="alert alert--error">
                    Code expiré
                </div>

            <?php endif; ?>

            <!-- TITLE -->
            <h3 class="login-title">Confirmation du compte</h3>

            <!-- FORM -->
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

                <!-- hidden -->
                <input type="hidden" name="conf" id="conf">


                <!-- ESPACE 1 -->
                <div style="height: 18px;"></div>

                <!-- resend -->
                <div id="resend-block">
                    <?php if ($remaining_time <= 0 && !empty($_SESSION['code_time'])): ?>
                        <a href="resendCode.php" class="resend-link">
                            Envoyer un nouveau code
                        </a>           
                    <?php endif; ?>          
                </div>
                    
                    
                <!-- ESPACE 2 -->
                <div style="height: 22px;"></div>
                    
                    
                <!-- button -->
                <button type="submit" id="btn-confirm" class="btn-login" disabled>
                    Confirmer
                </button>  
            </form>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
    <script>
        const remainingTime = <?= $remaining_time ?>;
    </script>
    <script src="script/confirm.js"></script>
<?php else: ?>

    <?php header("Location: index.php"); exit(); ?>

<?php endif; ?>