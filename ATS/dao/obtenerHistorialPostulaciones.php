<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
session_start();

date_default_timezone_set('America/Mexico_City');

$IdCandidato = $_SESSION['IdCandidato'] ?? null;

if (!$IdCandidato) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$conn = (new LocalConector())->conectar();

try {
    $stmt = $conn->prepare("
    SELECT 
        v.IdVacante,
        v.TituloVacante,
        v.Imagen,
        a.NombreArea,
        p.FechaPostulacion,
        v.EspacioTrabajo,
        e.NombreEstatus,
        e.Descripcion AS DescripcionEstatus
    FROM Postulaciones p
    INNER JOIN Vacantes v ON p.IdVacante = v.IdVacante
    INNER JOIN Area a ON v.IdArea = a.IdArea
    INNER JOIN Estatus e ON p.IdEstatus = e.IdEstatus
    WHERE p.IdCandidato = ?
");


    $stmt->bind_param("i", $IdCandidato);
    $stmt->execute();
    $result = $stmt->get_result();

    $postulaciones = [];
    while ($row = $result->fetch_assoc()) {
        $postulaciones[] = [
            'IdVacante' => $row['IdVacante'],
            'TituloVacante' => $row['TituloVacante'],
            'ImagenVacante' => $row['ImagenVacante'],
            'NombreArea' => $row['NombreArea'],
            'FechaPostulacion' => $row['FechaPostulacion'],
            'EspacioTrabajo' => $row['EspacioTrabajo'],
            'Estatus' => $row['NombreEstatus'],
            'DescripcionEstatus' => $row['DescripcionEstatus']
        ];
    }


    echo json_encode($postulaciones, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
