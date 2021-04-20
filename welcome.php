<?php
// Basado en https://www.tutorialrepublic.com/php-tutorial/php-mysql-login-system.php
// Inicializa una nueva sesión o identifica la sesión actual
session_start();
 
// Se verifica si el usuario esta logeado, si no, se redirecciona al pagina de login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>
 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <!-- Encabezado de la pagina-->
    <header>
        <nav class="contenedor-flex-1">

            <a href="index.html">
                <img src= "img/logo.png" alt="logo" width="250" height="100">
            </a>
            <ul class="nav"><!--lista no ordenanda con los enlaces-->
                <li><a href="index.html">Inicio </a></li>
                <li><a href="nosotrosl.html">Nosotros</a></li>
                <li><a href="contacto.html">Proyecto</a></li>
                <li><a href="">Sesión</a>
                    <ul>
                        <li><a href="reset_password.php">Cambia Contraseña</a></li>
                        <li><a href="data.php">Datos usuario</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li> 
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <div align = "center">
        <h1 class="my-5">Hola, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Bienvenido a nuestro sitio.</h1>
    </div>
    
    <section>
        <div align ="center">
            <img src="img/chefcito.gif"  padding="10%">
        </div>
     </section>

    <form action="http://api.thingspeak.com/update?key=CELL2W7CXZIZ6DV9&field1=1" method="post">
        <div align = "center">
            <button type="submit" formtarget="_blank">Tomar Foto en la Raspberry</button>
        </div>   
    </form>

    <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>
</html>
