<?php
session_start();
include_once("conexion.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['id'])) {
    $reporteId = $_POST['id'];
    $nuevoEstatus = 2; // Suponiendo que el ID 2 es "En Proceso" en la tabla de estatus

    // Conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Actualizar el estatus del reporte
    $stmt = $conex->prepare("UPDATE Reportes SET IdEstatus = ? WHERE IdReporte = ?");
    $stmt->bind_param('ii', $nuevoEstatus, $reporteId);

    if ($stmt->execute()) {
        // Obtener el correo del usuario que reportó el problema
        $stmt_user = $conex->prepare("
            SELECT Correo 
            FROM Usuario 
            INNER JOIN Reportes ON Usuario.NumNomina = Reportes.NumNomina 
            WHERE Reportes.IdReporte = ?
        ");
        $stmt_user->bind_param('i', $reporteId);
        $stmt_user->execute();
        $result = $stmt_user->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $emailUsuario = $user['Correo'];

            // Enviar correo de notificación al usuario
            $asunto = "Actualización de Estatus de Reporte";
            $mensaje = "Hola,<br><br>Te informamos que el estatus de tu reporte #$reporteId ha cambiado a 'En Proceso'. Nuestro equipo ya está trabajando en ello.<br><br>Saludos,<br>Equipo de Soporte";
            $correoResponse = enviarCorreoNotificacionEstatus($emailUsuario, $asunto, $mensaje);

            if ($correoResponse['status'] === 'success') {
                $response = array('status' => 'success', 'message' => 'Estatus actualizado y correo enviado.');
            } else {
                $response = array('status' => 'error', 'message' => 'Estatus actualizado, pero no se pudo enviar el correo. Error: ' . $correoResponse['message']);
            }
        } else {
            $response = array('status' => 'error', 'message' => 'No se encontró el correo del usuario.');
        }
    } else {
        $response = array('status' => 'error', 'message' => 'No se pudo actualizar el estatus');
    }

    $stmt->close();
    $stmt_user->close();
    $conex->close();
} else {
    $response = array('status' => 'error', 'message' => 'ID de reporte no especificado');
}

echo json_encode($response);

// Función para enviar el correo de notificación de cambio de estatus
function enviarCorreoNotificacionEstatus($destinatario, $asunto, $mensaje) {
    $contenido = "
    <html>
    <head>
        <title>$asunto</title>
    </head>
    <body style='font-family: Arial, sans-serif; text-align: center; background-color: #f6f6f6;'>
        <div style='background-color: #005195; padding: 20px; color: #ffffff;'>
            <h2>Notificación de Estatus de Reporte</h2>
        </div>
        <div style='padding: 20px;'>
            <p>$mensaje</p>
            <br>
            <p>Si tienes alguna pregunta, no dudes en contactar con soporte.</p>
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
        $mail->setFrom('tickets_enchulamelanave@grammermx.com', 'Administración Enchulame la Nave');
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
