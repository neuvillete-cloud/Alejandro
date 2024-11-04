<?php


include_once('conexion.php');
require_once __DIR__ . '/Mailer/mailerRecuperarContrasena.php';

echo(__DIR__ . '/Mailer/mailerRecuperarContrasena.php');
if (isset($_POST['correoRecuperacion'])) {
    $correo = $_POST['correoRecuperacion'];


    $user = consultarNumNomina($correo);

    if ($user) {

        $numNomina = $user['NumNomina'];
        $tokenResponse = generarToken($numNomina);

        if ($tokenResponse['status'] === 'success') {
            $token = $tokenResponse['token'];
            $enlace = "https://grammermx.com/AleTest/enchulame1/recuperaContrasena.php?numNomina=$numNomina&token=$token";
            $mensaje = "Para restablecer tu contraseña haz clic en el siguiente enlace: <br> <a href='$enlace'>Recuperar contraseña</a>";
            $asunto = "Recuperar contraseña";

            $correoResponse = emailRecuperarPassword($correo, $asunto, $mensaje);
            $correoResponse= array('status' => 'success', 'message' => 'Correo enviado exitosamente.');

            if ($correoResponse['status'] === 'success') {
                $response = array('status' => 'success', 'message' => 'Se ha enviado un correo para recuperar tu contraseña.');
            } else {
                $response = $correoResponse; // Error en envío de correo
            }
        } else {
            $response = $tokenResponse; // Error en generación de token
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

    if (!$conexion) {
        return array('status' => 'error', 'message' => 'No se pudo conectar a la base de datos.');
    }

    $stmt = $conexion->prepare("SELECT NumNomina FROM Usuario WHERE Correo = ?");
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $stmt->close();
        $conexion->close();
        return $usuario;
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

    if (!$conexion) {
        return array('status' => 'error', 'message' => 'Error en la conexión a la base de datos.');
    }

    //$token = bin2hex(random_bytes(16));
    $token= "12345";
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $conexion->prepare('INSERT INTO restablecerContrasena (NumNomina, Token, Expira) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $numNomina, $token, $expira);

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
