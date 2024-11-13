<?php
// Incluimos los archivos necesarios
include_once('conexion.php');
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['correoRecuperacion'])) {
    $correo = $_POST['correoRecuperacion'];

    $user = consultarNumNomina($correo);

    if ($user) {
        $numNomina = $user['NumNomina'];
        $tokenResponse = generarToken($numNomina);

        if ($tokenResponse['status'] === 'success') {
            $token = $tokenResponse['token'];
            $enlace = "https://grammermx.com/AleTest/enchulame1/restablecerContrasena.php?numNomina=$numNomina&token=$token";
            $mensaje = "Para restablecer tu contraseña, haz clic en el siguiente enlace: <a href='$enlace'>Recuperar contraseña</a>";
            $asunto = "Recuperar contrasena";

            $correoResponse = emailRecuperarPassword($correo, $asunto, $mensaje);

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

    $token = "12345"; // Cambia este token a uno generado dinámicamente en producción
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

// Función para enviar el correo de recuperación de contraseña
function emailRecuperarPassword($destinatario, $asunto, $mensaje) {
    $contenido = "
    <html>
    <head>
        <title>$asunto</title>
    </head>
    <body style='font-family: Arial, sans-serif; text-align: center; background-color: #f6f6f6;'>
        <div style='background-color: #005195; padding: 20px; color: #ffffff;'>
            <h2>Recuperación de contraseña</h2>
        </div>
        <div style='padding: 20px;'>
            <p>Hola,</p>
            <p>$mensaje</p>
            <br>
            <p>Si no solicitaste un cambio de contraseña, ignora este mensaje.</p>
        </div>
        <footer style='background-color: #f6f6f6; padding: 10px;'>
            <p>© Grammer Querétaro.</p>
        </footer>
    </body>
    </html>";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets_enchulamelanave@grammermx.com';
        $mail->Password = 'ECHGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('tickets_enchulamelanave@grammermx.com', 'Administracion Enchulame la nave');
        $mail->addAddress($destinatario);
        $mail->addBCC('tickets_enchulamelanave@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $contenido;

        if (!$mail->send()) {
            return array('status' => 'error', 'message' => 'Error al enviar el correo electrónico: ' . $mail->ErrorInfo);
        } else {
            return array('status' => 'success', 'message' => 'Correo enviado exitosamente.');
        }
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
    }
}
?>
