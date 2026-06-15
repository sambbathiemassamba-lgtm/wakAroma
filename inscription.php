<?php
// $_SESSION['eamil']  session pour recuperer l'email besion dans la page rendNewCode
// $_SESSION['auth'] = $nom; // authentification de l'utilisateur

session_start();
require_once 'sendEmail.php'; // PHHMailer
require_once 'function.php'; // founction 

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $prenom = htmlspecialchars(trim($_POST['prenom'] ?? ''));
    // Indicatif vient du champ hidden mis a jour par le JS du dropdown
    $indicatif = trim($_POST['indicatif'] ?? '+33');
    $tel        = trim($_POST['numero']    ?? '');
    // Nettoyer les espaces/tirets et recoller indicatif + numero
    $tel_clean  = preg_replace('/[\s\-\.]/', '', $tel);
    // Eviter la double presence de l'indicatif si le user tape +33...
    if (str_starts_with($tel_clean, $indicatif)) {
        $numero = $tel_clean;
    } else {
        $numero = $indicatif . ltrim($tel_clean, '0');
    }
    $email =  htmlspecialchars(trim($_POST['email'] ?? ''));
    $email_conf = htmlspecialchars(trim($_POST['email_conf'] ?? ''));
    $password =   htmlspecialchars($_POST['password'] ?? '');
    $password_conf = htmlspecialchars($_POST['password_conf'] ?? '');

    // checkbox conditions
    $accept_cgv    = $_POST['accept_cgv']    ?? null;
    $is_entreprise  = !empty($_POST['is_entreprise']) ? 1 : 0;
    $nom_entreprise = htmlspecialchars(trim($_POST['nom_entreprise'] ?? ''));

    // message d'erreur
    $errors = message_errors($nom, $prenom, $numero, $email, $email_conf,$password,$password_conf);

    // verification CGV
    if (!$accept_cgv) {
        $errors[] = "Vous devez accepter les conditions générales.";
    }

    // INSERT USER
    if (empty($errors)) 
    {
        // mot de passe hache
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $tokend = str_random(6); // founction pour recupere le code de validation
        
        // on cree l'utilisateur
        $inserted = insertion_users($nom, $prenom, $email, $numero, $password_hash, $tokend);

        if ($inserted === true) {
            // Mise à jour des champs entreprise si nécessaire
            if ($is_entreprise) {
                try {
                    $pdo_ent = new PDO("mysql:host=localhost;dbname=wakaroma;charset=utf8", "root", "",
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $pdo_ent->prepare("UPDATE users SET is_entreprise=1, nom_entreprise=? WHERE email=?")
                        ->execute([$nom_entreprise, $email]);
                } catch (Exception $e) { /* silencieux */ }
            }
            $_SESSION['success'] = "Votre inscription a été effectuée avec succès.";
            $_SESSION['email']   = $email;
            header("Location: confirmation.php");
            exit();
        }

        if ($inserted === 'mail_failed') {
            // Compte cree mais mail non envoye -> on redirige quand meme
            $_SESSION['warning'] = "Compte créé ! L'email de confirmation n'a pas pu être envoyé. Vérifiez vos spams ou demandez un renvoi.";
            $_SESSION['email']   = $email;
            header("Location: confirmation.php");
            exit();
        }

        // false -> doublon ou erreur SQL
        $_SESSION['error'] = "Cette adresse email ou ce numéro est déjà utilisé.";
    }

}
?>

<?php require_once 'header_login.php' ?>

<style>


/* ENTREPRISE */
.entreprise-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #444;
    font-weight: 600;
}
.entreprise-toggle input {
    transform: scale(1.2);
    accent-color: #c97b2b;
}
.entreprise-field {
    display: none;
    margin-top: 10px;
}
.entreprise-field.visible {
    display: block;
}

/* MOT DE PASSE — TOGGLE VISIBILITÉ */
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
.pw-toggle:hover {
    color: #c8943a;
}

/* CONDITIONS */

.terms-group{
    margin-top:20px;
}

.terms-check{
    display:flex;
    align-items:flex-start;
    gap:10px;
    font-size:14px;
    line-height:1.5;
    color:#444;
}

.terms-check input{
    margin-top:4px;
    transform:scale(1.1);
}

.terms-check a{
    color:#c97b2b;
    text-decoration:none;
    font-weight:600;
}

.terms-check a:hover{
    text-decoration:underline;
}

.terms-preview{
    margin-top:12px;
    padding:12px;
    background:#faf7f2;
    border:1px solid #eee;
    border-radius:10px;
    font-size:13px;
    color:#666;
}

.read-more{
    display:inline-block;
    margin-top:8px;
    color:#000;
    font-weight:600;
    text-decoration:none;
}

.read-more:hover{
    text-decoration:underline;
}

</style>

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

        <?php if(!empty($_SESSION['warning'])):?>
            <div class="alert alert--warning" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;padding:10px 14px;border-radius:8px;margin-bottom:10px;"><?= $_SESSION['warning'] ?></div>
            <?php unset($_SESSION['warning']); ?>
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
                            <input type="text" id="search" placeholder="Rechercher..." autocomplete="off" />
                            </div>
                                <div class="dd-list" id="list"></div>
                            </div>
                        </div>
            
                    <input class="phone-input" type="tel" name="numero" placeholder="06 12 34 56 78" id="phone"/>
            
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
                <div class="pw-wrap">
                    <input type="password" name="password" placeholder="***************" class="pw-input">
                    <button type="button" class="pw-toggle" aria-label="Afficher le mot de passe" onclick="togglePw(this)">
                        <svg class="pw-eye pw-eye--show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="pw-eye pw-eye--hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <!-- confirmation du mot de passe  -->
            <div class="form-group">
                <label>Confirmation mot de passe</label>
                <div class="pw-wrap">
                    <input type="password" name="password_conf" placeholder="***************" class="pw-input">
                    <button type="button" class="pw-toggle" aria-label="Afficher le mot de passe" onclick="togglePw(this)">
                        <svg class="pw-eye pw-eye--show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="pw-eye pw-eye--hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>


            <!-- entreprise -->
            <div class="form-group">
                <label class="entreprise-toggle">
                    <input type="checkbox" name="is_entreprise" id="isEntreprise" value="1"
                        <?= !empty($_POST['is_entreprise']) ? 'checked' : '' ?>>
                    Je représente une entreprise
                </label>
                <div class="entreprise-field" id="entrepriseField">
                    <input type="text" name="nom_entreprise" placeholder="Nom de l'entreprise"
                        value="<?= htmlspecialchars($_POST['nom_entreprise'] ?? '') ?>">
                </div>
            </div>

            <!-- CONDITIONS -->
            <div class="form-group terms-group">

                <label class="terms-check">

                    <input type="checkbox" name="accept_cgv" value="1" required>

                    <span>
                        J’accepte les
                        <a href="conditions.php" target="_blank">
                            Conditions Générales de Vente
                        </a>,
                        la
                        <a href="confidentialite.php" target="_blank">
                            Politique de confidentialité
                        </a>
                        et l’utilisation des cookies.
                    </span>

                </label>

                <div class="terms-preview">

                    <p>
                        Wakaroma protège vos données conformément au RGPD.
                        Vos informations sont utilisées uniquement pour la gestion
                        de votre compte et de vos commandes.
                    </p>

                    <a href="mentionsLegale.php" target="_blank" class="read-more">
                        Lire la suite
                    </a>

                </div>

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


<script>
document.getElementById('isEntreprise').addEventListener('change', function() {
    const field = document.getElementById('entrepriseField');
    field.classList.toggle('visible', this.checked);
});
// Au rechargement si erreur, garder le champ visible si coché
if (document.getElementById('isEntreprise').checked) {
    document.getElementById('entrepriseField').classList.add('visible');
}

function togglePw(btn) {
    const input   = btn.closest('.pw-wrap').querySelector('.pw-input');
    const eyeShow = btn.querySelector('.pw-eye--show');
    const eyeHide = btn.querySelector('.pw-eye--hide');
    const visible = input.type === 'text';
    input.type          = visible ? 'password' : 'text';
    eyeShow.style.display = visible ? '' : 'none';
    eyeHide.style.display = visible ? 'none' : '';
    btn.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
}
</script>
<script src="script/inscription.js"></script>