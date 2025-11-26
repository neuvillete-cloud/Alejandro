<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado o ID no proporcionado.']);
    exit();
}

$idSolicitud = intval($_GET['id']);
$idUsuario = $_SESSION['user_id'];

$con = new LocalConector();
$conex = $con->conectar();

// --- INICIO DE LA MODIFICACIÓN DE LA CONSULTA ---
// Se modifica la consulta para permitir ver la solicitud si el usuario es el creador
// O si la solicitud ha sido compartida (existe en SolicitudesCompartidas).
$sql_solicitud = "SELECT s.*, u.Nombre AS NombreUsuario, p.NombreProvedor, t.NombreTerciaria, l.NombreLugar, e.NombreEstatus, m.RutaArchivo 
                  FROM Solicitudes s
                  LEFT JOIN Usuarios u ON s.IdUsuario = u.IdUsuario
                  LEFT JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                  LEFT JOIN Terciarias t ON s.IdTerciaria = t.IdTerciaria
                  LEFT JOIN Lugares l ON s.IdLugar = l.IdLugar
                  LEFT JOIN Estatus e ON s.IdEstatus = e.IdEstatus
                  LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo
                  WHERE s.IdSolicitud = ? 
                  AND (
                      s.IdUsuario = ? 
                      OR EXISTS (
                          SELECT 1 FROM SolicitudesCompartidas sc WHERE sc.IdSolicitud = s.IdSolicitud
                      )
                  )";

$stmt = $conex->prepare($sql_solicitud);
$stmt->bind_param("ii", $idSolicitud, $idUsuario);
// --- FIN DE LA MODIFICACIÓN DE LA CONSULTA ---

$stmt->execute();
$resultado_solicitud = $stmt->get_result();

if ($resultado_solicitud->num_rows === 1) {
    $solicitud = $resultado_solicitud->fetch_assoc();

    // La consulta de defectos no cambia
    $stmt_defectos = $conex->prepare(
        "SELECT d.*, cd.NombreDefecto 
         FROM Defectos d 
         JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo 
         WHERE d.IdSolicitud = ?"
    );
    $stmt_defectos->bind_param("i", $idSolicitud);
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
