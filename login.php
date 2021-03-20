<?php
  
//conexion con la base de datos y el servidor
//	$link = mysqli_connect("fdb19.125mb.com","3387047_indicon","indiconiot2020","3387048_indicon") or die("<h2>No se encuentra el servidor</h2>");
  
// Datos para conectar a la base de datos.
$nombreServidor = "fdb19.125mb.com";
$nombreUsuario = "3387047_chefcito";
$passwordBaseDeDatos = "iotchefcito2021";
$nombreBaseDeDatos = "3387047_chefcito";
  
// Crear conexión con la base de datos.
$conn = new mysqli($nombreServidor, $nombreUsuario, $passwordBaseDeDatos, $nombreBaseDeDatos);
  
// Validar la conexión de base de datos.
if ($conn ->connect_error) {
  die("Connection failed: " . $conn ->connect_error);
}

// Se obtienen los datos cargados en el formulario de login.
$Usuario = $_POST['Usuario'];
$Contraseña = $_POST['Contraseña'];
  
//se encripta la contraseña
$contraseñaUser =md5($Contraseña);
  
// Consulta SQL.
//$sql = "SELECT * FROM Usuarios WHERE Usuario ='$Usuario' AND Password = '$contraseñaUser' " ;
$resultado = mysqli_query($conn, "SELECT * FROM Usuarios WHERE Usuario ='$Usuario' AND Password = '$contraseñaUser' ");

if($resultado){
  // Redireccion al usuario a la página de datos.
  header("Location: datos.html"); 
}else{
  echo 'El usuario o contraseña es incorrecto, <a href="login.html">vuelva a intenarlo</a>.<br/>';
}     
   
?>