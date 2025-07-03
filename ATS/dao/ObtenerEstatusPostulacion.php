<?php
header('Content-Type: application/json');
include_once("ConexionBD.php");

if (!isset($_GET['IdPostulacion'])) {
    echo json_encode(["success" => false, "message" => "Falta el IdPostulacion"]);
    exit();
}

$IdPostulacion = intval($_GET['IdPostulacion']);

$con = new LocalConector();
$conexion = $con->conectar();

if (!$conexion) {
    echo json_encode(["success" => false, "message" => "Error de conexión"]);
    exit();
}

$stmt = $conexion->prepare("SELECT IdEstatus FROM Postulaciones WHERE IdPostulacion = ?");
$stmt->bind_param("i", $IdPostulacion);
$stmt->execute();
$stmt->bind_result($IdEstatus);

if ($stmt->fetch()) {
    echo json_encode(["success" => true, "IdEstatus" => $IdEstatus]);
} else {
    echo json_encode(["success" => false, "message" => "Postulación no encontrada"]);
}

$stmt->close();
$conexion->close();
?>
