<?php
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['id']) && isset($_POST['email1'])) {
    $idSolicitud = $_POST['id'];
    $email1 = trim($_POST['email1']);
    $email2 = trim($_POST['email2'] ?? '');
    $email3 = trim($_POST['email3'] ?? '');

    // --- PASO 1: Contamos cuántos correos válidos se proporcionaron ---
    $aprobadoresRequeridos = 0;
    if (!empty($email1)) { $aprobadoresRequeridos++; }
    if (!empty($email2)) { $aprobadoresRequeridos++; }
    if (!empty($email3)) { $aprobadoresRequeridos++; }
    // --- FIN DEL PASO 1 ---

    $con = new LocalConector();
    $conex = $con->conectar();

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
    $asunto = "Solicitud Pendiente de Aprobación - Folio $folio";
    $mensaje = "
    <p>Estimado Aprobador,</p>
    <p>Se ha generado una nueva solicitud con el folio <strong>$folio</strong> que requiere su decisión.</p>
    <p>Por favor, ingrese al siguiente enlace para revisarla:</p>
    <p>
        <a href='$linkAprobacion' target='_blank' style='background: #005195; color: #FFFFFF; 
        padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; 
        display: inline-block;'>
            Revisar Solicitud
        </a>
    </p>
    <p>Saludos,<br>ATS - Grammer</p>";

    $correoResponse = enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje);

    if ($correoResponse['status'] === 'success') {
        // --- PASO 2: Si los correos se enviaron, actualizamos la solicitud en la BD ---
        $updateStmt = $conex->prepare("UPDATE Solicitudes SET AprobadoresRequeridos = ? WHERE IdSolicitud = ?");
        $updateStmt->bind_param("ii", $aprobadoresRequeridos, $idSolicitud);

        if ($updateStmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Correos enviados y solicitud actualizada correctamente."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Los correos se enviaron, pero hubo un error al actualizar la solicitud."]);
        }
        $updateStmt->close();
        // --- FIN DEL PASO 2 ---

    } else {
        echo json_encode(["status" => "error", "message" => "Error al enviar el correo: " . $correoResponse['message']]);
    }

    $conex->close();

} else {
    echo json_encode(["status" => "error", "message" => "ID de solicitud y primer correo son obligatorios."]);
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

        if (!empty($email1)) $mail->addAddress($email1);
        if (!empty($email2)) $mail->addAddress($email2);
        if (!empty($email3)) $mail->addAddress($email3);

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
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