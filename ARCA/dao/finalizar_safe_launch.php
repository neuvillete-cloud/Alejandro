<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

// Validar que sea POST y que el usuario esté logueado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    if (!isset($_POST['idSafeLaunch'])) {
        throw new Exception("ID de Safe Launch no proporcionado.");
    }
    $idSafeLaunch = intval($_POST['idSafeLaunch']);

    // Actualizamos el estatus de la solicitud principal a 'Cerrado' (IdEstatus = 4)
    $stmt_update_status = $conex->prepare("UPDATE SafeLaunchSolicitudes SET IdEstatus = 4 WHERE IdSafeLaunch = ?");
    $stmt_update_status->bind_param("i", $idSafeLaunch);

    if (!$stmt_update_status->execute()) {
        throw new Exception("Error al actualizar el estatus principal del Safe Launch a 'Cerrado'.");
    }

    if ($stmt_update_status->affected_rows === 0) {
        throw new Exception("No se encontró la solicitud de Safe Launch para finalizar.");
    }

    $stmt_update_status->close();

    $conex->commit(); // Confirmar transacción si todo fue exitoso
    echo json_encode(['status' => 'success', 'message' => 'Safe Launch finalizado y cerrado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback(); // Revertir cambios si hubo algún error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
}
?>

