<?php
// Inicia la sesión para poder acceder a las variables de sesión.
session_start();

// Incluye los archivos necesarios. Las rutas asumen que este script está en /dao/.
include_once("conexionArca.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// La respuesta siempre será en formato JSON.
header('Content-Type: application/json; charset=UTF-8');

// --- CONFIGURACIÓN ---
define('BASE_URL', 'https://grammermx.com/AleTest/ARCA');

// --- VALIDACIÓN INICIAL ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit();
}
if (!isset($_POST['action'], $_POST['idMetodo'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros para realizar la acción.']);
    exit();
}

// --- LÓGICA PRINCIPAL ---
$accion = $_POST['action'];
$idMetodo = intval($_POST['idMetodo']);
$response = ['status' => 'error', 'message' => 'Acción no reconocida.'];

$con = new LocalConector();
$conex = $con->conectar();

try {
    // --- CORRECCIÓN: Obtenemos también el correo del creador ---
    $stmt_datos = $conex->prepare("
        SELECT 
            s.IdSolicitud, 
            s.NumeroParte,
            u.Nombre AS NombreCreador,
            u.Correo AS EmailCreador
        FROM Metodos m
        JOIN Solicitudes s ON m.IdMetodo = s.IdMetodo
        JOIN Usuarios u ON s.IdUsuario = u.IdUsuario
        WHERE m.IdMetodo = ?
    ");
    $stmt_datos->bind_param("i", $idMetodo);
    $stmt_datos->execute();
    $datosSolicitud = $stmt_datos->get_result()->fetch_assoc();
    $stmt_datos->close();

    if (!$datosSolicitud) {
        throw new Exception("No se encontraron los datos de la solicitud asociada a este método.");
    }

    if ($accion === 'aprobar') {
        $stmt = $conex->prepare("UPDATE Metodos SET EstatusAprobacion = 'Aprobado' WHERE IdMetodo = ?");
        $stmt->bind_param("i", $idMetodo);
        $stmt->execute();

        // --- CORRECCIÓN: Se activa el envío de correo de aprobación ---
        $emailDestino = $datosSolicitud['EmailCreador'];
        enviarCorreoAprobacion($emailDestino, $datosSolicitud);

        $response = ['status' => 'success', 'message' => 'Método de trabajo aprobado y notificación enviada.'];

    } elseif ($accion === 'rechazar') {
        if (empty($_POST['email']) || empty($_POST['motivo'])) {
            throw new Exception("El correo del destinatario y el motivo del rechazo son obligatorios.");
        }
        $emailDestino = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $motivoRechazo = strip_tags($_POST['motivo']);

        if (!$emailDestino) {
            throw new Exception("La dirección de correo electrónico no es válida.");
        }

        $stmt = $conex->prepare("UPDATE Metodos SET EstatusAprobacion = 'Rechazado' WHERE IdMetodo = ?");
        $stmt->bind_param("i", $idMetodo);
        $stmt->execute();

        enviarCorreoRechazo($emailDestino, $motivoRechazo, $datosSolicitud);

        $response = ['status' => 'success', 'message' => 'Método rechazado y notificación enviada.'];
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} finally {
    $conex->close();
}

echo json_encode($response);


// --- FUNCIONES PARA ENVÍO DE CORREOS ---

/**
 * Envía un correo notificando el rechazo de un método de trabajo.
 */
function enviarCorreoRechazo($emailDestino, $motivo, $datosSolicitud) {
    $folio = "S-" . str_pad($datosSolicitud['IdSolicitud'], 4, '0', STR_PAD_LEFT);
    $linkSolicitud = BASE_URL . '/trabajar_solicitud.php?id=' . $datosSolicitud['IdSolicitud'];
    $asunto = "Acción Requerida: Método de Trabajo Rechazado para Folio $folio";
    // --- CORRECCIÓN: Se obtiene el año actual para usarlo en la plantilla ---
    $currentYear = date('Y');

    $cuerpoHTML = <<<HTML
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:"Lato", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#a83232;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'>ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Método de Trabajo Rechazado</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola {$datosSolicitud['NombreCreador']},</p>
            <p style='color:#6c757d;line-height:1.6;'>Te informamos que el método de trabajo que subiste para la solicitud con folio <strong style='color:#0056b3;'>$folio</strong> (No. Parte: {$datosSolicitud['NumeroParte']}) ha sido rechazado.</p>
            
            <table border='0' cellpadding='15' cellspacing='0' width='100%' style='border-collapse:collapse;margin:25px 0;background-color:#fdecea;border-radius:8px; border-left: 5px solid #a83232;'>
                <tr>
                    <td style='font-weight:bold;color:#a83232;vertical-align:top;'>Motivo del Rechazo:</td>
                    <td style='color:#333;'>{$motivo}</td>
                </tr>
            </table>

            <p style='color:#6c757d;line-height:1.6;'>Por favor, accede a la solicitud para subir una versión corregida del método de trabajo y continuar con el proceso.</p>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='{$linkSolicitud}' target='_blank' style='font-size:16px;font-family:"Lato", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;'>Ir a la Solicitud</a>
            </td></tr></table>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; {$currentYear} ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>
    HTML;

    enviarCorreo($emailDestino, $asunto, $cuerpoHTML);
}

/**
 * Envía un correo notificando la aprobación de un método de trabajo.
 */
function enviarCorreoAprobacion($emailDestino, $datosSolicitud) {
    $folio = "S-" . str_pad($datosSolicitud['IdSolicitud'], 4, '0', STR_PAD_LEFT);
    $linkSolicitud = BASE_URL . '/trabajar_solicitud.php?id=' . $datosSolicitud['IdSolicitud'];
    $asunto = "Información: Método de Trabajo Aprobado para Folio $folio";
    // --- CORRECCIÓN: Se obtiene el año actual para usarlo en la plantilla ---
    $currentYear = date('Y');

    $cuerpoHTML = <<<HTML
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:"Lato", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#28a745;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'>ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Método de Trabajo Aprobado</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola {$datosSolicitud['NombreCreador']},</p>
            <p style='color:#6c757d;line-height:1.6;'>¡Buenas noticias! El método de trabajo para la solicitud <strong style='color:#0056b3;'>$folio</strong> (No. Parte: {$datosSolicitud['NumeroParte']}) ha sido aprobado.</p>
            <p style='color:#6c757d;line-height:1.6;'>Ya puedes continuar con las siguientes etapas del proceso de contención.</p>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='{$linkSolicitud}' target='_blank' style='font-size:16px;font-family:"Lato", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;'>Ir a la Solicitud</a>
            </td></tr></table>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; {$currentYear} ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>
    HTML;

    enviarCorreo($emailDestino, $asunto, $cuerpoHTML);
}


/**
 * Función genérica para enviar correos usando PHPMailer.
 */
function enviarCorreo($destinatario, $asunto, $cuerpo) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_arca@grammermx.com';
        $mail->Password = 'SARCAGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_arca@grammermx.com', 'Sistema ARCA');
        $mail->addAddress($destinatario);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $cuerpo;
        $mail->send();
    } catch (Exception $e) {
        throw new Exception("El cambio en la base de datos fue exitoso, pero el correo no pudo ser enviado. Error: {$mail->ErrorInfo}");
    }
}
?>

