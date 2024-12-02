<?php
// Incluimos los archivos necesarios
include_once('conexion.php');
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['id']) && isset($_POST['comentarioFinal'])) {
    $reporteId = $_POST['id'];
    $comentarioFinal = $_POST['comentarioFinal'];
    $nuevoEstatus = 3; // ID para "Finalizado"
    $fechaFinalizado = date("Y-m-d H:i:s");

    // Manejo de la imagen subida
    $rutaArchivo = null;
    $baseUrl = "https://grammermx.com/AleTest/enchulame1/imagenes/fotosAdministrador/";
    if (isset($_FILES['fotoEvidencia']) && $_FILES['fotoEvidencia']['error'] === UPLOAD_ERR_OK) {
        $fotoEvidencia = $_FILES['fotoEvidencia'];

        // Validar tipo y tamaño del archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fotoEvidencia['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Archivo no válido. Debe ser JPEG, PNG o GIF.']);
            exit;
        }

        if ($fotoEvidencia['size'] > 5000000) {
            echo json_encode(['status' => 'error', 'message' => 'El archivo excede el tamaño máximo de 5MB.']);
            exit;
        }

        // Generar nombre único para la imagen
        $extension = pathinfo($fotoEvidencia['name'], PATHINFO_EXTENSION);
        $nombreUnico = "reporte_" . $reporteId . "_" . date("Ymd_His") . "." . $extension;
        $rutaLocal = "../AleTest/enchulame1/imagenes/fotosAdministrador/" . $nombreUnico;
        $rutaPublica = $baseUrl . $nombreUnico;

        // Mover archivo a la carpeta destino
        if (!move_uploaded_file($fotoEvidencia['tmp_name'], $rutaLocal)) {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la imagen.']);
            exit;
        }
    }

    // Conectar a la base de datos y actualizar el reporte
    $con = new LocalConector();
    $conex = $con->conectar();
    $stmt = $conex->prepare("UPDATE Reportes SET IdEstatus = ?, ComentariosFinales = ?, FotoEvidencia = ?, FechaFinalizado = ? WHERE IdReporte = ?");
    $stmt->bind_param('isssi', $nuevoEstatus, $comentarioFinal, $rutaPublica, $fechaFinalizado, $reporteId);

    if ($stmt->execute()) {
        // Obtener el correo del usuario
        $stmt_user = $conex->prepare("SELECT Correo FROM Usuario INNER JOIN Reportes ON Usuario.NumNomina = Reportes.NumNomina WHERE Reportes.IdReporte = ?");
        $stmt_user->bind_param('i', $reporteId);
        $stmt_user->execute();
        $result = $stmt_user->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $emailUsuario = $user['Correo'];

            // Enviar correo con PHPMailer
            $asunto = "Reporte Finalizado";
            $mensaje = "Hola,<br><br>Tu reporte #$reporteId ha sido marcado como 'Finalizado'.<br><br><strong>Comentario final:</strong> $comentarioFinal.<br><br>Adjunto encontrarás la evidencia.<br><br>Saludos,<br>Equipo de Soporte";

            $correoResponse = emailFinalizarReporte($emailUsuario, $asunto, $mensaje, $rutaLocal);

            if ($correoResponse['status'] === 'success') {
                echo json_encode(['status' => 'success', 'message' => 'Reporte finalizado y correo enviado.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Reporte finalizado, pero error al enviar correo: ' . $correoResponse['message']]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el correo del usuario.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el reporte.']);
    }

    $stmt->close();
    $stmt_user->close();
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
}

// Función para enviar el correo de reporte finalizado
function emailFinalizarReporte($destinatario, $asunto, $mensaje, $rutaLocal) {
    $contenido = "
    <html>
    <head>
        <title>$asunto</title>
    </head>
    <body style='font-family: Arial, sans-serif; text-align: center; background-color: #f6f6f6;'>
        <div style='background-color: #005195; padding: 20px; color: #ffffff;'>
            <h2>Reporte Finalizado</h2>
        </div>
        <div style='padding: 20px;'>
            <p>Hola,</p>
            <p>$mensaje</p>
            <br>
            <p>Si tienes alguna duda, por favor contáctanos.</p>
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
        $mail->setFrom('tickets_enchulamelanave@grammermx.com', 'Administración Grammer');
        $mail->addAddress($destinatario);
        $mail->addBCC('tickets_enchulamelanave@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $contenido;

        // Adjuntar la imagen si existe
        if ($rutaLocal && file_exists($rutaLocal)) {
            $mail->addAttachment($rutaLocal);
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
