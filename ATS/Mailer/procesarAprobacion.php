<?php
// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once("ConexionBD.php");
header('Content-Type: application/json');

if (empty($_POST['token']) || empty($_POST['accion'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos esenciales.']);
    exit;
}

$token = $_POST['token'];
$accion = $_POST['accion'];

$con = new LocalConector();
$conex = $con->conectar();

// --- 1. Validar el token y obtener datos importantes ---
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
    // --- 2. Lógica según la acción ---
    if ($accion === 'aprobar') {
        // El solicitante APROBÓ la descripción
        $nuevoEstatusSolicitud = 13; // "Descripción Aprobada"

        $stmtUpdateSol = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
        $stmtUpdateSol->bind_param("ii", $nuevoEstatusSolicitud, $idSolicitud);
        $stmtUpdateSol->execute();

        $stmtUpdateAprob = $conex->prepare("UPDATE AprobacionDescripcion SET Estatus = 'aprobado', TokenValido = 0 WHERE Id = ?");
        $stmtUpdateAprob->bind_param("i", $idAprobacion);
        $stmtUpdateAprob->execute();

    } elseif ($accion === 'rechazar') {
        // El solicitante RECHAZÓ la descripción
        $comentarios = $_POST['comentarios'];
        $archivoCorrecto = $_FILES['archivoCorrecto'];

        // Guardar el nuevo archivo subido por el solicitante
        $nombreUnico = "corr_" . $idSolicitud . "_" . time() . "_" . basename($archivoCorrecto['name']);
        $rutaDestino = "../descripciones/" . $nombreUnico;
        if (!move_uploaded_file($archivoCorrecto['tmp_name'], $rutaDestino)) {
            throw new Exception("Error al subir el archivo corregido.");
        }

        // Creamos un nuevo registro para la descripción corregida
        $stmtDesc = $conex->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
        $stmtDesc->bind_param("s", $nombreUnico);
        $stmtDesc->execute();
        $idNuevaDescripcion = $conex->insert_id;

        // Revertimos el estatus de la solicitud para que el admin pueda actuar de nuevo
        $estatusRevertido = 2; // "Aprobada" (para que vuelva a aparecer en la lista del admin)
        $stmtUpdateSol = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ?, IdDescripcion = ? WHERE IdSolicitud = ?");
        $stmtUpdateSol->bind_param("iii", $estatusRevertido, $idNuevaDescripcion, $idSolicitud);
        $stmtUpdateSol->execute();

        // Actualizamos el registro de aprobación
        $stmtUpdateAprob = $conex->prepare("UPDATE AprobacionDescripcion SET Estatus = 'rechazado', TokenValido = 0 WHERE Id = ?");
        $stmtUpdateAprob->bind_param("i", $idAprobacion);
        $stmtUpdateAprob->execute();

        // Notificar al administrador
        enviarCorreoRechazo($idSolicitud, $comentarios, $rutaDestino, $conex);
    }

    $conex->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conex->close();


// --- Función para notificar al administrador sobre el rechazo ---
function enviarCorreoRechazo($idSolicitud, $comentarios, $rutaArchivoAdjunto, $conex) {
    // Obtenemos el correo del primer administrador que encontremos
    // Podrías hacer esta lógica más específica si tienes varios admins
    $resultAdmin = $conex->query("SELECT Correo FROM Usuario WHERE IdRol = 1 LIMIT 1");
    if($resultAdmin->num_rows > 0) {
        $emailAdmin = $resultAdmin->fetch_assoc()['Correo'];
    } else {
        return; // No hay admin a quien notificar
    }

    $asunto = "Acción Requerida: Descripción de Puesto Rechazada (Solicitud ID: $idSolicitud)";
    $mensaje = "
    <p>Hola Administrador,</p>
    <p>El solicitante ha rechazado la descripción de puesto para la solicitud con ID <strong>$idSolicitud</strong>.</p>
    <p><strong>Comentarios del solicitante:</strong></p>
    <blockquote style='border-left: 4px solid #ccc; padding-left: 15px; margin-left: 0;'>
        <p><em>" . nl2br(htmlspecialchars($comentarios)) . "</em></p>
    </blockquote>
    <p>Se ha adjuntado la versión corregida del documento a este correo.</p>
    <p>Por favor, revisa el archivo adjunto y vuelve a subir la descripción desde el panel de 'Solicitudes Aprobadas'.</p>
    ";

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
        $mail->addAddress($emailAdmin);
        $mail->addAttachment($rutaArchivoAdjunto); // ¡Adjuntamos el archivo!

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje; // Usamos un mensaje simple, puedes crear una plantilla HTML si quieres

        $mail->send();
    } catch (Exception $e) {
        // No detenemos el proceso si el correo falla, pero podríamos registrar el error
    }
}

?>
