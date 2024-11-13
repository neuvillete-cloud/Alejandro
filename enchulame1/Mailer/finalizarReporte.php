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
    $extension = pathinfo($fotoEvidencia['name'], PATHINFO_EXTENSION);
    $nombreUnico = "reporte_" . $reporteId . "_" . date("Ymd_His") . "." . $extension;
    $rutaArchivo = "../imagenes/fotosAdministrador/" . $nombreUnico;
    move_uploaded_file($fotoEvidencia['tmp_name'], $rutaArchivo);

    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("UPDATE Reportes SET IdEstatus = ?, ComentariosFinales = ?, FotoEvidencia = ?, FechaFinalizado = ? WHERE IdReporte = ?");
    $stmt->bind_param('isssi', $nuevoEstatus, $comentarioFinal, $rutaArchivo, $fechaFinalizado, $reporteId);
    $stmt->execute();

    $stmt_user = $conex->prepare("SELECT Correo FROM Usuario INNER JOIN Reportes ON Usuario.NumNomina = Reportes.NumNomina WHERE Reportes.IdReporte = ?");
    $stmt_user->bind_param('i', $reporteId);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    $user = $result->fetch_assoc();

    $mail = new PHPMailer(true);
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
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
}
?>



