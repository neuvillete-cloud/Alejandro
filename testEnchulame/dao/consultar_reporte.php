<?php
include_once("conexion.php");

header('Content-type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Solo seleccionar reportes con status = 1 (reportes activos)
    $query = "SELECT id, objeto, fecha, descripcion, area FROM Reporte WHERE status = 1";
    $resultado = $conex->query($query);

    $reportes = array();
    if ($resultado->num_rows > 0) {
        // Recorremos los resultados y los guardamos en un array
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }

    $conex->close();
    // Devolvemos el array como JSON
    echo json_encode($reportes);
} catch (Exception $e) {
    // Si ocurre algún error, lo devolvemos como JSON
    echo json_encode(array("error" => $e->getMessage()));
}
exit;
?>


