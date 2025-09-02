<?php
include_once("ConexionBD.php");
// Requerimos los archivos de PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

// Usamos las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// --- CONFIGURACIÓN IMPORTANTE ---
// Reemplaza esto con la URL real de tu sitio
$url_sitio = "https://grammermx.com/AleTest/ATS";

if (empty($_POST['numNomina'])) {
    echo json_encode(['status' => 'error', 'message' => 'Número de nómina no proporcionado.']);
    exit;
}

$numNomina = $_POST['numNomina'];
$con = new LocalConector();
$conex = $con->conectar();

// 1. Verificar si el usuario existe y obtener su correo
// Asumo que la tabla Usuario tiene una columna 'Correo'. Si no, ajusta esta consulta.
$stmt = $conex->prepare("SELECT Correo FROM Usuario WHERE NumNomina = ?");
$stmt->bind_param("s", $numNomina);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    $emailDestino = $usuario['Correo'];

    // 2. Generar token seguro y fecha de expiración
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 3. Invalidar tokens viejos y guardar el nuevo en la BD
    $conex->begin_transaction();
    try {
        $stmt_invalidate = $conex->prepare("UPDATE RestablecerContrasena SET TokenValido = 0 WHERE NumNomina = ?");
        $stmt_invalidate->bind_param("s", $numNomina);
        $stmt_invalidate->execute();

        $stmt_insert = $conex->prepare("INSERT INTO RestablecerContrasena (NumNomina, Token, Expira, TokenValido) VALUES (?, ?, ?, 1)");
        $stmt_insert->bind_param("sss", $numNomina, $token, $expira);
        $stmt_insert->execute();

        $conex->commit();
    } catch (mysqli_sql_exception $exception) {
        $conex->rollback();
        echo json_encode(['status' => 'success']); // Mensaje genérico por seguridad
        exit;
    }

    // 4. Enviar el correo electrónico usando la nueva función
    $asunto = "Restablecimiento de Contraseña - ATS Grammer";
    $link = $url_sitio . "/restablecer_contrasena.php?token=" . $token;

    enviarCorreoRecuperacion($emailDestino, $asunto, $link);
}

// Por seguridad, siempre devolvemos un mensaje de éxito
echo json_encode(['status' => 'success']);
$conex->close();


// --- FUNCIÓN DE ENVÍO DE CORREO ADAPTADA DE TU EJEMPLO ---
function enviarCorreoRecuperacion($email, $asunto, $link) {
    $mensaje = "
    <p>Hola,</p>
    <p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente botón para continuar:</p>
    <p style='margin: 25px 0;'>
        <a href='$link' target='_blank' style='background: #005195; color: #FFFFFF; 
        padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: bold; 
        display: inline-block;'>
            Restablecer Contraseña
        </a>
    </p>
    <p>Si no solicitaste esto, puedes ignorar este correo. Este enlace es válido por 1 hora.</p>
    <p>Gracias,<br>Equipo de Grammer</p>";

    $contenidoHTML = "
    <html>
    <head><meta charset='UTF-8'><title>$asunto</title></head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f7fc;'>
        <table role='presentation' style='width: 100%; max-width: 600px; margin: 20px auto; background: #FFFFFF; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
            <tr>
                <td style='background-color: #005195; padding: 20px; color: #FFFFFF; text-align: center;'>
                    <h2>Restablecimiento de Contraseña</h2>
                </td>
            </tr>
            <tr>
                <td style='padding: 30px; text-align: left; color: #333333; line-height: 1.6;'>
                    $mensaje
                </td>
            </tr>
            <tr>
                <td style='background-color: #f1f1f1; color: #666666; padding: 15px; text-align: center; font-size: 12px;'>
                    <p>© " . date('Y') . " Grammer Querétaro. Todos los derechos reservados.</p>
                </td>
            </tr>
        </table>
    </body>
    </html>";

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
        $mail->setFrom('sistema_ats@grammermx.com', 'Administración ATS Grammer');

        // Destinatario
        $mail->addAddress($email);

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        $mail->send();
        return true; // Éxito
    } catch (Exception $e) {
        // En un entorno de producción, podrías registrar el error en un archivo log
        // error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false; // Error
    }
}
?>