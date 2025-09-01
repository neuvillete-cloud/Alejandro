<?php
include_once("ConexionBD.php");
header('Content-Type: application/json');

// IDs de Estatus de la tabla Postulaciones (ajusta si es necesario)
define('ID_ESTATUS_POR_REVISAR', 1);
define('ID_ESTATUS_EN_PROCESO', 4);
define('ID_ESTATUS_CONTRATADO', 9);
define('ID_ESTATUS_DESCARTADO', 3);

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->set_charset('utf8');

    $response = [];

    // 1. KPIs Principales (Tarjetas)
    $kpis = [];
    $kpis['vacantes_abiertas'] = $conex->query("SELECT COUNT(*) as total FROM Vacantes WHERE IdEstatus = 1")->fetch_assoc()['total'];
    $kpis['total_postulaciones'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones")->fetch_assoc()['total'];
    $kpis['nuevas_hoy'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones WHERE DATE(FechaPostulacion) = CURDATE()")->fetch_assoc()['total'];
    $kpis['contratados_mes'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones WHERE IdEstatus = " . ID_ESTATUS_CONTRATADO . " AND MONTH(fechaSeleccion) = MONTH(CURDATE()) AND YEAR(fechaSeleccion) = YEAR(CURDATE())")->fetch_assoc()['total'];
    $response['kpis'] = $kpis;

    // 2. Gráfica: Postulaciones por Área
    $resultArea = $conex->query("
        SELECT a.NombreArea, COUNT(p.IdPostulacion) as total 
        FROM Postulaciones p
        JOIN Vacantes v ON p.IdVacante = v.IdVacante
        JOIN Area a ON v.IdArea = a.IdArea
        GROUP BY a.NombreArea ORDER BY total DESC
    ");
    $response['postulacionesPorArea'] = $resultArea->fetch_all(MYSQLI_ASSOC);

    // 3. Gráfica: Embudo de Reclutamiento
    $embudo = [];
    $embudo['por_revisar'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones WHERE IdEstatus = " . ID_ESTATUS_POR_REVISAR)->fetch_assoc()['total'];
    $embudo['en_proceso'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones WHERE IdEstatus = " . ID_ESTATUS_EN_PROCESO)->fetch_assoc()['total'];
    $embudo['contratados'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones WHERE IdEstatus = " . ID_ESTATUS_CONTRATADO)->fetch_assoc()['total'];
    $embudo['descartados'] = $conex->query("SELECT COUNT(*) as total FROM Postulaciones WHERE IdEstatus = " . ID_ESTATUS_DESCARTADO)->fetch_assoc()['total'];
    $response['embudoReclutamiento'] = $embudo;

    // 4. Tabla: Top 5 Vacantes con más Postulantes
    $resultTop = $conex->query("
        SELECT v.TituloVacante, COUNT(p.IdPostulacion) as total 
        FROM Postulaciones p 
        JOIN Vacantes v ON p.IdVacante = v.IdVacante 
        GROUP BY v.IdVacante 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $response['topVacantes'] = $resultTop->fetch_all(MYSQLI_ASSOC);

    // 5. Gráfica: Actividad de los últimos 15 días
    $resultActividad = $conex->query("
        SELECT DATE(FechaPostulacion) as fecha, COUNT(*) as total 
        FROM Postulaciones 
        WHERE FechaPostulacion >= DATE_SUB(CURDATE(), INTERVAL 15 DAY) 
        GROUP BY fecha 
        ORDER BY fecha ASC
    ");
    $response['actividadReciente'] = $resultActividad->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $response]);
    $conex->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
