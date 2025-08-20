<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Función para construir y enviar correos de notificación.
 */
function enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje)
{
    $contenido = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$asunto</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #87CEEB, #B0E0E6); color: #FFFFFF; text-align: center;'>
        <table role='presentation' style='width: 100%; max-width: 600px; margin: auto; background: #FFFFFF; border-radius: 10px; overflow: hidden;'>
            <tr>
                <td style='background-color: #005195; padding: 20px; color: #FFFFFF; text-align: center;'>
                    <h2>Notificación de Solicitud</h2>
                </td>
            </tr>
            <tr>
                <td style='padding: 20px; text-align: left; color: #333333;'>
                    $mensaje
                </td>
            </tr>
            <tr>
                <td style='background-color: #005195; color: #FFFFFF; padding: 10px; text-align: center;'>
                    <p>© Grammer Querétaro.</p>
                </td>
            </tr>
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
        $mail->Body = $contenido;

        $mail->send();
        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Verificamos que se envíen los datos necesarios desde el frontend
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'], $_POST['status'], $_POST['num_nomina'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos (id, status, num_nomina)."]);
    exit;
}

// Recopilamos los datos enviados
$idSolicitud = (int)$_POST['id'];
$decision = (int)$_POST['status']; // La decisión del aprobador (5 para aprobar, 3 para rechazar)
$numNominaAprobador = trim($_POST['num_nomina']);
$comentario = trim($_POST['comentario'] ?? '');
$email1 = $_POST['email1'] ?? '';
$email2 = $_POST['email2'] ?? '';
$email3 = $_POST['email3'] ?? '';

$con = new LocalConector();
$conex = $con->conectar();
if (!$conex) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la BD."]);
    exit;
}

// Iniciamos una transacción para asegurar la integridad de los datos
$conex->begin_transaction();

try {
    // 1. OBTENER EL ESTADO ACTUAL DE LA SOLICITUD Y QUIÉNES SON LOS APROBADORES
    // Nota: Este código asume que tienes columnas 'IdAprobador1' y 'IdAprobador2' en tu tabla Solicitudes.
    $stmt = $conex->prepare("SELECT IdAprobador1, IdAprobador2, Aprobacion1, Aprobacion2 FROM Solicitudes WHERE IdSolicitud = ? FOR UPDATE");
    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No se encontró la solicitud con ID $idSolicitud.");
    }
    $solicitud = $result->fetch_assoc();
    $idAprobador1 = $solicitud['IdAprobador1'];
    $idAprobador2 = $solicitud['IdAprobador2'];

    // 2. DETERMINAR SI EL USUARIO ES APROBADOR 1 O 2
    $columnaAprobacion = null;
    $otraColumnaAprobacion = null;
    if ($numNominaAprobador == $idAprobador1) {
        $columnaAprobacion = "Aprobacion1";
        $otraColumnaAprobacion = "Aprobacion2";
    } elseif ($numNominaAprobador == $idAprobador2) {
        $columnaAprobacion = "Aprobacion2";
        $otraColumnaAprobacion = "Aprobacion1";
    } else {
        throw new Exception("No tienes permiso para actuar sobre esta solicitud.");
    }

    // 3. LÓGICA DE DECISIÓN
    $nuevoEstadoGeneral = null;
    $valorDecision = ($decision === 3) ? 0 : 1; // 0 para rechazo, 1 para aprobación

    // Actualizamos la decisión del aprobador actual
    $updateStmt = $conex->prepare("UPDATE Solicitudes SET $columnaAprobacion = ? WHERE IdSolicitud = ?");
    $updateStmt->bind_param("ii", $valorDecision, $idSolicitud);
    $updateStmt->execute();

    // Si es un RECHAZO, la solicitud se rechaza inmediatamente
    if ($valorDecision === 0) {
        $nuevoEstadoGeneral = 3; // Estado: Rechazado
        $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, Comentario = ? WHERE IdSolicitud = ?");
        $finalStmt->bind_param("isi", $nuevoEstadoGeneral, $comentario, $idSolicitud);
    }
    // Si es una APROBACIÓN, verificamos el estado del otro aprobador
    else {
        $otraAprobacion = $solicitud[$otraColumnaAprobacion];

        if ($otraAprobacion == 1) { // El otro aprobador ya había aprobado
            $nuevoEstadoGeneral = 5; // Estado: Aprobado (final)
            $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
            $finalStmt->bind_param("ii", $nuevoEstadoGeneral, $idSolicitud);
        } else { // El otro aprobador aún no ha aprobado
            $nuevoEstadoGeneral = 4; // Estado: Aprobado Parcialmente
            $finalStmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
            $finalStmt->bind_param("ii", $nuevoEstadoGeneral, $idSolicitud);
        }
    }
    $finalStmt->execute();

    // Si todo fue bien, guardamos los cambios en la base de datos
    $conex->commit();

    // 4. ENVIAR CORREOS SÓLO EN ESTADOS FINALES (Aprobado o Rechazado)
    if ($nuevoEstadoGeneral === 5 || $nuevoEstadoGeneral === 3) {
        // Obtenemos el folio para incluirlo en el correo
        $folioStmt = $conex->prepare("SELECT FolioSolicitud FROM Solicitudes WHERE IdSolicitud = ?");
        $folioStmt->bind_param("i", $idSolicitud);
        $folioStmt->execute();
        $res = $folioStmt->get_result();
        $fila = $res->fetch_assoc();
        $folio = $fila['FolioSolicitud'];

        if ($nuevoEstadoGeneral === 5) {
            $linkSistema = "https://grammermx.com/AleTest/ATS/Administrador.php";
            $mensaje = "<p>Tu solicitud con folio <strong>$folio</strong> ha sido <strong>aprobada</strong> por ambos responsables.</p><p>Revisa en el sistema o contacta con administración si necesitas más información.</p><p><a href='$linkSistema' target='_blank' style='background: #E6F4F9; color: #005195; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block;'>Ir al sistema</a></p><p>Saludos,<br>ATS - Grammer</p>";
            enviarCorreoNotificacion($email1, $email2, $email3, "Solicitud Aprobada: $folio", $mensaje);
        } elseif ($nuevoEstadoGeneral === 3) {
            $mensaje = "<p>Tu solicitud con folio <strong>$folio</strong> ha sido <strong>rechazada</strong>.</p><p>Motivo:</p><blockquote>$comentario</blockquote><p>Para más detalles, acércate a tu administrador.</p><p>Saludos,<br>ATS - Grammer</p>";
            enviarCorreoNotificacion($email1, $email2, $email3, "Solicitud Rechazada: $folio", $mensaje);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Estado actualizado correctamente.",
        "final_status" => $nuevoEstadoGeneral
    ]);

} catch (Exception $e) {
    // Si algo falla durante el proceso, revertimos todos los cambios
    $conex->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conex->close();
?>