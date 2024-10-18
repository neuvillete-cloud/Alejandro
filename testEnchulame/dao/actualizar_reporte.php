<?php
include_once("conexion.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Aquí puedes obtener los datos del reporte usando el ID
    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("SELECT objeto, fecha, descripcion, area FROM Reporte WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $reporte = $resultado->fetch_assoc();
    } else {
        echo "Reporte no encontrado.";
        exit;
    }

    $conex->close();
} else {
    echo "ID no especificado.";
    exit;
}
?>

