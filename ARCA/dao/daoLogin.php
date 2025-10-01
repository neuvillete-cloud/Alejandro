<?php
// CORRECCIÓN: Se añade 'cookie_path' => '/' para que la sesión sea válida en todo el dominio.
session_start(['cookie_path' => '/']);

// Incluimos tu archivo de conexión
include_once("conexionArca.php");

// Le decimos al navegador que la respuesta será en formato JSON.
header('Content-Type: application/json');

// Creamos una respuesta por defecto en caso de que algo falle.
$response = array('status' => 'error', 'message' => 'Ocurrió un error inesperado.');

// Verificamos que los datos se envíen por el método POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificamos que las variables 'nombreUsuario' y 'password' existan.
    if (isset($_POST['nombreUsuario'], $_POST['password'])) {

        $nombreUsuario = trim($_POST['nombreUsuario']);
        $password = trim($_POST['password']);

        $con = new LocalConector();
        $conex = $con->conectar();

        $stmt = $conex->prepare("SELECT IdUsuario, Nombre, Contraseña, IdRol FROM Usuarios WHERE NombreUsuario = ?");
        $stmt->bind_param("s", $nombreUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            if (password_verify($password, $usuario['Contraseña'])) {
                // ¡La contraseña es correcta!
                session_regenerate_id(true);

                // Guardamos los datos importantes del usuario en la sesión.
                $_SESSION['user_id'] = $usuario['IdUsuario'];
                $_SESSION['user_nombre'] = $usuario['Nombre'];
                $_SESSION['user_rol'] = $usuario['IdRol'];
                $_SESSION['loggedin'] = true;

                // --- INICIO DE LA LÓGICA DE REDIRECCIÓN INTEGRADA ---
                // Define la URL de redirección por defecto (dashboard).
                $url_destino = './index.php';

                // Comprueba si existe una URL guardada para un invitado.
                if (isset($_SESSION['url_destino_post_login'])) {
                    // Si existe, esa será nuestra URL de destino.
                    $url_destino = $_SESSION['url_destino_post_login'];
                    // La usamos una vez y la eliminamos para no afectar inicios de sesión futuros.
                    unset($_SESSION['url_destino_post_login']);
                }
                // --- FIN DE LA LÓGICA DE REDIRECCIÓN INTEGRADA ---

                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    $stmt_token = $conex->prepare("UPDATE Usuarios SET remember_token = ? WHERE IdUsuario = ?");
                    $stmt_token->bind_param("si", $token, $usuario['IdUsuario']);
                    $stmt_token->execute();
                    $stmt_token->close();

                    $cookie_value = $usuario['IdUsuario'] . ':' . $token;
                    $expiry = time() + (86400 * 30);
                    setcookie('remember_me', $cookie_value, $expiry, '/', '', true, true);
                }

                // --- CAMBIO IMPORTANTE EN LA RESPUESTA ---
                // Ahora, la respuesta exitosa también incluye la URL a la que se debe redirigir.
                $response = array(
                    'status' => 'success',
                    'message' => 'Acceso concedido. Redirigiendo...',
                    'redirect_url' => $url_destino // ¡NUEVO!
                );

            } else {
                $response = array('status' => 'error', 'message' => 'Nombre de usuario o contraseña incorrectos.');
            }
        } else {
            $response = array('status' => 'error', 'message' => 'Nombre de usuario o contraseña incorrectos.');
        }
        $stmt->close();
        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido.');
}

echo json_encode($response);
exit();
?>
