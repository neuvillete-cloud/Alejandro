<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php");

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => ''];

if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

$idReporte = $_POST['idReporte'] ?? null;

if (!$idReporte) {
    $response['message'] = 'ID de reporte no proporcionado.';
    echo json_encode($response);
    exit();
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Obtener y eliminar fotos de DefectosEncontrados
    $select_fotos = $conex->prepare("SELECT RutaFotoEvidencia FROM DefectosEncontrados WHERE IdReporte = ?");
    $select_fotos->bind_param("i", $idReporte);
    $select_fotos->execute();
    $result_fotos = $select_fotos->get_result();
    while ($row = $result_fotos->fetch_assoc()) {
        if (!empty($row['RutaFotoEvidencia']) && file_exists($row['RutaFotoEvidencia'])) {
            unlink($row['RutaFotoEvidencia']); // Eliminar archivo físico
        }
    }
    $select_fotos->close();

    // 2. Eliminar registros de DefectosEncontrados
    $delete_de = $conex->prepare("DELETE FROM DefectosEncontrados WHERE IdReporte = ?");
    $delete_de->bind_param("i", $idReporte);
    if (!$delete_de->execute()) {
        throw new Exception("Error al eliminar defectos encontrados: " . $delete_de->error);
    }
    $delete_de->close();

    // 3. Eliminar registros de ReporteDefectosOriginales
    $delete_rdo = $conex->prepare("DELETE FROM ReporteDefectosOriginales WHERE IdReporte = ?");
    $delete_rdo->bind_param("i", $idReporte);
    if (!$delete_rdo->execute()) {
        throw new Exception("Error al eliminar defectos originales del reporte: " . $delete_rdo->error);
    }
    $delete_rdo->close();

    // 4. Eliminar el ReporteInspeccion principal
    $delete_ri = $conex->prepare("DELETE FROM ReportesInspeccion WHERE IdReporte = ?");
    $delete_ri->bind_param("i", $idReporte);
    if (!$delete_ri->execute()) {
        throw new Exception("Error al eliminar el reporte principal: " . $delete_ri->error);
    }
    $delete_ri->close();

    $conex->commit(); // Confirmar la transacción
    $response['status'] = 'success';
    $response['message'] = 'Reporte eliminado exitosamente.';

} catch (Exception $e) {
    $conex->rollback(); // Revertir la transacción en caso de error
    $response['message'] = $e->getMessage();
} finally {
    $conex->close();
    echo json_encode($response);
}
?>