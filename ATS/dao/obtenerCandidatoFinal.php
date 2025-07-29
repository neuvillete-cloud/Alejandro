<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

$sql = "SELECT 
            p.IdPostulacion,
            c.Nombre AS NombreCandidato,
            c.Apellidos AS ApellidosCandidato,
            v.TituloVacante,
            a.NombreArea,
            s.Nombre AS NombreSelector,
            e.NombreEstatus
        FROM Postulaciones p
        INNER JOIN Candidatos c ON c.IdCandidato = p.IdCandidato
        INNER JOIN Vacantes v ON v.IdVacante = p.IdVacante
        INNER JOIN Area a ON a.IdArea = v.IdArea
        INNER JOIN Solicitudes s ON s.IdSolicitud = v.IdSolicitud
        INNER JOIN Estatus e ON e.IdEstatus = p.IdEstatus
        WHERE p.IdEstatus = 9";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar la consulta']);
    $conn->close();
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$candidatos = [];

while ($row = $result->fetch_assoc()) {
    $candidatos[] = [
        'IdPostulacion' => $row['IdPostulacion'],
        'NombreCompleto' => $row['NombreCandidato'] . ' ' . $row['ApellidosCandidato'],
        'TituloVacante' => $row['TituloVacante'],
        'NombreArea' => $row['NombreArea'],
        'NombreSelector' => $row['NombreSelector'],
        'NombreEstatus' => $row['NombreEstatus'],
        'Foto' => 'imagenes/user-default.png'
    ];
}

echo json_encode($candidatos, JSON_UNESCAPED_UNICODE);
$conn->close();
