<?php
// Requerimos los archivos de PHPMailer (ajusta la ruta si es necesario)
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

// Usamos las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN ---
// Asegúrate que esta sea tu URL base correcta
$url_sitio = "https://grammermx.com/AleTest/ATS";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idSolicitud']) && isset($_FILES['documento'])) {
    $idSolicitud = $_POST['idSolicitud'];
    $documento = $_FILES['documento'];

    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    try {
        // 1. Guardar el archivo en el servidor
        $directorioDestino = "../descripciones/";
        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0777, true); // Crea la carpeta si no existe
        }
        $nombreUnico = "desc_" . $idSolicitud . "_" . time() . "_" . preg_replace("/[^a-zA-Z0-9.\-_]/", "", basename($documento['name']));
        $rutaDestino = $directorioDestino . $nombreUnico;

        if (!move_uploaded_file($documento['tmp_name'], $rutaDestino)) {
            throw new Exception("Error al mover el archivo subido.");
        }

        // 2. Crear el registro en tu tabla DescripcionPuesto
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $nombreUnico);
        $stmtDesc->execute();
        $idDescripcion = $conex->insert_id;

        // 3. Actualizar la Solicitud con el IdDescripcion y el nuevo estatus
        $nuevoEstatus = 12; // ID de "Pendiente Aprobación Descripción"
        $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdDescripcion = ?, IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdate->bind_param("iii", $idDescripcion, $nuevoEstatus, $idSolicitud);
        $stmtUpdate->execute();

        // 4. Generar y guardar el token de aprobación
        $token = bin2hex(random_bytes(32));
        $stmtToken = $conex->prepare("INSERT INTO AprobacionDescripcion (IdSolicitud, Token) VALUES (?, ?)");
        $stmtToken->bind_param("is", $idSolicitud, $token);
        $stmtToken->execute();

        // 5. Obtener el correo del solicitante original
        $stmtEmail = $conex->prepare("SELECT u.Correo FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $stmtEmail->bind_param("i", $idSolicitud);
        $stmtEmail->execute();
        $resultEmail = $stmtEmail->get_result();
        if ($resultEmail->num_rows === 0) {
            throw new Exception("No se encontró el correo del solicitante original.");
        }
        $emailSolicitante = $resultEmail->fetch_assoc()['Correo'];

        // 6. Enviar correo de notificación al solicitante
        $linkAprobacion = $url_sitio . "/aprobar_descripcion.php?token=" . $token;
        enviarCorreoAprobacion($emailSolicitante, $linkAprobacion);

        // Si todo salió bien, confirmamos los cambios en la BD
        $conex->commit();
        echo json_encode(['status' => 'success', 'message' => 'Archivo subido. Se ha notificado al solicitante para su aprobación.']);

    } catch (Exception $e) {
        // Si algo falla, revertimos todos los cambios
        $conex->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o método incorrecto.']);
}


// --- FUNCIÓN DE ENVÍO DE CORREO CON PHPMailer ---
function enviarCorreoAprobacion($email, $link) {
    $asunto = "Acción Requerida: Aprobación de Descripción de Puesto";
    $mensaje = "
    <p>Hola,</p>
    <p>El administrador ha subido una descripción de puesto para una de tus solicitudes. Por favor, revísala y apruébala o recházala haciendo clic en el siguiente botón:</p>
    <p style='margin: 25px 0; text-align: center;'>
        <a href='$link' target='_blank' style='background: #005195; color: #FFFFFF; 
        padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; 
        display: inline-block;'>
            Revisar Descripción
        </a>
    </p>
    <p>Si tienes problemas con el botón, copia y pega el siguiente enlace en tu navegador:</p>
    <p style='font-size: 12px; color: #666;'>$link</p>
    <p>Saludos,<br>Sistema ATS - Grammer</p>";

    $contenidoHTML = "
    <html>
    <head><meta charset='UTF-8'><title>$asunto</title></head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f7fc;'>
        <table role='presentation' style='width: 100%; max-width: 600px; margin: 20px auto; background: #FFFFFF; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
            <tr>
                <td style='background-color: #005195; padding: 20px; color: #FFFFFF; text-align: center;'>
                    <h2>ATS Grammer</h2>
                </td>
            </tr>
            <tr>
                <td style='padding: 30px; text-align: left; color: #333333; line-height: 1.6;'>
                    $mensaje
                </td>
            </tr>
            <tr>
                <td style='background-color: #f1f1f1; color: #666666; padding: 15px; text-align: center; font-size: 12px;'>
                    <p>© " . date('Y') . " Grammer Querétaro. Este es un correo automatizado.</p>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $mail = new PHPMailer(true);

    try {
        // --- TU CONFIGURACIÓN DE HOSTINGER ---
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema ATS Grammer');

        // Destinatario
        $mail->addAddress($email);

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        $mail->send();
    } catch (Exception $e) {
        // Si el correo falla, lanzamos una excepción para que la transacción se revierta.
        throw new Exception("El correo no pudo ser enviado. Error de PHPMailer: {$mail->ErrorInfo}");
    }
}
?>
