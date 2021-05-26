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
$age = ""; 
$age_err = "";

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
    
    // Se valida si sí se ingresó una edad a partir de la entrada 'height' dada en el metodo 'POST'
    if(empty(trim($_POST["age"]))){
        $age_err = "Por favor ingrese su edad.";     
    } else{
        $age = trim($_POST["age"]); // Se guarda la altura en la variable 'age'
    }


    // Si nunca se necesitaron de las variables auxilaires de error, es porque los datos están bien
    if(empty($usergender_err) && empty($weight_err) && empty($height_err) && empty($age_err)){
        
        // Se prepara un estado de inserción de datos en la base de datos
        $sql = "UPDATE Usuarios SET usergender = ?, weight = ?, height = ?, age = ? WHERE id = ?";
         
        // Se prepara la ejecucion de el estado de inserción solicitado   
        if($stmt = mysqli_prepare($link, $sql)){
        
            // Se unen los parametros $param_usergender, $param_weight, $param_height a las variables del statement "stmt"
            mysqli_stmt_bind_param($stmt, "siiii", $param_usergender, $param_weight, $param_height, $param_age, $param_id);
            
            $param_id = $_SESSION["id"];  
            $param_usergender = $usergender; // Se establece el $param_usergender de acuerdo a la variabel $usergender
            $param_weight = $weight; //  el resultado se guarda en $param_weight
            $param_height = $height; //  el resultado se guarda en $param_height
            $param_age = $age; // el resultado se guarda en $param_age

            // Se intenta ejecutar el estado de inserción solicitado
            if(mysqli_stmt_execute($stmt)){
                // Si la ejecución es correcta, entonces se escribieron los datos en la base de datos
                //header("location: data.php"); // Se redirecciona a la pagina de user
                
                
                if($parama_usergender == 'Femenino'){
                   $BMR = ((10 * $param_weight) + (6.25 * $param_height) - (5 * $param_age) + 5) * 1.2;  
                } else{
                   $BMR = ((10 * $param_weight) + (6.25 * $param_height) - (5 * $param_age) -161) * 1.2;
                }
 
 
                
                
                
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
                <li><a href="contacto.html">Proyecto</a></li>
                <li><a href="welcome.php">Sesión</a>
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
        <h2>Por favor llena tus datos personales:</h2>
        <p></p>

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
                    <select name="usergender">
                        <option value=""></option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                <name="usergender" class="form-control <?php echo (!empty($usergender_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $usergender; ?>">
                <span class="invalid-feedback"><?php echo $usergender_err; ?></span>
            </div>   
            
            <div class="form-group">
                <label>Peso</label>

                <select name="weight">
                        <option value=""></option>
                        <option value="40">40Kg</option>
                        <option value="41">41Kg</option>
                        <option value="42">42Kg</option>
                        <option value="43">43Kg</option>
                        <option value="44">44Kg</option>
                        <option value="45">45Kg</option>
                        <option value="46">46Kg</option>
                        <option value="47">47Kg</option>
                        <option value="48">48Kg</option>
                        <option value="49">49Kg</option>
                        <option value="50">50Kg</option>

                        <option value="51">51Kg</option>
                        <option value="52">52Kg</option>
                        <option value="53">53Kg</option>
                        <option value="54">54Kg</option>
                        <option value="55">55Kg</option>
                        <option value="56">56Kg</option>
                        <option value="57">57Kg</option>
                        <option value="58">58Kg</option>
                        <option value="59">59Kg</option>
                        <option value="60">60Kg</option>

                        <option value="61">61Kg</option>
                        <option value="62">62Kg</option>
                        <option value="63">63Kg</option>
                        <option value="64">64Kg</option>
                        <option value="65">65Kg</option>
                        <option value="66">66Kg</option>
                        <option value="67">67Kg</option>
                        <option value="68">68Kg</option>
                        <option value="69">69Kg</option>
                        <option value="70">70Kg</option>

                        
                        <option value="71">71Kg</option>
                        <option value="72">72Kg</option>
                        <option value="73">73Kg</option>
                        <option value="74">74Kg</option>
                        <option value="75">75Kg</option>
                        <option value="76">76Kg</option>
                        <option value="77">77Kg</option>
                        <option value="78">78Kg</option>
                        <option value="79">79Kg</option>
                        <option value="80">80Kg</option>

                       
                        <option value="81">81Kg</option>
                        <option value="82">82Kg</option>
                        <option value="83">83Kg</option>
                        <option value="84">84Kg</option>
                        <option value="85">85Kg</option>
                        <option value="86">86Kg</option>
                        <option value="87">87Kg</option>
                        <option value="88">88Kg</option>
                        <option value="89">89Kg</option>
                        <option value="90">90Kg</option>

                      
                        <option value="91">91Kg</option>
                        <option value="92">92Kg</option>
                        <option value="93">93Kg</option>
                        <option value="94">94Kg</option>
                        <option value="95">95Kg</option>
                        <option value="96">96Kg</option>
                        <option value="97">97Kg</option>
                        <option value="98">98Kg</option>
                        <option value="99">99Kg</option>
                        <option value="100">100Kg</option>

                      
                        <option value="101">101Kg</option>
                        <option value="102">102Kg</option>
                        <option value="103">103Kg</option>
                        <option value="104">104Kg</option>
                        <option value="105">105Kg</option>
                        <option value="106">106Kg</option>
                        <option value="107">107Kg</option>
                        <option value="108">108Kg</option>
                        <option value="109">109Kg</option>
                        <option value="110">110Kg</option>

                       
                        <option value="111">111Kg</option>
                        <option value="112">112Kg</option>
                        <option value="113">113Kg</option>
                        <option value="114">114Kg</option>
                        <option value="115">115Kg</option>
                        <option value="116">116Kg</option>
                        <option value="117">117Kg</option>
                        <option value="118">118Kg</option>
                        <option value="119">119Kg</option>
                        <option value="120">120Kg</option>

            
                        <option value="121">121Kg</option>
                        <option value="122">122Kg</option>
                        <option value="123">123Kg</option>
                        <option value="124">124Kg</option>
                        <option value="125">125Kg</option>
                        <option value="126">126Kg</option>
                        <option value="127">127Kg</option>
                        <option value="128">128Kg</option>
                        <option value="129">129Kg</option>
                        <option value="130">130Kg</option>
                    </select>


                <name="weight" class="form-control <?php echo (!empty($weight_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $weight; ?>">
                <span class="invalid-feedback"><?php echo $weight_err; ?></span>
            </div>

            <div class="form-group">
                <label>Altura</label>

                <select name="height">
                        <option value=""></option>
                        <option value="140">1.40m</option>
                        <option value="141">1.41m</option>
                        <option value="142">1.42m</option>
                        <option value="143">1.43m</option>
                        <option value="144">1.44m</option>
                        <option value="145">1.45m</option>
                        <option value="146">1.46m</option>
                        <option value="147">1.47m</option>
                        <option value="148">1.48m</option>
                        <option value="149">1.49m</option>
                        <option value="150">1.50m</option>

                        <option value="151">1.51m</option>
                        <option value="152">1.52m</option>
                        <option value="153">1.53m</option>
                        <option value="154">1.54m</option>
                        <option value="155">1.55m</option>
                        <option value="156">1.56m</option>
                        <option value="157">1.57m</option>
                        <option value="158">1.58m</option>
                        <option value="159">1.59m</option>
                        <option value="160">1.60m</option>

                        <option value="161">1.61m</option>
                        <option value="162">1.62m</option>
                        <option value="163">1.63m</option>
                        <option value="164">1.64m</option>
                        <option value="165">1.65m</option>
                        <option value="166">1.66m</option>
                        <option value="167">1.67m</option>
                        <option value="168">1.68m</option>
                        <option value="169">1.69m</option>
                        <option value="170">1.70m</option>

                        <option value="171">1.71m</option>
                        <option value="172">1.72m</option>
                        <option value="173">1.73m</option>
                        <option value="174">1.74m</option>
                        <option value="175">1.75m</option>
                        <option value="176">1.76m</option>
                        <option value="177">1.77m</option>
                        <option value="178">1.78m</option>
                        <option value="179">1.79m</option>
                        <option value="180">1.80m</option>

                        <option value="181">1.81m</option>
                        <option value="182">1.82m</option>
                        <option value="183">1.83m</option>
                        <option value="184">1.84m</option>
                        <option value="185">1.85m</option>
                        <option value="186">1.86m</option>
                        <option value="187">1.87m</option>
                        <option value="188">1.88m</option>
                        <option value="189">1.89m</option>
                        <option value="190">1.90m</option>

                        <option value="191">1.91m</option>
                        <option value="192">1.92m</option>
                        <option value="193">1.93m</option>
                        <option value="194">1.94m</option>
                        <option value="195">1.95m</option>
                        <option value="196">1.96m</option>
                        <option value="197">1.97m</option>
                        <option value="198">1.98m</option>
                        <option value="199">1.99m</option>
                        <option value="200">2.0m</option>

                        <option value="201">2.01m</option>
                        <option value="202">2.02m</option>
                        <option value="203">2.03m</option>
                        <option value="204">2.04m</option>
                        <option value="205">2.05m</option>
                        <option value="206">2.06m</option>
                        <option value="207">2.07m</option>
                        <option value="208">2.08m</option>
                        <option value="209">2.09m</option>
                        <option value="210">2.10m</option>

                        <option value="211">2.11m</option>
                        <option value="212">2.12m</option>
                        <option value="213">2.13m</option>
                        <option value="214">2.14m</option>
                        <option value="215">2.15m</option>
                        <option value="216">2.16m</option>
                        <option value="217">2.17m</option>
                        <option value="218">2.18m</option>
                        <option value="219">2.19m</option>
                        <option value="220">2.20m</option>

                </select>


                <name="height" class="form-control <?php echo (!empty($height_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $height; ?>">
                <span class="invalid-feedback"><?php echo $height_err; ?></span>
            </div>
            
            <div class="form-group">
                <label>Age</label>

                <select name="age">
                        <option value=""></option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11">11</option>

                        <option value="12">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>

                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="26">25</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>

                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                        <option value="37">37</option>
                        <option value="38">38</option>
                        <option value="39">39</option>


                        <option value="40">40</option>
                        <option value="41">41</option>
                        <option value="42">42</option>
                        <option value="43">43</option>
                        <option value="44">44</option>
                        <option value="45">45</option>
                        <option value="46">46</option>
                        <option value="47">47</option>
                        <option value="48">48</option>
                        <option value="49">49</option>
                        <option value="50">50</option>

                        <option value="51">51</option>
                        <option value="52">52</option>
                        <option value="53">53</option>
                        <option value="54">54</option>
                        <option value="55">55</option>
                        <option value="56">56</option>
                        <option value="57">57</option>
                        <option value="58">58</option>
                        <option value="59">59</option>
                        <option value="60">60</option>

                        <option value="61">61</option>
                        <option value="62">62</option>
                        <option value="63">63</option>
                        <option value="64">64</option>
                        <option value="65">65</option>
                        <option value="66">66</option>
                        <option value="67">67</option>
                        <option value="68">68</option>
                        <option value="69">69</option>
                        <option value="70">70</option>

                        
                        <option value="71">71</option>
                        <option value="72">72</option>
                        <option value="73">73</option>
                        <option value="74">74</option>
                        <option value="75">75</option>
                        <option value="76">76</option>
                        <option value="77">77</option>
                        <option value="78">78</option>
                        <option value="79">79</option>
                        <option value="80">80</option>

                       
                        <option value="81">81</option>
                        <option value="82">82</option>
                        <option value="83">83</option>
                        <option value="84">84</option>
                        <option value="85">85</option>
                        <option value="86">86</option>
                        <option value="87">87</option>
                        <option value="88">88</option>
                        <option value="89">89</option>
                        <option value="90">90</option>

                      
                        <option value="91">91</option>
                        <option value="92">92</option>
                        <option value="93">93</option>
                        <option value="94">94</option>
                        <option value="95">95</option>
                        <option value="96">96</option>
                        <option value="97">97</option>
                        <option value="98">98</option>
                        <option value="99">99</option>
                        <option value="100">100</option>              
                  </select>


                <name="age" class="form-control <?php echo (!empty($age_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $age; ?>">
                <span class="invalid-feedback"><?php echo $age_err; ?></span>
            </div>
            
            <div> 
                <h1 class="my-5">Tu BMR es: <b><?php echo htmlspecialchars($BMR); ?></b></h1>
                
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
