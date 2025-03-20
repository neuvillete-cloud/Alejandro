<?php
header('Content-Type: application/json');
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('America/Mexico_City');

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

        $mail->addAddress($email1);
        if (!empty($email2)) $mail->addAddress($email2);
        if (!empty($email3)) $mail->addAddress($email3);

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenido;

        $mail->send();
    } catch (Exception $e) {
        error_log("Excepción al enviar correo: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'], $_POST['status'])) {
    $idSolicitud = intval($_POST['id']);
    $nuevoEstado = intval($_POST['status']);
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    $con = new LocalConector();
    $conex = $con->conectar();

    if (!$conex) {
        echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
        exit();
    }

    // Si es rechazo, guardar también el comentario
    if ($nuevoEstado == 3 && !empty($comentario)) {
        $stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, ComentarioRechazo = ? WHERE IdSolicitud = ?");
        $stmt->bind_param("isi", $nuevoEstado, $comentario, $idSolicitud);
    } else {
        $stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $stmt->bind_param("ii", $nuevoEstado, $idSolicitud);
    }

    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        if ($nuevoEstado == 2) {
            // Envío correo cuando es aprobado
            $consultaDatos = $conex->prepare("SELECT FolioSolicitud, Nombre FROM Solicitudes WHERE IdSolicitud = ?");
            $consultaDatos->bind_param("i", $idSolicitud);
            $consultaDatos->execute();
            $resultado = $consultaDatos->get_result();

            if ($resultado->num_rows > 0) {
                $solicitud = $resultado->fetch_assoc();
                $folio = $solicitud['FolioSolicitud'];
                $nombreSolicitante = $solicitud['Nombre'];

                $linkAprobacion = "https://grammermx.com/AleTest/ATS/Administrador.php";

                $asunto = "Nueva solicitud pendiente por aprobar - Folio $folio";
                $mensaje = "
                <p>Estimado administrador,</p>
                <p>Una solicitud ha sido aprobada y ahora requiere tu revisión y aprobación.</p>
                <p>Folio: <strong>$folio</strong></p>
                <p>Nombre del solicitante: <strong>$nombreSolicitante</strong></p>
                <p>Puedes verla y aprobarla desde el siguiente enlace:</p>
                <p>
                    <a href='$linkAprobacion' target='_blank' style='background: #E6F4F9; color: #005195; 
                    padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; 
                    display: inline-block;'>
                        Ver Solicitud
                    </a>
                </p>
                <p>Saludos,<br>ATS - Grammer</p>";

                enviarCorreoNotificacion('siguienteadmin@tudominio.com', 'otroadmin@tudominio.com', '', $asunto, $mensaje);
            }
        } elseif ($nuevoEstado == 3) {
            // Envío correo cuando es rechazo
            $consultaDatos = $conex->prepare("SELECT FolioSolicitud, Nombre FROM Solicitudes WHERE IdSolicitud = ?");
            $consultaDatos->bind_param("i", $idSolicitud);
            $consultaDatos->execute();
            $resultado = $consultaDatos->get_result();

            if ($resultado->num_rows > 0) {
                $solicitud = $resultado->fetch_assoc();
                $folio = $solicitud['FolioSolicitud'];
                $nombreSolicitante = $solicitud['Nombre'];

                $asunto = "Tu solicitud con folio $folio ha sido rechazada";
                $mensaje = "
                <p>Hola <strong>$nombreSolicitante</strong>,</p>
                <p>Lamentamos informarte que tu solicitud con el folio <strong>$folio</strong> ha sido rechazada.</p>
                <p>Motivo del rechazo:</p>
                <blockquote style='color: #d9534f;'><em>$comentario</em></blockquote>
                <p>Si tienes alguna duda, por favor contacta a tu administrador.</p>
                <p>Saludos,<br>ATS - Grammer</p>";

                enviarCorreoNotificacion('correo_solicitante@tudominio.com', '', '', $asunto, $mensaje);
            }
        }

        echo json_encode(["success" => true, "message" => "Estado actualizado y notificación enviada."]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontró la solicitud o el estado ya estaba actualizado"]);
    }

    $stmt->close();
    $conex->close();
} else {
    echo json_encode(["success" => false, "message" => "Datos inválidos o método incorrecto"]);
}
?>
