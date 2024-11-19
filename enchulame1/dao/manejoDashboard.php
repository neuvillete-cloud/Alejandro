<?php
// Conectar a la base de datos
include_once('conexion.php');
$con = new LocalConector();
$conex = $con->conectar();

// Consulta para obtener los reportes por mes y su estado con los nombres de los meses
$query = "
    SELECT 
        MONTHNAME(FechaRegistro) AS mes,  -- Devuelve el nombre del mes
        COUNT(*) AS totalReportes,
        SUM(CASE WHEN IdEstatus = 3 THEN 1 ELSE 0 END) AS reportesResueltos
    FROM Reportes
    WHERE YEAR(FechaRegistro) = YEAR(CURDATE())  -- Solo este año
    GROUP BY MONTH(FechaRegistro)  -- Agrupamos por mes (pero ya mostramos el nombre del mes)
    ORDER BY MONTH(FechaRegistro);  -- Ordenamos por mes
";

$result = $conex->query($query);

// Arreglos para los datos
$meses = [];
$totales = [];
$resueltos = [];

while ($row = $result->fetch_assoc()) {
    $meses[] = $row['mes'];  // Ahora 'mes' es el nombre del mes
    $totales[] = $row['totalReportes'];
    $resueltos[] = $row['reportesResueltos'];
}

// Convertir los datos a formato JSON para usarlos en JavaScript
echo json_encode(['meses' => $meses, 'totales' => $totales, 'resueltos' => $resueltos]);
?>
