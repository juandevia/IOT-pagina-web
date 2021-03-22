<?php
// Basado en https://www.tutorialrepublic.com/php-tutorial/php-mysql-login-system.php
// Credenciales para conectar a la base de datos.
define('DB_SERVER', 'fdb19.125mb.com');
define('DB_USERNAME', '3387047_chefcito');
define('DB_PASSWORD', 'iotchefcito2021');
define('DB_NAME', '3387047_chefcito');

/* Se conecta con la base de datos */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Validar la conexión de base de datos
if($link === false){
    die("ERROR: Could not connect to database server. " . mysqli_connect_error());
}

// Se definen las variables y se inicializan con valores vacios
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";
 
// Se procesa la data ingresada cuando el formulario sea ingresado
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Se valida el nombre de usuario
    if(empty(trim($_POST["username"]))){
        $username_err = "Por favor ingrese un Usuario.";
    } else{
        // Se prepara un estado de seleccion
        $sql = "SELECT id FROM Usuarios WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Vincular variables al estado seleccionado como parametros
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Se establecen los parametros
            $param_username = trim($_POST["username"]);
            
            // Intento de ejecutar el estado seleccionado
            if(mysqli_stmt_execute($stmt)){
                /* Se guarda el resultado */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Este Usuario no está disponible.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Ahhhh! Errores. Por favor intente mas tarde.";
            }

            // Se cierra el estado de seleccion
            mysqli_stmt_close($stmt);
        }
    }
    
    // Se valida la contraseña
    if(empty(trim($_POST["password"]))){
        $password_err = "Por favor ingrese una contraseña.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "La contraseña debe tener al menos 6 caracteres.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Se valida la confirmacion de la contraseña
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Por favor confirme la contraseña.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Las contraseñas no coinciden.";
        }
    }
    
    // Se verifican errores de entrada antes de escribir en la tabla de la base de datos
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Se prepara un estado de insercion
        $sql = "INSERT INTO Usuarios (username, password) VALUES (?, ?)";
         
        if($stmt = mysqli_prepare($link, $sql)){
            // Vincular variables al estado de insercion declarado
            mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);
            
            // Se establecen los parametros
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Se crea un hash para la contraseña
            
            // Intento de ejecutar el estado actual
            if(mysqli_stmt_execute($stmt)){
                // Se redirecciona a la pagina de login
                header("location: login.php");
            } else{
                echo "Ahhhh! Errores. Por favor intente mas tarde.";
            }

            // Se cierra el estado
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
    <title>Registro de Usuario</title>
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
                <li><a href="login.php">Inicia Sesión</a></li>

            </ul>
        </nav>
    </header>
    
    <div class="wrapper">
        <h2>Registro de Usuario</h2>
        <p>Por favor llene este formulario para crear un usuario.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Enviar">
                <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            </div>
            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión</a>.</p>
        </form>
    </div>
    
    <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>
</html>