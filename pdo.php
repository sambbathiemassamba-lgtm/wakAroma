<?php 

try{
    $host = "mysql:host=localhost;dbname=wakaroma;charset=utf8";
    $user = 'samzo';
    $password = 'Touba:55';

    $pdo = new PDO($host, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);

}catch(Exception $e)
{
    die($e->getMessage());
}


?>