<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    if (!isset($_POST['idReporte'])) {
        throw new Exception("ID de reporte no proporcionado para eliminación.");
    }

    $idReporte = intval($_POST['idReporte']);

    // Primero, obtener las rutas de las fotos de DefectosEncontrados para eliminarlas del servidor
    $stmt_get_fotos = $conex->prepare("SELECT RutaFotoEvidencia FROM DefectosEncontrados WHERE IdReporte = ?");
    $stmt_get_fotos->bind_param("i", $idReporte);
    $stmt_get_fotos->execute();
    $result_fotos = $stmt_get_fotos->get_result();
    $rutas_fotos_a_eliminar = [];
    while ($row = $result_fotos->fetch_assoc()) {
        $rutas_fotos_a_eliminar[] = $row['RutaFotoEvidencia'];
    }
    $stmt_get_fotos->close();

    // Eliminar DefectosEncontrados
    $stmt_delete_nuevos = $conex->prepare("DELETE FROM DefectosEncontrados WHERE IdReporte = ?");
    $stmt_delete_nuevos->bind_param("i", $idReporte);
    if (!$stmt_delete_nuevos->execute()) {
        throw new Exception("Error al eliminar nuevos defectos: " . $stmt_delete_nuevos->error);
    }
    $stmt_delete_nuevos->close();

    // Eliminar ReporteDefectosOriginales
    $stmt_delete_originales = $conex->prepare("DELETE FROM ReporteDefectosOriginales WHERE IdReporte = ?");
    $stmt_delete_originales->bind_param("i", $idReporte);
    if (!$stmt_delete_originales->execute()) {
        throw new Exception("Error al eliminar defectos originales: " . $stmt_delete_originales->error);
    }
    $stmt_delete_originales->close();

    // Finalmente, eliminar el ReportesInspeccion
    $stmt_delete_reporte = $conex->prepare("DELETE FROM ReportesInspeccion WHERE IdReporte = ?");
    $stmt_delete_reporte->bind_param("i", $idReporte);
    if (!$stmt_delete_reporte->execute()) {
        throw new Exception("Error al eliminar el reporte de inspección: " . $stmt_delete_reporte->error);
    }
    $stmt_delete_reporte->close();

    // Si todo fue bien en la DB, eliminar los archivos físicos
    // Asumiendo que la ruta física es relativa a la raíz del proyecto
    // Ajusta $baseUrl y $projectRoot si es necesario
    $baseUrl = "https://grammermx.com/AleTest/ARCA/";
    $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    $projectRoot = $documentRoot . '/AleTest/ARCA/';

    foreach ($rutas_fotos_a_eliminar as $ruta_publica) {
        $ruta_fisica = str_replace($baseUrl, $projectRoot, $ruta_publica);
        if (file_exists($ruta_fisica)) {
            unlink($ruta_fisica);
        }
    }

    $conex->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reporte eliminado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conex->close();
?>