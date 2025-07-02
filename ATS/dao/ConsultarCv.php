<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
session_start();

// Validar que llegue el IdPostulacion
if (!isset($_GET['IdPostulacion'])) {
    echo json_encode(['error' => 'ID de postulación no proporcionado']);
    exit;
}

$IdPostulacion = intval($_GET['IdPostulacion']);

try {
    $conn = (new LocalConector())->conectar();

    // Consulta para obtener el CV a partir de la postulación
    $sql = "SELECT c.CV 
            FROM Postulaciones p
            INNER JOIN Candidatos c ON p.IdCandidato = c.IdCandidato
            WHERE p.IdPostulacion = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $IdPostulacion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($fila = $result->fetch_assoc()) {
        if (empty($fila['CV'])) {
            echo json_encode(['error' => 'El candidato no tiene un CV cargado.']);
        } else {
            echo json_encode(['RutaCV' => $fila['CV']]); // URL completa ya guardada
        }
    } else {
        echo json_encode(['error' => 'Postulación no encontrada.']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'Error en base de datos: ' . $e->getMessage()]);
}

