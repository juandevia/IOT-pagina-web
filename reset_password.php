<?php
// Inicializa una nueva sesión o identifica la sesión actual
session_start();
 
// Se verifica si el usuario esta logeado
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    //  Si no está logeado, se redirecciona a la pagina de inicio de sesión
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
 
// Se definen las variables y se inicializan con valores vacios
$new_password = "";
$confirm_password = "";
$new_password_err = "";
$confirm_password_err = "";
 
// Se pregunta si ya se envió el formulario en el script html
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Una vez se envía el formulario al script php, se valida que la nueva contraseña no está vacío
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Por favor ingrese una nueva contraseña.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){ // Se valida que la nueva contraseña tenga al menos 6 caracteres
        $new_password_err = "La contraseña debe tener al menos 6 caracteres.";
    } else{
        $new_password = trim($_POST["new_password"]); // variable $new_password toma valor 'new_password' dado en el metodo POST
    }
    
    // Se valida la confirmacion de la nueva contraseña contraseña
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Por favor confirme la contraseña.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Las contraseñas no coinciden.";
        }
    }
        
    // Si los datos ingresados en el formulario están correctos
    if(empty($new_password_err) && empty($confirm_password_err)){

        // Se prepara un estado de actualizacion
        $sql = "UPDATE Usuarios SET password = ? WHERE id = ?";
        
        // Se prepara la ejecucion de el estado de actualización solicitado
        if($stmt = mysqli_prepare($link, $sql)){

            // Si sí se puede realizar la ejecución de la lectura de datos solicitado:
            // Se une el parametro string $param_username, integer $param_id a las variables de la tabla de estados "stmt"
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // Se establecen los parametros
            $param_password = password_hash($new_password, PASSWORD_DEFAULT); // Se escribe la nueva contraseña en el parametro de la tabla de estados
            $param_id = $_SESSION["id"];     // Se identifica el 'id' del usuario al que se le cambiará la contraseña
            
            // Intento de ejecutar el estado actual
            if(mysqli_stmt_execute($stmt)){

                // Si se logra ejecutar el estado de actualización solicitado, entonces la contraseña se cambió correctamente
                session_destroy();          // Se destruye la sesion y se redirecciona a la pagina de login
                header("location: login.php");
                exit();

            } else{
                echo "Ahhh! Errores. Por favor intente mas tarde.";
            }

            // Se cierra el estado iniciado
            mysqli_stmt_close($stmt);
        }
    }
    
    // Se cierra la conexion
    mysqli_close($link);
}
?>
 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reiniciar Contraseña</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>

     <!-- Encabezado de la pagina-->
     <header>
        <nav class="contenedor-flex-1">

            <a href="index.html">
                <img src= "img/logo.png" alt="logo" width="250" height="100">
            </a>
            <ul><!--lista no ordenanda con los enlaces-->
                <li><a href="index.html">Inicio </a></li>
                <li><a href="nosotrosl.html">Nosotros</a></li>
                <li><a href="contacto.html">Contacto</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <!-- Clase para crear el formulario de reestablecimiento de contraseña -->
    <div class="wrapper">
        <h2>Cambiar Contraseña</h2>
        <p>Por favor llene el siguiente formulario para reestablecer la contraseña.</p>
        <!-- Se crea el formulario para ser enviado a al codigo php -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
            <div class="form-group">
                <label>Nueva Contraseña</label>
                <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Enviar">
                <a class="btn btn-link ml-2" href="welcome.php">Cancelar</a>
            </div>
        </form>
    </div>
    
        <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>
</html>