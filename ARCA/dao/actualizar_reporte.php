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
$baseUrl = "https://grammermx.com/AleTest/ARCA/"; // Ajusta esta URL si es diferente
$projectRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/'; // Ajusta esta ruta

/**
 * Función para sanitizar nombres de archivo.
 */
function sanitizarNombreArchivo($nombre) {
    $nombre = preg_replace('/[^a-zA-Z0-9.\-_]/', '_', $nombre);
    return preg_replace('/_+/', '_', $nombre);
}

/**
 * Función para procesar un archivo subido.
 */
function procesarArchivoSubido($archivo, $subdirectorio, $prefijo) {
    global $baseUrl, $projectRoot;

    if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("No se ha seleccionado ningún archivo para el defecto.");
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error en la subida del archivo (código: {$archivo['error']}).");
    }

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
        throw new Exception("Falló la subida del archivo.");
    }

    return $baseUrl . $subdirectorio . $nombreUnico;
}


$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Validar y obtener datos del formulario
    if (!isset($_POST['idReporte']) || empty($_POST['idReporte'])) {
        throw new Exception("Faltan datos obligatorios para actualizar el reporte.");
    }

    $idReporte = intval($_POST['idReporte']);
    $idSolicitud = intval($_POST['idSolicitud']);

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
    $tiempoInspeccion = trim($_POST['tiempoInspeccion']);
    $comentarios = trim($_POST['comentarios']);
    $idTiempoMuerto = isset($_POST['idTiempoMuerto']) && $_POST['idTiempoMuerto'] !== '' ? intval($_POST['idTiempoMuerto']) : null;

    // 2. Actualizar el registro principal en ReportesInspeccion
    $stmt_reporte = $conex->prepare("UPDATE ReportesInspeccion SET
                                        NombreInspector = ?, FechaInspeccion = ?, RangoHora = ?,
                                        PiezasInspeccionadas = ?, PiezasAceptadas = ?, PiezasRetrabajadas = ?,
                                        TiempoInspeccion = ?, Comentarios = ?, IdTiempoMuerto = ?
                                    WHERE IdReporte = ?");
    $stmt_reporte->bind_param("sssiisssii",
        $nombreInspector, $fechaInspeccion, $rangoHora,
        $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
        $tiempoInspeccion, $comentarios, $idTiempoMuerto,
        $idReporte);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al actualizar el reporte principal: " . $stmt_reporte->error);
    }
    $stmt_reporte->close();

    // 3. Borrar los detalles antiguos (desglose, defectos) para reemplazarlos.
    // Este método (borrar y re-insertar) es más simple y seguro que tratar de actualizar cada detalle individualmente.
    $conex->query("DELETE FROM ReporteDesglosePartes WHERE IdReporte = $idReporte");
    $conex->query("DELETE FROM ReporteDefectosOriginales WHERE IdReporte = $idReporte");

    // Antes de borrar los nuevos defectos, obtenemos las rutas de las fotos para eliminarlas del servidor
    $res_fotos = $conex->query("SELECT RutaFotoEvidencia FROM DefectosEncontrados WHERE IdReporte = $idReporte");
    while ($fila = $res_fotos->fetch_assoc()) {
        if (!empty($fila['RutaFotoEvidencia'])) {
            $rutaFisica = str_replace($baseUrl, $projectRoot, $fila['RutaFotoEvidencia']);
            if (file_exists($rutaFisica)) {
                @unlink($rutaFisica); // Usar @ para suprimir errores si el archivo no existe por alguna razón
            }
        }
    }
    $conex->query("DELETE FROM DefectosEncontrados WHERE IdReporte = $idReporte");

    // 4. Insertar los nuevos detalles (lógica similar a guardar_reporte.php)

    // 4.1. Desglose de Partes
    if ($isVariosPartes && isset($_POST['partes_inspeccionadas']) && is_array($_POST['partes_inspeccionadas'])) {
        foreach ($_POST['partes_inspeccionadas'] as $parteData) {
            $numeroParte = trim($parteData['parte']);
            $cantidad = intval($parteData['cantidad']);
            if ($cantidad > 0 && !empty($numeroParte)) {
                $stmt_desglose = $conex->prepare("INSERT INTO ReporteDesglosePartes (IdReporte, NumeroParte, Cantidad) VALUES (?, ?, ?)");
                $stmt_desglose->bind_param("isi", $idReporte, $numeroParte, $cantidad);
                $stmt_desglose->execute();
                $stmt_desglose->close();
            }
        }
    }

    // 4.2. Defectos Originales
    if (isset($_POST['defectos_originales']) && is_array($_POST['defectos_originales'])) {
        foreach ($_POST['defectos_originales'] as $idDefectoOriginal => $defectData) {
            if (isset($defectData['entries']) && is_array($defectData['entries'])) {
                foreach ($defectData['entries'] as $entry) {
                    $cantidad = intval($entry['cantidad']);
                    if ($cantidad > 0) {
                        $lote = trim($entry['lote']);
                        $parte = $isVariosPartes ? trim($entry['parte']) : null;
                        $stmt_do = $conex->prepare("INSERT INTO ReporteDefectosOriginales (IdReporte, IdDefecto, CantidadEncontrada, Lote, NumeroParte) VALUES (?, ?, ?, ?, ?)");
                        $stmt_do->bind_param("iiiss", $idReporte, $idDefectoOriginal, $cantidad, $lote, $parte);
                        $stmt_do->execute();
                        $stmt_do->close();
                    }
                }
            }
        }
    }

    // 4.3. Nuevos Defectos Encontrados
    if (isset($_POST['nuevos_defectos']) && is_array($_POST['nuevos_defectos'])) {
        foreach ($_POST['nuevos_defectos'] as $tempId => $defectoData) {
            $cantidad = intval($defectoData['cantidad']);
            if ($cantidad > 0) {
                $idDefectoCatalogo = intval($defectoData['id']);
                $parte = $isVariosPartes ? trim($defectoData['parte']) : null;
                $rutaFotoEvidencia = $defectoData['foto_existente'] ?? null;

                // Si se subió un nuevo archivo, se procesa. Si no, se usa la ruta existente si la hay.
                if (isset($_FILES['nuevos_defectos']['name'][$tempId]['foto']) && $_FILES['nuevos_defectos']['error'][$tempId]['foto'] === UPLOAD_ERR_OK) {
                    $foto_para_procesar = [
                        'name' => $_FILES['nuevos_defectos']['name'][$tempId]['foto'],
                        'type' => $_FILES['nuevos_defectos']['type'][$tempId]['foto'],
                        'tmp_name' => $_FILES['nuevos_defectos']['tmp_name'][$tempId]['foto'],
                        'error' => $_FILES['nuevos_defectos']['error'][$tempId]['foto'],
                        'size' => $_FILES['nuevos_defectos']['size'][$tempId]['foto']
                    ];
                    $rutaFotoEvidencia = procesarArchivoSubido($foto_para_procesar, 'imagenes/imagenesDefectos/', "nuevo_defecto_reporte_{$idReporte}_");
                }

                if (empty($rutaFotoEvidencia)) {
                    throw new Exception("Se requiere una foto de evidencia para el nuevo defecto.");
                }

                $stmt_de = $conex->prepare("INSERT INTO DefectosEncontrados (IdReporte, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia, NumeroParte) VALUES (?, ?, ?, ?, ?)");
                $stmt_de->bind_param("iiiss", $idReporte, $idDefectoCatalogo, $cantidad, $rutaFotoEvidencia, $parte);
                $stmt_de->execute();
                $stmt_de->close();
            }
        }
    }

    $conex->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reporte actualizado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conex->close();
?>

