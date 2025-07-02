<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include_once("ConexionBD.php");

if (!isset($_GET['IdPostulacion'])) {
    echo json_encode(['error' => 'Falta el parámetro IdPostulacion']);
    exit;
}

$idPostulacion = $_GET['IdPostulacion'];
$response = ['debug' => 'Recibido IdPostulacion', 'id' => $idPostulacion];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("SELECT IdVacante FROM Postulaciones WHERE IdPostulacion = ?");
    $stmt->execute([$idPostulacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $response['error'] = 'No se encontró la postulación';
        echo json_encode($response);
        exit;
    }

    $idVacante = $row['IdVacante'];
    $response['IdVacante'] = $idVacante;

    // Omitimos el resto para hacer debug
    echo json_encode($response);
    exit;
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}
