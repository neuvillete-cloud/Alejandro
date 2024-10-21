<?php
include_once("conexion.php");

header('Content-type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener los reportes con status = 1
    $query = "SELECT id, objeto, fecha, descripcion, area FROM Reporte WHERE status = 1";
    $resultado = $conex->query($query);

    $reportes = array();

    // Si hay resultados, los añadimos al array
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }

    $conex->close();

    // Devolvemos siempre un array
    echo json_encode($reportes);

} catch (Exception $e) {
    // En caso de error, devolvemos un array vacío y el mensaje de error
    echo json_encode(array());
}
exit;
?>


