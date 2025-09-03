<?php
// --- INICIO: MODO DE DEPURACIÓN ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN: MODO DE DEPURACIÓN ---

// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

$url_sitio = "https://grammermx.com/AleTest/ATS";

function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idSolicitud']) && isset($_FILES['documento'])) {
    $idSolicitud = $_POST['idSolicitud'];
    $documento = $_FILES['documento'];

    // --- VERIFICACIÓN INICIAL DEL ARCHIVO ---
    if ($documento['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor (php.ini).',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo especificado en el formulario HTML.',
            UPLOAD_ERR_PARTIAL    => 'El archivo fue solo parcialmente subido.',
            UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'Error del servidor: No se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida del archivo.',
        ];
        $mensajeError = $uploadErrors[$documento['error']] ?? 'Ocurrió un error desconocido durante la subida.';
        echo json_encode(['status' => 'error', 'message' => $mensajeError]);
        exit;
    }

    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    try {
        // --- CONSTRUCCIÓN DE RUTA A PRUEBA DE BALAS ---
        // Usamos la ruta absoluta del servidor, la forma más confiable.
        $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . '/AleTest/ATS/descripciones/';

        // Verificamos si la carpeta existe. Si no, intentamos crearla.
        if (!is_dir($directorioDestino)) {
            if (!mkdir($directorioDestino, 0775, true)) {
                throw new Exception("Error Fatal: No se pudo crear la carpeta de destino 'descripciones'.");
            }
        }

        // Verificamos si tenemos permisos para escribir en la carpeta.
        if (!is_writable($directorioDestino)) {
            throw new Exception("Error de Permisos: El servidor no tiene permiso para escribir en la carpeta 'descripciones'. Por favor, desde tu panel de Hostinger, ajusta los permisos de esta carpeta a 775.");
        }

        // Preparamos el nombre y la ruta final
        $nombreOriginalLimpio = sanitizarNombreArchivo(basename($documento['name']));
        $nombreUnico = "desc_" . $idSolicitud . "_" . time() . "_" . $nombreOriginalLimpio;
        $rutaFisicaDestino = $directorioDestino . $nombreUnico;

        // Movemos el archivo
        if (!move_uploaded_file($documento['tmp_name'], $rutaFisicaDestino)) {
            throw new Exception("Falló la subida del archivo. La función move_uploaded_file no pudo completarse. Verifica la ruta de destino.");
        }

        // Construimos la URL pública para la base de datos
        $rutaPublica = $url_sitio . "/descripciones/" . $nombreUnico;

        // Guardamos la RUTA PÚBLICA en la base de datos
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $rutaPublica);
        $stmtDesc->execute();
        $idDescripcion = $conex->insert_id;

        // Actualizamos la Solicitud con el IdDescripcion y el nuevo estatus
        $nuevoEstatus = 12;
        $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdDescripcion = ?, IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdate->bind_param("iii", $idDescripcion, $nuevoEstatus, $idSolicitud);
        $stmtUpdate->execute();

        // Generamos y guardamos el token de aprobación
        $token = bin2hex(random_bytes(32));
        $stmtToken = $conex->prepare("INSERT INTO AprobacionDescripcion (IdSolicitud, Token) VALUES (?, ?)");
        $stmtToken->bind_param("is", $idSolicitud, $token);
        $stmtToken->execute();

        // Obtenemos el correo del solicitante
        $stmtEmail = $conex->prepare("SELECT u.Correo FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $stmtEmail->bind_param("i", $idSolicitud);
        $stmtEmail->execute();
        $resultEmail = $stmtEmail->get_result();
        if ($resultEmail->num_rows === 0) {
            throw new Exception("No se encontró el correo del solicitante original.");
        }
        $emailSolicitante = $resultEmail->fetch_assoc()['Correo'];

        // Enviamos el correo de notificación
        $linkAprobacion = $url_sitio . "/aprobar_descripcion.php?token=" . $token;
        enviarCorreoAprobacion($emailSolicitante, $linkAprobacion);

        $conex->commit();
        echo json_encode(['status' => 'success', 'message' => 'Archivo subido y solicitante notificado.']);

    } catch (Exception $e) {
        $conex->rollback();
        // Devolvemos el mensaje de error específico que generamos
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $conex->close();
}



// --- FUNCIÓN DE ENVÍO DE CORREO (VERSIÓN COMPATIBLE CON OUTLOOK) ---
function enviarCorreoAprobacion($email, $link) {
    global $url_sitio;

    $asunto = "Acción Requerida: Aprobación de Descripción de Puesto";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

    // Este es el nuevo HTML, construido con tablas para máxima compatibilidad
    $contenidoHTML = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$asunto</title>
    </head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td style='padding: 20px 0;'>
                    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                        <tr>
                            <td align='center' style='background-color: #005195; padding: 30px;'>
                                <img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 40px 30px; color: #333333; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;'>
                                <h2 style='color: #005195; margin-top: 0;'>Revisión Pendiente</h2>
                                <p>Hola,</p>
                                <p>El administrador ha subido una descripción de puesto para una de tus solicitudes y requiere tu revisión para continuar con el proceso.</p>
                                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                    <tr>
                                        <td align='center' style='padding: 20px 0;'>
                                            <table border='0' cellpadding='0' cellspacing='0'>
                                                <tr>
                                                    <td align='center' style='background-color: #0d6efd; border-radius: 8px;'>
                                                        <a href='$link' target='_blank' style='font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; padding: 15px 30px; border-radius: 8px; display: inline-block; font-weight: bold;'>Revisar Descripción</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                <p>Por favor, haz clic en el botón para ver el documento y aprobarlo o rechazarlo.</p>
                                <p>Saludos cordiales,<br><strong>Sistema ATS - Grammer</strong></p>
                            </td>
                        </tr>
                        <tr>
                            <td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d; font-family: Arial, sans-serif;'>
                                <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</p>
                            </td>
                        </tr>
                    </table>
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
        $mail->addAddress($email);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        if (!$mail->send()) {
            throw new Exception("Error al enviar correo: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        throw new Exception("El archivo se guardó, pero el correo no pudo ser enviado. Error: {$e->getMessage()}");
    }
}
?>