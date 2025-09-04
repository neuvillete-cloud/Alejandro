<?php
session_start();
include_once("ConexionBD.php");
header('Content-Type: application/json; charset=UTF-8');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- CONSULTA CORREGIDA ---
    // Se añade el JOIN con la tabla Usuario para obtener el nombre del solicitante
    $sql = "SELECT 
                s.IdSolicitud,
                s.Puesto,
                s.NumNomina,
                s.FolioSolicitud,
                s.TipoContratacion,
                s.FechaSolicitud,
                s.NombreReemplazo,
                a.NombreArea, 
                e.NombreEstatus,
                u.Nombre  -- <-- La columna que faltaba
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            JOIN Usuario u ON s.NumNomina = u.NumNomina -- <-- El JOIN que faltaba
            WHERE s.IdEstatus = 1"; // Se asume que 1 es el estatus "Pendiente" que el admin debe revisar

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conex->close();

    echo json_encode([
        'status' => 'success',
        'data' => $solicitudes
    ]);
} catch (Exception $e) {
    // Si hay un error, lo enviamos como JSON para que JS lo pueda leer
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>