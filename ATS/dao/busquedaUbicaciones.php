<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
$conn = (new LocalConector())->conectar();

$termino = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$sugerencias = [];

if ($termino !== '') {
    $sql = "
        SELECT DISTINCT CONCAT(Ciudad, ', ', Estado) AS Ubicacion
        FROM Vacantes 
        WHERE CONCAT(Ciudad, Estado) LIKE '%$termino%' 
        AND IdEstatus = 1
        ORDER BY Ubicacion ASC
        LIMIT 10
    ";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $sugerencias[] = $row['Ubicacion'];
    }
}

$conn->close();

echo json_encode($sugerencias, JSON_UNESCAPED_UNICODE);

