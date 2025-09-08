<?php
// 1. Encabezados y configuración inicial
header('Content-Type: application/json');
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('HR_MANAGER_NOMINA', '00030315');
$url_sitio = "https://grammermx.com/AleTest/ATS";

// 2. Verificación del método de la solicitud (DEBE SER LO PRIMERO)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Error: Método de solicitud no válido."]);
    exit;
}

// 3. Verificación de que todos los parámetros necesarios existen
$errors = [];
if (!isset($_POST['id']) || empty(trim($_POST['id']))) $errors[] = 'id';
if (!isset($_POST['status'])) $errors[] = 'status';
if (!isset($_POST['num_nomina']) || empty(trim($_POST['num_nomina']))) $errors[] = 'num_nomina';

if (!empty($errors)) {
    echo json_encode(["success" => false, "message" => "Error: Faltan parámetros: " . implode(', ', $errors)]);
    exit;
}

// 4. Asignación de variables (ahora es seguro hacerlo)
$idSolicitud = (int)$_POST['id'];
$decision = (int)$_POST['status'];
$numNominaAprobador = trim($_POST['num_nomina']);
$comentario = trim($_POST['comentario'] ?? '');
$approvalType = trim($_POST['approval_type'] ?? 'normal');

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 5. Lógica de negocio
    $stmt = $conex->prepare("SELECT IdAprobador1, IdAprobador2, Aprobacion1, Aprobacion2 FROM Solicitudes WHERE IdSolicitud = ? FOR UPDATE");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();

    if (!$solicitud) {
        throw new Exception("No se encontró la solicitud con ID: $idSolicitud.");
    }

    $idAprobador1 = $solicitud['IdAprobador1'];
    $idAprobador2 = $solicitud['IdAprobador2'];

    $columnaAprobacion = null;
    if ($numNominaAprobador == $idAprobador1) {
        $columnaAprobacion = "Aprobacion1";
    } elseif ($numNominaAprobador == $idAprobador2) {
        $columnaAprobacion = "Aprobacion2";
    }

    if (!$columnaAprobacion) {
        throw new Exception("Permiso denegado. Tu nómina ($numNominaAprobador) no corresponde a los aprobadores designados ($idAprobador1, $idAprobador2).");
    }

    $message = "";
    $nuevoEstadoGeneral = null;
    $valorDecision = ($decision === 3) ? 0 : 1; // 0 para rechazar, 1 para aprobar

    // Actualizar la columna de aprobación del gerente actual
    $updateStmt = $conex->prepare("UPDATE Solicitudes SET $columnaAprobacion = ? WHERE IdSolicitud = ?");
    $updateStmt->bind_param("ii", $valorDecision, $idSolicitud);
    $updateStmt->execute();

    if ($valorDecision === 0) { // RECHAZADO
        $nuevoEstadoGeneral = 3; // Estado final: Rechazado
        $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, Comentario = ? WHERE IdSolicitud = ?");
        $finalStmt->bind_param("isi", $nuevoEstadoGeneral, $comentario, $idSolicitud);
        $message = "La solicitud ha sido rechazada.";
    } else { // APROBADO
        // Volver a consultar para ver el estado de AMBAS aprobaciones
        $stmtCheck = $conex->prepare("SELECT Aprobacion1, Aprobacion2 FROM Solicitudes WHERE IdSolicitud = ?");
        $stmtCheck->bind_param("i", $idSolicitud);
        $stmtCheck->execute();
        $estadoActualizado = $stmtCheck->get_result()->fetch_assoc();

        if ($estadoActualizado['Aprobacion1'] == 1 && $estadoActualizado['Aprobacion2'] == 1) {
            $nuevoEstadoGeneral = 5; // Estado final: Aprobada (Cambiado de 2 a 5)
            $message = "¡Aprobación final! La solicitud ha sido aprobada por ambos responsables.";
        } else {
            $nuevoEstadoGeneral = 4; // Estado parcial: Aprobado Parcialmente
            $message = "Tu aprobación ha sido registrada. Aún falta la aprobación del otro gerente.";
        }
        $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $finalStmt->bind_param("ii", $nuevoEstadoGeneral, $idSolicitud);
    }
    $finalStmt->execute();

    // Marcar como confidencial SI APLICA
    if ($numNominaAprobador == HR_MANAGER_NOMINA && $approvalType === 'confidential' && $valorDecision === 1) {
        $confStmt = $conex->prepare("UPDATE Solicitudes SET EsConfidencial = 1 WHERE IdSolicitud = ?");
        $confStmt->bind_param("i", $idSolicitud);
        $confStmt->execute();
        $message .= " La solicitud ha sido marcada como confidencial.";
    }

    // Enviar correos si el estado es final
    if ($nuevoEstadoGeneral === 5 || $nuevoEstadoGeneral === 3) { // Cambiado de 2 a 5
        $infoStmt = $conex->prepare("SELECT s.Puesto, s.EsConfidencial, u.Correo AS CorreoSolicitante FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $infoStmt->bind_param("i", $idSolicitud);
        $infoStmt->execute();
        $infoSolicitud = $infoStmt->get_result()->fetch_assoc();

        if ($infoSolicitud) {
            if ($nuevoEstadoGeneral === 5) { // Cambiado de 2 a 5
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

function enviarCorreoAprobacionFinal($infoSolicitud, $idSolicitud, $conex) {
    global $url_sitio, $HR_MANAGER_NOMINA;
    $asunto = "Solicitud Aprobada: " . $infoSolicitud['Puesto'];
    $destinatarios = [];

    if ($infoSolicitud['EsConfidencial'] == 1) {
        $stmtHR = $conex->prepare("SELECT Correo FROM Usuario WHERE NumNomina = ?");
        $stmtHR->bind_param("s", $HR_MANAGER_NOMINA);
        $stmtHR->execute();
        if ($hrUser = $stmtHR->get_result()->fetch_assoc()) {
            $destinatarios[] = $hrUser['Correo'];
        }
    } else {
        $resultAdmin = $conex->query("SELECT Correo FROM Usuario WHERE IdRol = 1");
        while ($admin = $resultAdmin->fetch_assoc()) {
            $destinatarios[] = $admin['Correo'];
        }
    }

    if (!empty($destinatarios)) {
        $mensajeAdmin = "<h2 style='color: #198754; margin-top: 0;'>¡Solicitud Aprobada!</h2><p>Hola,</p><p>La solicitud de personal para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido completamente aprobada.</p><p>Puedes iniciar la siguiente fase desde el panel de 'Solicitudes Aprobadas' en el sistema ATS.</p>";
        enviarCorreoOutlook($destinatarios, $asunto, $mensajeAdmin);
    }

    if (!empty($infoSolicitud['CorreoSolicitante'])) {
        $asuntoSolicitante = "Actualización de tu Solicitud: " . $infoSolicitud['Puesto'];
        $mensajeSolicitante = "<h2 style='color: #198754; margin-top: 0;'>¡Tu Solicitud fue Aprobada!</h2><p>Hola,</p><p>Te informamos que tu solicitud para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido aprobada.</p><p>El equipo de Recursos Humanos comenzará con el proceso de reclutamiento.</p>";
        enviarCorreoOutlook([$infoSolicitud['CorreoSolicitante']], $asuntoSolicitante, $mensajeSolicitante);
    }
}

function enviarCorreoRechazoFinal($infoSolicitud, $idSolicitud, $comentario, $conex) {
    if (!empty($infoSolicitud['CorreoSolicitante'])) {
        $asunto = "Solicitud Rechazada: " . $infoSolicitud['Puesto'];
        $mensajeSolicitante = "<h2 style='color: #dc3545; margin-top: 0;'>Actualización sobre tu Solicitud</h2><p>Hola,</p><p>Te informamos que tu solicitud para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido rechazada.</p><p><strong>Comentarios del aprobador:</strong></p><blockquote style='border-left: 4px solid #dddddd; padding-left: 15px; margin-left: 0; font-style: italic;'><p>" . nl2br(htmlspecialchars($comentario)) . "</p></blockquote><p>Si tienes dudas, contacta directamente con el gerente que realizó la aprobación.</p>";
        enviarCorreoOutlook([$infoSolicitud['CorreoSolicitante']], $asunto, $mensajeSolicitante);
    }
}

function enviarCorreoOutlook(array $destinatarios, $asunto, $cuerpoMensaje) {
    global $url_sitio;
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    $destinatarios = array_filter(array_unique($destinatarios));
    if (empty($destinatarios)) return;

    $contenidoHTML = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px;'>
        <table align='center' width='600' style='border-collapse: collapse; background-color: #ffffff; border: 1px solid #dddddd;'>
            <tr><td align='center' style='background-color: #005195; padding: 30px;'><img src='$logoUrl' alt='Logo Grammer' width='150'></td></tr>
            <tr><td style='padding: 40px 30px; font-size: 16px; line-height: 1.6;'>
                $cuerpoMensaje
                <p>Saludos cordiales,<br><strong>Sistema ATS - Grammer</strong></p>
            </td></tr>
            <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</td></tr>
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

        foreach($destinatarios as $email) $mail->addAddress($email);

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
    }
}
?>

