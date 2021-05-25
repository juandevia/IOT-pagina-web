<?php

 
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
// zona horaria 
date_default_timezone_set('America/Bogota');
// Se define las variables y se inicializan con valores vacios
$email = "";
$email_err = "";
$token ="";
$url = ""; 
$asunto = ""; 
$cuerpo = ""; 

 
// Se pregunta si ya se envió el formulario en el script html
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Una vez se envía el formulario al script php, se valida si el email está vacío
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor ingrese un correo.";
    } else{
        // Se prepara un estado de selección de datos de la base de datos, retorna una tabla de estado con los datos obtenidos

     
        $sql = "SELECT id FROM Usuarios WHERE email = ?"; 
        
        // Se prepara la ejecucion de el estado de selección solicitado
        if($stmt = mysqli_prepare($link, $sql)){
            // Si sí se puede realizar la ejecución de la lectura de datos solicitado:
            // Se une el parametro $param_email a las variables del statement "stmt"
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Los parametros se establecen a partir de la entrada 'email' dada en el metodo 'POST'
            $param_email = trim($_POST["email"]); 

            if(mysqli_stmt_execute($stmt)){
                // Se guardan los datos obtenidos de la base de datos
                mysqli_stmt_store_result($stmt);
                 // Si la tabla de resultados tiene una sola fila, es posible enviar un correo para reestablecer contra
                if(mysqli_stmt_num_rows($stmt) == 1){

                   $sql = "SELECT id FROM Usuarios WHERE email = ?";
                   mysqli_stmt_bind_param($stmt,"s", $param_id);
                   $url = 'http://'. $_SERVER["SERVER_NAME"].'/login/new_pass.php?id='.$sql.'val='.$id;
                  
                   $asunto = 'Cambiar contraseña';
                   $cuerpo = "Hola, <br /><br  /> Para continuar con el proceso de cambio de contraseña, da click en 
                   el siguiente <a href='$url'> enlace.</a>";
                   
                   if(enviarEmail($email,$asunto,$cuerpo)){
                       echo "Le hemos enviado un correo para que restablezca su contraseña.";
                       exit;
                   } else {
                       $email_err = "Error al enviar correo.";
                   }
 
                    
                } else{
                    // Si la tabla de la base de datos no tiene el 'email' dato, no se reestablece nada
                    $email_err = "Este correo no está registrado en el sistema.";
                }  
                // No se pudo ejecutar el estado de inserción    
            } else{
                echo "Ahhhh! Errores. Por favor intente mas tarde.";
            }
            // Se cierra la tabla de estados de la solicitud
            mysqli_stmt_close($stmt);
        }
    } 
}
?>

<?php 

    function enviarEmail($email,$asunto,$cuerpo){
       use PHPMailer\PHPMailer\PHPMailer;
       use PHPMailer\PHPMailer\Exception;
       require_once 'PHPMailer/PHPMailerAutoload.php'; 
     
       $mail = new PHPMailer(true); 
       $mail -> isSMTP(); 
       $mail -> SMTPSecure = 'tls';
       $mail -> Host = 'smtp.gmail.com';
       $mail -> Port = '587'; 
       
       $mail -> Username = 'chefcitojjj@gmail.com';
       $mail -> Password = 'chefcito2021';
       
       $mail -> setFrom('chefcitojjj@gmail.com', 'Recuperación contraseña');
       $mail ->addAddress('$email'); 
       
       $mail ->Subject = $asunto;
       $mail ->Body = $cuerpo; 
       $mail ->IsHTML(true);
       
       if($mail->send())
       return true;
       else 
       return false;
       
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
                <li><a href="contacto.html">Proyecto</a></li>
                <li><a href="login.php">Inicia Sesión</a></li>

            </ul>
        </nav>
    </header>
    
    <!-- Clase para crear el formulario de recordar contra -->

        <?php 
        if(!empty($email_err)){
            echo '<div class="alert alert-danger">' . $email_err . '</div>';
        }        
        ?>
        
    

        <!-- Se crea el formulario para ser enviado a al codigo php -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    
            <div class="form-group">
                <h2>Recuperar contraseña</h2>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="text" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Enviar">
            </div>
            
            <section class="cuota">
            <span>¿Aun no estás registrado?</span> 
            <a href="registro.php">Registrate</a>
            </section>
        
        </form>
        
    
    
    <!--El pie de pagina -->
    <footer>
        <div> CHEFCITO corporation &copy; Todos los derechos reservados</div>
    </footer>
    
</body>

</html>
