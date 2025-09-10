<?php
require_once "ConexionBD.php";
// Se necesita PHPMailer para enviar la notificación
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');
session_start();

// Validación básica
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Se requiere método POST"]);
    exit;
}

if (!isset($_SESSION['IdCandidato']) || !isset($_GET['idVacante'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$idCandidato = intval($_SESSION['IdCandidato']);
$idVacante = intval($_GET['idVacante']);

// Validación de archivo
if (empty($_FILES['cv'])) {
    echo json_encode(["status" => "error", "message" => "Archivo no enviado"]);
    exit;
}

$archivo = $_FILES['cv'];
$extensionesPermitidas = ['pdf', 'doc', 'docx', 'rtf', 'txt'];
$maximoTamano = 5 * 1024 * 1024; // 5MB

$extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($extension), $extensionesPermitidas)) {
    echo json_encode(["status" => "error", "message" => "Formato de archivo no permitido"]);
    exit;
}

if ($archivo['size'] > $maximoTamano) {
    echo json_encode(["status" => "error", "message" => "El archivo excede el tamaño máximo permitido (5MB)"]);
    exit;
}

// Definir rutas
$baseUrl = "https://grammermx.com/AleTest/ATS/Cv/"; // URL pública
$uploadDir = "../Cv/"; // Carpeta física

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$nombreArchivo = "cv_" . uniqid() . "." . $extension;
$rutaLocal = $uploadDir . $nombreArchivo;
$rutaPublica = $baseUrl . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaLocal)) {
    echo json_encode(["status" => "error", "message" => "Error al subir el archivo"]);
    exit;
}

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$email = $_POST['email'] ?? '';
$ciudad = $_POST['ciudad'] ?? '';
$telefono = $_POST['telefono'] ?? '';

// Conectar y ejecutar transacción
$conn = (new LocalConector())->conectar();
$conn->begin_transaction();

try {
    // 1. Actualizar Candidato
    $sql1 = "UPDATE Candidatos 
             SET Nombre = ?, Apellidos = ?, Correo = ?, Telefono = ?, Ubicacion = ?, CV = ?
             WHERE IdCandidato = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $ciudad, $rutaPublica, $idCandidato);
    $stmt1->execute();

    // 2. Insertar en Postulaciones con IdEstatus = 1
    $sql2 = "INSERT INTO Postulaciones (IdCandidato, IdVacante, FechaPostulacion, IdEstatus) VALUES (?, ?, NOW(), ?)";
    $stmt2 = $conn->prepare($sql2);
    $idEstatus = 1;
    $stmt2->bind_param("iii", $idCandidato, $idVacante, $idEstatus);
    $stmt2->execute();

    // --- INICIO DE LA NUEVA LÓGICA DE NOTIFICACIÓN ---

    // 3. Obtener el nombre de la vacante para el correo
    $stmtVacante = $conn->prepare("SELECT TituloVacante FROM Vacantes WHERE IdVacante = ?");
    $stmtVacante->bind_param("i", $idVacante);
    $stmtVacante->execute();
    $resultVacante = $stmtVacante->get_result();
    $nombreVacante = $resultVacante->fetch_assoc()['TituloVacante'] ?? 'Vacante Desconocida';

    // 4. Obtener los correos de los administradores (Rol 1)
    $stmtAdmins = $conn->prepare("SELECT Correo FROM Usuario WHERE IdRol = 1");
    $stmtAdmins->execute();
    $resultAdmins = $stmtAdmins->get_result();
    $correosAdmins = [];
    while ($admin = $resultAdmins->fetch_assoc()) {
        $correosAdmins[] = $admin['Correo'];
    }

    // 5. Enviar el correo si hay administradores a quienes notificar
    if (!empty($correosAdmins)) {
        $nombreCompletoCandidato = $nombre . ' ' . $apellido;
        enviarCorreoNuevoCandidato($correosAdmins, $nombreCompletoCandidato, $nombreVacante);
    }

    // --- FIN DE LA NUEVA LÓGICA DE NOTIFICACIÓN ---

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Postulación registrada con éxito",
        "url_cv" => $rutaPublica
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
}

$conn->close();


/**
 * NUEVA FUNCIÓN: Envía un correo de notificación a los administradores
 * sobre una nueva postulación de candidato.
 */
function enviarCorreoNuevoCandidato($correosAdmins, $nombreCandidato, $nombreVacante) {
    $asunto = "Nueva Postulación Recibida: " . htmlspecialchars($nombreVacante);
    $url_sitio = "https://grammermx.com/AleTest/ATS";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    // Ajusta este enlace a la página donde los admins revisan las postulaciones
    $linkPostulaciones = $url_sitio . "/Postulaciones.php";

    $cuerpoMensaje = "
        <h2 style='color: #005195; font-family: Arial, sans-serif; font-size: 24px;'>Nueva Postulación Recibida</h2>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Hola Administrador,</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Se ha recibido una nueva postulación de un candidato a través del portal. Los detalles son los siguientes:</p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333; background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0d6efd;'>
            <strong>Candidato:</strong> " . htmlspecialchars($nombreCandidato) . "<br>
            <strong>Vacante a la que aplica:</strong> " . htmlspecialchars($nombreVacante) . "
        </p>
        <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Por favor, accede al panel de postulaciones para revisar el perfil completo del candidato y su CV.</p>
        
        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-top: 25px; margin-bottom: 25px;'>
          <tr><td><table border='0' cellspacing='0' cellpadding='0' align='center'><tr>
          <td align='center' style='border-radius: 8px; background-color: #198754;'>
            <a href='" . $linkPostulaciones . "' target='_blank' style='font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; border-radius: 8px; padding: 14px 28px; border: 1px solid #198754; display: inline-block; font-weight: bold;'>
              Ir al Panel de Postulaciones
            </a>
          </td>
          </tr></table></td></tr>
        </table>
    ";

    $contenidoHTML = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr_><td style='padding: 20px 0;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                <tr><td align='center' style='background-color: #005195; padding: 30px; border-top-left-radius: 12px; border-top-right-radius: 12px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                <tr><td style='padding: 40px 30px;'>
                    $cuerpoMensaje
                    <p style='font-family: Arial, sans-serif; font-size: 16px; color: #333;'>Saludos,<br><strong>Sistema ATS Grammer</strong></p>
                </td></tr>
                <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;'>
                    <p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</p>
                </td></tr>
            </table>
        </td></tr></table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema ATS Grammer');

        foreach($correosAdmins as $correo) {
            if(!empty($correo)) $mail->addAddress($correo);
        }

        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
    } catch (Exception $e) {
        // No se lanza una excepción para no fallar el proceso principal, solo se registra el error
        error_log("Error al enviar correo de nueva postulación al admin: " . $mail->ErrorInfo);
    }
}
?>
