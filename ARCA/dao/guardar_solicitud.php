<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

// Incluimos los archivos necesarios.
// Las rutas asumen que este archivo está en /dao/ y los otros en /dao/ también.
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
 */
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl;

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

    // Se construye la ruta física desde la raíz del servidor.
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
$conex->begin_transaction();

try {
    $idMetodoParaGuardar = null;

    // 1. Procesar el Método de Trabajo (archivo PDF opcional)
    if (isset($_FILES['metodoFile']) && $_FILES['metodoFile']['error'] === UPLOAD_ERR_OK) {
        if (empty(trim($_POST['tituloMetodo']))) {
            throw new Exception("Si adjuntas un método de trabajo, debes proporcionar un nombre para el método.");
        }
        $tituloMetodo = trim($_POST['tituloMetodo']);
        $rutaMetodoPublica = procesarArchivoSubido($_FILES['metodoFile'], 'Metodos/', 'metodo_');
        $stmt_metodo = $conex->prepare("INSERT INTO Metodos (TituloMetodo, RutaArchivo, IdUsuarioCarga) VALUES (?, ?, ?)");
        $idUsuarioCarga = $_SESSION['user_id'];
        $stmt_metodo->bind_param("ssi", $tituloMetodo, $rutaMetodoPublica, $idUsuarioCarga);
        if (!$stmt_metodo->execute()) {
            throw new Exception("Error al guardar el método en la base de datos: " . $stmt_metodo->error);
        }
        $idMetodoParaGuardar = $stmt_metodo->insert_id;
        $stmt_metodo->close();
    }

    // 2. Insertar los datos principales en la tabla `Solicitudes`.
    $stmt_solicitud = $conex->prepare(
        "INSERT INTO Solicitudes (IdUsuario, Responsable, NumeroParte, DescripcionParte, Cantidad, Descripcion, IdTerciaria, IdProvedor, IdLugar, IdEstatus, IdMetodo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $idEstatusInicial = 1;

    $stmt_solicitud->bind_param("isssisiiiii",
        $_SESSION['user_id'],
        $_POST['responsable'],
        $_POST['numeroParte'],
        $_POST['descripcionParte'],
        $_POST['cantidad'],
        $_POST['descripcion'],
        $_POST['IdTerciaria'],
        $_POST['IdProvedor'],
        $_POST['IdLugar'],
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
        // --- CAMBIO AQUÍ: Ahora recibimos el ID del defecto desde el <select>
        $id_defecto_catalogo = $defecto['id'];

        if ($_FILES['defectos']['error'][$key]['foto_ok'] !== UPLOAD_ERR_OK || $_FILES['defectos']['error'][$key]['foto_nok'] !== UPLOAD_ERR_OK) {
            throw new Exception("Faltan fotos o hay un error en la subida para el defecto seleccionado.");
        }

        $foto_ok_para_procesar = [
            'name' => $_FILES['defectos']['name'][$key]['foto_ok'], 'type' => $_FILES['defectos']['type'][$key]['foto_ok'],
            'tmp_name' => $_FILES['defectos']['tmp_name'][$key]['foto_ok'], 'error' => $_FILES['defectos']['error'][$key]['foto_ok'],
            'size' => $_FILES['defectos']['size'][$key]['foto_ok']
        ];
        $foto_nok_para_procesar = [
            'name' => $_FILES['defectos']['name'][$key]['foto_nok'], 'type' => $_FILES['defectos']['type'][$key]['foto_nok'],
            'tmp_name' => $_FILES['defectos']['tmp_name'][$key]['foto_nok'], 'error' => $_FILES['defectos']['error'][$key]['foto_nok'],
            'size' => $_FILES['defectos']['size'][$key]['foto_nok']
        ];

        $rutaFotoOk = procesarArchivoSubido($foto_ok_para_procesar, 'imagenes/imagenesDefectos/', "defecto_{$id_solicitud_nueva}_ok_");
        $rutaFotoNok = procesarArchivoSubido($foto_nok_para_procesar, 'imagenes/imagenesDefectos/', "defecto_{$id_solicitud_nueva}_nok_");

        // --- CAMBIO AQUÍ: Se actualizó la consulta INSERT para la tabla Defectos
        $stmt_defecto = $conex->prepare(
            "INSERT INTO Defectos (IdSolicitud, IdDefectoCatalogo, RutaFotoOk, RutaFotoNoOk) VALUES (?, ?, ?, ?)"
        );
        // --- CAMBIO AQUÍ: Se actualizó el bind_param
        $stmt_defecto->bind_param("iiss", $id_solicitud_nueva, $id_defecto_catalogo, $rutaFotoOk, $rutaFotoNok);

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