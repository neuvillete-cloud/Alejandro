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

date_default_timezone_set('America/Mexico_City');

function enviarCorreoNotificacion($emails, $asunto, $mensaje)
{
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

        foreach ($emails as $email) {
            if (!empty($email)) $mail->addAddress($email);
        }

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $idSolicitud = (int)$_POST['id'];
    $nuevoEstado = (int)$_POST['status'];
    $comentario = trim($_POST['comentario'] ?? '');

    $con = new LocalConector();
    $conex = $con->conectar();

    if (!$conex) {
        echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos."]);
        exit;
    }

    // Actualizar estado
    if ($nuevoEstado === 3 && !empty($comentario)) {
        $stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, Comentario = ? WHERE IdSolicitud = ?");
        $stmt->bind_param("isi", $nuevoEstado, $comentario, $idSolicitud);
    } else {
        $stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $stmt->bind_param("ii", $nuevoEstado, $idSolicitud);
    }

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Error al actualizar la solicitud."]);
        exit;
    }

    if ($stmt->affected_rows === 0) {
        echo json_encode(["success" => false, "message" => "No se encontró la solicitud o ya estaba actualizada."]);
        exit;
    }

    // Consultar datos del solicitante
    $consultaDatos = $conex->prepare("SELECT FolioSolicitud, Nombre, Correo FROM Solicitudes WHERE IdSolicitud = ?");
    $consultaDatos->bind_param("i", $idSolicitud);
    $consultaDatos->execute();
    $resultado = $consultaDatos->get_result();

    if ($resultado->num_rows > 0) {
        $solicitud = $resultado->fetch_assoc();
        $folio = $solicitud['FolioSolicitud'];
        $nombreSolicitante = $solicitud['Nombre'];
        $correoSolicitante = $solicitud['Correo'] ?? 'correo_solicitante@tudominio.com';

        if ($nuevoEstado === 5) {
            // Notificar aprobación
            $mensaje = "
                <p>Estimado administrador,</p>
                <p>Una solicitud ha sido aprobada y ahora requiere tu revisión.</p>
                <p>Folio: <strong>$folio</strong></p>
                <p>Nombre del solicitante: <strong>$nombreSolicitante</strong></p>
                <p>
                    <a href='https://grammermx.com/AleTest/ATS/Administrador.php' target='_blank' 
                    style='background: #E6F4F9; color: #005195; padding: 10px 20px; border-radius: 5px; 
                    text-decoration: none; font-weight: bold;'>Ver Solicitud</a>
                </p>
                <p>Saludos,<br>ATS - Grammer</p>";
            enviarCorreoNotificacion(['siguienteadmin@tudominio.com', 'otroadmin@tudominio.com'], "Nueva solicitud pendiente - Folio $folio", $mensaje);
        } elseif ($nuevoEstado === 3) {
            // Notificar rechazo
            $mensaje = "
                <p>Hola <strong>$nombreSolicitante</strong>,</p>
                <p>Tu solicitud con folio <strong>$folio</strong> ha sido rechazada.</p>
                <p>Motivo:</p>
                <blockquote style='color: #d9534f;'><em>$comentario</em></blockquote>
                <p>Para más información, contacta a tu administrador.</p>
                <p>Saludos,<br>ATS - Grammer</p>";
            enviarCorreoNotificacion([$correoSolicitante], "Tu solicitud con folio $folio ha sido rechazada", $mensaje);
        }
    }

    echo json_encode(["success" => true, "message" => "Estado actualizado y notificación enviada."]);
    exit;
} else {
    echo json_encode(["success" => false, "message" => "Datos incompletos o método incorrecto."]);
    exit;
}
