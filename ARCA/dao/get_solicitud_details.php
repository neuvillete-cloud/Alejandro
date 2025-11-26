<?php
// dao/get_solicitud_details.php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

// Validar sesión e ID
if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado o ID no proporcionado.']);
    exit();
}

$idSolicitud = intval($_GET['id']);
$idUsuario = $_SESSION['user_id'];

$con = new LocalConector();
$conex = $con->conectar();

// 1. OBTENER DATOS DE LA SOLICITUD (Con tu lógica de seguridad original)
// Verificamos si es el dueño O si está en SolicitudesCompartidas
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
$stmt->execute();
$resultado_solicitud = $stmt->get_result();

if ($resultado_solicitud->num_rows === 1) {
    $solicitud = $resultado_solicitud->fetch_assoc();

    // 2. OBTENER DEFECTOS ORIGINALES (Tu consulta original)
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

    // 3. OBTENER NUEVOS DEFECTOS ENCONTRADOS (Lógica Nueva Agregada)
    // Buscamos en DefectosEncontrados uniendo con ReportesInspeccion para filtrar por solicitud
    $sql_nuevos = "SELECT 
                        de.IdDefectoEncontrado,
                        de.Cantidad,
                        de.RutaFotoEvidencia,
                        de.NumeroParte,
                        cd.NombreDefecto,
                        ri.FechaInspeccion,
                        ri.NombreInspector
                   FROM DefectosEncontrados de
                   JOIN ReportesInspeccion ri ON de.IdReporte = ri.IdReporte
                   JOIN CatalogoDefectos cd ON de.IdDefectoCatalogo = cd.IdDefectoCatalogo
                   WHERE ri.IdSolicitud = ?";

    $stmt_nuevos = $conex->prepare($sql_nuevos);
    $stmt_nuevos->bind_param("i", $idSolicitud);
    $stmt_nuevos->execute();
    $resultado_nuevos = $stmt_nuevos->get_result();

    $nuevosDefectos = [];
    while ($nuevo = $resultado_nuevos->fetch_assoc()) {
        $nuevosDefectos[] = $nuevo;
    }
    // Agregamos los nuevos defectos al JSON de respuesta
    $solicitud['nuevosDefectos'] = $nuevosDefectos;


    echo json_encode(['status' => 'success', 'data' => $solicitud]);

} else {
    echo json_encode(['status' => 'error', 'message' => 'No se encontró la solicitud o no tienes permiso para verla.']);
}

$stmt->close();
$stmt_defectos->close(); // Cerramos el stmt de defectos
if(isset($stmt_nuevos)) $stmt_nuevos->close(); // Cerramos el stmt de nuevos
$conex->close();
?>