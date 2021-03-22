<?php
// Basado en https://www.tutorialrepublic.com/php-tutorial/php-mysql-login-system.php
// Inicializa la sesion
session_start();
 
// Se limpian todas las variables de la sesion
$_SESSION = array();
 
// Se destruye la sesion.
session_destroy();
 
// Se redirecciona a la pagina de login
header("location: login.php");
exit;
?>