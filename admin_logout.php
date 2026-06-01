<?php
session_start();
unset($_SESSION['admin_auth']);
session_destroy();
header("Location: admin_login.php");
exit();
