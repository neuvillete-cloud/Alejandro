<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
$conn = (new LocalConector())->conectar();

$termino = isset($_GET['q']) ? trim($_GET['q']) : '';
$sugerencias = [];

if ($termino !== '') {
    $termino = "%{$termino}%";
    $stmt = $conn->prepare("
        SELECT DISTINCT V.TituloVacante 
        FROM Vacantes V
        INNER JOIN Area A ON V.IdArea = A.IdArea
        WHERE (V.TituloVacante LIKE ? OR A.NombreArea LIKE ?)
        AND V.IdEstatus = 1
        ORDER BY V.TituloVacante ASC
        LIMIT 10
    ");
    $stmt->bind_param("ss", $termino, $termino);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $sugerencias[] = $row['TituloVacante'];
    }

    $stmt->close();
}

$conn->close();
echo json_encode($sugerencias, JSON_UNESCAPED_UNICODE);
