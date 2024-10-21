<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar que los datos requeridos están presentes
    if (isset($_POST['nomina'], $_POST['nombre'], $_POST['correo'], $_POST['password'])) {
        // Obtener los datos del formulario
        $nomina = $_POST['nomina'];
        $nombre = $_POST['nombre']; // Mantener el campo nombre
        $correo = $_POST['correo'];
        $password = $_POST['password'];

        // Lógica para validar las credenciales del usuario
        $response = validarCredenciales($nomina, $nombre, $correo, $password);

    } else {
        // Si faltan datos
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido. Solo se permite POST.');
}

// Enviar respuesta en formato JSON
echo json_encode($response);
exit();

// Función para validar las credenciales
function validarCredenciales($nomina, $nombre, $correo, $password) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para verificar si el usuario existe
    $stmt = $conex->prepare("SELECT * FROM usuario WHERE nomina = ? AND correo = ?");
    $stmt->bind_param("ss", $nomina, $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si el usuario existe
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Verificar el nombre y la contraseña
        if ($usuario['nombre'] === $nombre && password_verify($password, $usuario['password'])) {
            // Guardar la nómina en la sesión
            $_SESSION['nomina'] = $nomina;

            // Retornar éxito
            $response = array('status' => 'success', 'message' => 'Inicio de sesión exitoso.');
        } else {
            $response = array('status' => 'error', 'message' => 'Credenciales incorrectas.');
        }
    } else {
        $response = array('status' => 'error', 'message' => 'Usuario no encontrado.');
    }

    // Cerrar la conexión
    $stmt->close();
    $conex->close();

    return $response;
}
?>
