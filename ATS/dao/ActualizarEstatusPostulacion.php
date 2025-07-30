<?php
header('Content-Type: application/json');
session_start();
date_default_timezone_set('America/Mexico_City');

include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $idPostulacion = intval($_POST['id']);
    $nuevoEstado = intval($_POST['status']);

    $con = new LocalConector();
    $conex = $con->conectar();

    if (!$conex) {
        echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
        exit();
    }

    if ($nuevoEstado === 9) {
        // ✅ Verifica si la fecha es exactamente '0000-00-00 00:00:00' en lugar de NULL
        $fechaSeleccion = date("Y-m-d H:i:s");
        $sql = "UPDATE Postulaciones 
                SET IdEstatus = ?, 
                    fechaSeleccion = IF(fechaSeleccion = '0000-00-00 00:00:00', ?, fechaSeleccion) 
                WHERE IdPostulacion = ?";
        $stmt = $conex->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Error al preparar consulta con fecha"]);
            exit();
        }
        $stmt->bind_param("isi", $nuevoEstado, $fechaSeleccion, $idPostulacion);
    } else {
        $sql = "UPDATE Postulaciones SET IdEstatus = ? WHERE IdPostulacion = ?";
        $stmt = $conex->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Error al preparar consulta"]);
            exit();
        }
        $stmt->bind_param("ii", $nuevoEstado, $idPostulacion);
    }

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

