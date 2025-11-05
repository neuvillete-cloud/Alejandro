<?php
session_start();
// Asegúrate de que las rutas a estos archivos sean correctas desde /dao/
include_once("conexionArca.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

// 1. Validar el Correo
if (!isset($_POST['correo'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    exit;
}

$emailDestino = filter_var(trim($_POST['correo']), FILTER_VALIDATE_EMAIL);

if (!$emailDestino) {
    echo json_encode(["status" => "error", "message" => "La dirección de correo no es válida."]);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();

// --- ¡NUEVO! ---
// Establecer el charset de la conexión a utf8mb4 es crucial
// para manejar acentos y caracteres especiales correctamente.
$conex->set_charset("utf8mb4");

$conex->begin_transaction();

try {
    // 2. Buscar al usuario por correo
    $stmt_user = $conex->prepare("SELECT IdUsuario, Nombre FROM Usuarios WHERE Correo = ?");
    $stmt_user->bind_param("s", $emailDestino);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($usuario = $result_user->fetch_assoc()) {
        // Usuario encontrado
        $idUsuario = $usuario['IdUsuario'];
        $nombreUsuario = $usuario['Nombre'];

        // 3. Invalidar tokens antiguos (buena práctica)
        // Usamos TokenValido = 0 para marcar como usado/expirado
        // --- ¡CORRECCIÓN! --- Se usa 'ReestablecerContraseña' con 'ñ'
        $stmt_invalidate = $conex->prepare("UPDATE ReestablecerContraseña SET TokenValido = 0 WHERE IdUsuario = ?");
        $stmt_invalidate->bind_param("i", $idUsuario);
        $stmt_invalidate->execute();
        $stmt_invalidate->close();

        // 4. Crear nuevo token y expiración
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 hora de validez

        // 5. Insertar el nuevo token en la BD
        // --- ¡CORRECCIÓN! --- Se usa 'ReestablecerContraseña' con 'ñ'
        $stmt_token = $conex->prepare("INSERT INTO ReestablecerContraseña (IdUsuario, Token, Expira, TokenValido) VALUES (?, ?, ?, 1)");
        $stmt_token->bind_param("iss", $idUsuario, $token, $expira);

        if (!$stmt_token->execute()) {
            throw new Exception("Error al generar el enlace seguro.");
        }
        $stmt_token->close();

        // 6. Construir el enlace de recuperación
        // ¡DEBES CREAR ESTA PÁGINA: establecerNuevaContrasena.php!
        $url_sitio = "https://grammermx.com/AleTest/ARCA";
        $linkRecuperacion = "$url_sitio/establecerNuevaContrasena.php?token=$token";

        // 7. Enviar el correo
        enviarCorreoRecuperacion($emailDestino, $nombreUsuario, $linkRecuperacion);
    }

    // 8. Confirmar transacción (si el usuario existió)
    // Si el usuario NO existió, no hacemos nada en la BD, pero igual enviamos respuesta OK.
    if (isset($idUsuario)) {
        $conex->commit();
    } else {
        $conex->rollback(); // No se encontró usuario, no hay nada que hacer.
    }

    // (Seguridad) Siempre enviar un mensaje de éxito, exista o no el correo.
    // Esto previene que alguien pueda adivinar qué correos están registrados.
    echo json_encode([
        "status" => "success",
        "message" => "Si tu correo está registrado, recibirás un enlace de recuperación en breve."
    ]);

} catch (Exception $e) {
    $conex->rollback();
    // Enviar el mensaje de error real para depuración
    // En producción, podrías querer un mensaje más genérico
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}


/**
 * Envía el correo de recuperación de contraseña.
 */
function enviarCorreoRecuperacion($emailDestino, $nombreUsuario, $link) {
    $asunto = "Recuperación de Contraseña - Sistema ARCA";

    $contenidoHTML = "
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:\"Lato\", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#4a6984;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'>ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Restablecer tu Contraseña</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola, " . htmlspecialchars($nombreUsuario) . ":</p>
            <p style='color:#6c757d;line-height:1.6;'>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el sistema ARCA. Si no hiciste esta solicitud, puedes ignorar este correo.</p>
            <p style='color:#6c757d;line-height:1.6;'>Este enlace de recuperación expirará en <strong>1 hora</strong>.</p>

            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='$link' target='_blank' style='font-size:16px;font-family:\"Lato\", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;box-shadow: 0 2px 4px rgba(0,0,0,0.2);'>Restablecer Contraseña</a>
            </td></tr></table>
            
            <p style='color:#6c757d;line-height:1.6;'>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <p style='color:#6c757d;line-height:1.2;font-size:12px;word-break:break-all;'>$link</p>
            
            <p style='color:#6c757d;line-height:1.6;margin-top:25px;'>Saludos,<br><strong>El equipo del Sistema ARCA</strong></p>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; " . date('Y') . " ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>";
    // --- FIN DE PLANTILLA DE CORREO ---

    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor de correo (¡COPIADA DE TU EJEMPLO!)
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_arca@grammermx.com';
        $mail->Password = 'SARCAGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('sistema_arca@grammermx.com', 'Sistema ARCA');
        $mail->addAddress($emailDestino);
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammer.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        if (!$mail->send()) {
            throw new Exception("Error al enviar correo: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        // Importante: Lanzamos la excepción para que la transacción principal haga rollback.
        throw new Exception("El registro en BD fue exitoso, pero el correo no pudo ser enviado. Error: {$e->getMessage()}");
    }
}
?>
