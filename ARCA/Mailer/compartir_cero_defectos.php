<?php
session_start();

include_once("conexionArca.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


header('Content-Type: application/json; charset=UTF-8');

// 1. Validación de Entrada
// Nota: El frontend envía 'idCeroDefectos' y 'emails' (como string JSON)
if (!isset($_POST['idCeroDefectos'], $_POST['emails']) || !isset($_SESSION['loggedin'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos o sesión no válida."]);
    exit;
}

$idCeroDefectos = intval($_POST['idCeroDefectos']);
$emailsDestino = json_decode($_POST['emails'], true); // Decodificamos el JSON a un array PHP

if (!is_array($emailsDestino) || empty($emailsDestino)) {
    echo json_encode(["status" => "error", "message" => "La lista de correos no es válida."]);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 2. Obtener datos de la solicitud 'CeroDefectos' para el correo
    // Hacemos JOIN con la tabla de OEM para obtener el nombre real
    $stmt_data = $conex->prepare("SELECT cd.Linea, cd.Cliente, oem.NombreOEM 
                                  FROM CeroDefectosSolicitudes cd
                                  JOIN CeroDefectosOEM oem ON cd.IdOEM = oem.IdOEM 
                                  WHERE cd.IdCeroDefectos = ?");
    $stmt_data->bind_param("i", $idCeroDefectos);
    $stmt_data->execute();
    $solicitudData = $stmt_data->get_result()->fetch_assoc();
    $stmt_data->close();

    if (!$solicitudData) {
        throw new Exception("No se encontraron los datos del registro Cero Defectos.");
    }

    // Preparar la consulta para insertar tokens (fuera del bucle para eficiencia)
    $stmt_token = $conex->prepare("INSERT INTO CeroDefectosSolicitudesCompartidas (IdCeroDefectos, EmailDestino, Token) VALUES (?, ?, ?)");

    // URL Base para el enlace
    $url_sitio = "https://grammermx.com/AleTest/ARCA";

    // 3. Procesar cada correo en la lista
    foreach ($emailsDestino as $email) {
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        if (!$email) continue; // Saltar correos inválidos

        // Generar Token Único
        $token = bin2hex(random_bytes(32));

        // Insertar en tabla compartida
        $stmt_token->bind_param("iss", $idCeroDefectos, $email, $token);
        if (!$stmt_token->execute()) {
            throw new Exception("Error al guardar el acceso compartido para $email.");
        }

        // Construir enlace
        $linkVerSolicitud = "$url_sitio/historial_cero_defectos.php?token=$token";

        // Enviar Correo
        enviarCorreoCeroDefectos($email, $idCeroDefectos, $solicitudData, $linkVerSolicitud, $_SESSION['user_nombre']);
    }
    $stmt_token->close();

    // 4. Actualizar el estatus de la solicitud a 'Asignado' (IdEstatus = 2)
    $stmt_update_status = $conex->prepare("UPDATE CeroDefectosSolicitudes SET IdEstatus = 2 WHERE IdCeroDefectos = ?");
    $stmt_update_status->bind_param("i", $idCeroDefectos);

    if (!$stmt_update_status->execute()) {
        throw new Exception("Error al actualizar el estatus de la solicitud.");
    }
    $stmt_update_status->close();

    // 5. Obtener el nombre del nuevo estatus para actualizar el frontend
    $stmt_status_name = $conex->prepare("SELECT NombreEstatus FROM Estatus WHERE IdEstatus = 2");
    $stmt_status_name->execute();
    $statusResult = $stmt_status_name->get_result();
    $nuevoNombreEstatus = 'Asignado';
    if ($statusRow = $statusResult->fetch_assoc()) {
        $nuevoNombreEstatus = $statusRow['NombreEstatus'];
    }
    $stmt_status_name->close();

    // Clase CSS para el estatus (se mantiene azul 'recibido' visualmente para Asignado según tu lógica anterior)
    $nuevoEstatusClase = 'status-recibido';

    $conex->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Las invitaciones han sido enviadas y el registro actualizado.",
        "nuevoEstatusNombre" => $nuevoNombreEstatus,
        "nuevoEstatusClase" => $nuevoEstatusClase
    ]);

} catch (Exception $e) {
    $conex->rollback();
    // Log del error real para depuración si es necesario: error_log($e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}

// -----------------------------------------------------------------------------
// Función de correo adaptada para CERO DEFECTOS
// -----------------------------------------------------------------------------
function enviarCorreoCeroDefectos($emailDestino, $id, $data, $link, $nombreRemitente) {
    $folio = "ZD-" . str_pad($id, 4, '0', STR_PAD_LEFT);
    $asunto = "Acción Requerida: Cero Defectos ARCA - Folio $folio";

    $contenidoHTML = "
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:\"Lato\", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#4a6984;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'><i class='fa-solid fa-shield-halved'></i> ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Asignación de Cero Defectos</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola,</p>
            <p style='color:#6c757d;line-height:1.6;'><strong>" . htmlspecialchars($nombreRemitente) . "</strong> te ha asignado el registro con folio <strong style='color:#0056b3;'>$folio</strong> para seguimiento.</p>
            
            <table border='0' cellpadding='10' cellspacing='0' width='100%' style='border-collapse:collapse;margin:25px 0;background-color:#f8f9fa;border-radius:8px;'>
                <tr>
                    <td style='width:30%;font-weight:bold;color:#495057;border-bottom:1px solid #e9ecef;'>Línea:</td>
                    <td style='color:#212529;border-bottom:1px solid #e9ecef;'>" . htmlspecialchars($data['Linea']) . "</td>
                </tr>
                <tr>
                    <td style='font-weight:bold;color:#495057;border-bottom:1px solid #e9ecef;'>OEM:</td>
                    <td style='color:#212529;border-bottom:1px solid #e9ecef;'>" . htmlspecialchars($data['NombreOEM']) . "</td>
                </tr>
                <tr>
                    <td style='font-weight:bold;color:#495057;'>Cliente:</td>
                    <td style='color:#212529;'>" . htmlspecialchars($data['Cliente']) . "</td>
                </tr>
            </table>

            <p style='color:#6c757d;line-height:1.6;'>Para acceder al registro y comenzar a trabajar, haz clic en el siguiente botón:</p>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='$link' target='_blank' style='font-size:16px;font-family:\"Lato\", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;box-shadow: 0 2px 4px rgba(0,0,0,0.2);'>Ver Registro en ARCA</a>
            </td></tr></table>
            <p style='color:#6c757d;line-height:1.6;'>Saludos,<br><strong>El equipo del Sistema ARCA</strong></p>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; " . date('Y') . " ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>";

    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor de correo
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_arca@grammermx.com';
        $mail->Password = 'SARCAGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('sistema_arca@grammermx.com', 'Sistema ARCA');
        $mail->addAddress($emailDestino);
        // Copias ocultas para monitoreo
        $mail->addBCC('sistema_ats@grammermx.com');
        $mail->addBCC('extern.alejandro.torres@grammermx.com');

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $contenidoHTML;

        $mail->send();
    } catch (Exception $e) {
        // Si falla un correo individual, lanzamos excepción para que el usuario sepa que algo salió mal y se haga rollback
        throw new Exception("Error al enviar correo a $emailDestino: " . $mail->ErrorInfo);
    }
}
?>
