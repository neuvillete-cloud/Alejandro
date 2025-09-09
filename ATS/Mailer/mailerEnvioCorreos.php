<?php
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

if (
    !isset($_POST['id']) ||
    !isset($_POST['status']) ||
    !isset($_POST['email1'])
) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    exit;
}

$idSolicitud = intval($_POST['id']);
$nuevoEstado = intval($_POST['status']);
$email1 = trim($_POST['email1']);
$email2 = trim($_POST['email2'] ?? '');
$email3 = trim($_POST['email3'] ?? '');
$url_sitio = "https://grammermx.com/AleTest/ATS"; // Asegúrate que esta URL sea la correcta

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // --- PASO 1: OBTENER DATOS Y CONTAR APROBADORES ---
    $stmtFolio = $conex->prepare("SELECT FolioSolicitud, Puesto FROM Solicitudes WHERE IdSolicitud = ?");
    $stmtFolio->bind_param("i", $idSolicitud);
    $stmtFolio->execute();
    $resultData = $stmtFolio->get_result();
    if ($resultData->num_rows === 0) {
        throw new Exception("No se encontró la solicitud con el ID proporcionado.");
    }
    $solicitudData = $resultData->fetch_assoc();
    $folio = $solicitudData['FolioSolicitud'];
    $puesto = $solicitudData['Puesto'];


    $aprobadoresRequeridos = 0;
    if (!empty($email1)) { $aprobadoresRequeridos++; }
    if (!empty($email2)) { $aprobadoresRequeridos++; }
    if (!empty($email3)) { $aprobadoresRequeridos++; }

    // --- PASO 2: ENVIAR CORREOS DE NOTIFICACIÓN ---
    $linkAprobacion = "$url_sitio/aprobar_solicitud.php?folio=$folio"; // Página de aprobación asumida
    enviarCorreoNotificacion($email1, $email2, $email3, $folio, $puesto, $linkAprobacion, $url_sitio);

    // --- PASO 3: ACTUALIZAR ESTATUS Y APROBADORES EN LA BASE DE DATOS ---
    $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, AprobadoresRequeridos = ? WHERE IdSolicitud = ?");
    $stmtUpdate->bind_param("iii", $nuevoEstado, $aprobadoresRequeridos, $idSolicitud);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Los correos se enviaron, pero no se pudo actualizar el estatus de la solicitud.");
    }

    // Si todo salió bien, se confirman los cambios
    $conex->commit();
    echo json_encode(["status" => "success", "message" => "Solicitud aprobada y correos enviados exitosamente."]);

} catch (Exception $e) {
    // Si algo falla, se revierte todo
    $conex->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    $conex->close();
}


function enviarCorreoNotificacion($email1, $email2, $email3, $folio, $puesto, $link, $url_sitio) {
    // --- El contenido del correo se mantiene en Inglés ---
    $asunto = "Action Required: Approval for Personnel Request - Folio $folio";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

    $contenidoHTML = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$asunto</title>
    </head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='padding: 20px 0;'>
                    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                        <tr>
                            <td align='center' style='background-color: #005195; padding: 30px; border-top-left-radius: 12px; border-top-right-radius: 12px;'>
                                <img src='$logoUrl' alt='Grammer Logo' width='150' style='display: block;'>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 40px 30px; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333;'>
                                <h2 style='color: #005195; margin-top: 0; font-size: 24px;'>Request Pending Approval</h2>
                                <p>Dear Approver,</p>
                                <p>A new personnel requisition for the position of <strong>" . htmlspecialchars($puesto) . "</strong> (Folio: <strong>$folio</strong>) has been submitted and requires your decision.</p>
                                <p>Your timely action is crucial to move forward with the recruitment process. Please review the request details by clicking the button below.</p>
                                
                                <!-- Botón compatible con Outlook -->
                                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                    <tr>
                                        <td align='center' style='padding: 20px 0;'>
                                            <table border='0' cellspacing='0' cellpadding='0'>
                                                <tr>
                                                    <td align='center' style='border-radius: 8px; background-color: #0d6efd;'>
                                                        <a href='$link' target='_blank' style='font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; border-radius: 8px; padding: 15px 30px; border: 1px solid #0d6efd; display: inline-block; font-weight: bold;'>
                                                            Review Request
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p>Thank you for your attention to this matter.</p>
                                <p>Best regards,<br><strong>Grammer ATS System</strong></p>
                            </td>
                        </tr>
                        <tr>
                            <td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;'>
                                <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. This is an automated notification.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@gramermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Remitente y Destinatarios
        $mail->setFrom('sistema_ats@gramermx.com', 'Grammer ATS Administration');
        if (!empty($email1)) $mail->addAddress($email1);
        if (!empty($email2)) $mail->addAddress($email2);
        if (!empty($email3)) $mail->addAddress($email3);
        $mail->addBCC('sistema_ats@gramermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        $mail->send();
    } catch (Exception $e) {
        // Se lanza una excepción para revertir la transacción de la base de datos
        throw new Exception("No se pudo enviar el correo de notificación. Error: " . $mail->ErrorInfo);
    }
}
?>


