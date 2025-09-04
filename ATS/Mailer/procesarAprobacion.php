<?php
// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN Y FUNCIÓN AUXILIAR ---
$url_sitio = "https://grammermx.com/AleTest/ATS";

function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}
// --- FIN CONFIGURACIÓN ---

if (empty($_POST['token']) || empty($_POST['accion'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos esenciales.']);
    exit;
}

$token = $_POST['token'];
$accion = $_POST['accion'];

$con = new LocalConector();
$conex = $con->conectar();

$stmt = $conex->prepare("SELECT Id, IdSolicitud FROM AprobacionDescripcion WHERE Token = ? AND TokenValido = 1 AND Estatus = 'pendiente'");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Token inválido o ya utilizado.']);
    $conex->close();
    exit;
}

$aprobacion = $result->fetch_assoc();
$idSolicitud = $aprobacion['IdSolicitud'];
$idAprobacion = $aprobacion['Id'];

$conex->begin_transaction();
try {
    if ($accion === 'aprobar') {
        $nuevoEstatusSolicitud = 13; // "Descripción Aprobada"

        $stmtUpdateSol = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdateSol->bind_param("ii", $nuevoEstatusSolicitud, $idSolicitud);
        $stmtUpdateSol->execute();

        $stmtUpdateAprob = $conex->prepare("UPDATE AprobacionDescripcion SET Estatus = 'aprobado', TokenValido = 0 WHERE Id = ?");
        $stmtUpdateAprob->bind_param("i", $idAprobacion);
        $stmtUpdateAprob->execute();

    } elseif ($accion === 'rechazar') {
        if (empty($_POST['comentarios']) || !isset($_FILES['archivoCorrecto']) || $_FILES['archivoCorrecto']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Faltan comentarios o el archivo corregido no se subió correctamente.");
        }
        $comentarios = $_POST['comentarios'];
        $archivoCorrecto = $_FILES['archivoCorrecto'];

        // --- INICIO DE LA CORRECCIÓN DE RUTA ---
        $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . '/AleTest/ATS/descripciones/';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0775, true);
        }
        if (!is_writable($directorioDestino)) {
            throw new Exception("Error de Permisos: El servidor no puede escribir en la carpeta 'descripciones'.");
        }
        $nombreOriginalLimpio = sanitizarNombreArchivo(basename($archivoCorrecto['name']));
        $nombreUnico = "corr_" . $idSolicitud . "_" . time() . "_" . $nombreOriginalLimpio;
        $rutaFisicaDestino = $directorioDestino . $nombreUnico;

        if (!move_uploaded_file($archivoCorrecto['tmp_name'], $rutaFisicaDestino)) {
            throw new Exception("Error al mover el archivo corregido.");
        }
        $rutaPublica = $url_sitio . "/descripciones/" . $nombreUnico;
        // --- FIN DE LA CORRECCIÓN DE RUTA ---

        // Creamos un nuevo registro guardando la RUTA PÚBLICA
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $rutaPublica);
        $stmtDesc->execute();
        $idNuevaDescripcion = $conex->insert_id;

        // Revertimos el estatus de la solicitud
        $estatusRevertido = 2; // "Aprobada"
        $stmtUpdateSol = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, IdDescripcion = ? WHERE IdSolicitud = ?");
        $stmtUpdateSol->bind_param("iii", $estatusRevertido, $idNuevaDescripcion, $idSolicitud);
        $stmtUpdateSol->execute();

        // Actualizamos el registro de aprobación
        $stmtUpdateAprob = $conex->prepare("UPDATE AprobacionDescripcion SET Estatus = 'rechazado', TokenValido = 0 WHERE Id = ?");
        $stmtUpdateAprob->bind_param("i", $idAprobacion);
        $stmtUpdateAprob->execute();

        // Notificar a los administradores, pasando la RUTA FÍSICA para el adjunto
        enviarCorreoRechazo($idSolicitud, $comentarios, $rutaFisicaDestino, $conex);
    }

    $conex->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conex->close();


// --- FUNCIÓN PARA NOTIFICAR A TODOS LOS ADMINISTRADORES ---
function enviarCorreoRechazo($idSolicitud, $comentarios, $rutaArchivoAdjunto, $conex) {
    $resultAdmin = $conex->query("SELECT Correo FROM Usuario WHERE IdRol = 1");

    if ($resultAdmin->num_rows === 0) {
        return;
    }

    $asunto = "Acción Requerida: Descripción de Puesto Rechazada (Solicitud ID: $idSolicitud)";
    $mensaje = "
    <html><body>
    <p>Hola Administrador,</p>
    <p>El solicitante ha rechazado la descripción de puesto para la solicitud con ID <strong>$idSolicitud</strong>.</p>
    <p><strong>Comentarios del solicitante:</strong></p>
    <blockquote style='border-left: 4px solid #ccc; padding-left: 15px; margin-left: 0; font-style: italic;'>
        <p>" . nl2br(htmlspecialchars($comentarios)) . "</p>
    </blockquote>
    <p>Se ha adjuntado la versión corregida del documento a este correo.</p>
    <p>Por favor, revisa el archivo adjunto y vuelve a subir la descripción desde el panel de 'Solicitudes Aprobadas'. La solicitud ha sido reasignada a este panel.</p>
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

        while ($admin = $resultAdmin->fetch_assoc()) {
            $mail->addAddress($admin['Correo']);
        }

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->addAttachment($rutaArchivoAdjunto);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje;

        $mail->send();
    } catch (Exception $e) {
        // No detenemos el proceso si el correo falla
    }
}
?>