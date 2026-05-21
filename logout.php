<?php 
session_start();

// destruction de la session
session_destroy();

// suppression de la variable de session
unset($_SESSION['auth']);

// reorientation vers la page index.php
header("Location: index.php");
exit();
