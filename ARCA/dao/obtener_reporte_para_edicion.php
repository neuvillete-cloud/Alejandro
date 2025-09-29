<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$con = new LocalConector();
$conex = $con->conectar();

try {
    if (!isset($_GET['idReporte'])) {
        throw new Exception("ID de reporte no proporcionado.");
    }

    $idReporte = intval($_GET['idReporte']);

    // 1. Obtener datos del ReportesInspeccion
    $stmt_reporte = $conex->prepare("SELECT * FROM ReportesInspeccion WHERE IdReporte = ?");
    $stmt_reporte->bind_param("i", $idReporte);
    $stmt_reporte->execute();
    $reporte_data = $stmt_reporte->get_result()->fetch_assoc();
    $stmt_reporte->close();

    if (!$reporte_data) {
        throw new Exception("Reporte no encontrado.");
    }

    // 2. Obtener Defectos Originales asociados
    $stmt_defectos_originales = $conex->prepare("
        SELECT rdo.IdDefecto, rdo.CantidadEncontrada, rdo.Lote, d.IdDefectoCatalogo
        FROM ReporteDefectosOriginales rdo
        JOIN Defectos d ON rdo.IdDefecto = d.IdDefecto
        WHERE rdo.IdReporte = ?
    ");
    $stmt_defectos_originales->bind_param("i", $idReporte);
    $stmt_defectos_originales->execute();
    $result_defectos_originales = $stmt_defectos_originales->get_result();
    $defectos_originales_data = [];
    while ($row = $result_defectos_originales->fetch_assoc()) {
        $defectos_originales_data[] = $row;
    }
    $stmt_defectos_originales->close();

    // 3. Obtener Nuevos Defectos Encontrados (DefectosEncontrados)
    $stmt_nuevos_defectos = $conex->prepare("
        SELECT de.IdDefectoEncontrado, de.IdDefectoCatalogo, de.Cantidad, de.RutaFotoEvidencia
        FROM DefectosEncontrados de
        WHERE de.IdReporte = ?
    ");
    $stmt_nuevos_defectos->bind_param("i", $idReporte);
    $stmt_nuevos_defectos->execute();
    $result_nuevos_defectos = $stmt_nuevos_defectos->get_result();
    $nuevos_defectos_data = [];
    while ($row = $result_nuevos_defectos->fetch_assoc()) {
        $nuevos_defectos_data[] = $row;
    }
    $stmt_nuevos_defectos->close();

    echo json_encode([
        'status' => 'success',
        'reporte' => $reporte_data,
        'defectosOriginales' => $defectos_originales_data,
        'nuevosDefectos' => $nuevos_defectos_data
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conex->close();
?>
