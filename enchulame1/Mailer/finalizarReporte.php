<?php
session_start();
include_once("conexion.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['id']) && isset($_POST['comentarioFinal']) && isset($_FILES['fotoEvidencia'])) {
    $reporteId = $_POST['id'];
    $comentarioFinal = $_POST['comentarioFinal'];
    $nuevoEstatus = 3; // Suponiendo que el ID 3 es "Finalizado" en la tabla de estatus
    $fechaFinalizado = date("Y-m-d H:i:s");

    // Verificar que la imagen se haya subido correctamente
    if (isset($_FILES['fotoEvidencia']) && $_FILES['fotoEvidencia']['error'] === UPLOAD_ERR_OK) {
        $fotoEvidencia = $_FILES['fotoEvidencia'];

        // Verificar el tipo y tamaño del archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fotoEvidencia['type'], $allowedTypes)) {
            $response = array('status' => 'error', 'message' => 'El archivo debe ser una imagen JPEG, PNG o GIF');
            echo json_encode($response);
            exit;
        }

        if ($fotoEvidencia['size'] > 5000000) { // Limitar el tamaño a 5MB
            $response = array('status' => 'error', 'message' => 'El archivo excede el tamaño máximo permitido (5MB)');
            echo json_encode($response);
            exit;
        }

        // Generar un nombre único para la imagen usando el ID del reporte y fecha y hora de registro
        $extension = pathinfo($fotoEvidencia['name'], PATHINFO_EXTENSION);
        $nombreUnico = "reporte_" . $reporteId . "_" . date("Ymd_His") . "." . $extension;

        // Definir la ruta de guardado
        $directorio = "../imagenes/fotosAdministrador/";
        $rutaArchivo = $directorio . $nombreUnico;

        // Mover el archivo a la carpeta de destino
        if (!move_uploaded_file($fotoEvidencia['tmp_name'], $rutaArchivo)) {
            $response = array('status' => 'error', 'message' => 'Error al subir la foto de evidencia.');
            echo json_encode($response);
            exit;
        }
    } else {
        $rutaArchivo = null; // Si no hay foto, no la añadimos
    }

    // Conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Actualizar el estatus, comentario final, foto de evidencia y fecha de finalización del reporte
    $stmt = $conex->prepare("UPDATE Reportes SET IdEstatus = ?, ComentariosFinales = ?, FotoEvidencia = ?, FechaFinalizado = ? WHERE IdReporte = ?");
    $stmt->bind_param('isssi', $nuevoEstatus, $comentarioFinal, $rutaArchivo, $fechaFinalizado, $reporteId);

    if ($stmt->execute()) {
        // Obtener el correo del usuario que reportó el problema
        $stmt_user = $conex->prepare("
            SELECT Correo 
            FROM Usuario 
            INNER JOIN Reportes ON Usuario.NumNomina = Reportes.NumNomina 
            WHERE Reportes.IdReporte = ?
        ");
        $stmt_user->bind_param('i', $reporteId);
        $stmt_user->execute();
        $result = $stmt_user->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $emailUsuario = $user['Correo'];

            // Enviar correo de notificación al usuario con la imagen de evidencia adjunta
            $asunto = "Reporte Finalizado";
            $mensaje = "Hola,<br><br>Te informamos que el estatus de tu reporte #$reporteId ha cambiado a 'Finalizado'.<br><br><strong>Comentario final:</strong> $comentarioFinal<br><br>Adjunto encontrarás una imagen de evidencia.<br><br>Gracias por tu paciencia y por confiar en nuestro equipo.<br><br>Saludos,<br>Equipo de Soporte";
            $correoResponse = enviarCorreoNotificacionEstatus($emailUsuario, $asunto, $mensaje, $rutaArchivo);

            if ($correoResponse['status'] === 'success') {
                $response = array('status' => 'success', 'message' => 'Estatus actualizado, correo enviado y detalles finales registrados.');
            } else {
                $response = array('status' => 'error', 'message' => 'Estatus actualizado y detalles registrados, pero no se pudo enviar el correo. Error: ' . $correoResponse['message']);
            }
        } else {
            $response = array('status' => 'error', 'message' => 'No se encontró el correo del usuario.');
        }
    } else {
        $response = array('status' => 'error', 'message' => 'No se pudo actualizar el estatus ni los detalles del reporte.');
    }

    $stmt->close();
    $stmt_user->close();
    $conex->close();
} else {
    $response = array('status' => 'error', 'message' => 'Datos incompletos para finalizar el reporte.');
}

echo json_encode($response);

// Función para enviar el correo de notificación de cambio de estatus con imagen adjunta
function enviarCorreoNotificacionEstatus($destinatario, $asunto, $mensaje, $rutaArchivo) {
    $contenido = "
    <html>
    <head>
        <title>$asunto</title>
    </head>
    <body style='font-family: Arial, sans-serif; text-align: center; background-color: #f6f6f6;'>
        <div style='background-color: #005195; padding: 20px; color: #ffffff;'>
            <h2>Notificación de Estatus de Reporte</h2>
        </div>
        <div style='padding: 20px;'>
            <p>$mensaje</p>
            <br>
            <p>Si tienes alguna pregunta, no dudes en contactar con soporte.</p>
        </div>
        <footer style='background-color: #f6f6f6; padding: 10px;'>
            <p>© Grammer Querétaro.</p>
        </footer>
    </body>
    </html>";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets_enchulamelanave@grammermx.com';
        $mail->Password = 'ECHGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('tickets_enchulamelanave@grammermx.com', 'Administración Enchulame la Nave');
        $mail->addAddress($destinatario);
        $mail->addBCC('tickets_enchulamelanave@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $contenido;

        // Adjuntar la imagen de evidencia
        if (file_exists($rutaArchivo)) {
            $mail->addAttachment($rutaArchivo);
        }

        if (!$mail->send()) {
            return array('status' => 'error', 'message' => 'Error al enviar el correo electrónico: ' . $mail->ErrorInfo);
        } else {
            return array('status' => 'success', 'message' => 'Correo enviado exitosamente.');
        }
    } catch (Exception $e) {
        return array('status' => 'error', 'message' => 'Error: ' . $e->getMessage());
    }
}
?>


