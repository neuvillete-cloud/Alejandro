<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- CONSULTA MEJORADA ---
    // Agrupamos por solicitud y contamos las aprobaciones y rechazos.
    $sql = "
        SELECT 
            s.IdSolicitud, 
            s.FolioSolicitud, 
            s.Nombre AS NombreSolicitante, 
            ar.NombreArea,
            s.AprobadoresRequeridos, -- Obtenemos el número de aprobadores necesarios
            -- Contamos cuántos han aprobado (IdEstatus = 5 en Aprobadores)
            COUNT(CASE WHEN a.IdEstatus = 5 THEN 1 END) as ConteoAprobados,
            -- Contamos cuántos han rechazado (IdEstatus = 3 en Aprobadores)
            COUNT(CASE WHEN a.IdEstatus = 3 THEN 1 END) as ConteoRechazados
        FROM Solicitudes s
        LEFT JOIN Area ar ON s.IdArea = ar.IdArea
        LEFT JOIN Aprobadores a ON s.FolioSolicitud = a.FolioSolicitud
        WHERE s.IdEstatus = 2 -- Solo traemos las que están en esta fase de aprobación
        GROUP BY s.IdSolicitud; -- Agrupamos para que cada solicitud sea una sola fila
    ";

    $stmt = $conex->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conex->close();

    // --- LÓGICA PARA DETERMINAR EL ESTADO REAL ---
    // Recorremos los resultados para añadir un estado final calculado
    foreach ($solicitudes as &$solicitud) { // Usamos '&' para modificar el array directamente
        $requeridos = (int)$solicitud['AprobadoresRequeridos'];
        $aprobados = (int)$solicitud['ConteoAprobados'];
        $rechazados = (int)$solicitud['ConteoRechazados'];

        if ($rechazados > 0) {
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