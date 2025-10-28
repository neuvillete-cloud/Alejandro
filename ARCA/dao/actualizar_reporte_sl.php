<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php"); // Asume que está en la misma carpeta dao/
include_once("conexionArca.php");    // Asume que está en la misma carpeta dao/
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE BASE URL y RUTA ROOT (Ajusta si es necesario) ---
$baseUrl = "https://grammermx.com/AleTest/ARCA/";
$projectRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/';

// Funciones de sanitización y procesamiento de archivos ya no son necesarias aquí

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Validar y obtener datos del formulario
    if (!isset($_POST['idSLReporte']) || empty($_POST['idSLReporte']) || !isset($_POST['idSafeLaunch'])) {
        throw new Exception("Faltan datos obligatorios (ID de Reporte o ID de Safe Launch) para actualizar.");
    }

    $idSLReporte = intval($_POST['idSLReporte']);
    $idSafeLaunch = intval($_POST['idSafeLaunch']); // Aunque no se use en UPDATE, es bueno tenerlo por contexto

    // Obtener los demás datos del formulario
    $nombreInspector = isset($_POST['nombreInspector']) ? trim($_POST['nombreInspector']) : '';
    $fechaInspeccion = isset($_POST['fechaInspeccion']) ? trim($_POST['fechaInspeccion']) : '';
    $rangoHora = isset($_POST['rangoHoraCompleto']) ? trim($_POST['rangoHoraCompleto']) : '';
    $piezasInspeccionadas = isset($_POST['piezasInspeccionadas']) ? intval($_POST['piezasInspeccionadas']) : 0;
    $piezasAceptadas = isset($_POST['piezasAceptadas']) ? intval($_POST['piezasAceptadas']) : 0;
    $piezasRetrabajadas = isset($_POST['piezasRetrabajadas']) ? intval($_POST['piezasRetrabajadas']) : 0;
    $tiempoInspeccion = isset($_POST['tiempoInspeccion']) ? trim($_POST['tiempoInspeccion']) : '';
    $comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';
    // IdTiempoMuerto ya no existe

    // Validaciones básicas (igual que en guardar_reporte_safe_launch.php)
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

    // 2. Actualizar el registro principal en SafeLaunchReportesInspeccion
    $stmt_reporte = $conex->prepare("UPDATE SafeLaunchReportesInspeccion SET
                                        NombreInspector = ?, FechaInspeccion = ?, RangoHora = ?,
                                        PiezasInspeccionadas = ?, PiezasAceptadas = ?, PiezasRetrabajadas = ?,
                                        TiempoInspeccion = ?, Comentarios = ?
                                    WHERE IdSLReporte = ?");
    // Ajustar bind_param: removido IdTiempoMuerto
    $stmt_reporte->bind_param("sssiisssi",
        $nombreInspector, $fechaInspeccion, $rangoHora,
        $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
        $tiempoInspeccion, $comentarios,
        $idSLReporte);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al actualizar el reporte principal de Safe Launch: " . $stmt_reporte->error);
    }
    $stmt_reporte->close();

    // 3. Borrar los defectos antiguos asociados a este reporte
    $stmt_delete_defectos = $conex->prepare("DELETE FROM SafeLaunchReporteDefectos WHERE IdSLReporte = ?");
    $stmt_delete_defectos->bind_param("i", $idSLReporte);
    if (!$stmt_delete_defectos->execute()) {
        throw new Exception("Error al limpiar los defectos antiguos del reporte: " . $stmt_delete_defectos->error);
    }
    $stmt_delete_defectos->close();
    // Lógica para eliminar fotos ya no es necesaria

    // 4. Insertar los nuevos detalles de defectos (lógica similar a guardar_reporte_safe_launch.php)
    $totalDefectosClasificados = 0;
    if (isset($_POST['defectos']) && is_array($_POST['defectos'])) {
        foreach ($_POST['defectos'] as $idDefectoCatalogo => $defectData) {
            $cantidad = isset($defectData['cantidad']) ? intval($defectData['cantidad']) : 0;
            if ($cantidad > 0) {
                $lote = isset($defectData['lote']) ? trim($defectData['lote']) : null;
                $idDefectoCatalogo = intval($idDefectoCatalogo); // Asegurar que es entero

                $stmt_defecto = $conex->prepare("INSERT INTO SafeLaunchReporteDefectos (IdSLReporte, IdSLDefectoCatalogo, CantidadEncontrada, BachLote) VALUES (?, ?, ?, ?)");
                $stmt_defecto->bind_param("iiis", $idSLReporte, $idDefectoCatalogo, $cantidad, $lote);
                if (!$stmt_defecto->execute()) {
                    // Importante: Si falla la inserción de un defecto, la transacción hará rollback de todo
                    throw new Exception("Error al guardar el defecto actualizado del catálogo #{$idDefectoCatalogo}: " . $stmt_defecto->error);
                }
                $stmt_defecto->close();
                $totalDefectosClasificados += $cantidad;
            }
        }
    }

    // Validación final: la suma de defectos clasificados debe coincidir con las rechazadas disponibles
    $rechazadasDisponibles = $piezasRechazadasBrutas - $piezasRetrabajadas;
    if ($totalDefectosClasificados != $rechazadasDisponibles) {
        throw new Exception("Error de validación al actualizar: La suma de defectos clasificados ({$totalDefectosClasificados}) no coincide con las piezas rechazadas disponibles para clasificar ({$rechazadasDisponibles}).");
    }

    // Lógica para Desglose de Partes eliminada
    // Lógica para Defectos Originales eliminada
    // Lógica para Nuevos Defectos Encontrados eliminada

    $conex->commit(); // Confirmar transacción si todo fue exitoso
    echo json_encode(['status' => 'success', 'message' => 'Reporte Safe Launch actualizado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback(); // Revertir cambios si hubo algún error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
}
?>
