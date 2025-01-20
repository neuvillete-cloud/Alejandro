<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar que todos los datos requeridos están presentes
    if (isset($_POST['NumNomina'], $_POST['Nombre'], $_POST['Correo'], $_POST['Contrasena'], $_POST['Area'])) {
        // Obtener los datos del formulario
        $NumNomina = $_POST['NumNomina'];
        $Nombre = $_POST['Nombre'];
        $Correo = $_POST['Correo'];
        $Contrasena = $_POST['Contrasena'];
        $Area = $_POST['Area'];

        $con = new LocalConector();
        $conex = $con->conectar();

        // Buscar el IdArea correspondiente al nombre del área
        $consultaArea = $conex->prepare("SELECT IdArea FROM Area WHERE NombreArea = ?");
        $consultaArea->bind_param("s", $Area);
        $consultaArea->execute();
        $resultadoArea = $consultaArea->get_result();

        if ($resultadoArea->num_rows > 0) {
            $row = $resultadoArea->fetch_assoc();
            $IdArea = $row['IdArea'];

            // Asignar automáticamente el rol de solicitante (Id_Rol = 2)
            $response = registrarUsuarioEnDB($conex, $NumNomina, $Nombre, $Correo, $Contrasena, 2, $IdArea);
        } else {
            $response = array('status' => 'error', 'message' => 'Área no encontrada.');
        }

        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Se requiere método POST.');
}

echo json_encode($response);
exit();

// Función para registrar al usuario en la base de datos
function registrarUsuarioEnDB($conex, $NumNomina, $Nombre, $Correo, $Contrasena, $IdRol, $IdArea)
{
    // Insertar el usuario con Id_Rol = 2 (solicitante)
    $insertUsuario = $conex->prepare("INSERT INTO Usuario (NumNomina, Nombre, Correo, Contrasena, IdRol, IdArea)
                                      VALUES (?, ?, ?, ?, ?, ?)");
    $insertUsuario->bind_param("ssssii", $NumNomina, $Nombre, $Correo, $Contrasena, $IdRol, $IdArea);
    $resultado = $insertUsuario->execute();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => 'Usuario registrado exitosamente');
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar usuario');
    }

    return $response;
}
?>
