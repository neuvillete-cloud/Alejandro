<?php
session_start();
include_once("ConexionBD.php");
header('Content-Type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- INICIO DE LA CORRECCIÓN: Se añade s.Puesto a la consulta ---
    $sql = "
        SELECT 
            s.IdSolicitud, 
            s.Puesto, 
            s.FolioSolicitud, 
            s.Nombre AS NombreSolicitante, 
            ar.NombreArea,
            s.AprobadoresRequeridos,
            s.IdEstatus,
            COUNT(CASE WHEN a.IdEstatus = 5 THEN 1 END) as ConteoAprobados,
            COUNT(CASE WHEN a.IdEstatus = 3 THEN 1 END) as ConteoRechazados
        FROM Solicitudes s
        LEFT JOIN Area ar ON s.IdArea = ar.IdArea
        LEFT JOIN Aprobadores a ON s.FolioSolicitud = a.FolioSolicitud
        WHERE s.IdEstatus IN (2, 10)
        GROUP BY s.IdSolicitud;
    ";
    // --- FIN DE LA CORRECCIÓN ---

    $stmt = $conex->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conex->close();

    // Lógica para calcular el estado (sin cambios)
    foreach ($solicitudes as &$solicitud) {
        $requeridos = (int)($solicitud['AprobadoresRequeridos'] ?? 0);
        $aprobados = (int)$solicitud['ConteoAprobados'];
        $rechazados = (int)$solicitud['ConteoRechazados'];

        if ($solicitud['IdEstatus'] == 10) {
            $solicitud['EstadoFinalCalculado'] = 'Vacante Creada';
        } elseif ($rechazados > 0) {
            $solicitud['EstadoFinalCalculado'] = 'Rechazado en 2da Fase';
        } elseif ($requeridos > 0 && $aprobados >= $requeridos) {
            $solicitud['EstadoFinalCalculado'] = 'Completamente Aprobado';
        } else {
            $solicitud['EstadoFinalCalculado'] = "En Proceso ($aprobados de $requeridos)";
        }
    }

    echo json_encode(['status' => 'success', 'data' => $solicitudes]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $e->getMessage()]);
}
?>
