<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nomina = $_POST['nomina'];
    $correo = $_POST['correo'];

    // Verificar las credenciales del usuario
    $response = validarCredenciales($nomina, $correo);
    echo json_encode($response);
    exit();
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido. Solo se permite POST.');
    echo json_encode($response);
    exit();
}

// Función para validar las credenciales
function validarCredenciales($nomina, $correo) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para verificar si el usuario existe
    $stmt = $conex->prepare("SELECT * FROM Usuario WHERE nomina = ? AND correo = ?");
    $stmt->bind_param("ss", $nomina, $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si el usuario existe
    if ($resultado->num_rows > 0) {
        // Guardar la nómina en la sesión
        $_SESSION['nomina'] = $nomina;

        // Retornar éxito
        $response = array('status' => 'success', 'message' => 'Inicio de sesión exitoso.');
    } else {
        $response = array('status' => 'error', 'message' => 'Usuario no encontrado o credenciales incorrectas.');
    }

    // Cerrar la conexión
    $stmt->close();
    $conex->close();

    return $response;
}
?>
