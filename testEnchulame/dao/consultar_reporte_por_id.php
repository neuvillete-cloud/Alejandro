<?php
include_once("conexion.php");

header('Content-type: application/json');

$id=$_GET["id"];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $query = "SELECT id, objeto, fecha, descripcion, area FROM Reporte where id='$id'";
    $resultado = $conex->query($query);

    $reportes = array();
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }

    $conex->close();
    echo json_encode($reportes);
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()));
}
exit;
?>
