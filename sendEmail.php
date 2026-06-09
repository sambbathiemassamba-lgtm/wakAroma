<?php
//Import PHPMailer classes into the global namespace
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;




function str_random(int $long): string
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

    return substr(
        str_shuffle(
            str_repeat($alphabet, $long)
        ),
        0,
        $long
    );
}

function EnvoieMail(PHPMailer $mail, string $mailToSend, string $tokend): bool
{
    try {
        // Server settings
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'samzosamb123@gmail.com';
    $mail->Password   = 'oxwcjqcvmoettpkx';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Recipients
    $mail->setFrom('mail@gmail.com', 'WakAroma');

    $mail->addAddress($mailToSend, 'User');

    $mail->addReplyTo('noreply@wakaroma.com', 'No Reply');

    // Content
    $mail->isHTML(true);

    $mail->Subject = 'Validation de votre compte';

    $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de Compte</title>
    <style>
        body{
            background-color:#000;
            color:#fff;
            font-family:Arial,sans-serif;
            margin:0;
            padding:0;
        }

        .container{
            background-color:rgba(0,0,0,0.7);
            margin:40px auto;
            padding:40px;
            max-width:600px;
            text-align:center;
            border-radius:10px;
            border:1px solid #333;
        }

        h1{
            font-size:24px;
            color:#fff;
        }

        p{
            font-size:18px;
            color:#ccc;
        }

        .validation-code{
            background-color:rgba(0,0,0,0.9);
            padding:20px;
            font-size:32px;
            color:#00bfff;
            font-weight:bold;
            letter-spacing:5px;
            margin:20px 0;
            border-radius:5px;
            border:2px solid #00bfff;
        }

        .footer{
            margin-top:30px;
            font-size:14px;
            color:#888;
        }
    </style>

</head>
<body>
    <div class="container">

        <h1>Compte créé avec succès</h1>

        <p>Merci de confirmer votre compte :</p>

        <div class="validation-code">
            {$tokend}
        </div>

        <div class="footer">
            Rejoignez WakAroma et découvrez nos saveurs d’Afrique
        </div>

    </div>

</body>
</html>
HTML;

    $mail->AltBody = "Votre code de validation : {$tokend}";

        $mail->send();

        return true;
    } catch (Exception $e) {
        return false;
    }
}