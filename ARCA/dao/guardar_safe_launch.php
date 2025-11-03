<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

// Incluimos los archivos necesarios.
include_once("verificar_sesion.php");
include_once("conexionArca.php");

// Le decimos al navegador que la respuesta será en formato JSON.
header('Content-Type: application/json');

// --- CONFIGURACIÓN ---
// ¡IMPORTANTE! Asegúrate de que esta sea la URL base de tu proyecto.
$baseUrl = "https://grammermx.com/AleTest/ARCA/";

/**
 * Función para limpiar y sanitizar nombres de archivo.
 */
function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}

/**
 * Función robusta para procesar un archivo subido.
 * Esta función es la MISMA que la de 'guardar_solicitud.php'
 */
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl;

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No hay archivo, no es un error.
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

    // Se construye la ruta física desde la raíz del servidor.
    // (Asegúrate de que /AleTest/ARCA/ sea la ruta correcta desde DOCUMENT_ROOT)
    $directorioDestino = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/' . $subdirectorio;

    if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0775, true)) {
        throw new Exception("Error fatal: No se pudo crear la carpeta de destino: $subdirectorio");
    }
    if (!is_writable($directorioDestino)) {
        throw new Exception("Error de permisos: El servidor no puede escribir en la carpeta: $subdirectorio");
    }

    $nombreOriginalLimpio = sanitizarNombreArchivo(basename($archivo['name']));
    $nombreUnico = $prefijo . uniqid() . '_' . $nombreOriginalLimpio;
    $rutaFisicaDestino = $directorioDestino . $nombreUnico;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaFisicaDestino)) {
        throw new Exception("Falló la subida del archivo. No se pudo mover a la carpeta de destino.");
    }

    // Devolvemos la URL pública que se guardará en la base de datos.
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
$conex->begin_transaction(); // Iniciamos la transacción

try {
    $tituloInstruccionParaGuardar = null;
    $rutaInstruccionPublica = null;

    // 1. Procesar la Instrucción de Trabajo (archivo PDF opcional)
    if (isset($_FILES['fileInstruccion']) && $_FILES['fileInstruccion']['error'] === UPLOAD_ERR_OK) {
        // Si se sube un archivo, el título es obligatorio (validado en front, pero re-validamos)
        if (empty(trim($_POST['tituloInstruccion']))) {
            throw new Exception("Si adjuntas una instrucción, debes proporcionar un nombre para el documento.");
        }
        $tituloInstruccionParaGuardar = trim($_POST['tituloInstruccion']);

        // Guardamos en una carpeta nueva para Safe Launch
        $rutaInstruccionPublica = procesarArchivoSubido(
            $_FILES['fileInstruccion'],
            'SafeLaunch/Instrucciones/', // Nueva carpeta
            'sl_instruccion_'
        );

        if ($rutaInstruccionPublica === null) {
            throw new Exception("Hubo un problema al procesar el archivo de instrucción.");
        }
    }

    // 2. Insertar los datos principales en la tabla `SafeLaunchSolicitudes`.
    // --- CAMBIO AQUÍ: Se añade IdEstatus a la consulta ---
    $stmt_solicitud = $conex->prepare(
        "INSERT INTO SafeLaunchSolicitudes (IdUsuario, NombreProyecto, Cliente, TituloInstruccion, RutaInstruccion, IdEstatus) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    // Asumimos que 'user_id' está en la sesión, igual que en tu script base
    $idUsuario = $_SESSION['user_id'];
    // --- CAMBIO AQUÍ: Definimos el estatus por defecto (1 = 'Recibido') ---
    $defaultEstatus = 1;

    // --- CAMBIO AQUÍ: Se actualiza el bind_param de 'issss' a 'issssi' y se añade $defaultEstatus ---
    $stmt_solicitud->bind_param("issssi",
        $idUsuario,
        $_POST['nombreProyecto'],
        $_POST['cliente'],
        $tituloInstruccionParaGuardar, // Será NULL si no se subió archivo
        $rutaInstruccionPublica,     // Será NULL si no se subió archivo
        $defaultEstatus              // Estatus por defecto 'Recibido'
    );

    if (!$stmt_solicitud->execute()) {
        throw new Exception("Error al guardar la solicitud de Safe Launch: " . $stmt_solicitud->error);
    }
    $id_safelaunch_nuevo = $conex->insert_id; // Obtenemos el ID de la solicitud recién creada
    $stmt_solicitud->close();

    // 3. Procesar y guardar los defectos.
    if (!isset($_POST['defectos']) || !is_array($_POST['defectos']) || empty($_POST['defectos'])) {
        throw new Exception('No se registraron defectos para este Safe Launch.');
    }

    // Preparamos la consulta UNA VEZ, fuera del bucle
    $stmt_defecto = $conex->prepare(
        "INSERT INTO SafeLaunchDefectos (IdSafeLaunch, IdSLDefectoCatalogo) VALUES (?, ?)"
    );

    foreach ($_POST['defectos'] as $key => $defecto) {
        // Obtenemos el ID del catálogo de defectos de SL
        $id_defecto_catalogo_sl = $defecto['id'];

        // Vinculamos los parámetros y ejecutamos
        $stmt_defecto->bind_param("ii", $id_safelaunch_nuevo, $id_defecto_catalogo_sl);

        if (!$stmt_defecto->execute()) {
            throw new Exception("Error al guardar un defecto del Safe Launch: " . $stmt_defecto->error);
        }
    }
    $stmt_defecto->close();

    // Si todo fue exitoso, confirmamos la transacción.
    $conex->commit();
    $response = ['status' => 'success', 'message' => 'Safe Launch #' . $id_safelaunch_nuevo . ' guardado exitosamente.'];

} catch (Exception $e) {
    // Si algo falló, revertimos todos los cambios.
    $conex->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// Cerramos la conexión y enviamos la respuesta JSON.
$conex->close();
echo json_encode($response);
?>
