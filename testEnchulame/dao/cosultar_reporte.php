<?php

// Incluimos el archivo de conexión a la base de datos
include_once("conexion.php");

// Especificamos que el contenido será JSON
header('Content-type: application/json');

// Creamos una instancia de la clase LocalConector para conectarnos a la base de datos
$con = new LocalConector();
$conex = $con->conectar();

// Preparamos la consulta SQL para obtener todos los reportes
$query = "SELECT id, objeto, fecha, descripcion, area FROM Reporte";
$resultado = $conex->query($query);

// Creamos un array para almacenar los reportes
$reportes = array();

if ($resultado->num_rows > 0) {
    // Recorremos cada fila de los resultados
    while ($fila = $resultado->fetch_assoc()) {
        $reportes[] = $fila; // Agregamos cada reporte al array
    }
}

// Cerramos la conexión
$conex->close();

// Devolvemos los reportes en formato JSON
echo json_encode($reportes);
exit;


