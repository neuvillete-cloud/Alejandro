<?php
// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN Y FUNCIÓN AUXILIAR ---
$url_sitio = "https://grammermx.com/AleTest/ATS";

function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}
// --- FIN CONFIGURACIÓN ---

if (empty($_POST['token']) || empty($_POST['accion'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos esenciales.']);
    exit;
}

$token = $_POST['token'];
$accion = $_POST['accion'];

$con = new LocalConector();
$conex = $con->conectar();

$stmt = $conex->prepare("SELECT Id, IdSolicitud FROM AprobacionDescripcion WHERE Token = ? AND TokenValido = 1 AND Estatus = 'pendiente'");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Token inválido o ya utilizado.']);
    $conex->close();
    exit;
}

$aprobacion = $result->fetch_assoc();
$idSolicitud = $aprobacion['IdSolicitud'];
$idAprobacion = $aprobacion['Id'];

$conex->begin_transaction();
try {
    if ($accion === 'aprobar') {
        $nuevoEstatusSolicitud = 13; // "Descripción Aprobada"

        $stmtUpdateSol = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdateSol->bind_param("ii", $nuevoEstatusSolicitud, $idSolicitud);
        $stmtUpdateSol->execute();

        $stmtUpdateAprob = $conex->prepare("UPDATE AprobacionDescripcion SET Estatus = 'aprobado', TokenValido = 0 WHERE Id = ?");
        $stmtUpdateAprob->bind_param("i", $idAprobacion);
        $stmtUpdateAprob->execute();

        // --- NUEVO: Enviar correo de notificación de aprobación al administrador ---
        enviarCorreoAprobado($idSolicitud, $conex);

    } elseif ($accion === 'rechazar') {
        if (empty($_POST['comentarios']) || !isset($_FILES['archivoCorrecto']) || $_FILES['archivoCorrecto']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Faltan comentarios o el archivo corregido no se subió correctamente.");
        }
        $comentarios = $_POST['comentarios'];
        $archivoCorrecto = $_FILES['archivoCorrecto'];

        // Lógica para guardar archivo (sin cambios)
        $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . '/AleTest/ATS/descripciones/';
        if (!is_dir($directorioDestino)) { mkdir($directorioDestino, 0775, true); }
        if (!is_writable($directorioDestino)) { throw new Exception("Error de Permisos: El servidor no puede escribir en la carpeta 'descripciones'."); }
        $nombreOriginalLimpio = sanitizarNombreArchivo(basename($archivoCorrecto['name']));
        $nombreUnico = "corr_" . $idSolicitud . "_" . time() . "_" . $nombreOriginalLimpio;
        $rutaFisicaDestino = $directorioDestino . $nombreUnico;
        if (!move_uploaded_file($archivoCorrecto['tmp_name'], $rutaFisicaDestino)) { throw new Exception("Error al mover el archivo corregido."); }
        $rutaPublica = $url_sitio . "/descripciones/" . $nombreUnico;

        // Lógica de BD (sin cambios)
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $rutaPublica);
        $stmtDesc->execute();
        $idNuevaDescripcion = $conex->insert_id;
        $estatusRevertido = 2;
        $stmtUpdateSol = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, IdDescripcion = ? WHERE IdSolicitud = ?");
        $stmtUpdateSol->bind_param("iii", $estatusRevertido, $idNuevaDescripcion, $idSolicitud);
        $stmtUpdateSol->execute();
        $stmtUpdateAprob = $conex->prepare("UPDATE AprobacionDescripcion SET Estatus = 'rechazado', TokenValido = 0 WHERE Id = ?");
        $stmtUpdateAprob->bind_param("i", $idAprobacion);
        $stmtUpdateAprob->execute();

        // Notificar a los administradores
        enviarCorreoRechazo($idSolicitud, $comentarios, $rutaFisicaDestino, $conex);
    }

    $conex->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conex->close();


// --- NUEVA FUNCIÓN PARA NOTIFICAR APROBACIÓN ---
function enviarCorreoAprobado($idSolicitud, $conex) {
    global $url_sitio;

    // Obtenemos el puesto de la solicitud
    $stmtPuesto = $conex->prepare("SELECT Puesto FROM Solicitudes WHERE IdSolicitud = ?");
    $stmtPuesto->bind_param("i", $idSolicitud);
    $stmtPuesto->execute();
    $puesto = $stmtPuesto->get_result()->fetch_assoc()['Puesto'];

    // Obtenemos correos de administradores
    $resultAdmin = $conex->query("SELECT Correo FROM Usuario WHERE IdRol = 1");
    if ($resultAdmin->num_rows === 0) { return; }

    $asunto = "Descripción de Puesto Aprobada: " . $puesto;
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

    $contenidoHTML = "
    <!DOCTYPE html>
    <html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td style='padding: 20px 0;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <tr><td align='center' style='background-color: #005195; padding: 30px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                    <tr><td style='padding: 40px 30px; color: #333333; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;'>
                        <h2 style='color: #198754; margin-top: 0;'>¡Descripción Aprobada!</h2>
                        <p>Hola Administrador,</p>
                        <p>Le informamos que la descripción de puesto para la solicitud <strong>ID #$idSolicitud - \"$puesto\"</strong> ha sido <strong>aprobada</strong> por el solicitante.</p>
                        <p>Ya puedes continuar con el siguiente paso y crear la vacante desde el panel de 'Solicitudes Aprobadas'.</p>
                    </td></tr>
                    <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d;'>
                        <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México.</p>
                    </td></tr>
                </table>
            </td></tr>
        </table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        // ... (Configuración de PHPMailer)
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema de Aprobaciones ATS');
        while ($admin = $resultAdmin->fetch_assoc()) { $mail->addAddress($admin['Correo']); }
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) { /* No detener el proceso si el correo falla */ }
}


// --- FUNCIÓN DE RECHAZO (ESTÉTICA MEJORADA) ---
function enviarCorreoRechazo($idSolicitud, $comentarios, $rutaArchivoAdjunto, $conex) {
    global $url_sitio;

    // Obtenemos el puesto de la solicitud
    $stmtPuesto = $conex->prepare("SELECT Puesto FROM Solicitudes WHERE IdSolicitud = ?");
    $stmtPuesto->bind_param("i", $idSolicitud);
    $stmtPuesto->execute();
    $puesto = $stmtPuesto->get_result()->fetch_assoc()['Puesto'];

    // Obtenemos correos de administradores
    $resultAdmin = $conex->query("SELECT Correo FROM Usuario WHERE IdRol = 1");
    if ($resultAdmin->num_rows === 0) { return; }

    $asunto = "Acción Requerida: Descripción de Puesto Rechazada - " . $puesto;
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

    $contenidoHTML = "
    <!DOCTYPE html>
    <html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td style='padding: 20px 0;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <tr><td align='center' style='background-color: #005195; padding: 30px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                    <tr><td style='padding: 40px 30px; color: #333333; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;'>
                        <h2 style='color: #dc3545; margin-top: 0;'>Descripción Rechazada</h2>
                        <p>Hola Administrador,</p>
                        <p>El solicitante ha <strong>rechazado</strong> la descripción de puesto para la solicitud <strong>ID #$idSolicitud - \"$puesto\"</strong>.</p>
                        <p><strong>Comentarios del solicitante:</strong></p>
                        <blockquote style='border-left: 4px solid #dddddd; padding-left: 15px; margin-left: 0; font-style: italic;'>
                            <p>" . nl2br(htmlspecialchars($comentarios)) . "</p>
                        </blockquote>
                        <p>Se ha adjuntado la versión corregida del documento a este correo. La solicitud ha sido reasignada al panel de 'Solicitudes Aprobadas' para que puedas subir la nueva versión.</p>
                    </td></tr>
                    <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d;'>
                        <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México.</p>
                    </td></tr>
                </table>
            </td></tr>
        </table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        // ... (Configuración de PHPMailer)
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema de Aprobaciones ATS');
        while ($admin = $resultAdmin->fetch_assoc()) { $mail->addAddress($admin['Correo']); }
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->addAttachment($rutaArchivoAdjunto);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) { /* No detener el proceso si el correo falla */ }
}
?>