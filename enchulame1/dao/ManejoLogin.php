<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $NumNomina = $_POST['NumNomina'];
    $Contrasena = $_POST['Contrasena'];

    // Verificar las credenciales del usuario
    $response = validarCredenciales($NumNomina, $Contrasena);
    echo json_encode($response);
    exit();
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido. Solo se permite POST.');
    echo json_encode($response);
    exit();
}

// Función para validar las credenciales
function validarCredenciales($NumNomina, $Contrasena) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para verificar si el usuario existe
    $stmt = $conex->prepare("SELECT * FROM Usuario WHERE NumNomina = ? AND Contrasena = ?");
    $stmt->bind_param("ss", $NumNomina, $Contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si el usuario existe
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc(); // Obtener los datos del usuario
        // Guardar la nómina y rol en la sesión
        $_SESSION['NumNomina'] = $NumNomina;
        $_SESSION['Rol'] = $usuario['IdRol']; // Suponiendo que el campo del rol en la tabla se llama 'rol'


        // Retornar éxito y redireccionar dependiendo del rol
        if ($usuario['IdRol'] == 1) {
            // Redirigir a la página de administrador
            $response = array('status' => 'success', 'redirect' => 'Administrador.php');
        } elseif ($usuario['IdRol'] == 2) {
            // Redirigir a la página de reportes
            $response = array('status' => 'success', 'redirect' => 'reportes.php');
        } else {
            $response = array('status' => 'error', 'message' => 'Rol no reconocido.');
        }
    } else {
        $response = array('status' => 'error', 'message' => 'Usuario no encontrado o credenciales incorrectas.');
    }

    // Cerrar la conexión
    $stmt->close();
    $conex->close();

    return $response;
}
?>
