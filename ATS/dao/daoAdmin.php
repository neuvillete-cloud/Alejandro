<?php
session_start();
include_once("ConexionBD.php");
header('Content-Type: application/json; charset=UTF-8');

// --- CONFIGURACIÓN IMPORTANTE ---
define('HR_MANAGER_NOMINA', '00030315'); // Reemplaza con la nómina real
$currentUserNomina = $_SESSION['NumNomina'] ?? null;

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- CONSULTA CON FILTRO DE CONFIDENCIALIDAD ---
    $sql = "SELECT 
                s.IdSolicitud, s.Puesto, s.NumNomina, s.FolioSolicitud, 
                s.TipoContratacion, s.FechaSolicitud, s.NombreReemplazo,
                a.NombreArea, e.NombreEstatus, u.Nombre
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            JOIN Usuario u ON s.NumNomina = u.NumNomina
            WHERE s.IdEstatus = 1"; // Solicitudes "Pendientes"

    // Si el usuario actual NO es la gerente de RRHH, añadimos un filtro extra
    if ($currentUserNomina != HR_MANAGER_NOMINA) {
        $sql .= " AND s.EsConfidencial = 0";
    }
    // Si es RRHH, no se añade el filtro y puede ver todo.

    $stmt = $conex->prepare($sql);
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
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>
