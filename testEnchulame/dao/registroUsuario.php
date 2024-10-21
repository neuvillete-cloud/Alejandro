<?php
session_start(); // Iniciar sesión
include_once ("conexion.php");

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar que todos los datos requeridos están presentes
    if (isset($_POST['nomina'], $_POST['nombre'], $_POST['correo'], $_POST['password'])) {
        // Obtener los datos del formulario
        $nomina = $_POST['nomina'];
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $password = $_POST['password'];

        $response= registrarUsuarioEnDB($nomina, $nombre, $correo, $password);


    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');

    }

}
else{
    $response = array('status' => 'error', 'message' => 'se requiere metodo post.');
}
      echo json_encode($response);
      exit();

// Función para registrar al usuario en la base de datos
function registrarUsuarioEnDB($nomina, $nombre, $correo, $password)
{
    $con = new LocalConector();
    $conex = $con->conectar();

    $insertUsuario = $conex->prepare("INSERT INTO Usuario (nomina, nombre, correo, password)
                                      VALUES (?, ?, ?, ?)");
    $insertUsuario->bind_param("ssss", $nomina, $nombre, $correo, $password);
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
?>
