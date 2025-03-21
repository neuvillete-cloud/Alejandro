<?php
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $idSolicitud = $_POST['id'];
    $email1 = $_POST['email1'] ?? '';
    $email2 = $_POST['email2'] ?? '';
    $email3 = $_POST['email3'] ?? '';

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

    $linkAprobacion = "https://grammermx.com/AleTest/ATS/aprobar_solicitud.php?folio=$folio";
    $asunto = "Solicitud Pendiente de Aprobación";
    $mensaje = "
    <p>Estimado usuario,</p>
    <p>Se ha generado una nueva solicitud con el folio <strong>$folio</strong>.</p>
    <p>Puedes aprobar o rechazar la solicitud en el siguiente enlace:</p>
    <p>
        <a href='$linkAprobacion' target='_blank' style='background: #E6F4F9; color: #005195; 
        padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; 
        display: inline-block;'>
            Ver Solicitud
        </a>
    </p>
    <p>Saludos,<br>ATS - Grammer</p>";

    enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje);

    echo json_encode(["status" => "success", "message" => "Se envió la notificación por correo."]);

} else {
    echo json_encode(["status" => "error", "message" => "Se requiere método POST y el ID de solicitud."]);
}

function enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje) {
    $contenido = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$asunto</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #87CEEB, #B0E0E6); color: #FFFFFF; text-align: center;'>
        <table role='presentation' style='width: 100%; max-width: 600px; margin: auto; background: #FFFFFF; border-radius: 10px; overflow: hidden;'>
            <tr>
                <td style='background-color: #005195; padding: 20px; color: #FFFFFF; text-align: center;'>
                    <h2>Notificación de Solicitud</h2>
                </td>
            </tr>
            <tr>
                <td style='padding: 20px; text-align: left; color: #333333;'>
                    $mensaje
                </td>
            </tr>
            <tr>
                <td style='background-color: #005195; color: #FFFFFF; padding: 10px; text-align: center;'>
                    <p>© Grammer Querétaro.</p>
                </td>
            </tr>
        </table>
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

        if ($email1 !== '') $mail->addAddress($email1);
        if ($email2 !== '') $mail->addAddress($email2);
        if ($email3 !== '') $mail->addAddress($email3);

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenido;

        $mail->send();
    } catch (Exception $e) {
        error_log("Excepción al enviar correo: " . $e->getMessage());
    }
}
?>
