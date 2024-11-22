<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

if (isset($_GET['id'])) {
    $reporteId = $_GET['id'];

    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL para obtener las fotos asociadas al reporte
    $stmt = $conex->prepare("
        SELECT 
            r.IdReporte, 
            r.FotoProblema, 
            r.FotoEvidencia
        FROM 
            Reportes r
        WHERE 
            r.IdReporte = ?
    ");

    $stmt->bind_param('i', $reporteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reporte = $result->fetch_assoc();

        // Construir la respuesta con las URLs de las fotos
        $fotos = [];

        if ($reporte['FotoProblema']) {
            $fotos[] = [
                'url' => 'ruta_a_tus_imagenes/' . $reporte['FotoProblema'] // Ajusta la ruta según tu estructura de archivos
            ];
        }

        if ($reporte['FotoEvidencia']) {
            $fotos[] = [
                'url' => 'ruta_a_tus_imagenes/' . $reporte['FotoEvidencia'] // Ajusta la ruta según tu estructura de archivos
            ];
        }

        if (!empty($fotos)) {
            $response = array('status' => 'success', 'fotos' => $fotos);
        } else {
            $response = array('status' => 'error', 'message' => 'No se encontraron fotos para el reporte');
        }
    } else {
        $response = array('status' => 'error', 'message' => 'Reporte no encontrado');
    }

    $stmt->close();
    $conex->close();
} else {
    $response = array('status' => 'error', 'message' => 'ID de reporte no especificado');
}

echo json_encode($response);
?>

