<?php
session_start();

// Incluimos tu conexión y las librerías PHPMailer
include_once("conexionArca.php"); // Usando tu conector
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

// Inicializar la respuesta
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

// 1. Validar que recibimos el NumNomina
if (empty($_POST['numNomina'])) {
    $response['message'] = 'Por favor, introduce tu número de nómina.';
    echo json_encode($response);
    exit;
}

$numNomina = $_POST['numNomina'];

// Usamos tu método de conexión
$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 2. Buscar al usuario en la tabla 'Usuario'
    $stmtUser = $conex->prepare("SELECT Nombre, Correo FROM Usuario WHERE NumNomina = ?");
    $stmtUser->bind_param("s", $numNomina);
    $stmtUser->execute();
    $resultadoUser = $stmtUser->get_result();

    if ($resultadoUser->num_rows === 0) {
        // Si el usuario no existe, lanzamos una excepción para el rollback
        throw new Exception("El número de nómina no se encuentra registrado en el sistema.");
    }

    // 3. Obtener datos del usuario
    $usuario = $resultadoUser->fetch_assoc();
    $correoUsuario = $usuario['Correo'];
    $nombreUsuario = $usuario['Nombre'];
    $stmtUser->close();

    // 4. Generar un token seguro y un tiempo de expiración
    $token = bin2hex(random_bytes(32)); // Token de 64 caracteres
    $expiraTimestamp = time() + 3600; // El token expira en 1 hora (3600 segundos)
    $fechaExpira = date('Y-m-d H:i:s', $expiraTimestamp);

    /*
     * 5. Guardar el token en la tabla 'RestablecerContrasena'
     *
     * Usamos "INSERT ... ON DUPLICATE KEY UPDATE"
     * Esto es CRUCIAL porque la tabla 'RestablecerContrasena' tiene
     * una restricción UNIQUE en 'NumNomina'.
     *
     * - Si el usuario NUNCA ha pedido un token, inserta uno nuevo.
     * - Si el usuario YA TENÍA un token, lo actualiza (invalida el anterior).
     */
    $stmtToken = $conex->prepare("
        INSERT INTO RestablecerContrasena (NumNomina, Token, Expira, TokenValido) 
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
            Token = ?, 
            Expira = ?, 
            TokenValido = 1
    ");
    // Pasamos los valores dos veces, una para el INSERT, otra para el UPDATE
    $stmtToken->bind_param("sssss", $numNomina, $token, $fechaExpira, $token, $fechaExpira);

    if (!$stmtToken->execute()) {
        throw new Exception("Error al guardar el token: " . $stmtToken->error);
    }
    $stmtToken->close();

    /*
     * 6. Construir el enlace de reseteo
     * Usamos la URL base de tu ejemplo.
     */
    $url_sitio = "https://grammermx.com/AleTest/ARCA"; // ¡Tu URL real!
    // Esta es la página que debemos crear a continuación:
    $enlaceReseteo = "$url_sitio/resetear.php?token=" . urlencode($token);

    /*
     * 7. Enviar el correo electrónico
     * Llamamos a una función separada, igual que en tu ejemplo.
     * Esta función (definida abajo) DEBE lanzar una excepción si falla
     * el envío, para que la transacción de la BD haga rollback.
     */
    enviarCorreoReseteo($correoUsuario, $nombreUsuario, $numNomina, $enlaceReseteo);

    // 8. Si todo (BD y Correo) fue exitoso, confirmar la transacción
    $conex->commit();
    $response = [
        "status" => "success",
        "message" => "¡Éxito! Hemos enviado las instrucciones para restablecer tu contraseña a tu correo electrónico."
    ];
} catch (Exception $e) {
    // 9. Si algo falló (BD o Correo), revertir la transacción
    $conex->rollback();
    $response = ["status" => "error", "message" => $e->getMessage()];
} finally {
    // 10. Cerrar la conexión
    if (isset($conex)) {
        $conex->close();
    }
}

// 11. Devolver la respuesta al frontend
echo json_encode($response);
exit;


/**
 * ======================================================================
 * FUNCIÓN PARA ENVIAR CORREO DE RESETEO
 * ======================================================================
 * Adaptada con tu configuración de PHPMailer y Hostinger.
 */
function enviarCorreoReseteo($emailDestino, $nombreUsuario, $numNomina, $link) {

    $asunto = "ARCA - Restablecimiento de Contraseña";

    // Plantilla de correo HTML (adaptada del estilo de tu ejemplo)
    $contenidoHTML = "
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:\"Lato\", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#4a6984;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'>ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Restablecimiento de Contraseña</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola, " . htmlspecialchars($nombreUsuario) . ":</p>
            <p style='color:#6c757d;line-height:1.6;'>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta ARCA (Nómina: <strong style='color:#0056b3;'>" . htmlspecialchars($numNomina) . "</strong>).</p>
            <p style='color:#6c757d;line-height:1.6;'>Si tú no solicitaste esto, puedes ignorar este correo de forma segura.</p>
            
            <p style='color:#6c757d;line-height:1.6;'>Para establecer una nueva contraseña, por favor haz clic en el siguiente botón. <strong>Este enlace es válido por 1 hora:</strong></p>
            
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='$link' target='_blank' style='font-size:16px;font-family:\"Lato\", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;box-shadow: 0 2px 4px rgba(0,0,0,0.2);'>Restablecer Contraseña</a>
            </td></tr></table>

            <p style='color:#6c757d;line-height:1.6;font-size:13px;border-top:1px solid #e9ecef;padding-top:15px;margin-top:20px;'>
              Si el botón no funciona, copia y pega la siguiente URL en tu navegador:<br>
              <span style='color:#0056b3; font-size:11px; word-break:break-all;'>$link</span>
            </p>

            <p style='color:#6c757d;line-height:1.6;'>Saludos,<br><strong>El equipo del Sistema ARCA</strong></p>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; " . date('Y') . " ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>";
    // --- FIN DE PLANTILLA DE CORREO ---

    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor de correo (la que tú usas)
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_arca@grammermx.com';
        $mail->Password = 'SARCAGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('sistema_arca@grammermx.com', 'Sistema ARCA');
        $mail->addAddress($emailDestino); // Correo del usuario

        // Tus BCCs
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammermx.com');

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


