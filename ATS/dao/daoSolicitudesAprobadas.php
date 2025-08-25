<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json'); // Es buena práctica definir el header al inicio

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- CONSULTA ÚNICA Y MEJORADA ---
    // Esta consulta hace todo el trabajo en un solo paso.
    $sql = "
        SELECT 
            s.*, 
            ar.NombreArea
        FROM 
            Solicitudes s
        JOIN 
            Area ar ON s.IdArea = ar.IdArea
        WHERE
            -- Condición 1: El conteo de aprobaciones (Estatus 5) debe ser igual o mayor al requerido.
            (
                SELECT COUNT(*) 
                FROM Aprobadores a 
                WHERE a.FolioSolicitud = s.FolioSolicitud AND a.IdEstatus = 5
            ) >= s.AprobadoresRequeridos
            
            AND

            -- Condición 2: El conteo de rechazos (Estatus 3) debe ser exactamente CERO.
            (
                SELECT COUNT(*) 
                FROM Aprobadores a 
                WHERE a.FolioSolicitud = s.FolioSolicitud AND a.IdEstatus = 3
            ) = 0
            
            AND

            -- Condición 3: Asegurarnos de que el campo de requeridos tenga un valor válido.
            s.AprobadoresRequeridos > 0;
    ";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC); // Obtener todos los resultados de una vez

    $stmt->close();
    $conex->close();

    // Devolver respuesta JSON
    echo json_encode([
        'status' => 'success',
        'data' => $solicitudes
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>