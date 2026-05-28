<?php
// $_SESSION['eamil']  session pour recuperer l'email besion dans la page rendNewCode
// $_SESSION['auth'] = $nom; // authentification de l'utilisateur



session_start();
require_once 'sendEmail.php'; // PHHMailer
require_once 'function.php'; // founction 



if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $prenom = htmlspecialchars(trim($_POST['prenom'] ?? ''));
    $numero = htmlspecialchars($_POST['indicatif']);
    $numero .= htmlspecialchars(trim($_POST['numero'] ?? ''));
    $email =  htmlspecialchars(trim($_POST['email'] ?? ''));
    $email_conf = htmlspecialchars(trim($_POST['email_conf'] ?? ''));
    $password =   htmlspecialchars($_POST['password'] ?? '');
    $password_conf = htmlspecialchars($_POST['password_conf'] ?? '');

    // message d'erreur
    $errors = message_errors($nom, $prenom, $numero, $email, $email_conf,$password,$password_conf);

    // INSERT USER
    if (empty($errors)) 
    {
        // mot de passe hache
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $tokend = str_random(6); // founction pour recupere le code de validation
        
        // on cree l'utilisateur
        $inserted = insertion_users($nom, $prenom, $email, $numero, $password_hash, $tokend);

        if ($inserted) {
            $_SESSION['success'] = "Votre inscription a été effectuée avec succès.";
            $_SESSION['email'] = $email;
            header("Location: confirmation.php");
            exit();
        }

        $_SESSION['error'] = "E-mail est incorrect.";
    }


}
?>

<?php require_once 'header_login.php' ?>
        <h1 class="login-title">Bienvenue</h1>
        <p class="login-subtitle">Rejoignez WakAroma et découvrez nos saveurs d’Afrique.</p><br>

        <!-- ALERTES -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?><br>

        <?php if(!empty($_SESSION['error'])):?>
            <div class="alert alert--error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif;?>

        <!-- FORMULAIRE -->
        <form method="POST" class="login-form">
            <!-- champ cache -->
            <input type="hidden" name="indicatif" value="+33" />
           
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

            <!-- numero -->
            <div class="form-group">
            <label>Numéro</label>
            <div class="phone-wrap">
                <div class="dd-wrap">
                <div class="dd-trigger" id="trigger">
                    <span id="flag-display">🇫🇷</span>
                    <span id="dial-display">+33</span>
                    <i class="ti ti-chevron-down"></i>
                </div>
                <div class="dd-panel" id="panel">
                    <div class="dd-search-wrap">
                    <input type="text" id="search" name="numero" placeholder="Rechercher..." autocomplete="off" />
                    </div>
                    <div class="dd-list" id="list"></div>
                </div>
                </div>
                <input class="phone-input" type="tel" name="numero" placeholder="06 12 34 56 78" id="phone" />
            </div>
            </div>

            <!-- email -->
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email"  placeholder="nom@exemple.com">
            </div>

            <!-- confirmation de l'email -->
            <div class="form-group">
                <label>Confiamtion E-mail</label>
                <input type="email" name="email_conf" placeholder="Confirmer email">
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


<script src="script/inscription.js"></script>