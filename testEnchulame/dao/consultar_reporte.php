<?php
// Incluimos el archivo de conexión a la base de datos
include_once("conexion.php");

// Especificamos que el contenido será JSON
header('Content-type: application/json');

// Conexión a la base de datos
$con = new LocalConector();
$conex = $con->conectar();

// Realizamos la consulta
$query = "SELECT id, objeto, fecha, descripcion, area FROM Reporte";
$resultado = $conex->query($query);

// Verificamos si hay reportes
$reportes = array();
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $reportes[] = $fila;
    }
}

// Cerramos la conexión
$conex->close();

// Devolvemos el array en formato JSON
echo json_encode($reportes);
exit;
?>
