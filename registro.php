<?php
// Basado en https://www.tutorialrepublic.com/php-tutorial/php-mysql-login-system.php
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
$username = "";
$password = "";
$confirm_password = "";
$username_err = "";
$password_err = "";
$confirm_password_err = "";
 
// Se pregunta si ya se envió el formulario en el script html
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Una vez se envía el formulario al script php, se valida si el nombre de usuario está vacío
    if(empty(trim($_POST["username"]))){
        $username_err = "Por favor ingrese un Usuario.";

    } else{
        
        // Se prepara un estado de selección de datos de la base de datos, retorna una tabla de estado con los datos obtenidos
        $sql = "SELECT id FROM Usuarios WHERE username = ?";
        
        // Se prepara la ejecucion de el estado de selección solicitado
        if($stmt = mysqli_prepare($link, $sql)){

            // Si sí se puede realizar la ejecución de la lectura de datos solicitado:
            // Se une el parametro $param_username a las variables del statement "stmt"
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Los parametros se establecen a partir de la entrada 'username' dada en el metodo 'POST'
            $param_username = trim($_POST["username"]);
            
            // Se intenta ejecutar el estado de selección solicitado
            if(mysqli_stmt_execute($stmt)){

                // Se guardan los datos obtenidos de la base de datos
                mysqli_stmt_store_result($stmt);
                
                // Si la tabla de resultados tiene una sola fila, quiere decir que el nombre de usuario ya existe
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Este Usuario no está disponible.";
                } else{
                    // Si la tabla de la base de datos no tiene el 'username' dato, se almacena en la variable 'username'
                    $username = trim($_POST["username"]);
                }
            // No se pudo ejecutar el estado de inserción
            } else{
                echo "Ahhhh! Errores. Por favor intente mas tarde.";
            }

            // Se cierra la tabla de estados de la solicitud
            mysqli_stmt_close($stmt);
        }
    }
    
    // Se valida si sí se ingresó una contraseña a partir de la entrada 'password' dada en el metodo 'POST'
    if(empty(trim($_POST["password"]))){
        $password_err = "Por favor ingrese una contraseña.";     
    } elseif(strlen(trim($_POST["password"])) < 6){ // Se valida que tenga al menos 6 caracteres
        $password_err = "La contraseña debe tener al menos 6 caracteres.";
    } else{ // Se guarda la contraseña en la variable 'password'
        $password = trim($_POST["password"]);
    }
    
    // Se valida si sí se ingresó una contraseña a partir de la entrada 'confirm_password' dada en el metodo 'POST'
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Por favor confirme la contraseña.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]); // Se guarda la confirmación de la contraseña en la variable 'confirm_password'
        if(empty($password_err) && ($password != $confirm_password)){ // Se veririca que la contraseña sea igual a la verificación
            $confirm_password_err = "Las contraseñas no coinciden.";
        }
    }
    
    // Si nunca se necesitaron de las variables auxilaires de error, es porque los datos están bien
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)){
        
        // Se prepara un estado de inserción de datos en la base de datos
        $sql = "INSERT INTO Usuarios (username, password) VALUES (?, ?)";
         
        // Se prepara la ejecucion de el estado de inserción solicitado   
        if($stmt = mysqli_prepare($link, $sql)){
        
            // Se unen los parametros $param_username, $param_password a las variables del statement "stmt"
            mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);
            
            $param_username = $username; // Se establece el $param_username de acuerdo a la variabel $username
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Se crea un hash para la cifrar la variable $password, el resultado se guarda en $param_password
            
            // Se intenta ejecutar el estado de inserción solicitado
            if(mysqli_stmt_execute($stmt)){
                // Si la ejecución es correcta, entonces se escribieron los datos en la base de datos
                header("location: login.php"); // Se redirecciona a la pagina de login
            } else{
                // No se pudo ejecutar el estado de inserción
                echo "Ahhhh! Errores. Por favor intente mas tarde.";
            }

            // Se cierra la tabla de estados de la solicitud
            mysqli_stmt_close($stmt);
        }
    }
    
    // Se cierra la conexion con la base de datos
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

     <!-- Encabezado de la pagina -->
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

    <!-- Clase para crear el formulario de registro -->
    <div class="wrapper">
        <h2>Registro de Usuario</h2>
        <p>Por favor llene este formulario para crear un usuario.</p>
        <!-- Se crea el formulario para ser enviado a al codigo php -->
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