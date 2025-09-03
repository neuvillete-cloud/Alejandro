<?php
// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN ---
$url_sitio = "https://grammermx.com/AleTest/ATS"; // Asegúrate que esta sea tu URL base

// --- FUNCIÓN PARA LIMPIAR EL NOMBRE DEL ARCHIVO ---
function sanitizarNombreArchivo($nombre) {
    // 1. Reemplazar espacios y caracteres no alfanuméricos (excepto punto, guion y guion bajo) con un guion bajo
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    // 2. Eliminar múltiples guiones bajos seguidos para que no queden como "__"
    $nombre = preg_replace('/_+/', '_', $nombre);
    return $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idSolicitud']) && isset($_FILES['documento'])) {
    $idSolicitud = $_POST['idSolicitud'];
    $documento = $_FILES['documento'];

    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    try {
        // Limpiamos el nombre original del archivo antes de usarlo
        $nombreOriginalLimpio = sanitizarNombreArchivo(basename($documento['name']));

        // Creamos el nombre único usando el nombre ya limpio
        $nombreUnico = "desc_" . $idSolicitud . "_" . time() . "_" . $nombreOriginalLimpio;
        $rutaDestino = "../descripciones/" . $nombreUnico;

        // Asegúrate de que la carpeta 'descripciones' exista en la raíz de tu proyecto
        if (!is_dir('../descripciones')) {
            mkdir('../descripciones', 0777, true);
        }

        if (!move_uploaded_file($documento['tmp_name'], $rutaDestino)) {
            throw new Exception("Error al mover el archivo subido.");
        }

        // Guardamos en la BD el mismo nombre único y limpio
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $nombreUnico);
        $stmtDesc->execute();
        $idDescripcion = $conex->insert_id;

        // Actualizamos la Solicitud con el IdDescripcion y el nuevo estatus
        $nuevoEstatus = 12; // "Pendiente Aprobación Descripción"
        $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdDescripcion = ?, IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdate->bind_param("iii", $idDescripcion, $nuevoEstatus, $idSolicitud);
        $stmtUpdate->execute();

        // Generamos y guardamos el token de aprobación
        $token = bin2hex(random_bytes(32));
        $stmtToken = $conex->prepare("INSERT INTO AprobacionDescripcion (IdSolicitud, Token) VALUES (?, ?)");
        $stmtToken->bind_param("is", $idSolicitud, $token);
        $stmtToken->execute();

        // Obtenemos el correo del solicitante para enviar la notificación
        $stmtEmail = $conex->prepare("SELECT u.Correo FROM Solicitudes s JOIN Usuario u ON s.NumNomina = u.NumNomina WHERE s.IdSolicitud = ?");
        $stmtEmail->bind_param("i", $idSolicitud);
        $stmtEmail->execute();
        $resultEmail = $stmtEmail->get_result();
        if ($resultEmail->num_rows === 0) {
            throw new Exception("No se encontró el correo del solicitante.");
        }
        $emailSolicitante = $resultEmail->fetch_assoc()['Correo'];

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
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
}

// --- FUNCIÓN COMPLETA PARA ENVIAR CORREO DE APROBACIÓN CON PHPMailer ---
function enviarCorreoAprobacion($email, $link) {
    $asunto = "Acción Requerida: Aprobación de Descripción de Puesto";
    $mensaje = "
    <html><body>
    <p>Hola,</p>
    <p>El administrador ha subido una descripción de puesto para una de tus solicitudes. Por favor, revísala y apruébala o recházala haciendo clic en el siguiente botón:</p>
    <p style='margin: 25px 0;'>
        <a href='$link' target='_blank' style='background: #005195; color: #FFFFFF; 
        padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; 
        display: inline-block;'>
            Revisar Descripción
        </a>
    </p>
    <p>Si no esperabas este correo, puedes ignorarlo.</p>
    <p>Gracias,<br>Sistema ATS de Grammer</p>
    </body></html>";

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
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema de Aprobaciones ATS');

        $mail->addAddress($email);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;

        $mail->send();
    } catch (Exception $e) {
        // Si el correo falla, no detenemos el proceso, pero podríamos registrar el error.
        // error_log("PHPMailer Error al enviar a $email: " . $mail->ErrorInfo);
        throw new Exception("El archivo se guardó, pero no se pudo enviar el correo de notificación. Error: {$mail->ErrorInfo}");
    }
}
?>