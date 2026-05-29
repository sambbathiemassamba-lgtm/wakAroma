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
    try {
        $req = "
        SELECT 
            produits.id_produit,
            produits.nom,
            produits.description,
            produits.prix,
            produits.stock,
            images.url_image
        FROM produits
        LEFT JOIN images
            ON produits.id_produit = images.id_produit
        ORDER BY produits.id_produit ASC";

        return $pdo->query($req)->fetchAll(PDO::FETCH_OBJ);

    } catch (Exception $e) {
        die($e->getMessage());
    }
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
): bool
{
    global $pdo;

    try {
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

        $mail = new PHPMailer(true);
        $sendmail = EnvoieMail($mail, $email, $tokend);

        if ($sendmail === true) {
            return true;
        }

        $pdo->prepare("DELETE FROM users WHERE email = :email")->execute([':email' => $email]);
        return false;
    } catch (Exception $e) {
        $pdo->prepare("DELETE FROM users WHERE email = :email")->execute([':email' => $email]);
        return false;
    }
}


/**
 * ======================================================================================================
 * PAGE CONFIRMATION.PHP
 * ========================================================================================================
 */

function confirmation_code($code)
{
    global $pdo;

    // Recherche utilisateur avec le code
    $req = $pdo->prepare("
        SELECT id_user, confirmation_token
        FROM users
        WHERE confirmation_token = :code
        LIMIT 1
    ");

    $req->execute([
        ':code' => $code
    ]);

    $user = $req->fetch(PDO::FETCH_OBJ);

    // Si code invalide
    if(!$user)
    {
        return "Le code de confirmation est invalide.";
    }

    // Mise à jour du compte
    $update = $pdo->prepare("
        UPDATE users
        SET confirmation_token = NULL
        WHERE id_user = :id_user
    ");

    $update->execute([
        ':id_user' => $user->id_user
    ]);

    // Vérification réelle
    $check = $pdo->prepare("
        SELECT confirmation_token
        FROM users
        WHERE id_user = :id_user
    ");

    $check->execute([
        ':id_user' => $user->id_user
    ]);

    $result = $check->fetch(PDO::FETCH_OBJ);

    // Vérifie si ça a bien été mis à NULL
    if($result->confirmation_token === NULL)
    {
        return true;
    }

    return "Erreur lors de la confirmation du compte.";
}


// compte a rebour
function getRemainingTime(int $expire_time)
{
    $elapsed = time() - $_SESSION['code_time'];

    return max(0, $expire_time - $elapsed);
}


/**
 * =========================================================
 * PAGE LOGIN 
 * ========================================================
 */

function souvenirMoi()
{
    // Vérification cookie + session
    if(isset($_COOKIE['souvenir']) && !isset($_SESSION['auth']))
    {
        global $pdo;

        // Découpage cookie
        $parts = explode('===', $_COOKIE['souvenir']);

        // Vérification structure cookie
        if(count($parts) === 3)
        {
            $userId = $parts[0];
            $token = $parts[1];
            $hash = $parts[2];

            // Recherche utilisateur
            $req = $pdo->prepare("
                SELECT *
                FROM users
                WHERE souvenir_token = :id
            ");

            $req->execute([
                ':id' => $token
            ]);

            $user = $req->fetch(PDO::FETCH_OBJ);

            // Vérification utilisateur
            if($user)
            {


                // Vérification token
                if($token === $user->souvenir_token)
                {
                    // Recréation hash
                    $expectedHash = hash( 'sha256', $user->id . $token);

                    // Vérification hash sécurisé
                    if(hash_equals($expectedHash, $hash))
                    {
                        // Connexion session
                        $_SESSION['auth'] = [
                            'id_user' => $user->id,
                            'prenom'  => $user->prenom
                        ];

                        // Renouvellement cookie
                        setcookie( 'souvenir', $_COOKIE['souvenir'], time() + 60 * 60 * 24 * 7, '/', '', false, true
                        );

                    }else{

                        // Suppression cookie invalide
                        setcookie( 'souvenir', '', time() - 3600, '/'
                        );
                    }

                }else{

                    // Suppression cookie invalide
                    setcookie( 'souvenir', '', time() - 3600, '/'
                    );
                }

            }else{

                // Suppression cookie invalide
                setcookie( 'souvenir', '', time() - 3600, '/'
                );
            }
        }
    }
}