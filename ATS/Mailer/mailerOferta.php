<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario tenga sesión iniciada
if (!isset($_SESSION['NumNomina'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

// Incluir PHPMailer
// Asegúrate que la ruta al directorio de PHPMailer sea correcta desde aquí
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verificar que se recibieron los datos necesarios por POST
if (!isset($_POST['nombreCandidato'], $_POST['emailCandidato'], $_POST['nombreVacante'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos para enviar el correo.']);
    exit;
}

$nombreCandidato = trim($_POST['nombreCandidato']);
$emailCandidato = trim($_POST['emailCandidato']);
$nombreVacante = trim($_POST['nombreVacante']);
// Asegúrate que esta URL sea la correcta para tu sitio
$url_sitio = "https://grammermx.com/AleTest/ATS";

// Construir el enlace a la página de bienvenida
$enlaceBienvenida = $url_sitio . "/bienvenida.php";
$logoUrl = $url_sitio . '/imagenes/logo_blanco.png';

$asunto = "¡Felicidades! Un nuevo paso en tu carrera te espera en Grammer Automotive";

$cuerpoMensaje = "
<h1 style='color: #005195;'>¡Enhorabuena, " . htmlspecialchars($nombreCandidato) . "!</h1>
<p>El equipo de Adquisición de Talento de <strong>Grammer Automotive</strong> tiene excelentes noticias para ti.</p>
<p>Nos complace enormemente informarte que, tras un exitoso proceso de selección, has sido seleccionado(a) para el puesto de <strong>" . htmlspecialchars($nombreVacante) . "</strong>.</p>
<p>Tu perfil, habilidades y entusiasmo nos han convencido de que eres la persona ideal para unirte a nuestro equipo y contribuir a nuestros proyectos innovadores.</p>
<p>Para celebrar este momento y darte una mejor idea de lo que significa formar parte de nuestra familia, hemos preparado una página especial de bienvenida. Por favor, haz clic en el siguiente botón:</p>
<table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-top: 20px; margin-bottom: 20px;'>
  <tr>
    <td>
      <table border='0' cellspacing='0' cellpadding='0' align='center'>
        <tr>
          <td align='center' style='border-radius: 5px; background-color: #28a745; padding: 12px 25px;'>
            <a href='" . $enlaceBienvenida . "' target='_blank' style='font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; display: inline-block;'>
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

// Función central de envío de correo
function enviarCorreoOutlook($destinatario, $asunto, $cuerpoMensaje, $logoUrl) {
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
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_ats@grammermx.com';
        $mail->Password = 'SATSGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_ats@grammermx.com', 'Sistema ATS Grammer');
        $mail->addAddress($destinatario);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Enviar el correo y devolver la respuesta JSON
if (enviarCorreoOutlook($emailCandidato, $asunto, $cuerpoMensaje, $logoUrl)) {
    echo json_encode(['status' => 'success', 'message' => 'Correo enviado exitosamente.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el correo. Revisa la configuración del servidor.']);
}
?>


