<?php
session_start();
include_once("ConexionBD.php");

try {
    if (!isset($_SESSION['NumNomina'])) {
        throw new Exception("Número de nómina no disponible en la sesión.");
    }

    $numNomina = $_SESSION['NumNomina'];

    $con = new LocalConector();
    $conex = $con->conectar();

    $sql = "
        SELECT 
            p.IdPostulacion,
            p.IdVacante,
            c.Nombre AS Nombre,
            v.TituloVacante,
            p.FechaPostulacion,
            e.NombreEstatus
        FROM Postulaciones p
        INNER JOIN Candidatos c ON p.IdCandidato = c.IdCandidato
        INNER JOIN Vacantes v ON p.IdVacante = v.IdVacante
        INNER JOIN Estatus e ON p.IdEstatus = e.IdEstatus
        INNER JOIN Solicitudes s ON v.IdSolicitud = s.IdSolicitud
        WHERE 
            p.IdEstatus = 4 
            AND s.NumNomina = ?
            AND p.IdVacante NOT IN (
                SELECT IdVacante 
                FROM Postulaciones 
                WHERE IdEstatus = 9
            )
    ";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    $stmt->bind_param("s", $numNomina);
    $stmt->execute();
    $result = $stmt->get_result();
    $postulaciones = [];

    while ($row = $result->fetch_assoc()) {
        $postulaciones[] = $row;
    }

    $stmt->close();
    $conex->close();

    echo json_encode([
        'status' => 'success',
        'data' => $postulaciones
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>
