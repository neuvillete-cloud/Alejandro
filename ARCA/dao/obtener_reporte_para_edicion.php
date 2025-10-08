<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php");

header('Content-Type: application/json');
// --- MODIFICADO: Se añade la nueva clave para el desglose de partes ---
$response = ['status' => 'error', 'message' => '', 'reporte' => null, 'defectosOriginales' => [], 'nuevosDefectos' => [], 'desglosePartes' => []];

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
    // --- MODIFICADO: Obtener RangoHora como texto ---
    $stmt = $conex->prepare("SELECT IdReporte, IdSolicitud, PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas, 
                                   NombreInspector, FechaInspeccion, RangoHora, TiempoInspeccion, IdTiempoMuerto, Comentarios
                            FROM ReportesInspeccion WHERE IdReporte = ?");
    $stmt->bind_param("i", $idReporte);
    $stmt->execute();
    $reporte = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reporte) {
        throw new Exception("Reporte no encontrado.");
    }

    // --- NUEVO: Obtener el desglose de números de parte si existen ---
    $stmt_desglose = $conex->prepare("SELECT NumeroParte, Cantidad FROM ReporteDesglosePartes WHERE IdReporte = ?");
    $stmt_desglose->bind_param("i", $idReporte);
    $stmt_desglose->execute();
    $desglosePartes = $stmt_desglose->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_desglose->close();

    // --- MODIFICADO: Obtener NumeroParte de los defectos originales ---
    $stmt_do = $conex->prepare("SELECT IdDefecto, CantidadEncontrada, Lote, NumeroParte
                               FROM ReporteDefectosOriginales WHERE IdReporte = ?");
    $stmt_do->bind_param("i", $idReporte);
    $stmt_do->execute();
    $defectosOriginales = $stmt_do->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_do->close();

    // --- MODIFICADO: Obtener NumeroParte de los nuevos defectos ---
    $stmt_de = $conex->prepare("SELECT IdDefectoEncontrado, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia, NumeroParte
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
    $response['desglosePartes'] = $desglosePartes; // Añadir al response
    $response['defectosOriginales'] = $defectosOriginales;
    $response['nuevosDefectos'] = $nuevosDefectos;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    $conex->close();
    echo json_encode($response);
}
?>