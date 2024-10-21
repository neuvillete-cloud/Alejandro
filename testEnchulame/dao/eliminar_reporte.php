<?php
include_once("conexion.php");

header('Content-type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Obtener el ID del reporte a eliminar (cambiar status a 0)
    $idReporte = $_POST['id'];

    // Actualizar el campo status a 0 (eliminación lógica)
    $query = "UPDATE Reporte SET status = 0 WHERE id = ?";
    $stmt = $conex->prepare($query);
    $stmt->bind_param("i", $idReporte);

    if ($stmt->execute()) {
        echo json_encode(array("success" => true));
    } else {
        echo json_encode(array("success" => false, "error" => "No se pudo eliminar el reporte."));
    }

    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    echo json_encode(array("success" => false, "error" => $e->getMessage()));
}
exit;
?>

