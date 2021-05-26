<?php
// Basado en https://www.tutorialrepublic.com/php-tutorial/php-mysql-login-system.php
// Inicializa una nueva sesión o identifica la sesión actual
session_start();
 
// Se verifica si el usuario esta logeado, si no, se redirecciona al pagina de login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Credenciales para conectar a la base de datos.
define('DB_SERVER', 'fdb19.125mb.com');
define('DB_USERNAME', '3387047_chefcito');
define('DB_PASSWORD', 'iotchefcito2021');
define('DB_NAME', '3387047_chefcito');

// Se conecta con la base de datos, retorna un objeto contenedor con la informacion de la conexión
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Si la conexión no es posible con la base de datos
if($link === false){
    die("ERROR: No se pudo conectar con la base de datos " . mysqli_connect_error());
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

            <a href="index_2.html">
                <img src= "img/logo.png" alt="logo" width="250" height="100">
            </a>
            <ul class="nav"><!--lista no ordenanda con los enlaces-->
                <li><a href="index_2.html">Inicio </a></li>
                <li><a href="nosotrosl_2.html">Nosotros</a></li>
                <li><a href="contacto_2.html">Proyecto</a></li>
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
        <h1 class="my-5">Hola, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Bienvenido a nuestro sitio.<b></b></h1>
    </div>

    <div>
      

    </div> 
    
     <!--Cuota-->
     <section class="cuota2">
        <span></span>
        <a>Dashboard</a>
    </section>

    <section>
            <div class="container-dash"> 
                <div class="card">
                    <iframe class="iframee"  src="https://thingspeak.com/apps/matlab_visualizations/402723"></iframe>
                    <h4>Foto de tu última comida</h4>
                    <p>Esta fue última comida, de seguro estuvo deliciosa. </p>
                </div>

                <div class="card">
                    <iframe  class="iframee" src="https://thingspeak.com/apps/matlab_visualizations/402669"></iframe>
                    <h4>Alimentos consumidos en la última comida</h4>
                    <p>estos fueron tus últimos ingredientes, recuerda que cada vez que cocinas te conviertes en un mejor chef.</p>
                </div>

                <div class="card">
                    <iframe class="iframee" src="https://thingspeak.com/apps/matlab_visualizations/402717"></iframe>
                    <h4>Alimentos consumidos en el día</h4>
                    <p>WOW mira la fuente de tus recetas en el día.</p>
                </div>

                <div class="card">
                    <iframe class="iframee" src="https://thingspeak.com/apps/matlab_visualizations/402665"></iframe>
                    <h4>Kilocalorías consumidas en el día</h4>
                    <p>Este fue tu consumo calórico a lo largo del día.</p>
                </div>

                <div class="card">
                    <iframe class="iframee" src="https://thingspeak.com/apps/matlab_visualizations/402728"></iframe>
                    <h4>Kilocalorías totales diarias</h4>
                    <p>Este fue tu consumo calórico durante todo el día.</p>
                </div>

            </div>
    </section>


    <form action="http://api.thingspeak.com/update?key=CELL2W7CXZIZ6DV9&field1=1" method="post">
        <div align = "center">
            <button class="enviar" type="submit" formtarget="_blank">Tomar Foto en la Raspberry</button>
        </div>   
    </form>

    <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>
</html>
