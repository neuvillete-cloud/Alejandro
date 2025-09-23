<?php
session_start();
// Asegúrate de que las rutas a estos archivos sean correctas desde /dao/
include_once("conexionArca.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

// Verificamos que los datos necesarios lleguen por POST y que haya una sesión activa
if (!isset($_POST['id'], $_POST['email']) || !isset($_SESSION['loggedin'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos o sesión no válida."]);
    exit;
}

$idSolicitud = intval($_POST['id']);
$emailDestino = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

if (!$emailDestino) {
    echo json_encode(["status" => "error", "message" => "La dirección de correo no es válida."]);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciamos la transacción

try {
    // --- PASO 1: GENERAR Y GUARDAR EL TOKEN DE ACCESO ---
    $token = bin2hex(random_bytes(32)); // Token seguro de 64 caracteres

    $stmt_token = $conex->prepare("INSERT INTO SolicitudesCompartidas (IdSolicitud, EmailDestino, Token) VALUES (?, ?, ?)");
    $stmt_token->bind_param("iss", $idSolicitud, $emailDestino, $token);
    if (!$stmt_token->execute()) {
        throw new Exception("Error al generar el enlace seguro para compartir.");
    }
    $stmt_token->close();

    // --- PASO 2: ACTUALIZAR EL ESTATUS DE LA SOLICITUD ---
    $nuevoEstatus = 2; // Suponiendo que '2' es el estatus "En Revisión" o "Compartido"
    $stmt_update = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
    $stmt_update->bind_param("ii", $nuevoEstatus, $idSolicitud);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el estatus de la solicitud.");
    }
    $stmt_update->close();

    // --- PASO 3: OBTENER DATOS PARA EL CORREO ---
    $stmt_data = $conex->prepare("SELECT NumeroParte, DescripcionParte FROM Solicitudes WHERE IdSolicitud = ?");
    $stmt_data->bind_param("i", $idSolicitud);
    $stmt_data->execute();
    $solicitudData = $stmt_data->get_result()->fetch_assoc();
    if (!$solicitudData) {
        throw new Exception("No se encontraron los datos de la solicitud.");
    }
    $stmt_data->close();

    // --- PASO 4: CONSTRUIR EL LINK Y ENVIAR CORREO ---
    $url_sitio = "https://grammermx.com/AleTest/ARCA"; // <-- ¡TU URL REAL!
    $linkVerSolicitud = "$url_sitio/Historial.php?token=$token";

    enviarCorreoNotificacion($emailDestino, $idSolicitud, $solicitudData, $linkVerSolicitud, $_SESSION['user_nombre']);

    // Si todo fue exitoso, confirmamos los cambios en la base de datos
    $conex->commit();
    echo json_encode(["status" => "success", "message" => "La solicitud ha sido enviada y su estatus actualizado."]);

} catch (Exception $e) {
    // Si algo falló, revertimos todos los cambios
    $conex->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}


function enviarCorreoNotificacion($emailDestino, $id, $solicitudData, $link, $nombreRemitente) {
    $folio = "S-" . str_pad($id, 4, '0', STR_PAD_LEFT);
    $asunto = "Acción Requerida: Solicitud de Contención ARCA - Folio $folio";

    $contenidoHTML = "
    <!DOCTYPE html><html><body style='margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);'>
        <tr><td align='center' style='background-color:#4a6984;padding:20px;border-top-left-radius:12px;border-top-right-radius:12px;'><h1 style='color:#ffffff;margin:0;'>ARCA</h1></td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#4a6984;margin-top:0;'>Revisión de Solicitud de Contención</h2>
            <p>Hola,</p>
            <p><strong>" . htmlspecialchars($nombreRemitente) . "</strong> ha compartido contigo la solicitud de contención con folio <strong>$folio</strong> para tu revisión y seguimiento.</p>
            <table border='0' cellpadding='5' cellspacing='0' width='100%' style='border-collapse:collapse;margin-top:20px;margin-bottom:20px;'>
                <tr style='background-color:#f8f9fa;'><td style='padding:10px;width:30%;'><strong>No. de Parte:</strong></td><td style='padding:10px;'>" . htmlspecialchars($solicitudData['NumeroParte']) . "</td></tr>
                <tr><td style='padding:10px;'><strong>Descripción:</strong></td><td style='padding:10px;'>" . htmlspecialchars($solicitudData['DescripcionParte']) . "</td></tr>
            </table>
            <p>Para ver todos los detalles, incluyendo los defectos y las imágenes, por favor haz clic en el siguiente botón:</p>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='$link' target='_blank' style='font-size:16px;font-family:Arial,sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;'>Ver Solicitud en ARCA</a>
            </td></tr></table>
            <p>Saludos,<br><strong>Sistema ARCA</strong></p>
        </td></tr>
        <tr><td align='center' style='background-color:#f8f9fa;padding:20px;font-size:12px;color:#6c757d;'><p style='margin:0;'>&copy; " . date('Y') . " ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        // --- CONFIGURA AQUÍ TUS CREDENCIALES DE CORREO ---
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; // O el de tu proveedor
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_arca@grammermx.com';
        $mail->Password = 'SARCAGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Remitente y Destinatario
        $mail->setFrom('sistema_arca@grammermx.com', 'Sistema ARCA');
        $mail->addAddress($emailDestino);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        if (!$mail->send()) {
            throw new Exception("Error al enviar correo: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        // Lanza una excepción para que la transacción principal haga rollback
        throw new Exception("El registro en BD fue exitoso, pero el correo no pudo ser enviado. Error: {$e->getMessage()}");
    }
}
?>
