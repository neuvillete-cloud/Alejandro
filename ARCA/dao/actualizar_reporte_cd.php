<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Validar y obtener datos del formulario
    // Nota: 'idCDReporte' debe coincidir con el input hidden del formulario en reporte_cero_defectos.php
    if (!isset($_POST['idCDReporte']) || empty($_POST['idCDReporte']) || !isset($_POST['idCeroDefectos'])) {
        throw new Exception("Faltan datos obligatorios (ID de Reporte o ID de Cero Defectos) para actualizar.");
    }

    $idCDReporte = intval($_POST['idCDReporte']);
    $idCeroDefectos = intval($_POST['idCeroDefectos']);

    // Obtener los demás datos del formulario
    $nombreInspector = isset($_POST['nombreInspector']) ? trim($_POST['nombreInspector']) : '';
    $fechaInspeccion = isset($_POST['fechaInspeccion']) ? trim($_POST['fechaInspeccion']) : '';
    $turno = isset($_POST['turno']) ? trim($_POST['turno']) : '';
    $piezasProducidas = isset($_POST['piezasProducidas']) ? intval($_POST['piezasProducidas']) : 0;
    $piezasAceptadas = isset($_POST['piezasAceptadas']) ? intval($_POST['piezasAceptadas']) : 0;
    $comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

    // Validaciones básicas
    if (empty($nombreInspector) || empty($fechaInspeccion) || empty($turno) || $piezasProducidas < 0 || $piezasAceptadas < 0) {
        throw new Exception("Por favor, complete todos los campos requeridos y asegúrese de que las cantidades sean válidas.");
    }

    if ($piezasAceptadas > $piezasProducidas) {
        throw new Exception("Las piezas aceptadas no pueden ser mayores que las piezas producidas.");
    }

    // Cálculo de rechazadas (en Cero Defectos: Producidas - Aceptadas)
    $piezasRechazadasCalculadas = $piezasProducidas - $piezasAceptadas;

    // 2. Actualizar el registro principal en CeroDefectosReportesInspeccion
    $stmt_reporte = $conex->prepare("UPDATE CeroDefectosReportesInspeccion SET
                                        NombreInspector = ?, FechaInspeccion = ?, Turno = ?,
                                        PiezasProducidas = ?, PiezasAceptadas = ?,
                                        Comentarios = ?
                                    WHERE IdCDReporte = ?");

    // Tipos: s (string), s (string), s (string), i (int), i (int), s (string), i (int)
    $stmt_reporte->bind_param("sssiisi",
        $nombreInspector, $fechaInspeccion, $turno,
        $piezasProducidas, $piezasAceptadas,
        $comentarios,
        $idCDReporte);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al actualizar el reporte principal de Cero Defectos: " . $stmt_reporte->error);
    }
    $stmt_reporte->close();

    // 3. Borrar los defectos antiguos
    // En Cero Defectos solo hay una tabla de detalles, así que borramos todo lo asociado a este reporte para re-insertarlo
    $stmt_delete_defectos = $conex->prepare("DELETE FROM CeroDefectosReporteDefectos WHERE IdCDReporte = ?");
    $stmt_delete_defectos->bind_param("i", $idCDReporte);
    if (!$stmt_delete_defectos->execute()) {
        throw new Exception("Error al limpiar los defectos antiguos: " . $stmt_delete_defectos->error);
    }
    $stmt_delete_defectos->close();

    // 4. Insertar los detalles de defectos (Re-inserción)
    $totalDefectosRegistrados = 0;
    if (isset($_POST['defectos']) && is_array($_POST['defectos'])) {
        foreach ($_POST['defectos'] as $index => $defectData) {
            $cantidad = isset($defectData['cantidad']) ? intval($defectData['cantidad']) : 0;

            if ($cantidad > 0) {
                $idDefectoCatalogo = isset($defectData['id']) ? intval($defectData['id']) : 0;
                $prioridad = isset($defectData['prioridad']) ? $defectData['prioridad'] : 'Media';
                $encontradoEn = isset($defectData['encontrado']) ? $defectData['encontrado'] : 'Estación Media';
                $severidad = isset($defectData['severidad']) ? $defectData['severidad'] : 'Mayor';

                // Insertar cada defecto
                $stmt_defecto = $conex->prepare("INSERT INTO CeroDefectosReporteDefectos 
                    (IdCDReporte, IdDefectoCatalogo, Cantidad, Prioridad, EncontradoEn, Severidad) 
                    VALUES (?, ?, ?, ?, ?, ?)");

                $stmt_defecto->bind_param("iiisss",
                    $idCDReporte, $idDefectoCatalogo, $cantidad, $prioridad, $encontradoEn, $severidad
                );

                if (!$stmt_defecto->execute()) {
                    throw new Exception("Error al re-insertar defecto #{$idDefectoCatalogo}: " . $stmt_defecto->error);
                }
                $stmt_defecto->close();

                $totalDefectosRegistrados += $cantidad;
            }
        }
    }

    // 5. Validación final de consistencia
    // La suma de defectos registrados debe coincidir con las piezas rechazadas calculadas
    if ($totalDefectosRegistrados != $piezasRechazadasCalculadas) {
        throw new Exception("Error de validación: La suma de defectos ({$totalDefectosRegistrados}) no coincide con las piezas rechazadas ({$piezasRechazadasCalculadas}). Verifique 'Producidas vs Aceptadas' o la lista de defectos.");
    }

    $conex->commit(); // Confirmar transacción
    echo json_encode(['status' => 'success', 'message' => 'Reporte Cero Defectos actualizado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback(); // Revertir cambios si error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}
?>