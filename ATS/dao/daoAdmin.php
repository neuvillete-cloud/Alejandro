<?php
session_start();
include_once("ConexionBD.php");
header('Content-Type: application/json; charset=UTF-8');

// --- CONFIGURACIÓN DE ROLES ---
define('HR_MANAGER_NOMINA', '00030315'); // Nómina de la gerente de RRHH
$currentUserNomina = $_SESSION['NumNomina'] ?? null;

if (!$currentUserNomina) {
    http_response_code(401); // No autorizado
    echo json_encode(['status' => 'error', 'message' => 'No se ha iniciado sesión o la sesión ha expirado.']);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- CONSULTA CORREGIDA ---
    // Se añaden los campos que faltaban: NumNomina, TipoContratacion, FechaSolicitud, NombreReemplazo
    // Se filtra por IdEstatus = 5 (Aprobada por Gerentes), que es el estado que esta página debe manejar.
    $sql = "SELECT 
                s.IdSolicitud, s.Puesto, s.NumNomina, s.FolioSolicitud, 
                s.TipoContratacion, s.FechaSolicitud, s.NombreReemplazo, s.EsConfidencial,
                a.NombreArea, 
                e.NombreEstatus,
                u.Nombre 
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            JOIN Usuario u ON s.NumNomina = u.NumNomina
            WHERE s.IdEstatus = 5"; // <-- CAMBIO REALIZADO AQUÍ

    // --- FILTRO DE CONFIDENCIALIDAD ---
    // Si el usuario actual NO es la gerente de RRHH, no puede ver las solicitudes confidenciales.
    if ($currentUserNomina !== HR_MANAGER_NOMINA) {
        $sql .= " AND s.EsConfidencial = 0";
    }
    // La gerente de RRHH ve todo porque no se añade el filtro.

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conex->close();

    // Se devuelve la respuesta en el formato que espera el frontend.
    echo json_encode([
        'status' => 'success',
        'data' => $solicitudes
    ]);

} catch (Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta a la base de datos: ' . $e->getMessage()
    ]);
}
?>

