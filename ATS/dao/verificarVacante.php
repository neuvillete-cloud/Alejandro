<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');

if (!isset($_GET['idSolicitud'])) {
    echo json_encode(["error" => "Falta idSolicitud"]);
    exit;
}

$idSolicitud = $_GET['idSolicitud'];
$conn = (new LocalConector())->conectar();

$stmt = $conn->prepare("SELECT COUNT(*) FROM Vacantes WHERE IdSolicitud = ?");
$stmt->bind_param("i", $idSolicitud);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();
$conn->close();

echo json_encode(["existe" => $count > 0]);
