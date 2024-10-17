<?php
include_once ("connection.php");


header('Content-type: application/json');

//if(isset($_POST['nomina'], $_POST['nombre'], $_POST['email'], $_POST['contrasena'])){
 /*  $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contrasena = $_POST['contrasena'];
    $response = RegistrarUsuario($nomina, $nombre, $email, $contrasena);

//} else {
  //  $response = array('status' => 'error', 'message' => 'Error faltan datos');

//}

echo json_encode($response);
exit;

*/
echo "hola";

//$response = RegistrarUsuario("123","Alejandro","aletj","contra");
//echo json_encode($response);
function RegistrarUsuario($nomina, $nombre, $email, $contrasena)
{
    $con = new LocalConector();
    $conex = $con->conectar();

    $insertUsuario = $conex->prepare("INSERT INTO Usuario (nomina, nombre, email, contrasena)
                                      VALUES (?, ?, ?, ?)");
    $insertUsuario->bind_param("ssss", $nomina, $nombre, $email, $contrasena);
    $resultado = $insertUsuario->execute();

    $conex->close();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => 'usuario registrado exitosamente');
    } else {
        $response = array('status' => 'error', 'message' => 'error al registrar usuario');
    }

    return $response; // Retorna la respuesta
}
?>