<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar que todos los datos requeridos están presentes
    if (isset($_POST['NumNomina'], $_POST['Nombre'], $_POST['Correo'], $_POST['Contrasena'])) {
        // Obtener los datos del formulario
        $NumNomina = $_POST['NumNomina'];
        $Nombre = $_POST['Nombre'];
        $Correo = $_POST['Correo'];
        $Contrasena = $_POST['Contrasena'];

        // Asignar automáticamente el rol de solicitante (Id_Rol = 2)
        $response = registrarUsuarioEnDB($NumNomina, $Nombre, $Correo, $Contrasena, 2);

    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Se requiere método POST.');
}

echo json_encode($response);
exit();

// Función para registrar al usuario en la base de datos
function registrarUsuarioEnDB($NumNomina, $Nombre, $Correo, $Contrasena, $IdRol)
{
    $con = new LocalConector();
    $conex = $con->conectar();

    // Insertar el usuario con Id_Rol = 2 (solicitante)
    $insertUsuario = $conex->prepare("INSERT INTO Usuario (NumNomina, Nombre, Correo, Contrasena, IdRol)
                                      VALUES (?, ?, ?, ?, ?)");
    $insertUsuario->bind_param("ssssi", $NumNomina, $Nombre, $Correo, $Contrasena, $IdRol);
    $resultado = $insertUsuario->execute();

    $conex->close();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => 'Usuario registrado exitosamente');
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar usuario');
    }
    return $response;
}
?>
