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

function enviarCorreoNotificacion($email1, $email2, $email3, $asunto, $mensaje)
{
    $contenido = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$asunto</title>
        <style>
            body {
                font-family: Arial, sans-serif; 
                background: #f0f4f8; 
                margin: 0; 
                padding: 20px;
            }
            .container {
                background: #ffffff;
                max-width: 600px;
                margin: 0 auto;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .header {
                background-color: #005195;
                color: #ffffff;
                padding: 30px;
                text-align: center;
            }
            .header h2 {
                margin: 0;
                font-size: 26px;
            }
            .content {
                padding: 25px;
                color: #333333;
                font-size: 16px;
                line-height: 1.6;
            }
            .footer {
                background-color: #005195;
                color: #ffffff;
                padding: 15px;
                text-align: center;
                font-size: 13px;
            }
            a.button {
                display: inline-block;
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #007BFF;
                color: #ffffff;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
            }
            blockquote {
                border-left: 4px solid #007BFF;
                padding-left: 10px;
                color: #555;
                margin: 15px 0;
                background-color: #f7f9fb;
                padding: 10px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Notificación de Solicitud</h2>
            </div>
            <div class='content'>
                $mensaje
                <a href='https://tusistema.com' class='button'>Ir al Sistema</a>
            </div>
            <div class='footer'>
                © 2024 Grammer Querétaro — Este correo es informativo y no requiere respuesta.
            </div>
        </div>
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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $idSolicitud = (int)$_POST['id'];
    $nuevoEstado = (int)$_POST['status'];
    $comentario = trim($_POST['comentario'] ?? '');
    $email1 = $_POST['email1'] ?? '';
    $email2 = $_POST['email2'] ?? '';
    $email3 = $_POST['email3'] ?? '';

    $con = new LocalConector();
    $conex = $con->conectar();

    if (!$conex) {
        echo json_encode(["success" => false, "message" => "Error de conexión."]);
        exit;
    }

    if ($nuevoEstado === 3 && !empty($comentario)) {
        $stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, Comentario = ? WHERE IdSolicitud = ?");
        $stmt->bind_param("isi", $nuevoEstado, $comentario, $idSolicitud);
    } else {
        $stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $stmt->bind_param("ii", $nuevoEstado, $idSolicitud);
    }

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Error al actualizar."]);
        exit;
    }

    if ($stmt->affected_rows === 0) {
        echo json_encode(["success" => false, "message" => "No se encontró la solicitud."]);
        exit;
    }

    // Obtener folio para el correo
    $consulta = $conex->prepare("SELECT FolioSolicitud FROM Solicitudes WHERE IdSolicitud = ?");
    $consulta->bind_param("i", $idSolicitud);
    $consulta->execute();
    $res = $consulta->get_result();
    $fila = $res->fetch_assoc();
    $folio = $fila['FolioSolicitud'];

    if ($nuevoEstado === 5) {
        $mensaje = "
        <p>Tu solicitud con folio <strong>$folio</strong> ha sido <strong>aprobada</strong>.</p>
        <p>Revisa en el sistema o contacta con administración si necesitas más información.</p>
        <p>Saludos,<br>ATS - Grammer</p>";
        enviarCorreoNotificacion($email1, $email2, $email3, "Solicitud aprobada: $folio", $mensaje);
    } elseif ($nuevoEstado === 3) {
        $mensaje = "
        <p>Tu solicitud con folio <strong>$folio</strong> ha sido <strong>rechazada</strong>.</p>
        <p>Motivo:</p>
        <blockquote>$comentario</blockquote>
        <p>Para más detalles, acércate a tu administrador.</p>
        <p>Saludos,<br>ATS - Grammer</p>";
        enviarCorreoNotificacion($email1, $email2, $email3, "Solicitud rechazada: $folio", $mensaje);
    }

    echo json_encode(["success" => true, "message" => "Estado actualizado y correo enviado."]);
} else {
    echo json_encode(["success" => false, "message" => "Faltan datos."]);
}
?>


