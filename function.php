<?php 


require_once 'pdo.php'; // base de donne 
require_once 'sendEmail.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;

// Les fonction

/**
 * ==============================================================================
 * PAGE INDEX.PHP
 * ================================================================================
 */

// fonction pour recuperation produits + images
function recuperation_produits_images()
{
    global $pdo;
    try{
        $req = "
        SELECT 
            produits.nom,
            produits.description,
            produits.prix,
            images.url_image
        FROM produits
        INNER JOIN images
            ON produits.id_produit = images.id_produit";
    }catch(Exception $e)
    {
        die($e->getMessage());
    }

    return $pdo->query($req)->fetchAll(PDO::FETCH_OBJ);
}



/**
 * ===============================================================================
 * PAGE INSCRIPTION.PHP
 * ===============================================================================
 */

// fonction pour afficher les messages d'erreur
function message_errors(
    string $nom, 
    string $prenom, 
    string $numero, 
    string $email,
    string $email_conf,
    string $password,
    string $password_conf)
{
    // tableau message d'erreur
    $errors = [];

    // verification des champs

    // nom 
    if (empty($nom)) $errors[] = "Le champ nom n'est pas rempli.";

    // prenom
    if (empty($prenom)) $errors[] = "Le champ prénom est vide.";



    if (
        empty($numero) ||
        !preg_match('/^[0-9+\-\s]+$/', $numero)
    ) {
        $errors[] = "Le numéro de téléphone est invalide.";
    }

    // numero
    if (empty($numero) || !is_numeric($numero)) $errors[] = "Numéro invalide.";

    // email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if ($email !== $email_conf) $errors[] = "Les emails ne correspondent pas";

    // password
    if(empty($password) || empty($password_conf)) 
    {
        $errors[] = "Veuillez remplir les mots de passe.";
    }
    else 
    {
        if ($password !== $password_conf){
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        if (strlen($password) < 6) {
            $errors[] = "Mot de passe minimum 6 caractères.";
        }
    }

    // verification si l'email exist dans la base de donne 
    global $pdo;
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

    return $errors;
}

// fonction pour connecter l'utilisateur
function insertion_users(
    string $nom,
    string $prenom,
    string $email,
    string $numero,
    string $password_hash,
    string $tokend
)
{
    global $pdo;
    $insert = $pdo->prepare("
        INSERT INTO users (nom, prenom, email, numero, password_hash, confirmation_token)
        VALUES (:nom, :prenom, :email, :numero, :password_hash, :confirmation_token)
    ");
    $insert->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':email' => $email,
        ':numero' => $numero,
        ':password_hash' => $password_hash,
        ':confirmation_token' => $tokend 
    ]);

    // envoie de code de verification 
    $mail = new PHPMailer(true); // creation d'une instance
    $sendmail = EnvoieMail($mail, $email,  $tokend);
    
    if($sendmail)
    {
        $_SESSION['success'] = "Votre inscription a été effectuée avec succès.";
        
        // redirection vers la page de confirmation.ph[p]
        header("Location: confirmation.php");

    }
}


/**
 * ======================================================================================================
 * PAGE INSCRIPTION.PHP
 * ========================================================================================================
 */

function confirmation_code(?string $code)
{
    global $pdo;

    // Récupérer l'utilisateur avec le code
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

        // Créer session utilisateur
        $_SESSION['auth'] = [
            'id_user' => $data->id_user,
            'prenom'  => $data->prenom
        ];

        // Supprimer le token
        $update = "UPDATE users 
                   SET confirmation_token = NULL 
                   WHERE id_user = :id";

        $stmt = $pdo->prepare($update);

        $stmt->execute([
            ':id' => $data->id_user
        ]);

        return true;

    } else {

        return "Le code de confirmation est incorrect.";

    }
}


/*
 * ====================================================
 * PAGE CONFIRMATION
 * ========================================================
 */

// compte a rebour
function getRemainingTime(int $expire_time)
{
    $elapsed = time() - $_SESSION['code_time'];

    return max(0, $expire_time - $elapsed);
}