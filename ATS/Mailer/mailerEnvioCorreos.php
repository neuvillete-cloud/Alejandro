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

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // --- PASO 1: OBTENER DATOS Y CONTAR APROBADORES ---
    $stmtFolio = $conex->prepare("SELECT FolioSolicitud FROM Solicitudes WHERE IdSolicitud = ?");
    $stmtFolio->bind_param("i", $idSolicitud);
    $stmtFolio->execute();
    $resultFolio = $stmtFolio->get_result();
    if ($resultFolio->num_rows === 0) {
        throw new Exception("No se encontró la solicitud con el ID proporcionado.");
    }
    $folio = $resultFolio->fetch_assoc()['FolioSolicitud'];

    $aprobadoresRequeridos = 0;
    if (!empty($email1)) { $aprobadoresRequeridos++; }
    if (!empty($email2)) { $aprobadoresRequeridos++; }
    if (!empty($email3)) { $aprobadoresRequeridos++; }

    // --- PASO 2: ENVIAR LOS CORREOS ---
    $linkAprobacion = "https://grammermx.com/AleTest/ATS/aprobar_solicitud.php?folio=$folio";
    enviarCorreoNotificacion($email1, $email2, $email3, $folio, $linkAprobacion);

    // --- PASO 3: ACTUALIZAR EL ESTATUS Y APROBADORES EN LA BASE DE DATOS ---
    $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, AprobadoresRequeridos = ? WHERE IdSolicitud = ?");
    $stmtUpdate->bind_param("iii", $nuevoEstado, $aprobadoresRequeridos, $idSolicitud);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Los correos se enviaron, pero no se pudo actualizar el estatus de la solicitud.");
    }

    // Si todo salió bien, confirmamos los cambios
    $conex->commit();
    echo json_encode(["status" => "success", "message" => "Solicitud aprobada y correos enviados."]);

} catch (Exception $e) {
    // Si algo falla (el envío de correo o la actualización de BD), revertimos todo
    $conex->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    $conex->close();
}


function enviarCorreoNotificacion($email1, $email2, $email3, $folio, $link) {
    $asunto = "Solicitud Pendiente de Aprobación - Folio $folio";
    $contenidoHTML = "
    <!DOCTYPE html>
    <html lang='es'>
    <head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td style='padding: 20px 0;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <tr><td align='center' style='background-color: #005195; padding: 30px;'><img src='https://grammermx.com/AleTest/ATS/imagenes/logo_blanco.png' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                    <tr><td style='padding: 40px 30px; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;'>
                        <h2 style='color: #005195; margin-top: 0;'>Solicitud Pendiente</h2>
                        <p>Estimado Aprobador,</p>
                        <p>Se ha generado una nueva solicitud con el folio <strong>$folio</strong> que requiere su decisión.</p>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                            <tr><td align='center' style='padding: 20px 0;'>
                                <a href='$link' target='_blank' style='font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; background-color: #0d6efd; padding: 15px 30px; border-radius: 8px; display: inline-block; font-weight: bold;'>Revisar Solicitud</a>
                            </td></tr>
                        </table>
                        <p>Saludos cordiales,<br><strong>Sistema ATS - Grammer</strong></p>
                    </td></tr>
                    <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d;'>
                        <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México.</p>
                    </td></tr>
                </table>
            </td></tr>
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
        $mail->Body = $contenidoHTML;

        $mail->send();
    } catch (Exception $e) {
        // Si el correo falla, lanzamos una excepción para que la transacción de la base de datos se revierta
        throw new Exception("No se pudo enviar el correo de notificación. Error: " . $mail->ErrorInfo);
    }
}
?>