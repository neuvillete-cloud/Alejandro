<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

try {
    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener las solicitudes con estatus 2
    $sql = "
        SELECT s.FolioSolicitud, s.NombreSolicitante, s.FechaSolicitud, 
               a.NombreAprobador, a.EstatusAprobacion, a.FechaAprobacion
        FROM Solicitudes s
        LEFT JOIN Aprobadores a ON s.FolioSolicitud = a.FolioSolicitud
        WHERE s.Estatus = 2
    ";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = [];

    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

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