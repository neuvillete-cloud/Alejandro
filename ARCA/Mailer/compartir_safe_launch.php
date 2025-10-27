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

// --- CAMBIO 1: Validar 'idSafeLaunch' en lugar de 'id' ---
if (!isset($_POST['idSafeLaunch'], $_POST['email']) || !isset($_SESSION['loggedin'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos o sesión no válida."]);
    exit;
}

$idSafeLaunch = intval($_POST['idSafeLaunch']);
$emailDestino = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

if (!$emailDestino) {
    echo json_encode(["status" => "error", "message" => "La dirección de correo no es válida."]);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // --- CAMBIO 2: Guardar en la nueva tabla 'SafeLaunchSolicitudesCompartidas' ---
    $token = bin2hex(random_bytes(32));
    $stmt_token = $conex->prepare("INSERT INTO SafeLaunchSolicitudesCompartidas (IdSafeLaunch, EmailDestino, Token) VALUES (?, ?, ?)");
    $stmt_token->bind_param("iss", $idSafeLaunch, $emailDestino, $token);
    if (!$stmt_token->execute()) {
        throw new Exception("Error al generar el enlace seguro para compartir.");
    }
    $stmt_token->close();

    // --- CAMBIO 3: Se elimina el PASO 2 (Actualizar Estatus de Solicitud) ---
    // El estatus de Safe Launch se maneja por revisión, no por envío de correo.

    // --- CAMBIO 4: Obtener datos de 'SafeLaunchSolicitudes' ---
    $stmt_data = $conex->prepare("SELECT NombreProyecto, Cliente FROM SafeLaunchSolicitudes WHERE IdSafeLaunch = ?");
    $stmt_data->bind_param("i", $idSafeLaunch);
    $stmt_data->execute();
    $solicitudData = $stmt_data->get_result()->fetch_assoc();
    if (!$solicitudData) {
        throw new Exception("No se encontraron los datos del Safe Launch.");
    }
    $stmt_data->close();

    // --- CAMBIO 5: Obtener defectos de las tablas 'SafeLaunch' ---
    $stmt_defectos = $conex->prepare("SELECT sldc.NombreDefecto 
                                     FROM SafeLaunchDefectos sld
                                     JOIN SafeLaunchCatalogoDefectos sldc ON sld.IdSLDefectoCatalogo = sldc.IdSLDefectoCatalogo 
                                     WHERE sld.IdSafeLaunch = ?");
    $stmt_defectos->bind_param("i", $idSafeLaunch);
    $stmt_defectos->execute();
    $resultado_defectos = $stmt_defectos->get_result();
    $defectos_nombres = [];
    while ($fila = $resultado_defectos->fetch_assoc()) {
        $defectos_nombres[] = $fila['NombreDefecto'];
    }
    $stmt_defectos->close();


    // --- CAMBIO 6: Construir link a 'historial_safe_launch.php' ---
    $url_sitio = "https://grammermx.com/AleTest/ARCA"; // <-- ¡TU URL REAL!
    $linkVerSolicitud = "$url_sitio/historial_safe_launch.php?token=$token";

    // Pasamos los nuevos datos a la función del correo adaptada.
    enviarCorreoSafeLaunch($emailDestino, $idSafeLaunch, $solicitudData, $defectos_nombres, $linkVerSolicitud, $_SESSION['user_nombre']);

    $conex->commit();
    echo json_encode(["status" => "success", "message" => "El Safe Launch ha sido compartido."]);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}


// --- CAMBIO 7: Nueva función de correo específica para Safe Launch ---
function enviarCorreoSafeLaunch($emailDestino, $id, $solicitudData, $defectos_nombres, $link, $nombreRemitente) {
    $folio = "SL-" . str_pad($id, 4, '0', STR_PAD_LEFT);
    $asunto = "Acción Requerida: Solicitud de Safe Launch ARCA - Folio $folio";

    // Construimos la lista de defectos
    $listaDefectosHTML = '';
    if (!empty($defectos_nombres)) {
        foreach ($defectos_nombres as $defecto) {
            $listaDefectosHTML .= "<li style='margin-bottom: 5px; color: #555;'>" . htmlspecialchars($defecto) . "</li>";
        }
        $listaDefectosHTML = "<ul style='margin-top: 5px; padding-left: 20px;'>" . $listaDefectosHTML . "</ul>";
    } else {
        $listaDefectosHTML = "<span style='color: #888;'>No se especificaron defectos.</span>";
    }

    $contenidoHTML = "
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:\"Lato\", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#4a6984;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'><i class='fa-solid fa-shield-halved'></i> ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Revisión de Solicitud de Safe Launch</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola,</p>
            <p style='color:#6c757d;line-height:1.6;'><strong>" . htmlspecialchars($nombreRemitente) . "</strong> ha compartido contigo la solicitud con folio <strong style='color:#0056b3;'>$folio</strong> para tu revisión y seguimiento.</p>
            
            <table border='0' cellpadding='10' cellspacing='0' width='100%' style='border-collapse:collapse;margin:25px 0;background-color:#f8f9fa;border-radius:8px;'>
                <tr>
                    <td style='width:30%;font-weight:bold;color:#495057;border-bottom:1px solid #e9ecef;'>Proyecto:</td>
                    <td style='color:#212529;border-bottom:1px solid #e9ecef;'>" . htmlspecialchars($solicitudData['NombreProyecto']) . "</td>
                </tr>
                <tr>
                    <td style='font-weight:bold;color:#495057;border-bottom:1px solid #e9ecef;'>Cliente:</td>
                    <td style='color:#212529;border-bottom:1px solid #e9ecef;'>" . htmlspecialchars($solicitudData['Cliente']) . "</td>
                </tr>
                 <tr>
                    <td style='font-weight:bold;color:#495057;vertical-align:top;'>Defectos:</td>
                    <td style='color:#212529;'>$listaDefectosHTML</td>
                </tr>
            </table>

            <p style='color:#6c757d;line-height:1.6;'>Para ver todos los detalles, incluyendo la instrucción de trabajo (si aplica), por favor haz clic en el siguiente botón:</p>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='$link' target='_blank' style='font-size:16px;font-family:\"Lato\", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;box-shadow: 0 2px 4px rgba(0,0,0,0.2);'>Ver Solicitud en ARCA</a>
            </td></tr></table>
            <p style='color:#6c757d;line-height:1.6;'>Saludos,<br><strong>El equipo del Sistema ARCA</strong></p>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; " . date('Y') . " ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>";
    // --- FIN DE PLANTILLA DE CORREO ---

    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor de correo (igual que la original)
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
        $mail->addBCC('extern.alejandro.torres@grammermx.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        if (!$mail->send()) {
            throw new Exception("Error al enviar correo: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        throw new Exception("El registro en BD fue exitoso, pero el correo no pudo ser enviado. Error: {$e->getMessage()}");
    }
}
?>


