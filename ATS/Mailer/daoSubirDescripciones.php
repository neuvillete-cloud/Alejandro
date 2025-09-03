<?php
// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

// Usamos las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN ---
// URL pública base (como en tu ejemplo que funciona)
$url_sitio = "https://grammermx.com/AleTest/ATS/descripcion/";

// --- FUNCIÓN PARA LIMPIAR EL NOMBRE DEL ARCHIVO ---
function sanitizarNombreArchivo($nombre) {
    // Reemplaza espacios y caracteres especiales con guiones bajos, excepto puntos, guiones y guiones bajos.
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    // Elimina múltiples guiones bajos seguidos
    return preg_replace('/_+/', '_', $nombre);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idSolicitud']) && isset($_FILES['documento'])) {
    $idSolicitud = $_POST['idSolicitud'];
    $documento = $_FILES['documento'];

    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    try {
        // 1. Limpiar el nombre del archivo
        $nombreOriginalLimpio = sanitizarNombreArchivo(basename($documento['name']));
        $nombreUnico = "desc_" . $idSolicitud . "_" . time() . "_" . $nombreOriginalLimpio;

        // 2. Definir la RUTA LOCAL RELATIVA para guardar en el servidor
        $rutaLocal = "../descripciones/" . $nombreUnico;

        // 3. Definir la RUTA PÚBLICA COMPLETA para guardar en la base de datos
        $rutaPublica = $url_sitio . "/descripciones/" . $nombreUnico;

        // Asegurarse de que la carpeta de destino exista
        if (!is_dir('../descripciones')) {
            mkdir('../descripciones', 0777, true);
        }

        // 4. Mover el archivo al servidor usando la RUTA LOCAL
        if (!move_uploaded_file($documento['tmp_name'], $rutaLocal)) {
            throw new Exception("Error al mover el archivo. Revisa los permisos de la carpeta 'descripciones'.");
        }

        // 5. Guardar la RUTA PÚBLICA en la tabla DescripcionPuesto
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $rutaPublica);
        $stmtDesc->execute();
        $idDescripcion = $conex->insert_id;

        // 6. Actualizar la Solicitud con el IdDescripcion y el nuevo estatus
        $nuevoEstatus = 12; // "Pendiente Aprobación Descripción"
        $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdDescripcion = ?, IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdate->bind_param("iii", $idDescripcion, $nuevoEstatus, $idSolicitud);
        $stmtUpdate->execute();

        // 7. Generar y guardar el token de aprobación
        $token = bin2hex(random_bytes(32));
        $stmtToken = $conex->prepare("INSERT INTO AprobacionDescripcion (IdSolicitud, Token) VALUES (?, ?)");
        $stmtToken->bind_param("is", $idSolicitud, $token);
        $stmtToken->execute();

        // 8. Obtener el correo del solicitante
        $stmtEmail = $conex->prepare("SELECT u.Correo FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $stmtEmail->bind_param("i", $idSolicitud);
        $stmtEmail->execute();
        $resultEmail = $stmtEmail->get_result();
        if ($resultEmail->num_rows === 0) {
            throw new Exception("No se encontró el correo del solicitante original.");
        }
        $emailSolicitante = $resultEmail->fetch_assoc()['Correo'];

        // 9. Enviar correo de notificación
        $linkAprobacion = $url_sitio . "/aprobar_descripcion.php?token=" . $token;
        enviarCorreoAprobacion($emailSolicitante, $linkAprobacion);

        $conex->commit();
        echo json_encode(['status' => 'success', 'message' => 'Archivo subido. Se ha notificado al solicitante para su aprobación.']);

    } catch (Exception $e) {
        $conex->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o método incorrecto.']);
}


// --- FUNCIÓN DE ENVÍO DE CORREO CON PHPMailer (Tu estructura original) ---
function enviarCorreoAprobacion($email, $link) {
    $asunto = "Acción Requerida: Aprobación de Descripción de Puesto";
    // El mensaje simple que se insertará en tu plantilla HTML
    $mensaje = "
    <p>Estimado Aprobador,</p>
    <p>El administrador ha subido una descripción de puesto para una de tus solicitudes que requiere tu decisión.</p>
    <p>Por favor, ingrese al siguiente enlace para revisarla:</p>
    <p>
        <a href='$link' target='_blank' style='background: #005195; color: #FFFFFF; 
        padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; 
        display: inline-block;'>
            Revisar Descripción
        </a>
    </p>
    <p>Saludos,<br>ATS - Grammer</p>";

    // Tu plantilla HTML para el correo
    $contenidoHTML = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$asunto</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #87CEEB, #B0E0E6); color: #FFFFFF; text-align: center;'>
        <table role='presentation' style='width: 100%; max-width: 600px; margin: auto; background: #FFFFFF; border-radius: 10px; overflow: hidden;'>
            <tr>
                <td style='background-color: #005195; padding: 20px; color: #FFFFFF; text-align: center;'>
                    <h2>Notificación de Aprobación</h2>
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
        // --- TU CONFIGURACIÓN DE HOSTINGER ---
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Administración ATS Grammer');

        // Destinatario
        $mail->addAddress($email);

        // Tus copias ocultas
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        if (!$mail->send()) {
            throw new Exception("Error al enviar correo: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        // Si el correo falla, la transacción se revertirá gracias al 'throw'
        throw new Exception("El archivo se guardó, pero el correo no pudo ser enviado. Error: {$e->getMessage()}");
    }
}
?>