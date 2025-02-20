<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

// Verificar si se proporciona el FolioSolicitud
if (!isset($_GET['folio'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Folio de solicitud no especificado'
    ]);
    exit;
}

$folioSolicitud = $_GET['folio'];

try {
    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL para obtener la solicitud específica
    $sql = "SELECT * FROM Solicitudes WHERE FolioSolicitud = ?";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    // Vincular parámetros y ejecutar la consulta
    $stmt->bind_param('s', $folioSolicitud);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontró la solicitud
    if ($solicitud = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'data' => $solicitud
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se encontró la solicitud con el folio proporcionado'
        ]);
    }

    // Cerrar recursos
    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>
