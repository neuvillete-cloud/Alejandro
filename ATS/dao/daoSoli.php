<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

// Verificar si la sesión tiene el número de nómina
if (!isset($_SESSION['NumNomina'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Número de nómina no especificado en la sesión'
    ]);
    exit;
}

$numNomina = $_SESSION['NumNomina'];

try {
    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL para obtener todas las columnas de la tabla Solicitudes del usuario
    $sql = "SELECT * FROM Solicitudes WHERE NumNomina = ?";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    // Vincular parámetros y ejecutar la consulta
    $stmt->bind_param('s', $numNomina);
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener los resultados
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);

    // Cerrar recursos
    $stmt->close();
    $conex->close();

    // Devolver respuesta JSON
    echo json_encode([
        'status' => 'success',
        'data' => $solicitudes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>
