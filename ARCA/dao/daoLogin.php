<?php
// session_start() es crucial para poder crear y manejar la sesión del usuario.
// Debe ir al principio de todo.
session_start();

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

        // Limpiamos los datos de entrada para evitar espacios en blanco.
        $nombreUsuario = trim($_POST['nombreUsuario']);
        $password = trim($_POST['password']);

        // Creamos una instancia de tu clase de conexión y nos conectamos.
        $con = new LocalConector();
        $conex = $con->conectar();

        // 1. Preparamos la consulta para buscar al usuario de forma segura.
        $stmt = $conex->prepare("SELECT IdUsuario, Nombre, Contraseña, IdRol FROM Usuarios WHERE NombreUsuario = ?");
        $stmt->bind_param("s", $nombreUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        // 2. Verificamos si se encontró exactamente un usuario.
        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();

            // 3. Verificamos que la contraseña ingresada coincida con la contraseña "hasheada" de la base de datos.
            if (password_verify($password, $usuario['Contraseña'])) {
                // ¡La contraseña es correcta!

                // 4. Regeneramos el ID de la sesión por seguridad (previene ataques de fijación de sesión).
                session_regenerate_id(true);

                // 5. Guardamos los datos importantes del usuario en la sesión.
                $_SESSION['user_id'] = $usuario['IdUsuario'];
                $_SESSION['user_nombre'] = $usuario['Nombre'];
                $_SESSION['user_rol'] = $usuario['IdRol'];
                $_SESSION['loggedin'] = true;

                // 6. Lógica para "Recordar Sesión".
                if (isset($_POST['remember'])) {
                    // Generamos un token seguro y único.
                    $token = bin2hex(random_bytes(32));

                    // Guardamos el token en la base de datos para este usuario.
                    $stmt_token = $conex->prepare("UPDATE Usuarios SET remember_token = ? WHERE IdUsuario = ?");
                    $stmt_token->bind_param("si", $token, $usuario['IdUsuario']);
                    $stmt_token->execute();
                    $stmt_token->close();

                    // Creamos la cookie en el navegador del usuario.
                    $cookie_value = $usuario['IdUsuario'] . ':' . $token;
                    $expiry = time() + (86400 * 30); // La cookie expira en 30 días.

                    // setcookie(nombre, valor, expiración, ruta, dominio, https_only, httponly)
                    setcookie('remember_me', $cookie_value, $expiry, '/', '', true, true);
                }

                $response = array('status' => 'success', 'message' => 'Acceso concedido. Redirigiendo...');
            } else {
                // Si la contraseña no coincide.
                $response = array('status' => 'error', 'message' => 'Nombre de usuario o contraseña incorrectos.');
            }
        } else {
            // Si el nombre de usuario no se encontró.
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

// Enviamos la respuesta final en formato JSON al JavaScript.
echo json_encode($response);
exit();
?>