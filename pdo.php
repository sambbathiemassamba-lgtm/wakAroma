<?php 

try{
    $host = "mysql:host=localhost;dbname=wakaroma;charset=utf8";
    $user = 'root';
    $password = '';

    $pdo = new PDO($host, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);

}catch(Exception $e)
{
    die($e->getMessage());
}


?>