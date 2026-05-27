<?php

//  $_SESSION['errors'] : session des erreurs qu'on affichera dans la page confirmation
//  $_SESSION['success'] : session des success qu'on affichera dans la page confirmation

session_start();

require_once 'pdo.php';
require_once 'function.php';
require_once 'sendEmail.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email'];

//GENERATION CODE
$token = str_random(6);

//UPDATE DB

$req = $pdo->prepare("
    UPDATE users 
    SET confirmation_token = :token
    WHERE email = :email
");

$req->execute([
    'token' => $token,
    'email' => $email
]);


$_SESSION['code_time'] = time();


//ENVOI EMAIL
$mail = new PHPMailer(true);

$send = EnvoieMail($mail, $email, $token);

if ($send === true) {

    $_SESSION['success'] = "Nouveau code envoyé.";

} else {

    $_SESSION['errors'] = "Erreur lors de l'envoi du mail.";

    // suppression de l'utilisateur si l'email est incorrect
    $pdo->prepare("DELETE users WHERE id = : idEmail")->execute([':ideEmail' => $_SESSION['email']]);
}


header("Location: confirmation.php");
exit();