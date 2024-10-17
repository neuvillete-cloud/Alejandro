<?php
include_once("conexion.php");

header('Content-type: application/json');

if (isset($_POST['objeto'], $_POST['Fecha'], $_POST['Descripcion'], $_POST['Area'])) {
    $objeto = $_POST['objeto'];
    $fecha = $_POST['Fecha'];
    $descripcion = $_POST['Descripcion'];
    $area = $_POST['Area'];

    $response = RegistrarReporte($objeto, $fecha, $descripcion, $area);
} else {
    $response = array('status' => 'error', 'message' => 'Error: faltan datos');
}

echo json_encode($response);
exit;

function RegistrarReporte($objeto, $fecha, $descripcion, $area) {
    $con = new LocalConector();
    $conex = $con->conectar();

    $insertReporte = $conex->prepare("INSERT INTO Reporte (objeto, fecha, descripcion, area)
                                       VALUES (?, ?, ?, ?)");
    $insertReporte->bind_param("ssss", $objeto, $fecha, $descripcion, $area);
    $resultado = $insertReporte->execute();

    $conex->close();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => 'Reporte registrado exitosamente');
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar reporte');
    }
    return $response; // Retorna la respuesta
}
?>

