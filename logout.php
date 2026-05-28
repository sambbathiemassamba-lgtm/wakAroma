<?php 
session_start();

setcookie('souvenir', '', time() - 3600);


// reorientation vers la page index.php
header("Location: index.php");
exit();
