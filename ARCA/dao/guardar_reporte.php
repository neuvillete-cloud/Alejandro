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
 * Devuelve la URL pública del archivo.
 */
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl;

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("No se ha seleccionado ningún archivo para el defecto.");
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

    // Asumiendo que este script está en /ARCA/dao/ y los uploads en /ARCA/
    $projectRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/';
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

    return $baseUrl . $subdirectorio . $nombreUnico;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Obtener datos del formulario principal (ReportesInspeccion)
    $idSolicitud = intval($_POST['idSolicitud']);

    // --- Determinar si la solicitud es de "Varios" números de parte desde la BD ---
    $stmt_solicitud_info = $conex->prepare("SELECT NumeroParte FROM Solicitudes WHERE IdSolicitud = ?");
    $stmt_solicitud_info->bind_param("i", $idSolicitud);
    $stmt_solicitud_info->execute();
    $result_solicitud = $stmt_solicitud_info->get_result()->fetch_assoc();
    $isVariosPartes = (strtolower($result_solicitud['NumeroParte']) === 'varios');
    $stmt_solicitud_info->close();

    $idReporte = isset($_POST['idReporte']) && $_POST['idReporte'] !== '' ? intval($_POST['idReporte']) : null;
    $nombreInspector = trim($_POST['nombreInspector']);
    $fechaInspeccion = trim($_POST['fechaInspeccion']);
    $rangoHora = trim($_POST['rangoHoraCompleto']);
    $piezasInspeccionadas = intval($_POST['piezasInspeccionadas']);
    $piezasAceptadas = intval($_POST['piezasAceptadas']);
    $piezasRetrabajadas = intval($_POST['piezasRetrabajadas']);
    $tiempoInspeccion = trim($_POST['tiempoInspeccion']);
    $comentarios = trim($_POST['comentarios']);
    $idTiempoMuerto = isset($_POST['idTiempoMuerto']) && $_POST['idTiempoMuerto'] !== '' ? intval($_POST['idTiempoMuerto']) : null;

    if (empty($nombreInspector) || empty($fechaInspeccion) || empty($rangoHora) || $piezasInspeccionadas < 0 || $piezasAceptadas < 0 || $piezasRetrabajadas < 0) {
        throw new Exception("Por favor, complete todos los campos requeridos y asegúrese de que las cantidades sean válidas.");
    }
    if ($piezasAceptadas > $piezasInspeccionadas) {
        throw new Exception("Las piezas aceptadas no pueden ser mayores que las piezas inspeccionadas.");
    }
    $piezasRechazadasBrutas = $piezasInspeccionadas - $piezasAceptadas;
    if ($piezasRetrabajadas > $piezasRechazadasBrutas) {
        throw new Exception("Las piezas retrabajadas no pueden exceder las piezas rechazadas (" . $piezasRechazadasBrutas . ").");
    }

    // 2. Insertar en ReportesInspeccion
    $stmt_reporte = $conex->prepare("INSERT INTO ReportesInspeccion (
                                        IdSolicitud, NombreInspector, FechaInspeccion, RangoHora,
                                        PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas,
                                        TiempoInspeccion, Comentarios, IdTiempoMuerto
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_reporte->bind_param("isssiiissi",
        $idSolicitud, $nombreInspector, $fechaInspeccion, $rangoHora,
        $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
        $tiempoInspeccion, $comentarios, $idTiempoMuerto);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al guardar el reporte de inspección: " . $stmt_reporte->error);
    }
    $lastIdReporte = $stmt_reporte->insert_id;
    $stmt_reporte->close();

    // --- NUEVO: Actualizar el estatus de la solicitud a "En Proceso" (3) ---
    // Se verifica si es el primer reporte para esta solicitud.
    $check_stmt = $conex->prepare("SELECT COUNT(IdReporte) FROM ReportesInspeccion WHERE IdSolicitud = ?");
    $check_stmt->bind_param("i", $idSolicitud);
    $check_stmt->execute();
    $report_count = $check_stmt->get_result()->fetch_row()[0];
    $check_stmt->close();

    // Si el conteo es 1, significa que el que acabamos de insertar es el primero.
    if ($report_count == 1) {
        $update_status_stmt = $conex->prepare("UPDATE Solicitudes SET IdEstatus = 3 WHERE IdSolicitud = ?");
        $update_status_stmt->bind_param("i", $idSolicitud);
        if (!$update_status_stmt->execute()) {
            // Opcional: lanzar una excepción si falla la actualización del estatus
            // throw new Exception("Error al actualizar el estatus de la solicitud.");
        }
        $update_status_stmt->close();
    }
    // --- FIN DEL NUEVO BLOQUE ---

    // 2.5. Procesar desglose de partes si aplica
    if ($isVariosPartes && isset($_POST['partes_inspeccionadas']) && is_array($_POST['partes_inspeccionadas'])) {
        foreach ($_POST['partes_inspeccionadas'] as $parteData) {
            $numeroParte = isset($parteData['parte']) ? trim($parteData['parte']) : null;
            $cantidad = isset($parteData['cantidad']) ? intval($parteData['cantidad']) : 0;

            if ($cantidad > 0 && !empty($numeroParte)) {
                $stmt_desglose = $conex->prepare("INSERT INTO ReporteDesglosePartes (IdReporte, NumeroParte, Cantidad) VALUES (?, ?, ?)");
                $stmt_desglose->bind_param("isi", $lastIdReporte, $numeroParte, $cantidad);
                if (!$stmt_desglose->execute()) {
                    throw new Exception("Error al guardar el desglose para el número de parte {$numeroParte}: " . $stmt_desglose->error);
                }
                $stmt_desglose->close();
            }
        }
    }

    // 3. Procesar Defectos Originales
    if (isset($_POST['defectos_originales']) && is_array($_POST['defectos_originales'])) {
        foreach ($_POST['defectos_originales'] as $idDefectoOriginal => $defectData) {
            if (isset($defectData['entries']) && is_array($defectData['entries'])) {
                foreach ($defectData['entries'] as $entry) {
                    $cantidad = intval($entry['cantidad']);
                    if ($cantidad > 0) {
                        $lote = isset($entry['lote']) ? trim($entry['lote']) : null;
                        $parte = ($isVariosPartes && isset($entry['parte'])) ? trim($entry['parte']) : null;
                        $stmt_defecto_original = $conex->prepare("INSERT INTO ReporteDefectosOriginales (IdReporte, IdDefecto, CantidadEncontrada, Lote, NumeroParte) VALUES (?, ?, ?, ?, ?)");
                        $stmt_defecto_original->bind_param("iiiss", $lastIdReporte, $idDefectoOriginal, $cantidad, $lote, $parte);
                        if (!$stmt_defecto_original->execute()) {
                            throw new Exception("Error al guardar defecto original #{$idDefectoOriginal} con lote {$lote}: " . $stmt_defecto_original->error);
                        }
                        $stmt_defecto_original->close();
                    }
                }
            }
        }
    }

    // 4. Procesar Nuevos Defectos Encontrados
    if (isset($_POST['nuevos_defectos']) && is_array($_POST['nuevos_defectos'])) {
        foreach ($_POST['nuevos_defectos'] as $tempId => $defectoData) {
            $cantidad = intval($defectoData['cantidad']);
            if ($cantidad > 0) {
                $idDefectoCatalogo = intval($defectoData['id']);
                $parte = ($isVariosPartes && isset($defectoData['parte'])) ? trim($defectoData['parte']) : null;

                $foto_para_procesar = [
                    'name' => $_FILES['nuevos_defectos']['name'][$tempId]['foto'],
                    'type' => $_FILES['nuevos_defectos']['type'][$tempId]['foto'],
                    'tmp_name' => $_FILES['nuevos_defectos']['tmp_name'][$tempId]['foto'],
                    'error' => $_FILES['nuevos_defectos']['error'][$tempId]['foto'],
                    'size' => $_FILES['nuevos_defectos']['size'][$tempId]['foto']
                ];

                $rutaFotoEvidencia = procesarArchivoSubido($foto_para_procesar, 'imagenes/imagenesDefectos/', "nuevo_defecto_reporte_{$lastIdReporte}_");

                $stmt_nuevo_defecto = $conex->prepare("INSERT INTO DefectosEncontrados (IdReporte, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia, NumeroParte) VALUES (?, ?, ?, ?, ?)");
                $stmt_nuevo_defecto->bind_param("iiiss", $lastIdReporte, $idDefectoCatalogo, $cantidad, $rutaFotoEvidencia, $parte);

                if (!$stmt_nuevo_defecto->execute()) {
                    $rutaFisicaToDelete = str_replace($baseUrl, rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/', $rutaFotoEvidencia);
                    if (file_exists($rutaFisicaToDelete)) {
                        unlink($rutaFisicaToDelete);
                    }
                    throw new Exception("Error al guardar el nuevo defecto #{$tempId} en la base de datos: " . $stmt_nuevo_defecto->error);
                }
                $stmt_nuevo_defecto->close();
            }
        }
    }

    $conex->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reporte de inspección guardado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conex->close();
?>

