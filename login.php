<?php
// Basado en https://www.tutorialrepublic.com/php-tutorial/php-mysql-login-system.php
// Inicializa la sesion
session_start();
 
// Se valida si el usuario ya esta loggeado
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    // Si sí, se redirecciona a la pagina de bienvenida
    header("location: welcome.php");
    exit;
}

// Credenciales para conectar a la base de datos.
define('DB_SERVER', 'fdb19.125mb.com');
define('DB_USERNAME', '3387047_chefcito');
define('DB_PASSWORD', 'iotchefcito2021');
define('DB_NAME', '3387047_chefcito');

// Se conecta con la base de datos, retorna un objeto contenedor con la informacion necesaria para conectarse
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Si la conexión no es posible con la base de datos
if($link === false){
    die("ERROR: No se pudo conectar con la base de datos " . mysqli_connect_error());
}

// Se define las variables y se inicializan con valores vacios
$username = "";
$password = "";
$username_err = "";
$password_err = "";
$login_err = "";
 
// Se pregunta si ya se envió el formulario en el script html
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Una vez se envía el formulario al script php, se valida si el nombre de usuario está vacío
    if(empty(trim($_POST["username"]))){
        $username_err = "Por favor ingrese un Nombre de Usuario.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Se valida si la password esta vacia
    if(empty(trim($_POST["password"]))){
        $password_err = "Por favor ingrese su contraseña.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Si los datos ingresados en el formulario no están vacios
    if(empty($username_err) && empty($password_err)){

        // Prepare un estado de seleccion
        $sql = "SELECT id, username, password FROM Usuarios WHERE username = ?";
        
        // Se prepara la ejecucion de el estado de selección solicitado
        if($stmt = mysqli_prepare($link, $sql)){

            // Si sí se puede realizar la ejecución de la lectura de datos solicitado:
            // Se une el parametro $param_username a las variables del statement "stmt"
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Se establecen los parametros
            $param_username = $username;
            
            // Intento de ejecutar el estado seleccionado
            if(mysqli_stmt_execute($stmt)){

                // Se guarda el resultado como tabla de estados en en la variable 'stmt'
                mysqli_stmt_store_result($stmt);
                
                // Si la tabla de resultados tiene una sola fila, quiere decir que el nombre de usuario ya existe
                if(mysqli_stmt_num_rows($stmt) == 1){   

                    // Se vinculan los parámetros $id, $username, $hashed_password a los resultados en la tabla de estados "stmt"
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);

                    // Se extraen los resultados del estado de selección preparado
                    if(mysqli_stmt_fetch($stmt)){
                        // Si sí se pudieron extraer correctamente:
                        // Se verifica si la contraseña es correcta
                        if(password_verify($password, $hashed_password)){
                            // Si la contraseña es correcta, se inicia una nueva sesion
                            session_start();
                            // Se guardan los datos en las variables de la nueva sesión
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            // Se redirecciona el usuario a la pagina de bienvenida
                            header("location: welcome.php");
                        } else{
                            // La contraseña es incorrecta
                            $login_err = "Usuario o Contraseña incorrectos.";
                        }
                    }
                } else{
                    // El Nombre de Usuario no existe
                    $login_err = "Usuario invalido.";
                }
            } else{
                echo "Ahhh! Errores. Por favor intente mas tarde.";
            }

            // Se cierra el estado de seleccion
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
    <title>Inicia Sesión</title>
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
    
    <!-- Clase para crear el formulario de inicio de sesión -->
    <div class="wrapper">
        <h2>Login</h2>
        <p>Por favor ingrese sus datos para Iniciar Sesión.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <!-- Se crea el formulario para ser enviado a al codigo php -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Iniciar Sesión">
            </div>
            <p>¿Aun no estás registrado? <a href="registro.php">Registrate</a>.</p>
        </form>
    </div>
    
    <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>
</html>