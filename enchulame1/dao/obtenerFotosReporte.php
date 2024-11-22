<?php
session_start();
include_once("conexion.php");

header('Content-Type: application/json'); // Asegura que la salida sea JSON

if (isset($_GET['id'])) {
    $reporteId = intval($_GET['id']); // Sanitizar el parámetro

    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("
        SELECT IdReporte, FotoProblema, FotoEvidencia
        FROM Reportes
        WHERE IdReporte = ?
    ");
    $stmt->bind_param('i', $reporteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reporte = $result->fetch_assoc();
        $fotos = [];

        if (!empty($reporte['FotoProblema'])) {
            $fotos[] = [
                'url' =>  $reporte['FotoProblema']
            ];
        }
        if (!empty($reporte['FotoEvidencia'])) {
            $fotos[] = [
                'url' => $reporte['FotoEvidencia']
            ];
        }

        if (!empty($fotos)) {
            echo json_encode(['status' => 'success', 'fotos' => $fotos]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontraron fotos asociadas al reporte.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Reporte no encontrado.']);
    }

    $stmt->close();
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID de reporte no proporcionado.']);
}
?>
