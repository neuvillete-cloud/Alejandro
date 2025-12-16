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
    // Validar campos obligatorios específicos de Cero Defectos
    if (empty($_POST['linea']) || empty($_POST['oem'])) {
        throw new Exception("Los campos Línea y OEM son obligatorios.");
    }

    $tituloInstruccionParaGuardar = null;
    $rutaInstruccionPublica = null;

    // 1. Procesar la Instrucción de Trabajo (archivo PDF opcional)
    if (isset($_FILES['fileInstruccion']) && $_FILES['fileInstruccion']['error'] === UPLOAD_ERR_OK) {
        if (empty(trim($_POST['tituloInstruccion']))) {
            throw new Exception("Si adjuntas una instrucción, debes proporcionar un nombre para el documento.");
        }
        $tituloInstruccionParaGuardar = trim($_POST['tituloInstruccion']);

        $rutaInstruccionPublica = procesarArchivoSubido(
            $_FILES['fileInstruccion'],
            'CeroDefectos/Instrucciones/',
            'zd_instruccion_'
        );

        if ($rutaInstruccionPublica === null) {
            throw new Exception("Hubo un problema al procesar el archivo de instrucción.");
        }
    }

    // 2. Insertar los datos principales en la tabla `CeroDefectosSolicitudes`.
    // CAMBIO: Ahora insertamos en IdOEM en lugar de IdProvedor
    $stmt_solicitud = $conex->prepare(
        "INSERT INTO CeroDefectosSolicitudes (IdUsuario, Linea, IdOEM, Cliente, TituloInstruccion, RutaInstruccion, IdEstatus) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $idUsuario = $_SESSION['user_id'];
    $linea = $_POST['linea'];
    $idOEM = $_POST['oem']; // Este valor viene del <select> y ahora es el IdOEM de la tabla nueva
    $cliente = $_POST['cliente'];
    $defaultEstatus = 1; // 1 = 'Recibido'

    $stmt_solicitud->bind_param("isisssi",
        $idUsuario,
        $linea,
        $idOEM,
        $cliente,
        $tituloInstruccionParaGuardar,
        $rutaInstruccionPublica,
        $defaultEstatus
    );

    if (!$stmt_solicitud->execute()) {
        throw new Exception("Error al guardar el registro de Cero Defectos: " . $stmt_solicitud->error);
    }
    $id_nuevo_registro = $conex->insert_id;
    $stmt_solicitud->close();

    $conex->commit();
    $response = ['status' => 'success', 'message' => 'Registro Cero Defectos #' . $id_nuevo_registro . ' guardado exitosamente.'];

} catch (Exception $e) {
    $conex->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

$conex->close();
echo json_encode($response);
?>
