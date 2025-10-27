<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado o ID no proporcionado.']);
    exit();
}

$idSafeLaunch = intval($_GET['id']);
$idUsuario = $_SESSION['user_id'];

$con = new LocalConector();
$conex = $con->conectar();

// --- CAMBIO: Consulta adaptada para Safe Launch ---
// Se obtiene la solicitud si el usuario es el creador
// O si la solicitud ha sido compartida (existe en SafeLaunchSolicitudesCompartidas).
$sql_solicitud = "SELECT sl.*, u.Nombre AS NombreResponsable 
                  FROM SafeLaunchSolicitudes sl
                  LEFT JOIN Usuarios u ON sl.IdUsuario = u.IdUsuario
                  WHERE sl.IdSafeLaunch = ? 
                  AND (
                      sl.IdUsuario = ? 
                      OR EXISTS (
                          SELECT 1 FROM SafeLaunchSolicitudesCompartidas slsc 
                          WHERE slsc.IdSafeLaunch = sl.IdSafeLaunch
                      )
                  )";

$stmt = $conex->prepare($sql_solicitud);
$stmt->bind_param("ii", $idSafeLaunch, $idUsuario);
// --- FIN DEL CAMBIO ---

$stmt->execute();
$resultado_solicitud = $stmt->get_result();

if ($resultado_solicitud->num_rows === 1) {
    $solicitud = $resultado_solicitud->fetch_assoc();

    // --- CAMBIO: Consulta de defectos adaptada para Safe Launch ---
    $stmt_defectos = $conex->prepare(
        "SELECT sldc.NombreDefecto 
         FROM SafeLaunchDefectos sld 
         JOIN SafeLaunchCatalogoDefectos sldc ON sld.IdSLDefectoCatalogo = sldc.IdSLDefectoCatalogo 
         WHERE sld.IdSafeLaunch = ?"
    );
    $stmt_defectos->bind_param("i", $idSafeLaunch);
    // --- FIN DEL CAMBIO ---

    $stmt_defectos->execute();
    $resultado_defectos = $stmt_defectos->get_result();

    $defectos = [];
    while ($defecto = $resultado_defectos->fetch_assoc()) {
        $defectos[] = $defecto;
    }

    $solicitud['defectos'] = $defectos;

    echo json_encode(['status' => 'success', 'data' => $solicitud]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró la solicitud o no tienes permiso para verla.']);
}

$stmt->close();
$conex->close();
?>

