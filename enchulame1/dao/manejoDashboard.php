<?php
// Conectar a la base de datos
include_once('conexion.php');
$con = new LocalConector();
$conex = $con->conectar();

// Consulta para obtener los reportes por mes y su estado (registrados y finalizados)
$query = "
    SELECT 
        MONTHNAME(FechaRegistro) AS mes,  -- Devuelve el nombre del mes
        COUNT(CASE WHEN FechaRegistro IS NOT NULL THEN 1 END) AS totalReportes,  -- Reportes con FechaRegistro (registrados)
        COUNT(CASE WHEN IdEstatus = 3 AND FechaFinalizado IS NOT NULL THEN 1 END) AS reportesFinalizados  -- Reportes con IdEstatus = 3 y FechaFinalizado
    FROM Reportes
    WHERE YEAR(FechaRegistro) = YEAR(CURDATE())  -- Solo este año
    GROUP BY MONTH(FechaRegistro)  -- Agrupamos por mes
    ORDER BY MONTH(FechaRegistro);  -- Ordenamos por mes
";

$result = $conex->query($query);

// Arreglos para los datos
$meses = [];
$totales = [];
$finalizados = [];

while ($row = $result->fetch_assoc()) {
    $meses[] = $row['mes'];  // Nombre del mes
    $totales[] = $row['totalReportes'];  // Total de reportes registrados
    $finalizados[] = $row['reportesFinalizados'];  // Total de reportes finalizados
}

// Convertir los datos a formato JSON para usarlos en JavaScript
echo json_encode(['meses' => $meses, 'totales' => $totales, 'finalizados' => $finalizados]);
?>
