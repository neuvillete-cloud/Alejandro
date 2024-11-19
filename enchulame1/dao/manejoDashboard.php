<?php
// Conectar a la base de datos
include_once('conexion.php');
$con = new LocalConector();
$conex = $con->conectar();

// Consulta para obtener los reportes por mes y su estado
$query = "
    SELECT 
        MONTH(FechaRegistro) AS mesRegistro,
        YEAR(FechaRegistro) AS anioRegistro,
        COUNT(*) AS totalReportes,
        COUNT(CASE WHEN IdEstatus = 3 AND FechaFinalizado IS NOT NULL THEN 1 END) AS reportesFinalizados
    FROM Reportes
    WHERE YEAR(FechaRegistro) = YEAR(CURDATE())  -- Solo este año
    GROUP BY MONTH(FechaRegistro), YEAR(FechaRegistro)
    ORDER BY mesRegistro;
";

$result = $conex->query($query);

// Arreglos para los datos
$meses = [];
$totales = [];
$finalizados = [];

while ($row = $result->fetch_assoc()) {
    $meses[] = $row['mesRegistro']; // Mes de registro
    $totales[] = $row['totalReportes'];
    $finalizados[] = $row['reportesFinalizados'];
}

// Convertir los datos a formato JSON para usarlos en JavaScript
echo json_encode(['meses' => $meses, 'totales' => $totales, 'finalizados' => $finalizados]);
?>

