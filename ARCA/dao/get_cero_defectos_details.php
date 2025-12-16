<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado o ID no proporcionado.']);
    exit();
}

$idCeroDefectos = intval($_GET['id']);
$idUsuario = $_SESSION['user_id'];

$con = new LocalConector();
$conex = $con->conectar();

// --- CAMBIO: Consulta adaptada para Cero Defectos ---
// 1. Apunta a la tabla CeroDefectosSolicitudes
// 2. Hace JOIN con CeroDefectosOEM para traer el nombre del fabricante
// 3. Verifica permisos (Dueño o Compartido)
$sql_solicitud = "SELECT cd.*, u.Nombre AS NombreResponsable, oem.NombreOEM 
                  FROM CeroDefectosSolicitudes cd
                  LEFT JOIN Usuarios u ON cd.IdUsuario = u.IdUsuario
                  LEFT JOIN CeroDefectosOEM oem ON cd.IdOEM = oem.IdOEM
                  WHERE cd.IdCeroDefectos = ? 
                  AND (
                      cd.IdUsuario = ? 
                      OR EXISTS (
                          SELECT 1 FROM CeroDefectosSolicitudesCompartidas cdc 
                          WHERE cdc.IdCeroDefectos = cd.IdCeroDefectos
                      )
                  )";

$stmt = $conex->prepare($sql_solicitud);
$stmt->bind_param("ii", $idCeroDefectos, $idUsuario);
// --- FIN DEL CAMBIO ---

$stmt->execute();
$resultado_solicitud = $stmt->get_result();

if ($resultado_solicitud->num_rows === 1) {
    $solicitud = $resultado_solicitud->fetch_assoc();

    // NOTA: Se eliminó la consulta de defectos ya que Cero Defectos no guarda defectos iniciales.

    echo json_encode(['status' => 'success', 'data' => $solicitud]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró la solicitud o no tienes permiso para verla.']);
}

$stmt->close();
$conex->close();
?>
