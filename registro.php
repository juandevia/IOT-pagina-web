<?php
	//conexion con la base de datos y el servidor
	$link = mysqli_connect("fdb19.125mb.com","3387047_indicon","indiconiot2020") or die("<h2>No se encuentra el servidor</h2>");
	$db = mysqli_select_db($link,"3387047_indicon") or die("<h2>Error de Conexion</h2>");

	//obtenemos los valores del formulario
	$Usuario = $_POST['Usuario'];
	$Contraseña = $_POST['Contraseña'];

	//Obtiene la longitus de un string
	$req = (strlen($Usuario)*strlen($Contraseña)) or die("No se han llenado todos los campos");

	//se encripta la contraseña
	$contraseñaUser =md5($Contraseña);

	//ingresamos la informacion a la base de datos
	mysqli_query($link,"INSERT INTO Usuarios VALUES('','$Usuario','$contraseñaUser')") or die("<h2>Error Guardando los datos</h2>");
	echo'
		<script>
			alert("Registro Exitoso");
			location.href="login.html";
		</script>
	'
?>