<?php
session_start();
include_once("ConexionBD.php");
header('Content-Type: application/json; charset=UTF-8');

// --- CONFIGURACIÓN DE ROLES ---
define('HR_MANAGER_NOMINA', '00030315'); // Nómina de la gerente de RRHH
$currentUserNomina = $_SESSION['NumNomina'] ?? null;

if (!$currentUserNomina) {
    http_response_code(401); // No autorizado
    echo json_encode(['error' => 'No se ha iniciado sesión o la sesión ha expirado.']);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta base para obtener las solicitudes aprobadas (IdEstatus = 5)
    // Se añade un JOIN con la tabla Usuario para obtener el nombre del solicitante
    $sql = "SELECT 
                s.IdSolicitud, s.Puesto, s.FolioSolicitud, s.IdEstatus,
                a.NombreArea, 
                e.NombreEstatus,
                u.Nombre 
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            JOIN Usuario u ON s.NumNomina = u.NumNomina
            WHERE s.IdEstatus = 5";

    // --- FILTRO DE CONFIDENCIALIDAD ---
    // Si el usuario actual NO es la gerente de RRHH, se añade una condición
    // para que solo pueda ver las solicitudes que NO son confidenciales.
    if ($currentUserNomina !== HR_MANAGER_NOMINA) {
        $sql .= " AND s.EsConfidencial = 0";
    }
    // Si el usuario es RRHH, no se añade el filtro, por lo que puede ver todo.

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conex->close();

    echo json_encode([
        'data' => $solicitudes
    ]);

} catch (Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode([
        'error' => 'Error en la consulta a la base de datos: ' . $e->getMessage()
    ]);
}
?>
