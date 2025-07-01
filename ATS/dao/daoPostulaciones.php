<?php
session_start();
include_once("ConexionBD.php");

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $sql = "
        SELECT 
            p.IdPostulacion,
            c.Nombre AS NombreCandidato,
            v.TituloVacante,
            p.FechaPostulacion
        FROM Postulaciones p
        INNER JOIN Candidatos c ON p.IdCandidato = c.IdCandidato
        INNER JOIN Vacantes v ON p.IdVacante = v.IdVacante
        WHERE p.IdEstatus = 1
    ";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

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

