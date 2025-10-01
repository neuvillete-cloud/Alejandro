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

// --- CONFIGURACIÓN ---
define('BASE_URL', 'https://grammermx.com/AleTest/ARCA');
define('ADMIN_EMAIL', 'extern.alejandro.torres@grammer.com'); // Correo del admin

// --- VALIDACIONES ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit();
}
if (!isset($_POST['idSolicitud']) || !isset($_FILES['metodoFile']) || $_FILES['metodoFile']['error'] !== UPLOAD_ERR_OK) {
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
        throw new Exception("Falló la subida del archivo. No se pudo mover a la carpeta de destino.");
    }
    return BASE_URL . '/' . $subdirectorio . $nombreUnico;
}

function notificarAdminNuevoMetodo($idSolicitud, $datosSolicitud) {
    $asunto = "ARCA: Método Corregido para Revisión - Folio S-" . str_pad($idSolicitud, 4, '0', STR_PAD_LEFT);
    $linkAprobacion = BASE_URL . '/aprobar_metodos.php';
    $currentYear = date('Y');
    // Esta es una plantilla de correo simplificada. Puedes copiar y pegar la plantilla completa de tus otros archivos si lo deseas.
    $cuerpoHTML = <<<HTML
    <!DOCTYPE html><html><body>
    <p>Hola Administrador,</p>
    <p>Se ha subido una versión corregida del método de trabajo para la solicitud con folio <strong>S-{$idSolicitud}</strong> y requiere tu aprobación.</p>
    <p><strong>Creador:</strong> {$datosSolicitud['responsable']}<br><strong>No. de Parte:</strong> {$datosSolicitud['numeroParte']}</p>
    <a href='{$linkAprobacion}'>Ir al Panel de Aprobación</a>
    <p>&copy; {$currentYear} ARCA Systems.</p>
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
        error_log("Correo de resubida a admin no enviado para Solicitud {$idSolicitud}: " . $mail->ErrorInfo);
    }
}

// --- LÓGICA PRINCIPAL ---
$idSolicitud = intval($_POST['idSolicitud']);
$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    // 1. Obtener datos de la solicitud
    $stmt_sol = $conex->prepare("SELECT IdMetodo, Responsable, NumeroParte FROM Solicitudes WHERE IdSolicitud = ?");
    $stmt_sol->bind_param("i", $idSolicitud);
    $stmt_sol->execute();
    $solicitud = $stmt_sol->get_result()->fetch_assoc();
    $stmt_sol->close();

    if (!$solicitud || empty($solicitud['IdMetodo'])) {
        throw new Exception("La solicitud no existe o no tiene un método asociado para actualizar.");
    }
    $idMetodo = $solicitud['IdMetodo'];

    // 2. Procesar el nuevo archivo
    $rutaMetodoPublica = procesarArchivoSubido($_FILES['metodoFile'], 'Metodos/', 'metodo_corregido_');

    // 3. Actualizar el método en la BD
    $stmt_update = $conex->prepare("UPDATE Metodos SET RutaArchivo = ?, EstatusAprobacion = 'Pendiente' WHERE IdMetodo = ?");
    $stmt_update->bind_param("si", $rutaMetodoPublica, $idMetodo);
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el método en la base de datos.");
    }
    $stmt_update->close();

    // 4. Notificar al administrador
    notificarAdminNuevoMetodo($idSolicitud, ['responsable' => $solicitud['Responsable'], 'numeroParte' => $solicitud['NumeroParte']]);

    $conex->commit();
    $response = ['status' => 'success', 'message' => 'Método de trabajo corregido y enviado para revisión.'];

} catch (Exception $e) {
    $conex->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} finally {
    $conex->close();
}

echo json_encode($response);
?>


