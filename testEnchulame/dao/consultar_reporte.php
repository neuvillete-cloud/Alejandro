<?php
include_once("conexion.php");

header('Content-type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    if ($conex->connect_error) {
        throw new Exception('Error de conexión: ' . $conex->connect_error);
    }

    $query = "SELECT id, objeto, fecha, descripcion, area FROM Reporte";
    $resultado = $conex->query($query);

    if (!$resultado) {
        throw new Exception('Error en la consulta: ' . $conex->error);
    }

    $reportes = array();
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $reportes[] = $fila;
        }
    }

    $conex->close();

    $json = json_encode($reportes);
    if ($json === false) {
        throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
    }

    echo $json;
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()));
}
exit;
?>

