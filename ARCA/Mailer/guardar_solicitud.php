<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluimos los archivos necesarios.
include_once("verificar_sesion.php");
include_once("conexionArca.php");

// Incluimos PHPMailer
require 'Phpmailer/Exception.php';
require 'Phpmailer/PHPMailer.php';
require 'Phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// --- CONFIGURACIÓN ---
$baseUrl = "https://grammermx.com/AleTest/ARCA/"; // URL base de tu proyecto.
define('ADMIN_EMAIL', 'extern.alejandro.torres@grammer.com'); // <-- IMPORTANTE: Correo del administrador.

/**
 * Función para limpiar y sanitizar nombres de archivo.
 */
function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}

/**
 * Función robusta para procesar un archivo subido.
 */
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl;
    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($archivo['error'] !== UPLOAD_ERR_OK) throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");

    $directorioDestino = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/' . $subdirectorio;
    if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0775, true)) throw new Exception("Error fatal: No se pudo crear la carpeta: $subdirectorio");
    if (!is_writable($directorioDestino)) throw new Exception("Error de permisos: No se puede escribir en: $subdirectorio");

    $nombreOriginalLimpio = sanitizarNombreArchivo(basename($archivo['name']));
    $nombreUnico = $prefijo . uniqid() . '_' . $nombreOriginalLimpio;
    $rutaFisicaDestino = $directorioDestino . $nombreUnico;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisicaDestino)) throw new Exception("Falló la subida del archivo.");

    return $baseUrl . $subdirectorio . $nombreUnico;
}

// --- LÓGICA PRINCIPAL DEL SCRIPT ---
$response = ['status' => 'error', 'message' => 'Ocurrió un error inesperado.'];

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_SESSION['loggedin'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit();
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction();

try {
    $idMetodoParaGuardar = null;

    // 1. Procesar el Método de Trabajo (si se subió)
    if (isset($_FILES['metodoFile']) && $_FILES['metodoFile']['error'] === UPLOAD_ERR_OK) {
        if (empty(trim($_POST['tituloMetodo']))) throw new Exception("Debe proporcionar un nombre para el método de trabajo.");
        $tituloMetodo = trim($_POST['tituloMetodo']);
        $rutaMetodoPublica = procesarArchivoSubido($_FILES['metodoFile'], 'Metodos/', 'metodo_');

        $stmt_metodo = $conex->prepare("INSERT INTO Metodos (TituloMetodo, RutaArchivo, IdUsuarioCarga) VALUES (?, ?, ?)");
        $idUsuarioCarga = $_SESSION['user_id'];
        $stmt_metodo->bind_param("ssi", $tituloMetodo, $rutaMetodoPublica, $idUsuarioCarga);
        if (!$stmt_metodo->execute()) throw new Exception("Error al guardar el método: " . $stmt_metodo->error);

        $idMetodoParaGuardar = $stmt_metodo->insert_id;
        $stmt_metodo->close();
    }

    // 2. Insertar la Solicitud principal
    $stmt_solicitud = $conex->prepare(
        "INSERT INTO Solicitudes (IdUsuario, Responsable, NumeroParte, DescripcionParte, Cantidad, Descripcion, IdTerciaria, IdProvedor, IdLugar, IdEstatus, IdMetodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $idEstatusInicial = 1;
    $stmt_solicitud->bind_param("isssisiiiii", $_SESSION['user_id'], $_POST['responsable'], $_POST['numeroParte'], $_POST['descripcionParte'], $_POST['cantidad'], $_POST['descripcion'], $_POST['IdTerciaria'], $_POST['IdProvedor'], $_POST['IdLugar'], $idEstatusInicial, $idMetodoParaGuardar);
    if (!$stmt_solicitud->execute()) throw new Exception("Error al guardar la solicitud: " . $stmt_solicitud->error);

    $id_solicitud_nueva = $conex->insert_id;
    $stmt_solicitud->close();

    // 3. Procesar y guardar los defectos
    if (!isset($_POST['defectos']) || !is_array($_POST['defectos'])) throw new Exception('No se encontraron defectos para registrar.');

    foreach ($_POST['defectos'] as $key => $defecto) {
        $id_defecto_catalogo = $defecto['id'];
        if ($_FILES['defectos']['error'][$key]['foto_ok'] !== UPLOAD_ERR_OK || $_FILES['defectos']['error'][$key]['foto_nok'] !== UPLOAD_ERR_OK) throw new Exception("Faltan fotos o hay un error en la subida para el defecto.");

        $foto_ok_para_procesar = ['name' => $_FILES['defectos']['name'][$key]['foto_ok'], 'type' => $_FILES['defectos']['type'][$key]['foto_ok'], 'tmp_name' => $_FILES['defectos']['tmp_name'][$key]['foto_ok'], 'error' => $_FILES['defectos']['error'][$key]['foto_ok'], 'size' => $_FILES['defectos']['size'][$key]['foto_ok']];
        $foto_nok_para_procesar = ['name' => $_FILES['defectos']['name'][$key]['foto_nok'], 'type' => $_FILES['defectos']['type'][$key]['foto_nok'], 'tmp_name' => $_FILES['defectos']['tmp_name'][$key]['foto_nok'], 'error' => $_FILES['defectos']['error'][$key]['foto_nok'], 'size' => $_FILES['defectos']['size'][$key]['foto_nok']];

        $rutaFotoOk = procesarArchivoSubido($foto_ok_para_procesar, 'imagenes/imagenesDefectos/', "defecto_{$id_solicitud_nueva}_ok_");
        $rutaFotoNok = procesarArchivoSubido($foto_nok_para_procesar, 'imagenes/imagenesDefectos/', "defecto_{$id_solicitud_nueva}_nok_");

        $stmt_defecto = $conex->prepare("INSERT INTO Defectos (IdSolicitud, IdDefectoCatalogo, RutaFotoOk, RutaFotoNoOk) VALUES (?, ?, ?, ?)");
        $stmt_defecto->bind_param("iiss", $id_solicitud_nueva, $id_defecto_catalogo, $rutaFotoOk, $rutaFotoNok);
        if (!$stmt_defecto->execute()) throw new Exception("Error al guardar el defecto: " . $stmt_defecto->error);
        $stmt_defecto->close();
    }

    // 4. Si se guardó un método, notificar al administrador.
    if ($idMetodoParaGuardar !== null) {
        enviarNotificacionAdmin($id_solicitud_nueva, $_POST['numeroParte']);
    }

    // Si todo fue exitoso, confirmamos la transacción.
    $conex->commit();
    $response = ['status' => 'success', 'message' => 'Solicitud #' . $id_solicitud_nueva . ' guardada exitosamente.'];

} catch (Exception $e) {
    $conex->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

$conex->close();
echo json_encode($response);


/**
 * Función para enviar correo de notificación al administrador.
 */
function enviarNotificacionAdmin($idSolicitud, $numeroParte) {
    global $baseUrl;
    $folio = "S-" . str_pad($idSolicitud, 4, '0', STR_PAD_LEFT);
    $asunto = "Nuevo Método de Trabajo para Revisión - Folio $folio";
    $linkAprobacion = $baseUrl . "aprobar_metodos.php";
    $numeroParteLimpio = htmlspecialchars($numeroParte);
    $year = date('Y');

    // --- Plantilla de Correo Integrada ---
    $contenidoHTML = <<<HTML
    <!DOCTYPE html><html><head><style> @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap'); </style></head><body style='margin:0;padding:0;background-color:#f4f6f9;font-family:"Lato", Arial, sans-serif;'>
    <table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td style="padding:20px 0;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse:collapse;background-color:#ffffff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.08);border: 1px solid #dbe1e8;">
        <tr><td align="center" style="background-color:#4a6984;padding:25px;border-top-left-radius:12px;border-top-right-radius:12px;">
            <h1 style="color:#ffffff;margin:0;font-size:28px;">ARCA</h1>
        </td></tr>
        <tr><td style="padding:40px 30px;">
            <h2 style="color:#333333;margin-top:0;font-size:22px;">Acción Requerida: Nuevo Método de Trabajo</h2>
            <p style="color:#6c757d;line-height:1.6;">Hola Administrador,</p>
            <p style="color:#6c757d;line-height:1.6;">Se ha adjuntado un nuevo método de trabajo que requiere tu revisión para la solicitud con folio <strong style="color:#4a6984;">{$folio}</strong>.</p>
            
            <table border="0" cellpadding="10" cellspacing="0" width="100%" style="border-collapse:collapse;margin:25px 0;background-color:#f8f9fa;border-radius:8px;">
                <tr>
                    <td style="width:30%;font-weight:bold;color:#495057;border-bottom:1px solid #e9ecef;">No. de Parte:</td>
                    <td style="color:#212529;border-bottom:1px solid #e9ecef;">{$numeroParteLimpio}</td>
                </tr>
            </table>
            <p style="color:#6c757d;line-height:1.6;">Por favor, accede al panel de administración para revisarlo y tomar una acción (aprobar o rechazar).</p>
            <table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" style="padding:20px 0;">
                <a href="{$linkAprobacion}" target="_blank" style="font-size:16px;font-family:'Lato', Arial, sans-serif;color:#ffffff;text-decoration:none;background-color:#5c85ad;border-radius:8px;padding:15px 30px;display:inline-block;font-weight:bold;">Revisar Métodos Pendientes</a>
            </td></tr></table>
        </td></tr>
        <tr><td align="center" style="background-color:#e9ecef;padding:20px;font-size:12px;color:#6c757d;border-bottom-left-radius:12px;border-bottom-right-radius:12px;"><p style="margin:0;">&copy; {$year} ARCA Systems. Notificación automatizada.</p></td></tr>
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
        $mail->Body = $contenidoHTML;
        $mail->send();

    } catch (Exception $e) {
        // No detenemos el proceso si el correo falla, pero podríamos registrar el error.
        error_log("Correo de notificación a admin no enviado para Solicitud $idSolicitud. Error: {$mail->ErrorInfo}");
    }
}


