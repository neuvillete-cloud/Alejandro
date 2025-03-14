<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

try {
    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL con filtro para IdEstatus = 5
    $sql = "SELECT 
                s.*, 
                a.NombreArea, 
                e.NombreEstatus 
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            WHERE s.IdEstatus = 5";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    // Ejecutar la consulta
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener los resultados
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);

    // Cerrar recursos
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
