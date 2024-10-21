<?php
include_once("conexion.php");

session_start(); // Asegúrate de iniciar la sesión para acceder a la nómina
header('Content-type: application/json');

if (isset($_POST['objeto'], $_POST['Descripcion'], $_POST['Area'])) {
    $objeto = $_POST['objeto'];
    $descripcion = $_POST['Descripcion'];
    $area = $_POST['Area'];

    // Establece la fecha actual
    $fecha = date('Y-m-d H:i:s');

    // Obtén el número de nómina de la sesión
    $nomina = isset($_SESSION['nomina']) ? $_SESSION['nomina'] : null;

    $response = RegistrarReporte($objeto, $fecha, $descripcion, $area, $nomina);
} else {
    $response = array('status' => 'error', 'message' => 'Error: faltan datos');
}

echo json_encode($response);
exit;

function RegistrarReporte($objeto, $fecha, $descripcion, $area, $nomina) {
    $con = new LocalConector();
    $conex = $con->conectar();

    $insertReporte = $conex->prepare("INSERT INTO Reporte (objeto, fecha, descripcion, area, nomina) 
                                       VALUES (?, ?, ?, ?, ?)");
    $insertReporte->bind_param("ssssi", $objeto, $fecha, $descripcion, $area, $nomina);
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



