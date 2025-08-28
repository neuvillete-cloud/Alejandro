<?php
session_start();
require_once "ConexionBD.php";

header('Content-Type: application/json');

// Verificamos que se haya enviado un ID de vacante
if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID de vacante no proporcionado.']);
    exit;
}

$idVacante = $_POST['id'];

// Usamos la sesión para contar solo UNA visita por usuario para cada vacante
if (!isset($_SESSION['vacantes_vistas'])) {
    $_SESSION['vacantes_vistas'] = [];
}

// Solo actualizamos el contador si el usuario no ha visto esta vacante en esta sesión
if (!in_array($idVacante, $_SESSION['vacantes_vistas'])) {
    try {
        $conn = (new LocalConector())->conectar();

        $stmt = $conn->prepare("UPDATE Vacantes SET Visitas = Visitas + 1 WHERE IdVacante = ?");
        $stmt->bind_param('i', $idVacante);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Si la actualización fue exitosa, guardamos el ID en la sesión
            $_SESSION['vacantes_vistas'][] = $idVacante;
            echo json_encode(['status' => 'success', 'message' => 'Vista registrada.']);
        } else {
            echo json_encode(['status' => 'noop', 'message' => 'No se actualizó la vista (ID no encontrado).']);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
    }
} else {
    // Si ya la vio, no hacemos nada, solo informamos
    echo json_encode(['status' => 'already_viewed', 'message' => 'El usuario ya ha visto esta vacante en esta sesión.']);
}
?>
