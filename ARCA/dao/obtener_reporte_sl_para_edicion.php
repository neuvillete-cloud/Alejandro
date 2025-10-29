<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php"); // Para verificar la sesión

header('Content-Type: application/json');

// Estructura de respuesta inicial
// --- MODIFICADO: Añadida la clave 'nuevosDefectos' ---
$response = ['status' => 'error', 'message' => '', 'reporte' => null, 'defectos' => [], 'nuevosDefectos' => []];

// Verificar sesión
if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

// Obtener y validar el ID del reporte de Safe Launch
$idSLReporte = $_GET['idSLReporte'] ?? null;
if (!$idSLReporte || !filter_var($idSLReporte, FILTER_VALIDATE_INT)) {
    $response['message'] = 'ID de reporte Safe Launch no válido o no proporcionado.';
    echo json_encode($response);
    exit();
}
$idSLReporte = intval($idSLReporte);

$con = new LocalConector();
$conex = $con->conectar();

try {
    // 1. Obtener los datos principales del reporte de Safe Launch
    $stmt = $conex->prepare("SELECT IdSLReporte, IdSafeLaunch, PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas,
                                   NombreInspector, FechaInspeccion, RangoHora, TiempoInspeccion, Comentarios
                            FROM SafeLaunchReportesInspeccion WHERE IdSLReporte = ?");
    $stmt->bind_param("i", $idSLReporte);
    $stmt->execute();
    $reporte = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reporte) {
        throw new Exception("Reporte Safe Launch no encontrado.");
    }

    // 2. Obtener los defectos clasificados (DE LA CUADRÍCULA) para este reporte
    $stmt_defectos = $conex->prepare("SELECT IdSLDefectoCatalogo, CantidadEncontrada, BachLote
                                      FROM SafeLaunchReporteDefectos WHERE IdSLReporte = ?");
    $stmt_defectos->bind_param("i", $idSLReporte);
    $stmt_defectos->execute();
    $defectos = $stmt_defectos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_defectos->close();

    // --- INICIO NUEVO: 3. Obtener los NUEVOS DEFECTOS (OPCIONALES) para este reporte ---
    // (Datos de la tabla que acabamos de crear)
    $stmt_nuevos_defectos = $conex->prepare("SELECT IdSLNuevoDefecto, IdSLDefectoCatalogo, Cantidad
                                            FROM SafeLaunchNuevosDefectos WHERE IdSLReporte = ?");
    $stmt_nuevos_defectos->bind_param("i", $idSLReporte);
    $stmt_nuevos_defectos->execute();
    $nuevosDefectos = $stmt_nuevos_defectos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_nuevos_defectos->close();
    // --- FIN NUEVO ---

    // Formatear la fecha para el input type="date"
    if ($reporte['FechaInspeccion']) {
        $reporte['FechaInspeccion'] = date('Y-m-d', strtotime($reporte['FechaInspeccion']));
    }

    // Preparar la respuesta exitosa
    $response['status'] = 'success';
    $response['reporte'] = $reporte;
    $response['defectos'] = $defectos; // Los defectos de la cuadrícula
    $response['nuevosDefectos'] = $nuevosDefectos; // --- AÑADIDO: Los defectos opcionales

} catch (Exception $e) {
    $response['message'] = "Error al obtener datos: " . $e->getMessage();
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
    echo json_encode($response); // Enviar la respuesta JSON
}
?>
