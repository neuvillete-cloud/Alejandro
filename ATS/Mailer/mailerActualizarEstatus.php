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
$decision = (int)$_POST['status']; // 2 para aprobar, 3 para rechazar desde el frontend
$numNominaAprobador = trim($_POST['num_nomina']);
$comentario = trim($_POST['comentario'] ?? '');
$approvalType = trim($_POST['approval_type'] ?? 'normal');

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 1. Obtener el estado actual de la solicitud y los aprobadores designados
    $stmt = $conex->prepare("SELECT IdAprobador1, IdAprobador2, Aprobacion1, Aprobacion2 FROM Solicitudes WHERE IdSolicitud = ? FOR UPDATE");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();

    if (!$solicitud) {
        throw new Exception("No se encontró la solicitud con ID: $idSolicitud.");
    }

    $idAprobador1 = $solicitud['IdAprobador1'];
    $idAprobador2 = $solicitud['IdAprobador2'];

    // 2. Determinar si el usuario actual es un aprobador válido y qué columna le corresponde
    $columnaAprobacion = null;
    $otraColumnaAprobacion = null;

    if ($numNominaAprobador == $idAprobador1) {
        $columnaAprobacion = "Aprobacion1";
        $otraColumnaAprobacion = "Aprobacion2";
    } elseif ($numNominaAprobador == $idAprobador2) {
        $columnaAprobacion = "Aprobacion2";
        $otraColumnaAprobacion = "Aprobacion1";
    }

    // --- CAMBIO CLAVE 1: Mensaje de error mejorado ---
    // Si el usuario no es ninguno de los dos aprobadores, se lanza un error descriptivo.
    if (!$columnaAprobacion) {
        throw new Exception("Permiso denegado. Tu nómina ($numNominaAprobador) no corresponde a los aprobadores designados ($idAprobador1, $idAprobador2).");
    }

    $message = "";
    $nuevoEstadoGeneral = null;

    // 3. Lógica para manejar la decisión (Rechazar o Aprobar)
    if ($decision === 3) { // El usuario RECHAZÓ la solicitud
        $valorDecision = 0; // 0 representa rechazo en la BD
        $nuevoEstadoGeneral = 3; // 3 = Rechazado (estado final)

        // Actualiza su propia columna de aprobación a 'rechazado'
        $updateStmt = $conex->prepare("UPDATE Solicitudes SET $columnaAprobacion = ? WHERE IdSolicitud = ?");
        $updateStmt->bind_param("ii", $valorDecision, $idSolicitud);
        $updateStmt->execute();

        // Actualiza el estado general de la solicitud a 'Rechazado' y guarda el comentario
        $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, Comentario = ? WHERE IdSolicitud = ?");
        $finalStmt->bind_param("isi", $nuevoEstadoGeneral, $comentario, $idSolicitud);
        $finalStmt->execute();

        $message = "La solicitud ha sido rechazada.";

    } else { // El usuario APROBÓ la solicitud
        $valorDecision = 1; // 1 representa aprobación en la BD

        // Actualiza su propia columna de aprobación a 'aprobado'
        $updateStmt = $conex->prepare("UPDATE Solicitudes SET $columnaAprobacion = ? WHERE IdSolicitud = ?");
        $updateStmt->bind_param("ii", $valorDecision, $idSolicitud);
        $updateStmt->execute();

        // --- CAMBIO CLAVE 2: Lógica de aprobación final simplificada ---
        // Obtenemos el valor de la aprobación del OTRO gerente DESPUÉS de actualizar la nuestra.
        $stmtCheck = $conex->prepare("SELECT Aprobacion1, Aprobacion2 FROM Solicitudes WHERE IdSolicitud = ?");
        $stmtCheck->bind_param("i", $idSolicitud);
        $stmtCheck->execute();
        $estadoActualizado = $stmtCheck->get_result()->fetch_assoc();

        if ($estadoActualizado['Aprobacion1'] == 1 && $estadoActualizado['Aprobacion2'] == 1) {
            // Si AMBOS han aprobado, el estado final es 'Aprobada'
            $nuevoEstadoGeneral = 2; // 2 = Aprobada
            $message = "¡Aprobación final! La solicitud ha sido aprobada por ambos responsables.";
        } else {
            // Si solo uno ha aprobado, el estado es 'Aprobado Parcialmente'
            $nuevoEstadoGeneral = 4; // 4 = Aprobado Parcialmente
            $message = "Tu aprobación ha sido registrada. Aún falta la aprobación del otro gerente.";
        }

        // Actualiza el estado general de la solicitud
        $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $finalStmt->bind_param("ii", $nuevoEstadoGeneral, $idSolicitud);
        $finalStmt->execute();
    }

    // 4. Manejar la opción de 'Confidencial' si fue seleccionada por RRHH
    if ($numNominaAprobador == HR_MANAGER_NOMINA && $approvalType === 'confidential' && $valorDecision === 1) {
        $confStmt = $conex->prepare("UPDATE Solicitudes SET EsConfidencial = 1 WHERE IdSolicitud = ?");
        $confStmt->bind_param("i", $idSolicitud);
        $confStmt->execute();
        $message .= " La solicitud ha sido marcada como confidencial.";
    }

    // 5. Enviar notificaciones por correo electrónico si el estado es final (Aprobado o Rechazado)
    if ($nuevoEstadoGeneral === 2 || $nuevoEstadoGeneral === 3) {
        $infoStmt = $conex->prepare("SELECT s.Puesto, s.EsConfidencial, u.Correo AS CorreoSolicitante FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $infoStmt->bind_param("i", $idSolicitud);
        $infoStmt->execute();
        $infoSolicitud = $infoStmt->get_result()->fetch_assoc();

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
    // Devolvemos el mensaje de error de la excepción, que ahora es más descriptivo
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
$conex->close();


// --- El resto de las funciones de correo no necesitan cambios ---

// --- FUNCIÓN DE CORREO DE APROBACIÓN FINAL (SIN CAMBIOS, PERO REVISADA) ---
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
        if ($hrUser = $resultHR->fetch_assoc()) {
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
        enviarCorreoOutlook($destinatarios, $asunto, $mensajeAdmin, $logoUrl);
    }

    if (!empty($infoSolicitud['CorreoSolicitante'])) {
        $asuntoSolicitante = "Actualización de tu Solicitud: " . $infoSolicitud['Puesto'];
        $mensajeSolicitante = "<h2 style='color: #198754; margin-top: 0;'>¡Tu Solicitud fue Aprobada!</h2><p>Hola,</p><p>Te informamos que tu solicitud para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido aprobada.</p><p>El equipo de Recursos Humanos comenzará con el proceso de reclutamiento.</p>";
        enviarCorreoOutlook([$infoSolicitud['CorreoSolicitante']], $asuntoSolicitante, $mensajeSolicitante, $logoUrl);
    }
}

// --- FUNCIÓN DE CORREO DE RECHAZO FINAL (SIN CAMBIOS, PERO REVISADA) ---
function enviarCorreoRechazoFinal($infoSolicitud, $idSolicitud, $comentario, $conex) {
    global $url_sitio;
    $asunto = "Solicitud Rechazada: " . $infoSolicitud['Puesto'];
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

    if (!empty($infoSolicitud['CorreoSolicitante'])) {
        $mensajeSolicitante = "<h2 style='color: #dc3545; margin-top: 0;'>Actualización sobre tu Solicitud</h2><p>Hola,</p><p>Te informamos que tu solicitud para el puesto <strong>\"" . htmlspecialchars($infoSolicitud['Puesto']) . "\"</strong> (ID: $idSolicitud) ha sido rechazada.</p><p><strong>Comentarios del aprobador:</strong></p><blockquote style='border-left: 4px solid #dddddd; padding-left: 15px; margin-left: 0; font-style: italic;'><p>" . nl2br(htmlspecialchars($comentario)) . "</p></blockquote><p>Si tienes dudas, contacta directamente con el gerente que realizó la aprobación.</p>";
        enviarCorreoOutlook([$infoSolicitud['CorreoSolicitante']], $asunto, $mensajeSolicitante, $logoUrl);
    }
}


// --- FUNCIÓN CENTRAL DE ENVÍO (SIN CAMBIOS) ---
function enviarCorreoOutlook(array $destinatarios, $asunto, $cuerpoMensaje, $logoUrl) {
    $destinatarios = array_filter(array_unique($destinatarios)); // Evitar duplicados y vacíos
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
            $mail->addAddress($email);
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
