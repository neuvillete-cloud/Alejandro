<?php
session_start(); // Es crucial para manejar la sesión del usuario

// Incluimos tu archivo de conexión
include_once("conexionArca.php");

header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Ocurrió un error inesperado.');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nombreUsuario'], $_POST['password'])) {
        $nombreUsuario = trim($_POST['nombreUsuario']);
        $password = trim($_POST['password']);

        if (empty($nombreUsuario) || empty($password)) {
            $response = array('status' => 'error', 'message' => 'El nombre de usuario y la contraseña son obligatorios.');
        } else {
            $con = new LocalConector();
            $conex = $con->conectar();

            // 1. Preparamos la consulta para buscar al usuario
            $stmt = $conex->prepare("SELECT IdUsuario, Nombre, Contraseña, IdRol FROM Usuarios WHERE NombreUsuario = ?");
            $stmt->bind_param("s", $nombreUsuario);
            $stmt->execute();
            $resultado = $stmt->get_result();

            // 2. Verificamos si se encontró al usuario
            if ($resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();

                // 3. Verificamos la contraseña hasheada
                if (password_verify($password, $usuario['Contraseña'])) {
                    // ¡Contraseña correcta!

                    // 4. Regeneramos el ID de sesión por seguridad
                    session_regenerate_id(true);

                    // 5. Guardamos los datos del usuario en la sesión
                    $_SESSION['user_id'] = $usuario['IdUsuario'];
                    $_SESSION['user_nombre'] = $usuario['Nombre'];
                    $_SESSION['user_rol'] = $usuario['IdRol'];
                    $_SESSION['loggedin'] = true;

                    $response = array('status' => 'success', 'message' => 'Acceso concedido. Redirigiendo...');

                } else {
                    // Contraseña incorrecta
                    $response = array('status' => 'error', 'message' => 'Nombre de usuario o contraseña incorrectos.');
                }
            } else {
                // Usuario no encontrado
                $response = array('status' => 'error', 'message' => 'Nombre de usuario o contraseña incorrectos.');
            }
            $stmt->close();
            $conex->close();
        }
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido.');
}

echo json_encode($response);
exit();
?>
