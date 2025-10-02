<?php
session_start(['cookie_path' => '/']);

// Incluye los archivos necesarios.
include_once("conexionArca.php");
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=UTF-8');

// --- CONFIGURACIÓN ---
define('BASE_URL', 'https://grammermx.com/AleTest/ARCA');
define('ADMIN_EMAIL', 'extern.alejandro.torres@grammer.com');

// --- VALIDACIONES ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit();
}
if (!isset($_POST['idSolicitud'], $_POST['tituloMetodo']) || !isset($_FILES['metodoFile']) || $_FILES['metodoFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos o hubo un error al subir el archivo.']);
    exit();
}

// --- FUNCIONES AUXILIARES ---
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    $directorioDestino = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/' . $subdirectorio;
    if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0775, true)) {
        throw new Exception("Error fatal: No se pudo crear la carpeta de destino.");
    }
    $nombreOriginalLimpio = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', basename($archivo['name']));
    $nombreUnico = $prefijo . uniqid() . '_' . $nombreOriginalLimpio;
    $rutaFisicaDestino = $directorioDestino . $nombreUnico;
    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisicaDestino)) {
        throw new Exception("Falló la subida del archivo.");
    }
    return BASE_URL . '/' . $subdirectorio . $nombreUnico;
}

function notificarAdminNuevoMetodo($idSolicitud, $datosSolicitud) {
    $asunto = "ARCA: Nuevo Método de Trabajo para Revisión - Folio S-" . str_pad($idSolicitud, 4, '0', STR_PAD_LEFT);
    $linkAprobacion = BASE_URL . '/aprobar_metodos.php';
    $currentYear = date('Y');

    $cuerpoHTML = <<<HTML
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f8f9fa;font-family:"Lato", Arial, sans-serif;'>
    <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td style='padding:20px 0;'>
    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);border: 1px solid #dee2e6;'>
        <tr><td align='center' style='background-color:#4a6984;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;'>
            <h1 style='color:#ffffff;margin:0;font-size:28px;letter-spacing:1px;'>ARCA</h1>
        </td></tr>
        <tr><td style='padding:40px 30px;'>
            <h2 style='color:#343a40;margin-top:0;font-size:22px;'>Acción Requerida: Revisar Método de Trabajo</h2>
            <p style='color:#6c757d;line-height:1.6;'>Hola Administrador,</p>
            <p style='color:#6c757d;line-height:1.6;'>Se ha subido un nuevo método de trabajo para la solicitud con folio <strong style='color:#0056b3;'>S-{$idSolicitud}</strong> y requiere tu aprobación.</p>
            <table border='0' cellpadding='10' cellspacing='0' width='100%' style='border-collapse:collapse;margin:25px 0;background-color:#f8f9fa;border-radius:8px;'>
                <tr>
                    <td style='width:30%;font-weight:bold;color:#495057;'>Creador:</td>
                    <td style='color:#212529;'>{$datosSolicitud['responsable']}</td>
                </tr>
                <tr>
                    <td style='font-weight:bold;color:#495057;'>No. de Parte:</td>
                    <td style='color:#212529;'>{$datosSolicitud['numeroParte']}</td>
                </tr>
            </table>
            <p style='color:#6c757d;line-height:1.6;'>Por favor, accede al panel de administración para aprobar o rechazar el método.</p>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td align='center' style='padding:20px 0;'>
                <a href='{$linkAprobacion}' target='_blank' style='font-size:16px;font-family:"Lato", Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;'>Ir al Panel de Aprobación</a>
            </td></tr></table>
        </td></tr>
        <tr><td align='center' style='background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;'><p style='margin:0;'>&copy; {$currentYear} ARCA Systems. Notificación automatizada.</p></td></tr>
    </table>
    </td></tr></table>
    </body></html>
    HTML;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sistema_arca@grammermx.com';
        $mail->Password = 'SARCAGrammer2024.';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('sistema_arca@grammermx.com', 'Sistema ARCA');
        $mail->addAddress(ADMIN_EMAIL);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHTML;
        $mail->send();
    } catch (Exception $e) {
        error_log("Correo de nuevo método a admin no enviado para Solicitud {$idSolicitud}: " . $mail->ErrorInfo);
    }
}

// --- LÓGICA PRINCIPAL ---
$idSolicitud = intval($_POST['idSolicitud']);
$tituloMetodo = trim($_POST['tituloMetodo']);
$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 1. Procesar y subir el archivo PDF.
    $rutaMetodoPublica = procesarArchivoSubido($_FILES['metodoFile'], 'Metodos/', 'metodo_inicial_');

    // 2. Insertar el nuevo método en la tabla `Metodos`.
    $stmt_metodo = $conex->prepare("INSERT INTO Metodos (TituloMetodo, RutaArchivo, IdUsuarioCarga) VALUES (?, ?, ?)");
    $idUsuarioCarga = $_SESSION['user_id'];
    $stmt_metodo->bind_param("ssi", $tituloMetodo, $rutaMetodoPublica, $idUsuarioCarga);
    if (!$stmt_metodo->execute()) {
        throw new Exception("Error al guardar el método en la base de datos.");
    }
    $idMetodoNuevo = $stmt_metodo->insert_id;
    $stmt_metodo->close();

    // 3. Actualizar la solicitud para enlazarla con el nuevo método.
    $stmt_update = $conex->prepare("UPDATE Solicitudes SET IdMetodo = ? WHERE IdSolicitud = ?");
    $stmt_update->bind_param("ii", $idMetodoNuevo, $idSolicitud);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al enlazar el método con la solicitud.");
    }
    $stmt_update->close();

    // 4. Obtener datos para el correo y notificar al administrador.
    $stmt_datos = $conex->prepare("SELECT Responsable, NumeroParte FROM Solicitudes WHERE IdSolicitud = ?");
    $stmt_datos->bind_param("i", $idSolicitud);
    $stmt_datos->execute();
    $datosParaCorreo = $stmt_datos->get_result()->fetch_assoc();
    $stmt_datos->close();

    notificarAdminNuevoMetodo($idSolicitud, ['responsable' => $datosParaCorreo['Responsable'], 'numeroParte' => $datosParaCorreo['NumeroParte']]);

    $conex->commit();
    $response = ['status' => 'success', 'message' => 'Método de trabajo subido y enviado para revisión.'];

} catch (Exception $e) {
    $conex->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} finally {
    $conex->close();
}

echo json_encode($response);
?>


