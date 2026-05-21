<?php
session_start();
require_once 'pdo.php';

$errors = null;

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if (!empty($_POST['conf'])) {

        $code = trim($_POST['conf']);

        // 1. Récupérer l'utilisateur avec le code
        $req = "SELECT id_user, prenom 
                FROM users 
                WHERE confirmation_token = :code 
                LIMIT 1";

        $stmt = $pdo->prepare($req);
        $stmt->execute([
            ':code' => $code
        ]);

        $data = $stmt->fetch(PDO::FETCH_OBJ);

        if ($data) {

            // 2. Créer session utilisateur
            $_SESSION['auth'] = [
                'id_user' => $data->id_user,
                'prenom'  => $data->prenom
            ];

            // 3. Supprimer le token (validation du compte)
            $update = "UPDATE users 
                       SET confirmation_token = NULL 
                       WHERE id_user = :id";

            $stmt = $pdo->prepare($update);
            $stmt->execute([
                ':id' => $data->id_user
            ]);

            // 4. Redirection
            header("Location: boutique.php");
            exit();

        } else {
            $errors = "Le code de confirmation est incorrect.";
        }
    }
}
?>
<link rel="stylesheet" href="style.css">
<?php if(isset($_SESSION['success'])):?>

<div class="login-page">
    <div class="login-card">
        <div class="login-card__brand">
            <img src="logo/logo.jpeg" class="login-card__logo">
            <h1 class="login-card__title">WakAroma</h1>
            <p class="login-card__subtitle">Épices d'Afrique</p>
        </div>
            
        <a href="index.php" class="login-card__back">← Retour à la boutique</a>

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
        <form action="" method="POST" class="login-form">
                <div class="form-group">
                    <label>Confiamtion du compte</label>
                    <input type="text" name="conf" placeholder="code de 6 caractères">
                </div>
                <button type="submit" class="btn-login">VALIDER</button>
        </form>
    </div>
</div>
    <?php require_once 'footer.php'?>
<?php else: ?>
    <?php 
        header('Location: index.php'); 
        exit();
    ?>
<?php endif?>