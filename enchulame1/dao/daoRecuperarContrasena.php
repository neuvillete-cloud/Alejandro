<?php
header('Content-Type: application/json');
include_once('conexion.php'); // Asegúrate de que este archivo configure la conexión a tu base de datos
require_once __DIR__ . '/../Mailer/MailerRecuperarPassword.php'; // Ajusta la ruta si es necesario

if (isset($_POST['correoRecuperacion'])) {
    $correo = $_POST['correoRecuperacion'];
    $user = consultarNumNomina($correo);

    if ($user) {
        $numNomina = $user['NumNomina'];
        $tokenResponse = generarToken($numNomina);

        if ($tokenResponse['status'] === 'success') {
            $token = $tokenResponse['token'];
            $enlace = "https://grammermx.com/AleTest/enchulame1/recuperaContrasena.php?numNomina=$numNomina&token=$token"; // Cambia "tusitio.com" a la URL real de tu aplicación
            $mensaje = "Para restablecer tu contraseña haz clic en el siguiente enlace: <br> <a href='$enlace'>Recuperar contraseña</a>";
            $asunto = "Recuperar contraseña";

            // Enviar el correo electrónico
            $correoResponse = emailRecuperarPassword($correo, $asunto, $mensaje);

            if ($correoResponse['status'] === 'success') {
                $response = array('status' => 'success', 'message' => 'Se ha enviado un correo para recuperar tu contraseña.');
            } else {
                $response = $correoResponse; // Respuesta de error si falla el envío del correo
            }
        } else {
            $response = $tokenResponse; // Error en la generación del token
        }
    } else {
        $response = array('status' => 'error', 'message' => 'Correo electrónico no registrado');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Error: Faltan datos en el formulario');
}

echo json_encode($response);

// Función para consultar el NumNomina del usuario basado en el correo
function consultarNumNomina($correo) {
    $con = new LocalConector();
    $conexion = $con->conectar();

    $stmt = $conexion->prepare("SELECT NumNomina FROM Usuario WHERE Correo = ?");
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $stmt->close();
        $conexion->close();
        return $usuario; // Retorna el NumNomina como array asociativo
    } else {
        $stmt->close();
        $conexion->close();
        return null;
    }
}

// Función para generar y almacenar un token de recuperación de contraseña
function generarToken($numNomina) {
    $con = new LocalConector();
    $conexion = $con->conectar();

    $token = bin2hex(random_bytes(16));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $tokenValido = 1; // Indicador de que el token es válido

    $stmt = $conexion->prepare('INSERT INTO restablecerContrasena (NumNomina, Token, Expira, TokenValido) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $numNomina, $token, $expira, $tokenValido);

    if ($stmt->execute()) {
        $stmt->close();
        $conexion->close();
        return array('status' => 'success', 'token' => $token);
    } else {
        $stmt->close();
        $conexion->close();
        return array('status' => 'error', 'message' => 'Error: No se ha podido generar el token.');
    }
}
?>
