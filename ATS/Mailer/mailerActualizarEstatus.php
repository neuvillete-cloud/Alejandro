<?php
header('Content-Type: application/json');
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php'; // Asegúrate que la ruta sea correcta
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CONFIGURACIÓN IMPORTANTE ---
define('HR_MANAGER_NOMINA', '00030315'); // Reemplaza con la nómina real de RRHH
$url_sitio = "https://grammermx.com/AleTest/ATS";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'], $_POST['status'], $_POST['num_nomina'])) {
    echo json_encode(["success" => false, "message" => "Error: Faltan parámetros esenciales (id, status, o num_nomina)."]);
    exit;
}

$idSolicitud = (int)$_POST['id'];
$decision = (int)$_POST['status'];
$numNominaAprobador = trim($_POST['num_nomina']);
$comentario = trim($_POST['comentario'] ?? '');
$approvalType = trim($_POST['approval_type'] ?? 'normal');

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // --- LÓGICA DE APROBACIÓN (SIN CAMBIOS) ---
    $stmt = $conex->prepare("SELECT IdAprobador1, IdAprobador2, Aprobacion1, Aprobacion2 FROM Solicitudes WHERE IdSolicitud = ? FOR UPDATE");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();
    if (!$solicitud) throw new Exception("No se encontró la solicitud.");

    $idAprobador1 = $solicitud['IdAprobador1'];
    $idAprobador2 = $solicitud['IdAprobador2'];
    $columnaAprobacion = ($numNominaAprobador == $idAprobador1) ? "Aprobacion1" : (($numNominaAprobador == $idAprobador2) ? "Aprobacion2" : null);
    $otraColumnaAprobacion = ($columnaAprobacion == "Aprobacion1") ? "Aprobacion2" : "Aprobacion1";
    if (!$columnaAprobacion) throw new Exception("No tienes permiso para actuar sobre esta solicitud.");

    $valorDecision = ($decision === 3) ? 0 : 1;
    $updateStmt = $conex->prepare("UPDATE Solicitudes SET $columnaAprobacion = ? WHERE IdSolicitud = ?");
    $updateStmt->bind_param("ii", $valorDecision, $idSolicitud);
    $updateStmt->execute();

    $message = "";
    $nuevoEstadoGeneral = null;

    if ($valorDecision === 0) {
        $nuevoEstadoGeneral = 3; // Rechazado
        $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, Comentario = ? WHERE IdSolicitud = ?");
        $finalStmt->bind_param("isi", $nuevoEstadoGeneral, $comentario, $idSolicitud);
        $message = "La solicitud ha sido rechazada.";
    } else {
        $otraAprobacion = $solicitud[$otraColumnaAprobacion];
        if ($otraAprobacion == 1) {
            $nuevoEstadoGeneral = 2; // Aprobada (final)
            $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
            $finalStmt->bind_param("ii", $nuevoEstadoGeneral, $idSolicitud);
            $message = "¡Aprobación final! La solicitud ha sido aprobada por ambos responsables.";
        } else {
            $nuevoEstadoGeneral = 4; // Aprobado Parcialmente
            $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
            $finalStmt->bind_param("ii", $nuevoEstadoGeneral, $idSolicitud);
            $message = "Tu aprobación ha sido registrada. Aún falta la aprobación del otro gerente.";
        }
    }
    $finalStmt->execute();

    if ($numNominaAprobador == HR_MANAGER_NOMINA && $approvalType === 'confidential') {
        $confStmt = $conex->prepare("UPDATE Solicitudes SET EsConfidencial = 1 WHERE IdSolicitud = ?");
        $confStmt->bind_param("i", $idSolicitud);
        $confStmt->execute();
        $message .= " La solicitud ha sido marcada como confidencial.";
    }

    // --- LÓGICA DE NOTIFICACIÓN FINAL (CON VERIFICACIÓN AÑADIDA) ---
    if ($nuevoEstadoGeneral === 2 || $nuevoEstadoGeneral === 3) {
        $infoStmt = $conex->prepare("SELECT s.Puesto, s.EsConfidencial, u.Correo AS CorreoSolicitante FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $infoStmt->bind_param("i", $idSolicitud);
        $infoStmt->execute();
        $infoSolicitud = $infoStmt->get_result()->fetch_assoc();

        // Verificamos que la consulta para obtener la info del correo SÍ devolvió un resultado
        if ($infoSolicitud) {
            if ($nuevoEstadoGeneral === 2) {
                enviarCorreoAprobacionFinal($infoSolicitud, $idSolicitud, $conex);
            } else {
                enviarCorreoRechazoFinal($infoSolicitud, $idSolicitud, $comentario, $conex);
            }
        }
    }

    $conex->commit();
    echo json_encode(["success" => true, "message" => $message]);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conex->close();


// --- FUNCIÓN DE CORREO DE APROBACIÓN FINAL (CORREGIDA) ---
function enviarCorreoAprobacionFinal($infoSolicitud, $idSolicitud, $conex) {
    global $url_sitio;
    $asunto = "Solicitud Aprobada: " . $infoSolicitud['Puesto'];
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    $destinatarios = [];

    if ($infoSolicitud['EsConfidencial'] == 1) {
        $stmtHR = $conex->prepare("SELECT Correo FROM Usuario WHERE NumNomina = ?");
        $stmtHR->bind_param("s", HR_MANAGER_NOMINA);
        $stmtHR->execute();
        $resultHR = $stmtHR->get_result();
        // --- INICIO DE LA CORRECCIÓN ---
        if ($hrUser = $resultHR->fetch_assoc()) {
            $destinatarios[] = $hrUser['Correo'];
        }
        // --- FIN DE LA CORRECCIÓN ---
    } else {
        $resultAdmin = $conex->query("SELECT Correo FROM Usuario WHERE IdRol = 1");
        while ($admin = $resultAdmin->fetch_assoc()) {
            $destinatarios[] = $admin['Correo'];
        }
    }

    $mensajeAdmin = "<h2 style='color: #198754; margin-top: 0;'>¡Solicitud Aprobada!</h2><p>Hola,</p><p>La solicitud de personal para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido completamente aprobada.</p><p>Puedes iniciar la siguiente fase desde el panel de 'Solicitudes Aprobadas' en el sistema ATS.</p>";
    enviarCorreoOutlook($destinatarios, $asunto, $mensajeAdmin, $logoUrl);

    $asuntoSolicitante = "Actualización de tu Solicitud: " . $infoSolicitud['Puesto'];
    $mensajeSolicitante = "<h2 style='color: #198754; margin-top: 0;'>¡Tu Solicitud fue Aprobada!</h2><p>Hola,</p><p>Te informamos que tu solicitud para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido aprobada.</p><p>El equipo de Recursos Humanos comenzará con el proceso de reclutamiento.</p>";
    // --- INICIO DE LA CORRECCIÓN ---
    if (!empty($infoSolicitud['CorreoSolicitante'])) {
        enviarCorreoOutlook([$infoSolicitud['CorreoSolicitante']], $asuntoSolicitante, $mensajeSolicitante, $logoUrl);
    }
    // --- FIN DE LA CORRECCIÓN ---
}

// --- FUNCIÓN DE CORREO DE RECHAZO FINAL (CORREGIDA) ---
function enviarCorreoRechazoFinal($infoSolicitud, $idSolicitud, $comentario, $conex) {
    global $url_sitio;
    $asunto = "Solicitud Rechazada: " . $infoSolicitud['Puesto'];
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    $mensajeSolicitante = "<h2 style='color: #dc3545; margin-top: 0;'>Actualización sobre tu Solicitud</h2><p>Hola,</p><p>Te informamos que tu solicitud para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido rechazada.</p><p><strong>Comentarios del aprobador:</strong></p><blockquote style='border-left: 4px solid #dddddd; padding-left: 15px; margin-left: 0; font-style: italic;'><p>" . nl2br(htmlspecialchars($comentario)) . "</p></blockquote><p>Si tienes dudas, contacta directamente con el gerente que realizó la aprobación.</p>";
    // --- INICIO DE LA CORRECCIÓN ---
    if (!empty($infoSolicitud['CorreoSolicitante'])) {
        enviarCorreoOutlook([$infoSolicitud['CorreoSolicitante']], $asunto, $mensajeSolicitante, $logoUrl);
    }
    // --- FIN DE LA CORRECCIÓN ---
}


// --- FUNCIÓN CENTRAL DE ENVÍO (SIN CAMBIOS) ---
function enviarCorreoOutlook(array $destinatarios, $asunto, $cuerpoMensaje, $logoUrl) {
    if (empty($destinatarios)) return;
    $contenidoHTML = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td style='padding: 20px 0;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff;'>
                    <tr><td align='center' style='background-color: #005195; padding: 30px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                    <tr><td style='padding: 40px 30px; color: #333333; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;'>
                        $cuerpoMensaje
                        <p>Saludos cordiales,<br><strong>Sistema ATS - Grammer</strong></p>
                    </td></tr>
                    <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d;'><p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</p></td></tr>
                </table>
            </td></tr>
        </table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema ATS Grammer');

        foreach($destinatarios as $email) {
            if (!empty($email)) $mail->addAddress($email);
        }

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error al enviar a " . implode(", ", $destinatarios) . ": " . $mail->ErrorInfo);
    }
}
?>