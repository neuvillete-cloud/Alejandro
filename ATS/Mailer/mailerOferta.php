<?php
header('Content-Type: application/json');
session_start(); // Es buena práctica tener la sesión iniciada para validar roles si es necesario

// Se cambia la ruta para acceder a la conexión desde la carpeta 'mailer'
include_once("ConexionBD.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verificar que el usuario tenga sesión iniciada (medida de seguridad básica)
if (!isset($_SESSION['NumNomina'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado. Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// Se leen y validan los datos que envía el JavaScript
$idPostulacion = filter_input(INPUT_POST, 'idPostulacion', FILTER_VALIDATE_INT);
$nombreCandidato = trim($_POST['nombreCandidato'] ?? '');
$correoCandidato = filter_input(INPUT_POST, 'correoCandidato', FILTER_VALIDATE_EMAIL);
$vacante = trim($_POST['vacante'] ?? '');
$url_sitio = "https://grammermx.com/AleTest/ATS"; // Asegúrate que esta URL es correcta

if (!$idPostulacion || empty($nombreCandidato) || !$correoCandidato || empty($vacante)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos esenciales para procesar la solicitud.']);
    exit;
}

$conn = (new LocalConector())->conectar();
$conn->begin_transaction(); // Iniciar transacción para asegurar la integridad de los datos

try {
    // --- LÓGICA CRÍTICA: Verificación para evitar el doble envío ---
    $stmt = $conn->prepare("SELECT OfertaEnviada FROM Postulaciones WHERE IdPostulacion = ? FOR UPDATE");
    $stmt->bind_param('i', $idPostulacion);
    $stmt->execute();
    $result = $stmt->get_result();
    $postulacion = $result->fetch_assoc();

    if (!$postulacion) {
        throw new Exception("La postulación no fue encontrada en la base de datos.");
    }
    if ($postulacion['OfertaEnviada'] == 1) {
        // Si ya se envió, se detiene el proceso y se informa al usuario.
        throw new Exception("La oferta para este candidato ya ha sido enviada previamente.");
    }

    // --- Si la verificación pasa, se procede a construir y enviar el correo ---

    $linkBienvenida = $url_sitio . "/bienvenida.php";
    $logoUrl = $url_sitio . '/imagenes/logo_blanco.png';
    $asunto = "¡Felicidades! Un nuevo paso en tu carrera te espera en Grammer Automotive";

    // Contenido principal del mensaje
    $cuerpoMensaje = "
        <h1 style='color: #005195;'>¡Enhorabuena, " . htmlspecialchars($nombreCandidato) . "!</h1>
        <p>El equipo de Adquisición de Talento de <strong>Grammer Automotive</strong> tiene excelentes noticias para ti.</p>
        <p>Nos complace enormemente informarte que, tras un exitoso proceso de selección, has sido seleccionado(a) para el puesto de <strong>" . htmlspecialchars($vacante) . "</strong>.</p>
        <p>Tu perfil, habilidades y entusiasmo nos han convencido de que eres la persona ideal para unirte a nuestro equipo y contribuir a nuestros proyectos innovadores.</p>
        <p>Para celebrar este momento y darte una mejor idea de lo que significa formar parte de nuestra familia, hemos preparado una página especial de bienvenida. Por favor, haz clic en el siguiente botón:</p>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-top: 20px; margin-bottom: 20px;'>
          <tr>
            <td>
              <table border='0' cellspacing='0' cellpadding='0' align='center'>
                <tr>
                  <td align='center' style='border-radius: 5px; background-color: #28a745; padding: 12px 25px;'>
                    <a href='" . $linkBienvenida . "' target='_blank' style='font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display: inline-block;'>
                      <strong>Descubre tu Bienvenida a Grammer</strong>
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
        <p style='margin-top: 25px;'>En los próximos días, un miembro de nuestro equipo de Recursos Humanos se pondrá en contacto contigo para formalizar la oferta y detallar los siguientes pasos del proceso de contratación.</p>
        <p>¡Estamos muy emocionados por comenzar a trabajar contigo!</p>
    ";

    // Plantilla HTML completa que envuelve el cuerpo del mensaje
    $contenidoHTML = "
    <!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
    <body style='margin: 0; padding: 0; background-color: #f4f7fc; font-family: Arial, sans-serif;'>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr><td style='padding: 20px 0;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                    <tr><td align='center' style='background-color: #005195; padding: 30px;'><img src='$logoUrl' alt='Logo Grammer' width='150' style='display: block;'></td></tr>
                    <tr><td style='padding: 40px 30px; color: #333333; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;'>
                        $cuerpoMensaje
                        <p>Atentamente,<br><strong>Equipo de Adquisición de Talento<br>Grammer Automotive</strong></p>
                    </td></tr>
                    <tr><td align='center' style='background-color: #f8f9fa; padding: 20px; font-size: 12px; color: #6c757d;'><p style='margin: 0;'>&copy; " . date('Y') . " Grammer Automotive de México. Este es un correo automatizado.</p></td></tr>
                </table>
            </td></tr>
        </table>
    </body></html>";

    $mail = new PHPMailer(true);
    // Configuración del servidor SMTP (Hostinger)
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sistema_ats@grammermx.com';
    $mail->Password = 'SATSGrammer2024.';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // Configuración del correo
    $mail->setFrom('sistema_ats@grammermx.com', 'Sistema ATS Grammer');
    $mail->addAddress($correoCandidato, $nombreCandidato);
    $mail->addBCC('sistema_ats@grammermx.com');
    $mail->addBCC('extern.alejandro.torres@grammer.com');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $asunto;
    $mail->Body = $contenidoHTML; // Se usa la plantilla HTML completa

    $mail->send();

    // --- LÓGICA CRÍTICA: Actualizar la base de datos para marcar como enviado ---
    $updateStmt = $conn->prepare("UPDATE Postulaciones SET OfertaEnviada = 1 WHERE IdPostulacion = ?");
    $updateStmt->bind_param('i', $idPostulacion);
    $updateStmt->execute();

    $conn->commit(); // Confirmar los cambios en la base de datos solo si todo fue exitoso
    echo json_encode(['status' => 'success', 'message' => 'El correo de oferta ha sido enviado con éxito.']);

} catch (Exception $e) {
    $conn->rollback(); // Revertir cualquier cambio en la base de datos si algo falla
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>

