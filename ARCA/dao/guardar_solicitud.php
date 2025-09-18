<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

// Incluimos los archivos necesarios. Asegúrate de que las rutas sean correctas desde la carpeta 'php'.
include_once("verificar_sesion.php");
include_once("conexionArca.php");

// Le decimos al navegador que la respuesta será en formato JSON.
header('Content-Type: application/json');

// --- CONFIGURACIÓN ---
// ¡IMPORTANTE! Cambia esto a la URL base de tu proyecto.
$baseUrl = "https://grammermx.com/AleTest/ARCA";

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

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

    $directorioDestino = $_SERVER['DOCUMENT_ROOT'] . '/AleTest/ARCA/' . $subdirectorio;

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

    // 1. Procesar el Método de Trabajo (archivo PDF opcional)
    if (isset($_FILES['metodoFile']) && $_FILES['metodoFile']['error'] === UPLOAD_ERR_OK) {

        // --- CAMBIO AQUÍ: Recibimos y validamos el nuevo campo 'tituloMetodo' ---
        if (empty(trim($_POST['tituloMetodo']))) {
            throw new Exception("Si adjuntas un método de trabajo, debes proporcionar un nombre para el método.");
        }
        $tituloMetodo = trim($_POST['tituloMetodo']);

        $rutaMetodoPublica = procesarArchivoSubido($_FILES['metodoFile'], 'Metodos/', 'metodo_');

        $stmt_metodo = $conex->prepare(
            "INSERT INTO Metodos (TituloMetodo, RutaArchivo, IdUsuarioCarga) VALUES (?, ?, ?)"
        );

        $idUsuarioCarga = $_SESSION['user_id'];

        // --- CAMBIO AQUÍ: Usamos el título que nos envió el usuario ---
        $stmt_metodo->bind_param("ssi", $tituloMetodo, $rutaMetodoPublica, $idUsuarioCarga);

        if (!$stmt_metodo->execute()) {
            throw new Exception("Error al guardar el método en la base de datos: " . $stmt_metodo->error);
        }

        $idMetodoParaGuardar = $stmt_metodo->insert_id;
        $stmt_metodo->close();
    }

    // 2. Insertar los datos principales en la tabla `Solicitudes`.
    $stmt_solicitud = $conex->prepare(
        "INSERT INTO Solicitudes (IdUsuario, Responsable, NumeroParte, Cantidad, Descripcion, IdTerciaria, IdCommodity, IdProvedor, IdEstatus, IdMetodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $idEstatusInicial = 1;

    $stmt_solicitud->bind_param("isssisiiii",
        $_SESSION['user_id'],
        $_POST['responsable'],
        $_POST['numeroParte'],
        $_POST['cantidad'],
        $_POST['descripcion'],
        $_POST['IdTerciaria'],
        $_POST['IdCommodity'],
        $_POST['IdProvedor'],
        $idEstatusInicial,
        $idMetodoParaGuardar
    );

    if (!$stmt_solicitud->execute()) {
        throw new Exception("Error al guardar la solicitud principal: " . $stmt_solicitud->error);
    }
    $id_solicitud_nueva = $conex->insert_id;
    $stmt_solicitud->close();

    // 3. Procesar y guardar los defectos y sus imágenes.
    if (!isset($_POST['defectos']) || !is_array($_POST['defectos'])) {
        throw new Exception('No se encontraron defectos para registrar.');
    }

    foreach ($_POST['defectos'] as $key => $defecto) {
        $nombre_defecto = trim($defecto['nombre']);

        if ($_FILES['defectos']['error'][$key]['foto_ok'] !== UPLOAD_ERR_OK || $_FILES['defectos']['error'][$key]['foto_nok'] !== UPLOAD_ERR_OK) {
            throw new Exception("Faltan fotos o hay un error en la subida para el defecto: " . htmlspecialchars($nombre_defecto));
        }

        $rutaFotoOk = procesarArchivoSubido($_FILES['defectos']['tmp_name'][$key]['foto_ok'], 'imagenes/imagenesDefectos/', "defecto_{$id_solicitud_nueva}_ok_");
        $rutaFotoNok = procesarArchivoSubido($_FILES['defectos']['tmp_name'][$key]['foto_nok'], 'imagenes/imagenesDefectos/', "defecto_{$id_solicitud_nueva}_nok_");

        $stmt_defecto = $conex->prepare(
            "INSERT INTO Defectos (IdSolicitud, NombreDefecto, RutaFotoOk, RutaFotoNoOk) VALUES (?, ?, ?, ?)"
        );
        $stmt_defecto->bind_param("isss", $id_solicitud_nueva, $nombre_defecto, $rutaFotoOk, $rutaFotoNok);

        if (!$stmt_defecto->execute()) {
            throw new Exception("Error al guardar el defecto: " . $stmt_defecto->error);
        }
        $stmt_defecto->close();
    }

    // Si todo fue exitoso, confirmamos la transacción.
    $conex->commit();
    $response = ['status' => 'success', 'message' => 'Solicitud #' . $id_solicitud_nueva . ' guardada exitosamente.'];

} catch (Exception $e) {
    // Si algo falló, revertimos todos los cambios.
    $conex->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// Cerramos la conexión y enviamos la respuesta JSON.
$conex->close();
echo json_encode($response);
?>
