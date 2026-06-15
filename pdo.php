<?php 

try{
    $host = "mysql:host=kgaftzfwakaroma.mysql.db;dbname=kgaftzfwakaroma;charset=utf8";
    $user = 'kgaftzfwakaroma';
    $password = 'Wakaroma1';

    $pdo = new PDO($host, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);

}catch(Exception $e)
{
    die($e->getMessage());
}


?>
