<?php
include_once("conexion.php");

if (isset($_POST['id'], $_POST['objeto'], $_POST['fecha'], $_POST['descripcion'], $_POST['area'])) {
    $id = $_POST['id'];
    $objeto = $_POST['objeto'];
    $fecha = $_POST['fecha'];
    $descripcion = $_POST['descripcion'];
    $area = $_POST['area'];

    $con = new LocalConector();
    $conex = $con->conectar();

    $updateReporte = $conex->prepare("UPDATE Reporte SET objeto = ?, fecha = ?, descripcion = ?, area = ? WHERE id = ?");
    $updateReporte->bind_param("ssssi", $objeto, $fecha, $descripcion, $area, $id);
    $resultado = $updateReporte->execute();

    $conex->close();

    if ($resultado) {
        echo "Reporte actualizado exitosamente.";
        // Puedes redirigir a otra página aquí, como una página de éxito o la lista de reportes
    } else {
        echo "Error al actualizar el reporte.";
    }
} else {
    echo "Error: faltan datos.";
}
?>

