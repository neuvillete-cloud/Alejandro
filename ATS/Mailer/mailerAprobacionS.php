<?php
header('Content-Type: application/json');
session_start();
include_once("ConexionBD.php");
// Se necesita PHPMailer aquí también para enviar la notificación final
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Verificación de datos de entrada ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Se requiere método POST.']);
    exit;
}
if (!isset($_POST['accion'], $_POST['folio'], $_POST['num_nomina_aprobador'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos. Faltó folio, acción o nómina.']);
    exit;
}

// --- Recolección de datos ---
$Accion = $_POST['accion'];
$FolioSolicitud = $_POST['folio'];
$NumNominaAprobador = $_POST['num_nomina_aprobador'];
$Comentario = ($Accion == 'rechazar' && isset($_POST['comentario'])) ? $_POST['comentario'] : "";
$IdEstatusDecision = ($Accion == 'rechazar') ? 3 : 5; // 5 = Aprobado, 3 = Rechazado

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 1. Buscamos el nombre del aprobador actual
    $stmtUser = $conex->prepare("SELECT Nombre FROM Usuario WHERE NumNomina = ?");
    $stmtUser->bind_param("s", $NumNominaAprobador);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    if ($resultUser->num_rows === 0) {
        throw new Exception('Aprobador no encontrado en la base de datos de usuarios.');
    }
    $nombreAprobador = $resultUser->fetch_assoc()['Nombre'];
    $stmtUser->close();

    // 2. Verificamos si este aprobador ya registró una acción
    $stmtCheck = $conex->prepare("SELECT IdAprobador FROM Aprobadores WHERE FolioSolicitud = ? AND Nombre = ?");
    $stmtCheck->bind_param("ss", $FolioSolicitud, $nombreAprobador);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        throw new Exception('Ya has registrado una acción para esta solicitud previamente.');
    }
    $stmtCheck->close();

    // 3. Si todo está bien, registramos la decisión del aprobador en la tabla Aprobadores
    $stmtInsert = $conex->prepare("INSERT INTO Aprobadores (Nombre, IdEstatus, FolioSolicitud, Comentarios) VALUES (?, ?, ?, ?)");
    $stmtInsert->bind_param("siss", $nombreAprobador, $IdEstatusDecision, $FolioSolicitud, $Comentario);
    $stmtInsert->execute();

    // --- INICIO DE LA NUEVA LÓGICA DE NOTIFICACIÓN FINAL ---

    // 4. Obtenemos el total de aprobadores requeridos para esta solicitud
    $stmtReq = $conex->prepare("SELECT AprobadoresRequeridos, Puesto FROM Solicitudes WHERE FolioSolicitud = ?");
    $stmtReq->bind_param("s", $FolioSolicitud);
    $stmtReq->execute();
    $solicitudInfo = $stmtReq->get_result()->fetch_assoc();
    $requeridos = $solicitudInfo['AprobadoresRequeridos'];
    $puesto = $solicitudInfo['Puesto'];
    $stmtReq->close();

    // 5. Contamos cuántos han aprobado hasta ahora
    $stmtConteo = $conex->prepare("SELECT COUNT(IdAprobador) as conteo FROM Aprobadores WHERE FolioSolicitud = ? AND IdEstatus = 5");
    $stmtConteo->bind_param("s", $FolioSolicitud);
    $stmtConteo->execute();
    $conteoActual = $stmtConteo->get_result()->fetch_assoc()['conteo'];
    $stmtConteo->close();

    // 6. Comparamos: si el conteo es igual al requerido Y la acción fue 'aprobar', enviamos el correo
    if ($Accion == 'aprobar' && $conteoActual >= $requeridos) {
        // Actualizamos el estado final de la solicitud a 5 (Aprobada 2da Fase)
        $stmtFinal = $conex->prepare("UPDATE Solicitudes SET IdEstatus = 5 WHERE FolioSolicitud = ?");
        $stmtFinal->bind_param("s", $FolioSolicitud);
        $stmtFinal->execute();
        $stmtFinal->close();

        // Buscamos los correos de los administradores (Rol 1)
        $stmtAdmins = $conex->prepare("SELECT Correo FROM Usuario WHERE IdRol = 1");
        $stmtAdmins->execute();
        $resultAdmins = $stmtAdmins->get_result();
        $correosAdmins = [];
        while ($admin = $resultAdmins->fetch_assoc()) {
            $correosAdmins[] = $admin['Correo'];
        }
        $stmtAdmins->close();

        // Enviamos la notificación final
        if (!empty($correosAdmins)) {
            enviarCorreoAprobacionFinalAdmin($correosAdmins, $FolioSolicitud, $puesto);
        }
    }
    // --- FIN DE LA NUEVA LÓGICA ---

    $conex->commit();
    echo json_encode(['success' => true, 'message' => "Acción registrada con éxito."]);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conex) $conex->close();
}


/**
 * Función que envía el correo de notificación final a los administradores.
 */
function enviarCorreoAprobacionFinalAdmin($correosAdmins, $folio, $puesto) {
    $asunto = "Request Fully Approved: Folio $folio";
    $url_sitio = "https://grammermx.com/AleTest/ATS";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    $linkSeguimiento = "$url_sitio/SAprobadas.php";

    $cuerpo = "
        <h2 style='color: #198754;'>Request Approved and Ready</h2>
        <p>Hello Administrator,</p>
        <p>The personnel requisition for the position of <strong>" . htmlspecialchars($puesto) . "</strong> (Folio: <strong>$folio</strong>) has been fully approved by all required managers.</p>
        <p>You can now proceed with the next steps in the recruitment process. Please visit the approved requests panel to continue.</p>
        <a href='$linkSeguimiento' style='display: inline-block; background-color: #0d6efd; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Approved Requests</a>
    ";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Grammer ATS System');

        foreach($correosAdmins as $correo) {
            if(!empty($correo)) $mail->addAddress($correo);
        }

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $cuerpo; // Para simplicidad, pero idealmente usarías tu plantilla completa
        $mail->send();
    } catch (Exception $e) {
        // No lanzamos una excepción para no revertir la aprobación, solo registramos el error
        error_log("Error al enviar correo de aprobación final al admin: " . $mail->ErrorInfo);
    }
}
?>

