<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php"); // Para verificar la sesión

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => ''];

// Verificar sesión
if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

// Obtener y validar el ID del reporte de Safe Launch
$idSLReporte = $_POST['idSLReporte'] ?? null;
if (!$idSLReporte || !filter_var($idSLReporte, FILTER_VALIDATE_INT)) {
    $response['message'] = 'ID de reporte Safe Launch no válido o no proporcionado.';
    echo json_encode($response);
    exit();
}
$idSLReporte = intval($idSLReporte);

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Eliminar registros de SafeLaunchReporteDefectos asociados
    // (No hay fotos que eliminar en este flujo)
    $delete_defectos_sl = $conex->prepare("DELETE FROM SafeLaunchReporteDefectos WHERE IdSLReporte = ?");
    $delete_defectos_sl->bind_param("i", $idSLReporte);
    if (!$delete_defectos_sl->execute()) {
        throw new Exception("Error al eliminar los defectos asociados al reporte Safe Launch: " . $delete_defectos_sl->error);
    }
    $delete_defectos_sl->close();

    // Lógica para eliminar DefectosEncontrados y ReporteDefectosOriginales eliminada

    // 2. Eliminar el SafeLaunchReportesInspeccion principal
    $delete_reporte_sl = $conex->prepare("DELETE FROM SafeLaunchReportesInspeccion WHERE IdSLReporte = ?");
    $delete_reporte_sl->bind_param("i", $idSLReporte);
    if (!$delete_reporte_sl->execute()) {
        // Verificar si el error es por una restricción de clave foránea (aunque no debería pasar si la eliminación anterior funcionó)
        if ($conex->errno == 1451) { // Error de restricción de clave foránea
            throw new Exception("Error: No se puede eliminar el reporte porque tiene datos asociados que no se pudieron eliminar primero.");
        } else {
            throw new Exception("Error al eliminar el reporte principal de Safe Launch: " . $delete_reporte_sl->error);
        }
    }
    // Verificar si se eliminó alguna fila
    $filas_afectadas = $delete_reporte_sl->affected_rows;
    $delete_reporte_sl->close();

    if ($filas_afectadas > 0) {
        $conex->commit(); // Confirmar la transacción
        $response['status'] = 'success';
        $response['message'] = 'Reporte Safe Launch eliminado exitosamente.';
    } else {
        // Si no se afectaron filas, puede que el ID no existiera
        throw new Exception("No se encontró el reporte Safe Launch con el ID proporcionado o ya fue eliminado.");
    }

} catch (Exception $e) {
    $conex->rollback(); // Revertir la transacción en caso de error
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
    echo json_encode($response); // Enviar la respuesta JSON
}
?>

