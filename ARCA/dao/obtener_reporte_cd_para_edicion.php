<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php"); // Para verificar la sesión

header('Content-Type: application/json');

// Estructura de respuesta inicial
$response = ['status' => 'error', 'message' => '', 'reporte' => null, 'defectos' => []];

// Verificar sesión
if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

// Obtener y validar el ID del reporte de Cero Defectos
$idCDReporte = $_GET['idCDReporte'] ?? null;
if (!$idCDReporte || !filter_var($idCDReporte, FILTER_VALIDATE_INT)) {
    $response['message'] = 'ID de reporte Cero Defectos no válido o no proporcionado.';
    echo json_encode($response);
    exit();
}
$idCDReporte = intval($idCDReporte);

$con = new LocalConector();
$conex = $con->conectar();

try {
    // 1. Obtener los datos principales del reporte de Cero Defectos
    // Nota: Cambiamos PiezasInspeccionadas por PiezasProducidas y RangoHora por Turno según tu estructura CD
    $stmt = $conex->prepare("SELECT IdCDReporte, IdCeroDefectos, PiezasProducidas, PiezasAceptadas,
                                   NombreInspector, FechaInspeccion, Turno, Comentarios
                            FROM CeroDefectosReportesInspeccion WHERE IdCDReporte = ?");
    $stmt->bind_param("i", $idCDReporte);
    $stmt->execute();
    $reporte = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reporte) {
        throw new Exception("Reporte Cero Defectos no encontrado.");
    }

    // 2. Obtener los defectos registrados para este reporte
    // En Cero Defectos guardamos más detalles como Prioridad, EncontradoEn y Severidad
    $stmt_defectos = $conex->prepare("SELECT IdDefectoCatalogo, Cantidad, Prioridad, EncontradoEn, Severidad
                                      FROM CeroDefectosReporteDefectos WHERE IdCDReporte = ?");
    $stmt_defectos->bind_param("i", $idCDReporte);
    $stmt_defectos->execute();
    $defectos = $stmt_defectos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_defectos->close();

    // Formatear la fecha para el input type="date"
    if ($reporte['FechaInspeccion']) {
        $reporte['FechaInspeccion'] = date('Y-m-d', strtotime($reporte['FechaInspeccion']));
    }

    // Preparar la respuesta exitosa
    $response['status'] = 'success';
    $response['reporte'] = $reporte;
    $response['defectos'] = $defectos;

} catch (Exception $e) {
    $response['message'] = "Error al obtener datos: " . $e->getMessage();
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
    echo json_encode($response); // Enviar la respuesta JSON
}
?>