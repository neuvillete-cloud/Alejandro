<?php
session_start();
include_once("conexion.php");

if (isset($_POST['id'])) {
    $reporteId = $_POST['id'];
    $nuevoEstatus = 2; // Suponiendo que el ID 2 es "En Proceso" en la tabla de estatus

    // Conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Actualizar el estatus del reporte
    $stmt = $conex->prepare("UPDATE Reportes SET IdEstatus = ? WHERE IdReporte = ?");
    $stmt->bind_param('ii', $nuevoEstatus, $reporteId);

    if ($stmt->execute()) {
        $response = array('status' => 'success', 'message' => 'Estatus actualizado a En Proceso');
    } else {
        $response = array('status' => 'error', 'message' => 'No se pudo actualizar el estatus');
    }

    $stmt->close();
    $conex->close();
} else {
    $response = array('status' => 'error', 'message' => 'ID de reporte no especificado');
}

echo json_encode($response);
?>
