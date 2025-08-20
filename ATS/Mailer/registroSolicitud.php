<?php
session_start();
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('America/Mexico_City');

// --- Define aquí los datos de los dos aprobadores fijos ---
define('APROBADOR1_NOMINA', '00030315'); // <-- REEMPLAZA con la nómina real del Aprobador 1
define('APROBADOR2_NOMINA', '00030320'); // <-- REEMPLAZA con la nómina real del Aprobador 2
define('APROBADOR1_EMAIL', 'extern.alejandro.torres@grammer.com'); // <-- REEMPLAZA con el email real
define('APROBADOR2_EMAIL', 'extern.alejandro.torres@grammer.com'); // <-- REEMPLAZA con el email real

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
        $IdEstatus = 1; // 1 = Pendiente (estado inicial)

        $con = new LocalConector();
        $conex = $con->conectar();

        $consultaArea = $conex->prepare("SELECT IdArea FROM Area WHERE NombreArea = ?");
        $consultaArea->bind_param("s", $NombreArea);
        $consultaArea->execute();
        $resultadoArea = $consultaArea->get_result();

        if ($resultadoArea->num_rows > 0) {
            $row = $resultadoArea->fetch_assoc();
            $IdArea = $row['IdArea'];

            // Llamamos a la función actualizada, pasando los aprobadores
            $response = registrarSolicitudEnDB(
                $conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion,
                $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud,
                $IdEstatus, 1, APROBADOR1_NOMINA, APROBADOR2_NOMINA
            );

            // Si la solicitud se registró exitosamente, enviamos correo a los aprobadores
            if ($response['status'] == 'success') {
                $linkAprobacion = "https://grammermx.com/AleTest/ATS/AdministradorIng.php";

                $asunto = "Nueva solicitud registrada - Folio $FolioSolicitud";
                $mensaje = "
                <p>Estimados aprobadores,</p>
                <p>Se ha generado una nueva solicitud con el folio <strong>$FolioSolicitud</strong> que requiere su atención.</p>
                <p><strong>Solicitante:</strong> $Nombre</p>
                <p><strong>Área:</strong> $NombreArea</p>
                <p><strong>Puesto:</strong> $Puesto</p>
                <p>Por favor, ingrese al sistema para revisar y tomar una decisión.</p>
                <p>
                    <a href='$linkAprobacion' target='_blank' style='background: #E6F4F9; color: #005195; 
                    padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; 
                    display: inline-block;'>
                        Ir al Sistema de Aprobación
                    </a>
                </p>
                <p>Saludos,<br>ATS - Grammer</p>";

                // Enviamos el correo a los emails de los aprobadores definidos arriba
                enviarCorreoNotificacion(APROBADOR1_EMAIL, APROBADOR2_EMAIL, '', $asunto, $mensaje);
            }

        } else {
            $response = ['status' => 'error', 'message' => 'El área proporcionada no existe.'];
        }

        $conex->close();
    } else {
        $response = ['status' => 'error', 'message' => 'Datos incompletos.'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Se requiere método POST.'];
}

echo json_encode($response);
exit();

// Función actualizada para aceptar y guardar los aprobadores
function registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus, $IdDescripcion, $idAprobador1, $idAprobador2)
{
    $query = "INSERT INTO Solicitudes (
                NumNomina, IdArea, Puesto, TipoContratacion, Nombre, NombreReemplazo, 
                FechaSolicitud, FolioSolicitud, IdEstatus, IdDescripcion, 
                IdAprobador1, IdAprobador2
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $insertSolicitud = $conex->prepare($query);
    // Agregamos dos 's' (string) para los nuevos números de nómina de los aprobadores
    $insertSolicitud->bind_param("sissssssiiss",
        $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo,
        $FechaSolicitud, $FolioSolicitud, $IdEstatus, $IdDescripcion,
        $idAprobador1, $idAprobador2
    );

    $resultado = $insertSolicitud->execute();

    if ($resultado) {
        return ['status' => 'success', 'message' => 'Solicitud registrada exitosamente', 'folio' => $FolioSolicitud];
    } else {
        return ['status' => 'error', 'message' => 'Error al registrar la solicitud: ' . $conex->error];
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

        if (!empty($email1)) $mail->addAddress($email1);
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