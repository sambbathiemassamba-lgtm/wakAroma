<?php
session_start();
require_once 'pdo.php';


$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_conf = $_POST['password_conf'] ?? '';

    // VALIDATION
    if (empty($nom)) $errors[] = "Le champ nom n'est pas rempli.";
    if (empty($prenom)) $errors[] = "Le champ prénom est vide.";
    if (empty($numero) || !is_numeric($numero)) $errors[] = "Numéro invalide.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

    if (empty($password) || empty($password_conf)) {
        $errors[] = "Veuillez remplir les mots de passe.";
    } else {
        if ($password !== $password_conf) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Mot de passe minimum 6 caractères.";
        }
    }

    $check = $pdo->prepare("
        SELECT id_user 
        FROM users 
        WHERE email = :email OR numero = :numero
    ");

    $check->execute([
        ':email' => $email,
        ':numero' => $numero
    ]);

    if ($check->fetch()) {
        $errors[] = "L'email ou le numéro existe déjà.";
    }

    // INSERT USER
    if (empty($errors)) {

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
      
        
        $insert = $pdo->prepare("
            INSERT INTO users (nom, prenom, email, numero, password_hash, confirmation_token)
            VALUES (:nom, :prenom, :email, :numero, :password_hash)
        ");

        $insert->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':email' => $email,
            ':numero' => $numero,
            ':password_hash' => $password_hash,
        ]);
        
        // $success = "Votre inscription a été effectuée avec succès.";
    }
}
?>

<link rel="stylesheet" href="style.css">


<div class="login-page">

    <div class="login-page__overlay"></div>

    <div class="login-card">

        <!-- LOGO -->
        <div class="login-card__brand">
            <img src="logo/logo.jpeg" class="login-card__logo">
            <h1 class="login-card__title">WakAroma</h1>
            <p class="login-card__subtitle">Épices d'Afrique</p>
        </div>

        <a href="index.php" class="login-card__back">← Retour à la boutique</a>

        <h1 class="login-title">Bienvenue</h1>
        <p class="login-subtitle">Rejoignez WakAroma et découvrez nos saveurs d’Afrique.</p>

        <br>

        <!-- ALERTES -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert--success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <br>

        <!-- FORMULAIRE -->
        <form method="POST" class="login-form">

            <!-- nom -->
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom"  placeholder="nom de famille">
            </div>

            <!-- prenom -->
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom"  placeholder="prenom">
            </div>

            <!-- numero telephone -->
            <div class="form-group">
                <label>Numéro</label>
                <input type="text" name="numero"  placeholder="07 60 90 06 21">
            </div>

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

            <!-- confirmation du mot de passe  -->
            <div class="form-group">
                <label>Confirmation mot de passe</label>
                <input type="password" name="password_conf" placeholder="***************">
            </div>

            <button type="submit" class="btn-login">S'INSCRIRE</button>

        </form>

        <br>
        <p class="login-register">
            Vous avez déjà un compte ?
            <a href="login.php">Connectez-vous</a>
        </p>

    </div>

</div>

<!-- FOOTER -->
<?php require_once "footer.php"; ?>