<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE BASE URL (Asegúrate de que esta sea la correcta para tu entorno) ---
$baseUrl = "https://grammermx.com/AleTest/ARCA/"; // Ajusta esta URL si es diferente

/**
 * Función para limpiar y sanitizar nombres de archivo.
 */
function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}

/**
 * Función robusta para procesar un archivo subido.
 * Recibe el array de $_FILES (o un sub-array en el caso de arrays de archivos),
 * el subdirectorio dentro de la raíz del proyecto, y un prefijo para el nombre único.
 * Devuelve la URL pública del archivo.
 *
 * @param array $archivo Array asociativo del archivo subido (ej. $_FILES['mi_archivo']).
 * @param string $subdirectorio Subdirectorio relativo a la raíz del proyecto (ej. 'imagenes/defectos/').
 * @param string $prefijo Prefijo para el nombre único del archivo.
 * @return string La URL pública del archivo.
 * @throws Exception Si hay errores en la subida o permisos.
 */
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl;

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        // En este contexto, si no hay archivo, es un error para nuevos defectos
        throw new Exception("No se ha seleccionado ningún archivo para el defecto.");
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

    // Se construye la ruta física desde la raíz del servidor.
    // Asumiendo que este script está en /ARCA/dao/ y los uploads en /ARCA/
    $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    $projectRoot = $documentRoot . '/AleTest/ARCA/'; // Ajusta esta ruta si tu proyecto ARCA no está en /AleTest/ARCA/
    $directorioDestino = $projectRoot . $subdirectorio;

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


$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Obtener datos del formulario principal (ReportesInspeccion)
    $idSolicitud = intval($_POST['idSolicitud']);
    $idReporte = isset($_POST['idReporte']) && $_POST['idReporte'] !== '' ? intval($_POST['idReporte']) : null; // Para edición
    $nombreInspector = trim($_POST['nombreInspector']);
    $fechaInspeccion = trim($_POST['fechaInspeccion']);

    // --- MODIFICACIÓN: Capturar el rango de hora como texto ---
    $rangoHora = trim($_POST['rangoHoraCompleto']);

    $piezasInspeccionadas = intval($_POST['piezasInspeccionadas']);
    $piezasAceptadas = intval($_POST['piezasAceptadas']);
    $piezasRetrabajadas = intval($_POST['piezasRetrabajadas']);
    $tiempoInspeccion = trim($_POST['tiempoInspeccion']);
    $comentarios = trim($_POST['comentarios']);
    $idTiempoMuerto = isset($_POST['idTiempoMuerto']) && $_POST['idTiempoMuerto'] !== '' ? intval($_POST['idTiempoMuerto']) : null;

    // --- MODIFICACIÓN: Actualizar validación de datos ---
    if (empty($nombreInspector) || empty($fechaInspeccion) || empty($rangoHora) || $piezasInspeccionadas < 0 || $piezasAceptadas < 0 || $piezasRetrabajadas < 0) {
        throw new Exception("Por favor, complete todos los campos requeridos y asegúrese de que las cantidades sean válidas.");
    }
    if ($piezasAceptadas > $piezasInspeccionadas) {
        throw new Exception("Las piezas aceptadas no pueden ser mayores que las piezas inspeccionadas.");
    }

    // Nueva validación para Piezas Retrabajadas
    $piezasRechazadasBrutas = $piezasInspeccionadas - $piezasAceptadas;
    if ($piezasRetrabajadas > $piezasRechazadasBrutas) {
        throw new Exception("Las piezas retrabajadas no pueden exceder las piezas rechazadas (" . $piezasRechazadasBrutas . ").");
    }

    // 2. Insertar/Actualizar en ReportesInspeccion
    $stmt_reporte = null;
    if ($idReporte) { // Modo edición (placeholder por ahora)
        throw new Exception("La funcionalidad de edición de reportes no está implementada en este script.");
    } else { // Modo inserción
        // --- MODIFICACIÓN: Se inserta en la nueva columna RangoHora y se deja IdRangoHora como NULL ---
        $stmt_reporte = $conex->prepare("INSERT INTO ReportesInspeccion (
                                            IdSolicitud, NombreInspector, FechaInspeccion, RangoHora, IdRangoHora,
                                            PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas,
                                            TiempoInspeccion, Comentarios, IdTiempoMuerto
                                        ) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)");
        // --- MODIFICACIÓN: Se ajusta el bind_param para usar la variable de texto y omitir el IdRangoHora ---
        $stmt_reporte->bind_param("isssiiiiss",
            $idSolicitud, $nombreInspector, $fechaInspeccion, $rangoHora,
            $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
            $tiempoInspeccion, $comentarios, $idTiempoMuerto);
    }

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al guardar el reporte de inspección: " . $stmt_reporte->error);
    }
    $lastIdReporte = $stmt_reporte->insert_id;
    $stmt_reporte->close();

    // 3. Procesar Defectos Originales (LÓGICA ACTUALIZADA PARA MÚLTIPLES LOTES)
    if (isset($_POST['defectos_originales']) && is_array($_POST['defectos_originales'])) {
        foreach ($_POST['defectos_originales'] as $idDefectoOriginal => $defectData) {
            if (isset($defectData['entries']) && is_array($defectData['entries'])) {
                foreach ($defectData['entries'] as $entry) {
                    $cantidad = intval($entry['cantidad']);
                    $lote = isset($entry['lote']) ? trim($entry['lote']) : null;

                    if ($cantidad > 0) {
                        $stmt_defecto_original = $conex->prepare("INSERT INTO ReporteDefectosOriginales (IdReporte, IdDefecto, CantidadEncontrada, Lote) VALUES (?, ?, ?, ?)");
                        $stmt_defecto_original->bind_param("iiis", $lastIdReporte, $idDefectoOriginal, $cantidad, $lote);
                        if (!$stmt_defecto_original->execute()) {
                            throw new Exception("Error al guardar defecto original #{$idDefectoOriginal} con lote {$lote}: " . $stmt_defecto_original->error);
                        }
                        $stmt_defecto_original->close();
                    }
                }
            }
        }
    }

    // 4. Procesar Nuevos Defectos Encontrados con la nueva lógica de subida de imágenes
    if (isset($_POST['nuevos_defectos']) && is_array($_POST['nuevos_defectos'])) {
        foreach ($_POST['nuevos_defectos'] as $tempId => $defectoData) {
            $idDefectoCatalogo = intval($defectoData['id']);
            $cantidad = intval($defectoData['cantidad']);

            if ($cantidad > 0) {
                // Preparamos el array de archivo para la función procesarArchivoSubido
                // Verificamos si la clave 'foto' existe en el array $_FILES['nuevos_defectos']['name'][$tempId]
                // Esto es necesario porque $_FILES se organiza de manera diferente cuando hay arrays de inputs de archivo.
                $foto_para_procesar = [
                    'name' => $_FILES['nuevos_defectos']['name'][$tempId]['foto'],
                    'type' => $_FILES['nuevos_defectos']['type'][$tempId]['foto'],
                    'tmp_name' => $_FILES['nuevos_defectos']['tmp_name'][$tempId]['foto'],
                    'error' => $_FILES['nuevos_defectos']['error'][$tempId]['foto'],
                    'size' => $_FILES['nuevos_defectos']['size'][$tempId]['foto']
                ];

                // Usar la función robusta para procesar el archivo
                // La ruta para guardar será 'imagenes/imagenesDefectos/' como en el otro script
                $rutaFotoEvidencia = procesarArchivoSubido($foto_para_procesar, 'imagenes/imagenesDefectos/', "nuevo_defecto_reporte_{$lastIdReporte}_");

                $stmt_nuevo_defecto = $conex->prepare("INSERT INTO DefectosEncontrados (IdReporte, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia) VALUES (?, ?, ?, ?)");
                $stmt_nuevo_defecto->bind_param("iiis", $lastIdReporte, $idDefectoCatalogo, $cantidad, $rutaFotoEvidencia);
                if (!$stmt_nuevo_defecto->execute()) {
                    // Si falla la inserción en DB, intentar borrar el archivo subido
                    // La función procesarArchivoSubido ya devuelve la URL pública, necesitamos la ruta física para unlink
                    $rutaFisicaToDelete = str_replace($baseUrl, $projectRoot, $rutaFotoEvidencia);
                    if (file_exists($rutaFisicaToDelete)) {
                        unlink($rutaFisicaToDelete);
                    }
                    throw new Exception("Error al guardar el nuevo defecto #{$tempId} en la base de datos: " . $stmt_nuevo_defecto->error);
                }
                $stmt_nuevo_defecto->close();
            }
        }
    }

    // Si todo salió bien, confirmamos la transacción
    $conex->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reporte de inspección guardado exitosamente.']);

} catch (Exception $e) {
    // Si algo falló, revertimos todos los cambios.
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conex->close();
?>

