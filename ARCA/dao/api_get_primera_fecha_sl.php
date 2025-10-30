<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'ID no proporcionado.', 'primeraFecha' => null];
$idSafeLaunch = $_GET['idSafeLaunch'] ?? null;

if (!$idSafeLaunch || !filter_var($idSafeLaunch, FILTER_VALIDATE_INT)) {
    $response['message'] = 'ID de Safe Launch no válido.';
    echo json_encode($response);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();

try {
    $stmt = $conex->prepare("SELECT MIN(FechaInspeccion) AS primeraFecha 
                             FROM SafeLaunchReportesInspeccion 
                             WHERE IdSafeLaunch = ?");
    $stmt->bind_param("i", $idSafeLaunch);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result && $result['primeraFecha']) {
        $response['status'] = 'success';
        $response['message'] = 'Primera fecha encontrada.';
        $response['primeraFecha'] = $result['primeraFecha'];
    } else {
        $response['status'] = 'not_found';
        $response['message'] = 'Aún no hay reportes de inspección para esta solicitud.';
    }
} catch (Exception $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

$conex->close();
echo json_encode($response);
?>

