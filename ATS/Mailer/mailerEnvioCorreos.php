<?php
session_start();
include_once("ConexionBD.PHP");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🚨 Capturar salida inesperada antes de json_encode
ob_start();

file_put_contents('debug_post.log', print_r($_POST, true));

// 🚀 Ver qué datos llegan al PHP
print_r($_POST);
$debug_output = ob_get_clean();

// 🔍 Si hay espacios o caracteres inesperados, los verás aquí
echo json_encode([
    "debug_post" => $_POST,
    "debug_output" => trim($debug_output)
]);

// 🛑 IMPORTANTE: Detener aquí para analizar la salida
exit;

// Verifica si los valores existen
if (!isset($_POST['id']) || !isset($_POST['email1'])) {
    echo json_encode(["status" => "error", "message" => "ID de solicitud y primer correo son obligatorios."]);
    exit;
}

if (isset($_POST['id']) && isset($_POST['email1'])) {
    $idSolicitud = $_POST['id'];
    $email1 = $_POST['email1'];
    $email2 = $_POST['email2'] ?? '';
    $email3 = $_POST['email3'] ?? '';

    // Conexión usando LocalConector
    $con = new LocalConector();
    $conex = $con->conectar();

    // Obtener el folio de la solicitud
    $stmt = $conex->prepare("SELECT FolioSolicitud FROM Solicitudes WHERE IdSolicitud = ?");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "No se encontró la solicitud."]);
        exit;
    }

    $fila = $resultado->fetch_assoc();
    $folio = $fila['FolioSolicitud'];
    $stmt->close();

    // Generar enlace de aprobación
    $linkAprobacion = "https://grammermx.com/AleTest/ATS/aprobar_solicitud.php?folio=$folio";

    // Enviar correo
    $asunto = "Solicitud Pendiente de Aprobación";
    $mensaje = "
        <p>Estimado usuario,</p>
        <p>Se ha generado una nueva solicitud con el folio <strong>$folio</strong>.</p>
        <p>Puedes aprobar o rechazar la solicitud en el siguiente enlace:</p>
        <p><a href='$linkAprobacion' style='background: #28a745; color: #fff; padding: 10px 15px; text-decoration: none;'>Ver Solicitud</a></p>
        <p>Saludos,<br>ATS - Grammer</p>
    ";

    $correoResponse = enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje);

    if ($correoResponse['status'] === 'success') {
        echo json_encode(["status" => "success", "message" => "Correo enviado correctamente."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al enviar el correo: " . $correoResponse['message']]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ID de solicitud y primer correo son obligatorios."]);
}

// Función para enviar correos
function enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje) {
    $contenido = "
    <html>
    <head>
        <title>$asunto</title>
    </head>
    <body style='font-family: Arial, sans-serif; text-align: center; background-color: #f6f6f6;'>
        <div style='background-color: #005195; padding: 20px; color: #ffffff;'>
            <h2>Notificación de Solicitud</h2>
        </div>
        <div style='padding: 20px;'>
            <p>$mensaje</p>
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
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Administración ATS Grammer');

        $mail->addAddress($email1);
        if (!empty($email2)) $mail->addAddress($email2);
        if (!empty($email3)) $mail->addAddress($email3);

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $contenido;

        if (!$mail->send()) {
            return array('status' => 'error', 'message' => $mail->ErrorInfo);
        } else {
            return array('status' => 'success', 'message' => 'Correo enviado exitosamente.');
        }
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
    }
}
?>

