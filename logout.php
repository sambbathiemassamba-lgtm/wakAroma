<?php 
session_start();

// Suppression du cookie "souvenir"
setcookie('souvenir', '', time() - 3600, '/');

// Destruction de la session
session_unset();
session_destroy();

// Réorientation vers la page index.php
header("Location: index.php");
exit();