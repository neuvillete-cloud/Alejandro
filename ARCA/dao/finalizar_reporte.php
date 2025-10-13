<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE BASE URL ---
$baseUrl = "https://grammermx.com/AleTest/ARCA/";

function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}

function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl;

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("No se ha seleccionado ningún archivo para el defecto.");
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

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
$conex->begin_transaction();

try {
    $idSolicitud = intval($_POST['idSolicitud']);
    $tiempoTotalInspeccion = isset($_POST['tiempoTotalInspeccion']) ? trim($_POST['tiempoTotalInspeccion']) : null;

    // CASO 2: Se está finalizando y guardando un reporte de sesión al mismo tiempo.
    if (isset($_POST['piezasInspeccionadas']) && $_POST['piezasInspeccionadas'] !== '') {
        // --- Lógica para guardar el reporte de sección (adaptada de guardar_reporte.php) ---

        $stmt_solicitud_info = $conex->prepare("SELECT NumeroParte FROM Solicitudes WHERE IdSolicitud = ?");
        $stmt_solicitud_info->bind_param("i", $idSolicitud);
        $stmt_solicitud_info->execute();
        $result_solicitud = $stmt_solicitud_info->get_result()->fetch_assoc();
        $isVariosPartes = (strtolower($result_solicitud['NumeroParte']) === 'varios');
        $stmt_solicitud_info->close();

        $nombreInspector = trim($_POST['nombreInspector']);
        $fechaInspeccion = trim($_POST['fechaInspeccion']);
        $rangoHora = trim($_POST['rangoHoraCompleto']);
        $piezasInspeccionadas = intval($_POST['piezasInspeccionadas']);
        $piezasAceptadas = intval($_POST['piezasAceptadas']);
        $piezasRetrabajadas = intval($_POST['piezasRetrabajadas']);
        $tiempoInspeccionSesion = trim($_POST['tiempoInspeccion']);
        $comentarios = trim($_POST['comentarios']);
        $idTiempoMuerto = isset($_POST['idTiempoMuerto']) && $_POST['idTiempoMuerto'] !== '' ? intval($_POST['idTiempoMuerto']) : null;

        $stmt_reporte = $conex->prepare("INSERT INTO ReportesInspeccion (
                                            IdSolicitud, NombreInspector, FechaInspeccion, RangoHora,
                                            PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas,
                                            TiempoInspeccion, Comentarios, IdTiempoMuerto
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_reporte->bind_param("isssiiissi",
            $idSolicitud, $nombreInspector, $fechaInspeccion, $rangoHora,
            $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
            $tiempoInspeccionSesion, $comentarios, $idTiempoMuerto);

        if (!$stmt_reporte->execute()) {
            throw new Exception("Error al guardar el reporte de inspección: " . $stmt_reporte->error);
        }
        $lastIdReporte = $stmt_reporte->insert_id;
        $stmt_reporte->close();

        // Procesar desglose de partes si aplica
        if ($isVariosPartes && isset($_POST['partes_inspeccionadas']) && is_array($_POST['partes_inspeccionadas'])) {
            foreach ($_POST['partes_inspeccionadas'] as $parteData) {
                $numeroParte = isset($parteData['parte']) ? trim($parteData['parte']) : null;
                $cantidad = isset($parteData['cantidad']) ? intval($parteData['cantidad']) : 0;
                if ($cantidad > 0 && !empty($numeroParte)) {
                    $stmt_desglose = $conex->prepare("INSERT INTO ReporteDesglosePartes (IdReporte, NumeroParte, Cantidad) VALUES (?, ?, ?)");
                    $stmt_desglose->bind_param("isi", $lastIdReporte, $numeroParte, $cantidad);
                    if (!$stmt_desglose->execute()) throw new Exception("Error al guardar el desglose para el número de parte {$numeroParte}.");
                    $stmt_desglose->close();
                }
            }
        }

        // Procesar Defectos Originales
        if (isset($_POST['defectos_originales']) && is_array($_POST['defectos_originales'])) {
            foreach ($_POST['defectos_originales'] as $idDefectoOriginal => $defectData) {
                if (isset($defectData['entries']) && is_array($defectData['entries'])) {
                    foreach ($defectData['entries'] as $entry) {
                        $cantidad = intval($entry['cantidad']);
                        if ($cantidad > 0) {
                            $lote = isset($entry['lote']) ? trim($entry['lote']) : null;
                            $parte = ($isVariosPartes && isset($entry['parte'])) ? trim($entry['parte']) : null;
                            $stmt_def_orig = $conex->prepare("INSERT INTO ReporteDefectosOriginales (IdReporte, IdDefecto, CantidadEncontrada, Lote, NumeroParte) VALUES (?, ?, ?, ?, ?)");
                            $stmt_def_orig->bind_param("iiiss", $lastIdReporte, $idDefectoOriginal, $cantidad, $lote, $parte);
                            if (!$stmt_def_orig->execute()) throw new Exception("Error al guardar defecto original #{$idDefectoOriginal}.");
                            $stmt_def_orig->close();
                        }
                    }
                }
            }
        }

        // Procesar Nuevos Defectos Encontrados
        if (isset($_POST['nuevos_defectos']) && is_array($_POST['nuevos_defectos'])) {
            foreach ($_POST['nuevos_defectos'] as $tempId => $defectoData) {
                $cantidad = intval($defectoData['cantidad']);
                if ($cantidad > 0) {
                    $idDefectoCatalogo = intval($defectoData['id']);
                    $parte = ($isVariosPartes && isset($defectoData['parte'])) ? trim($defectoData['parte']) : null;
                    $foto_para_procesar = ['name'=>$_FILES['nuevos_defectos']['name'][$tempId]['foto'], 'type'=>$_FILES['nuevos_defectos']['type'][$tempId]['foto'], 'tmp_name'=>$_FILES['nuevos_defectos']['tmp_name'][$tempId]['foto'], 'error'=>$_FILES['nuevos_defectos']['error'][$tempId]['foto'], 'size'=>$_FILES['nuevos_defectos']['size'][$tempId]['foto']];
                    $rutaFotoEvidencia = procesarArchivoSubido($foto_para_procesar, 'imagenes/imagenesDefectos/', "nuevo_defecto_reporte_{$lastIdReporte}_");
                    $stmt_nuevo_defecto = $conex->prepare("INSERT INTO DefectosEncontrados (IdReporte, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia, NumeroParte) VALUES (?, ?, ?, ?, ?)");
                    $stmt_nuevo_defecto->bind_param("iiiss", $lastIdReporte, $idDefectoCatalogo, $cantidad, $rutaFotoEvidencia, $parte);
                    if (!$stmt_nuevo_defecto->execute()) {
                        $rutaFisicaToDelete = str_replace($baseUrl, rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/', $rutaFotoEvidencia);
                        if (file_exists($rutaFisicaToDelete)) unlink($rutaFisicaToDelete);
                        throw new Exception("Error al guardar el nuevo defecto #{$tempId}.");
                    }
                    $stmt_nuevo_defecto->close();
                }
            }
        }
    }

    // --- LÓGICA DE FINALIZACIÓN (se ejecuta en ambos casos) ---
    if (empty($tiempoTotalInspeccion)) {
        throw new Exception("El tiempo total de inspección es requerido para finalizar.");
    }

    $stmt_finalizar = $conex->prepare("UPDATE Solicitudes SET IdEstatus = 4, TiempoTotalInspeccion = ? WHERE IdSolicitud = ?");
    $stmt_finalizar->bind_param("si", $tiempoTotalInspeccion, $idSolicitud);

    if (!$stmt_finalizar->execute()) {
        throw new Exception("Error al finalizar la solicitud: " . $stmt_finalizar->error);
    }
    $stmt_finalizar->close();

    $conex->commit();
    echo json_encode(['status' => 'success', 'message' => 'Contención finalizada y reporte guardado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conex->close();
?>
