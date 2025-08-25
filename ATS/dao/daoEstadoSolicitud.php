<?php
session_start();
include_once("ConexionBD.php"); // Ojo: la ruta a la conexión puede cambiar

header('Content-Type: application/json');

// Verificamos que el usuario haya iniciado sesión
if (!isset($_SESSION['NumNomina'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado.']);
    exit;
}
$numNominaSolicitante = $_SESSION['NumNomina'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Buscamos la última solicitud creada por el usuario logueado
    $sql = "
        SELECT 
            s.IdSolicitud,
            s.FolioSolicitud,
            s.IdEstatus,
            e.NombreEstatus
        FROM Solicitudes s
        JOIN Estatus e ON s.IdEstatus = e.IdEstatus
        WHERE s.NumNomina = ?
        ORDER BY s.FechaSolicitud DESC -- Ordenamos para obtener la más reciente
        LIMIT 1; -- Solo queremos la última
    ";

    $stmt = $conex->prepare($sql);
    $stmt->bind_param("s", $numNominaSolicitante);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $solicitud = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'data' => $solicitud
        ]);
    } else {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'No se encontraron solicitudes para este usuario.'
        ]);
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>
