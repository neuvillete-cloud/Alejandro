<?php
include_once("conexion.php");

header('Content-type: application/json');

try {
    // Obtener los datos enviados por el formulario
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $objeto = isset($_POST['objeto']) ? $_POST['objeto'] : '';
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
    $area = isset($_POST['area']) ? $_POST['area'] : '';

    // Validar que los campos requeridos no estén vacíos
    if (empty($objeto) || empty($descripcion) || empty($area)) {
        throw new Exception('Todos los campos son obligatorios.');
    }

    // Conectar a la base de datos
    $con = new LocalConector();
    $conex = $con->conectar();

    // Preparar la consulta SQL
    $query = "UPDATE Reporte SET objeto = ?, descripcion = ?, area = ? WHERE id = ?";
    $stmt = $conex->prepare($query);
    $stmt->bind_param("sssi", $objeto, $descripcion, $area, $id);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo json_encode(array("success" => true));
    } else {
        throw new Exception('No se pudo actualizar el reporte.');
    }

    // Cerrar la conexión
    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    echo json_encode(array("success" => false, "message" => $e->getMessage()));
}
?>


