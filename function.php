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
// $recherche (facultatif) : filtre sur le nom du produit, la description
// OU le nom de la catégorie (ex: "café", "épices", "thé")
function recuperation_produits_images(?string $recherche = null)
{
    global $pdo;
    try {
        // On récupère l'image de couverture (is_cover=1) en priorité,
        // sinon la première image disponible, sinon NULL.
        $req = "
        SELECT 
            produits.id_produit,
            produits.nom,
            produits.description,
            produits.prix,
            produits.stock,
            categories.nom AS nom_categorie,
            COALESCE(
                (SELECT url_image FROM images
                 WHERE images.id_produit = produits.id_produit AND images.is_cover = 1
                 LIMIT 1),
                (SELECT url_image FROM images
                 WHERE images.id_produit = produits.id_produit
                 ORDER BY id_image ASC LIMIT 1)
            ) AS url_image,
            (SELECT valeur FROM caracteristiques
             WHERE caracteristiques.id_produit = produits.id_produit AND caracteristiques.nom = 'Poids'
             LIMIT 1) AS unite
        FROM produits
        LEFT JOIN categories ON categories.id_categorie = produits.id_categorie";

        $params = [];

        // Si un terme de recherche est fourni, on filtre
        // uniquement sur le nom du produit OU le nom de la catégorie
        if ($recherche !== null && trim($recherche) !== '') {
            $req .= "
        WHERE produits.nom LIKE :q_nom
           OR categories.nom LIKE :q_cat";

            $like = '%' . trim($recherche) . '%';
            $params = [
                ':q_nom'  => $like,
                ':q_cat'  => $like
            ];
        }

        $req .= "
        ORDER BY produits.id_produit ASC";

        $stmt = $pdo->prepare($req);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_OBJ);

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



    // numero
    if (empty($numero) || !preg_match('/^[0-9+\-\s]+$/', $numero)) {
        $errors[] = "Le numéro de téléphone est invalide.";
    }

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

function confirmation_code(string $code)
{
    global $pdo;

    // Lier le code à l'email de l'utilisateur en cours d'inscription (session)
    $email = $_SESSION['email'] ?? null;

    if (empty($email)) {
        return "Session expirée. Veuillez vous réinscrire.";
    }

    // Recherche utilisateur par email + token
    $req = $pdo->prepare("
        SELECT id_user, confirmation_token
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $req->execute([
        ':email' => $email
    ]);

    $user = $req->fetch(PDO::FETCH_OBJ);

    // Si utilisateur introuvable
    if (!$user) {
        return "Aucun compte en attente de confirmation pour cet email.";
    }

    // Vérification du code saisi vs token en base
    if (trim($user->confirmation_token) !== trim($code)) {
        return "Le code de confirmation est invalide.";
    }

    // Mise à jour : token à NULL = compte confirmé
    $update = $pdo->prepare("
        UPDATE users
        SET confirmation_token = NULL
        WHERE id_user = :id_user
    ");

    $update->execute([
        ':id_user' => $user->id_user
    ]);

    // Vérification que la mise à jour a bien eu lieu
    $check = $pdo->prepare("
        SELECT confirmation_token
        FROM users
        WHERE id_user = :id_user
    ");

    $check->execute([
        ':id_user' => $user->id_user
    ]);

    $result = $check->fetch(PDO::FETCH_OBJ);

    // empty() couvre NULL, "", "0" — plus fiable que === NULL
    if (empty($result->confirmation_token)) {
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




// ══════════════════════════════════════════════════════════════
//  À AJOUTER dans function.php
//  Récupère un produit complet (images + caractéristiques + catégorie)
//  par son id_produit
// ══════════════════════════════════════════════════════════════
 
function recuperation_produit_by_id(int $id_produit): ?object
{
    global $pdo;
 
    // ── Produit + fiche découvrir + image de couverture + catégorie ──
    $sql = "
        SELECT
            p.id_produit,
            p.nom,
            p.description,           -- description courte (catalogue)
            p.reference,
            p.prix,
            p.stock,
            p.seuil_alerte,
            c.nom                    AS nom_categorie,
            COALESCE(
                (SELECT url_image FROM images
                 WHERE images.id_produit = p.id_produit AND images.is_cover = 1
                 LIMIT 1),
                (SELECT url_image FROM images
                 WHERE images.id_produit = p.id_produit
                 ORDER BY id_image ASC LIMIT 1)
            )                        AS url_image,
            dp.description_long,     -- description longue (table decouvrir_produit)
            dp.image_url             -- image dédiée page découvrir
        FROM produits p
        LEFT JOIN categories       c  ON c.id_categorie = p.id_categorie
        LEFT JOIN decouvrir_produit dp ON dp.id_produit  = p.id_produit
        WHERE p.id_produit = :id
        LIMIT 1
    ";
 
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_produit]);
    $produit = $stmt->fetch(PDO::FETCH_OBJ);
 
    if (!$produit) {
        return null;
    }
 
    // ── Caractéristiques ──
    $stmtCarac = $pdo->prepare("
        SELECT nom, valeur
        FROM caracteristiques
        WHERE id_produit = :id
        ORDER BY id_caracteristique ASC
    ");
    $stmtCarac->execute([':id' => $id_produit]);
    $produit->caracteristiques = $stmtCarac->fetchAll(PDO::FETCH_OBJ);
 
    return $produit;
}