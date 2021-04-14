<?php

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
$usergender = "";
$weight = "";
$height = "";
$usergender_err = "";
$weight_err = "";
$height_err = "";

// Se pregunta si ya se envió el formulario en el script html
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Una vez  se envía el formulario al script php, se valida si los datos del usuario están vacíos
    if(empty(trim($_POST["usergender"]))){
        $usergender_err = "Por favor ingrese su sexo.";
    } else{
        $usergender = trim($_POST["usergender"]); // variable $usergender toma valor 'usegender' dado en el metodo POST
    }
   
    // Se valida si sí se ingresó una peso a partir de la entrada 'weight' dada en el metodo 'POST'
    if(empty(trim($_POST["weight"]))){
        $weight_err = "Por favor ingrese su peso.";     
    } 
     else{ // Se guarda el peso en la variable 'weight'
        $weight = trim($_POST["weight"]);
    }
    
    // Se valida si sí se ingresó una altura a partir de la entrada 'height' dada en el metodo 'POST'
    if(empty(trim($_POST["height"]))){
        $height_err = "Por favor ingrese su altura.";     
    } else{
        $height = trim($_POST["height"]); // Se guarda la altura en la variable 'height'
    }
    
    // Si nunca se necesitaron de las variables auxilaires de error, es porque los datos están bien
    if(empty($usergender_err) && empty($weight_err) && empty($height_err)){
        
        // Se prepara un estado de inserción de datos en la base de datos
        $sql = "UPDATE Usuarios SET usergender = ?, weight = ?, height = ? WHERE id = ?";
         
        // Se prepara la ejecucion de el estado de inserción solicitado   
        if($stmt = mysqli_prepare($link, $sql)){
        
            // Se unen los parametros $param_usergender, $param_weight, $param_height a las variables del statement "stmt"
            mysqli_stmt_bind_param($stmt, "siii", $param_usergender, $param_weight, $param_height, $param_id);
            
            $param_id = $_SESSION["id"];  
            $param_usergender = $usergender; // Se establece el $param_usergender de acuerdo a la variabel $usergender
            $param_weight = $weight; //  el resultado se guarda en $param_weight
            $param_height = $height; //  el resultado se guarda en $param_height

            // Se intenta ejecutar el estado de inserción solicitado
            if(mysqli_stmt_execute($stmt)){
                // Si la ejecución es correcta, entonces se escribieron los datos en la base de datos
                header("location: welcome.php"); // Se redirecciona a la pagina de user
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
    <title>Registro de datos</title>
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
            <ul class="nav"><!--lista no ordenanda con los enlaces-->
                <li><a href="index.html">Inicio </a></li>
                <li><a href="nosotrosl.html">Nosotros</a></li>
                <li><a href="contacto.html">Contacto</a></li>
                <li><a href="">Sesión</a>
                    <ul>
                        <li><a href="reset_password.php">Cambiar contraseña</a></li>
                        <li><a href="data.php">Datos usuario</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li> 
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Clase para crear el formulario de registro de datos -->
    <div class="wrapper">
        <h2>Registro de datos del usuario</h2>
        <p>Por favor llene este formulario.</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>
    </div>
        <!-- Se crea el formulario para ser enviado a al codigo php -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Género</label>
                <input type="text" name="usergender" class="form-control <?php echo (!empty($usergender_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $usergender; ?>">
                <span class="invalid-feedback"><?php echo $usergender_err; ?></span>
            </div>   
            
            <div class="form-group">
                <label>Peso</label>
                <input type="number" name="weight" class="form-control <?php echo (!empty($weight_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $weight; ?>">
                <span class="invalid-feedback"><?php echo $weight_err; ?></span>
            </div>

            <div class="form-group">
                <label>Altura</label>
                <input type="number" name="height" class="form-control <?php echo (!empty($height_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $height; ?>">
                <span class="invalid-feedback"><?php echo $height_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Enviar">
                <input type="reset" class="btn btn-secondary ml-2" value="Reset">
            </div>
        </form>

    
    <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>
</html>