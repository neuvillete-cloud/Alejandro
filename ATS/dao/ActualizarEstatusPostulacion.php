<?php
header('Content-Type: application/json');
session_start(); // Iniciar sesión si es necesario

// Incluir la conexión a la base de datos
include_once("ConexionBD.php");

// Verificar si la solicitud es POST y contiene los datos requeridos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $idPostulacion = intval($_POST['id']);
    $nuevoEstado = intval($_POST['status']);

    // Conectar a la base de datos
    $con = new LocalConector();
    $conex = $con->conectar(); // Tu método personalizado

    if (!$conex) {
        echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
        exit();
    }

    $stmt = $conex->prepare("UPDATE Postulaciones SET IdEstatus = ? WHERE IdPostulacion = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error al preparar la consulta"]);
        exit();
    }

    $stmt->bind_param("ii", $nuevoEstado, $idPostulacion);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Estado actualizado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontró la postulación o ya tenía ese estado"]);
    }

    $stmt->close();
    $conex->close();
} else {
    echo json_encode(["success" => false, "message" => "Datos inválidos o método incorrecto"]);
}
?>
