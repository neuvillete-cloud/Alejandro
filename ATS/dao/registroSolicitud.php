<?php
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nombre'], $_POST['area'], $_POST['puesto'], $_POST['tipo'])) {
        $NumNomina = $_SESSION['NumNomina'] ?? null;
        $Nombre = $_POST['nombre'];
        $NombreArea = $_POST['area'];
        $Puesto = $_POST['puesto'];
        $TipoContratacion = $_POST['tipo'];
        $NombreReemplazo = ($TipoContratacion == 'reemplazo' && isset($_POST['reemplazoNombre'])) ? $_POST['reemplazoNombre'] : "";

        if (!$NumNomina) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el número de nómina en la sesión.']);
            exit();
        }

        $FechaSolicitud = date('Y-m-d H:i:s');
        $FolioSolicitud = uniqid('FOLIO-');
        $IdEstatus = 1;

        $con = new LocalConector();
        $conex = $con->conectar();

        $consultaArea = $conex->prepare("SELECT IdArea FROM Area WHERE NombreArea = ?");
        $consultaArea->bind_param("s", $NombreArea);
        $consultaArea->execute();
        $resultadoArea = $consultaArea->get_result();

        if ($resultadoArea->num_rows > 0) {
            $row = $resultadoArea->fetch_assoc();
            $IdArea = $row['IdArea'];

            $response = registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus, 1);

            // Si la solicitud se registró exitosamente, enviamos correo
            if ($response['status'] == 'success') {
                $linkAprobacion = "https://grammermx.com/AleTest/ATS/aprobar_solicitud.php";

                $asunto = "Nueva solicitud registrada - Folio $FolioSolicitud";
                $mensaje = "
                <p>Estimado usuario,</p>
                <p>Se ha generado una nueva solicitud con el folio <strong>$FolioSolicitud</strong>.</p>
                <p>Nombre del solicitante: <strong>$Nombre</strong></p>
                <p>Área: <strong>$NombreArea</strong></p>
                <p>Puesto: <strong>$Puesto</strong></p>
                <p>Tipo de contratación: <strong>$TipoContratacion</strong></p>
                <p>Fecha de solicitud: <strong>$FechaSolicitud</strong></p>
                <p>Puedes aprobar o rechazar la solicitud en el siguiente enlace:</p>
                <p>
                    <a href='$linkAprobacion' target='_blank' style='background: #E6F4F9; color: #005195; 
                    padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; 
                    display: inline-block;'>
                        Ver Solicitud
                    </a>
                </p>
                <p>Saludos,<br>ATS - Grammer</p>";


                // Aquí defines a quién quieres enviarlo (puedes cambiarlo por variables si quieres hacerlo dinámico)
                enviarCorreoNotificacion('destinatario1@tucorreo.com', 'destinatario2@tucorreo.com', '', $asunto, $mensaje);
            }

        } else {
            $response = array('status' => 'error', 'message' => 'El área proporcionada no existe.');
        }

        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Se requiere método POST.');
}

echo json_encode($response);
exit();

function registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus, $IdDescripcion)
{
    $insertSolicitud = $conex->prepare("INSERT INTO Solicitudes (NumNomina, IdArea, Puesto, TipoContratacion, Nombre, NombreReemplazo, FechaSolicitud, FolioSolicitud, IdEstatus, IdDescripcion)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertSolicitud->bind_param("sissssssii", $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus, $IdDescripcion);
    $resultado = $insertSolicitud->execute();

    if ($resultado) {
        return array('status' => 'success', 'message' => 'Solicitud registrada exitosamente', 'folio' => $FolioSolicitud);
    } else {
        return array('status' => 'error', 'message' => 'Error al registrar la solicitud');
    }
}

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

        if (!$mail->send()) {
            error_log("Error al enviar correo: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        error_log("Excepción al enviar correo: " . $e->getMessage());
    }
}
?>
