<?php
session_start();
include_once("conexion.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

if (isset($_POST['id']) && isset($_POST['comentarioFinal']) && isset($_FILES['fotoEvidencia'])) {
    $reporteId = $_POST['id'];
    $comentarioFinal = $_POST['comentarioFinal'];
    $nuevoEstatus = 3;
    $fechaFinalizado = date("Y-m-d H:i:s");

    $fotoEvidencia = $_FILES['fotoEvidencia'];
    $errorFoto = $fotoEvidencia['error'];

    // Comprobar si hubo algún error al subir el archivo
    if ($errorFoto !== UPLOAD_ERR_OK) {
        switch ($errorFoto) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $mensajeError = "El archivo es demasiado grande.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $mensajeError = "El archivo se subió parcialmente.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $mensajeError = "No se seleccionó ningún archivo.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $mensajeError = "Falta la carpeta temporal.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $mensajeError = "No se pudo escribir el archivo en el disco.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $mensajeError = "Subida detenida por la extensión.";
                break;
            default:
                $mensajeError = "Error desconocido al subir el archivo.";
                break;
        }
        echo json_encode(['status' => 'error', 'message' => $mensajeError]);
        exit;
    }

    $extension = pathinfo($fotoEvidencia['name'], PATHINFO_EXTENSION);
    $nombreUnico = "reporte_" . $reporteId . "_" . date("Ymd_His") . "." . $extension;
    $rutaArchivo = "../imagenes/fotosAdministrador/" . $nombreUnico;

    // Intentar mover el archivo subido
    if (!move_uploaded_file($fotoEvidencia['tmp_name'], $rutaArchivo)) {
        echo json_encode(['status' => 'error', 'message' => 'Error al mover la foto de evidencia al servidor']);
        exit;
    }

    // Conectar a la base de datos y actualizar el estado
    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("UPDATE Reportes SET IdEstatus = ?, ComentariosFinales = ?, FotoEvidencia = ?, FechaFinalizado = ? WHERE IdReporte = ?");
    $stmt->bind_param('isssi', $nuevoEstatus, $comentarioFinal, $rutaArchivo, $fechaFinalizado, $reporteId);
    $stmt->execute();

    // Enviar correo de notificación
    $stmt_user = $conex->prepare("SELECT Correo FROM Usuario INNER JOIN Reportes ON Usuario.NumNomina = Reportes.NumNomina WHERE Reportes.IdReporte = ?");
    $stmt_user->bind_param('i', $reporteId);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    $user = $result->fetch_assoc();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets_enchulamelanave@grammermx.com';
        $mail->Password = 'ECHGrammer2024.';
        $mail->setFrom('tickets_enchulamelanave@grammermx.com');
        $mail->addAddress($user['Correo']);
        $mail->addAttachment($rutaArchivo);
        $mail->isHTML(true);
        $mail->Subject = 'Reporte Finalizado';
        $mail->Body = "Tu reporte #$reporteId ha sido finalizado. Comentario: $comentarioFinal";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Reporte finalizado y correo enviado']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
}
?>
