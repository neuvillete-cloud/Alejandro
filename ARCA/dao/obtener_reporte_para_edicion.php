<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php");

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => '', 'reporte' => null, 'defectosOriginales' => [], 'nuevosDefectos' => []];

if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

$idReporte = $_GET['idReporte'] ?? null;

if (!$idReporte) {
    $response['message'] = 'ID de reporte no proporcionado.';
    echo json_encode($response);
    exit();
}

$con = new LocalConector();
$conex = $con->conectar();

try {
    // Obtener datos del reporte principal
    $stmt = $conex->prepare("SELECT IdReporte, IdSolicitud, PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas, 
                                   NombreInspector, FechaInspeccion, IdRangoHora, TiempoInspeccion, IdTiempoMuerto, Comentarios
                            FROM ReportesInspeccion WHERE IdReporte = ?");
    $stmt->bind_param("i", $idReporte);
    $stmt->execute();
    $reporte = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reporte) {
        throw new Exception("Reporte no encontrado.");
    }

    // Obtener defectos originales del reporte
    $stmt_do = $conex->prepare("SELECT IdDefecto, CantidadEncontrada, Lote 
                               FROM ReporteDefectosOriginales WHERE IdReporte = ?");
    $stmt_do->bind_param("i", $idReporte);
    $stmt_do->execute();
    $defectosOriginales = $stmt_do->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_do->close();

    // Obtener nuevos defectos encontrados del reporte
    $stmt_de = $conex->prepare("SELECT IdDefectoEncontrado, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia 
                               FROM DefectosEncontrados WHERE IdReporte = ?");
    $stmt_de->bind_param("i", $idReporte);
    $stmt_de->execute();
    $nuevosDefectos = $stmt_de->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_de->close();

    // Formatear la fecha para el input type="date"
    if ($reporte['FechaInspeccion']) {
        $reporte['FechaInspeccion'] = date('Y-m-d', strtotime($reporte['FechaInspeccion']));
    }

    $response['status'] = 'success';
    $response['reporte'] = $reporte;
    $response['defectosOriginales'] = $defectosOriginales;
    $response['nuevosDefectos'] = $nuevosDefectos;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    $conex->close();
    echo json_encode($response);
}
?>