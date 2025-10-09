<?php
// dao/api_generar_reporte.php

header('Content-Type: application/json');
include_once("conexionArca.php");
include_once("verificar_sesion.php");

$response = ['status' => 'error', 'message' => 'Petición no válida.'];

if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

$con = new LocalConector();
$conex = $con->conectar();

try {
    $idSolicitud = $_GET['idSolicitud'] ?? null;
    $tipoReporte = $_GET['tipo'] ?? null;

    if (!$idSolicitud || !$tipoReporte) {
        throw new Exception("No se ha especificado una solicitud o tipo de reporte.");
    }

    // 1. Obtener información base de la solicitud
    $stmt_solicitud = $conex->prepare(
        "SELECT s.NumeroParte, u.Nombre AS Responsable, s.Cantidad, s.TiempoTotalInspeccion 
         FROM Solicitudes s 
         JOIN Usuarios u ON s.IdUsuario = u.IdUsuario 
         WHERE s.IdSolicitud = ?"
    );
    $stmt_solicitud->bind_param("i", $idSolicitud);
    $stmt_solicitud->execute();
    $infoSolicitud = $stmt_solicitud->get_result()->fetch_assoc();
    if (!$infoSolicitud) {
        throw new Exception("Solicitud no encontrada.");
    }

    $whereClause = "WHERE ri.IdSolicitud = ?";
    $params = [$idSolicitud];
    $types = "i";

    if ($tipoReporte === 'parcial') {
        $fechaInicio = $_GET['inicio'] ?? null;
        $fechaFin = $_GET['fin'] ?? null;
        if (!$fechaInicio || !$fechaFin) {
            throw new Exception("Debe proporcionar un rango de fechas para el reporte parcial.");
        }
        $whereClause .= " AND ri.FechaInspeccion BETWEEN ? AND ?";
        array_push($params, $fechaInicio, $fechaFin);
        $types .= "ss";
    }

    // 2. Obtener resumen de inspección
    $query_resumen = "SELECT 
                        SUM(ri.PiezasInspeccionadas) AS inspeccionadas,
                        SUM(ri.PiezasAceptadas) AS aceptadas,
                        SUM(ri.PiezasRetrabajadas) AS retrabajadas,
                        COUNT(ri.IdReporte) AS total_horas
                      FROM ReportesInspeccion ri {$whereClause}";
    $stmt_resumen = $conex->prepare($query_resumen);
    $stmt_resumen->bind_param($types, ...$params);
    $stmt_resumen->execute();
    $resumen = $stmt_resumen->get_result()->fetch_assoc();
    $resumen['rechazadas'] = ($resumen['inspeccionadas'] ?? 0) - ($resumen['aceptadas'] ?? 0);
    $tiempoTotal = ($tipoReporte === 'final' && !empty($infoSolicitud['TiempoTotalInspeccion']))
        ? $infoSolicitud['TiempoTotalInspeccion']
        : ($resumen['total_horas'] ?? 0) . ' hora(s)';

    // 3. Obtener detalle de defectos (combinando originales y nuevos)
    $query_defectos = "SELECT cd.NombreDefecto, SUM(rdo.CantidadEncontrada) AS Cantidad, GROUP_CONCAT(DISTINCT rdo.Lote SEPARATOR ', ') AS Lotes
                       FROM ReporteDefectosOriginales rdo
                       JOIN Defectos d ON rdo.IdDefecto = d.IdDefecto
                       JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo
                       JOIN ReportesInspeccion ri ON rdo.IdReporte = ri.IdReporte
                       {$whereClause}
                       GROUP BY cd.NombreDefecto
                       
                       UNION ALL
                       
                       SELECT cd.NombreDefecto, SUM(de.Cantidad) AS Cantidad, 'N/A' AS Lotes
                       FROM DefectosEncontrados de
                       JOIN CatalogoDefectos cd ON de.IdDefectoCatalogo = cd.IdDefectoCatalogo
                       JOIN ReportesInspeccion ri ON de.IdReporte = ri.IdReporte
                       {$whereClause}
                       GROUP BY cd.NombreDefecto";

    $stmt_defectos = $conex->prepare($query_defectos);
    // Para UNION, los parámetros deben pasarse dos veces
    $union_params = array_merge($params, $params);
    $union_types = $types . $types;
    $stmt_defectos->bind_param($union_types, ...$union_params);
    $stmt_defectos->execute();
    $defectos_result = $stmt_defectos->get_result()->fetch_all(MYSQLI_ASSOC);

    // Consolidar defectos si tienen el mismo nombre
    $defectos_consolidados = [];
    foreach ($defectos_result as $def) {
        if (!isset($defectos_consolidados[$def['NombreDefecto']])) {
            $defectos_consolidados[$def['NombreDefecto']] = ['nombre' => $def['NombreDefecto'], 'cantidad' => 0, 'lotes' => []];
        }
        $defectos_consolidados[$def['NombreDefecto']]['cantidad'] += $def['Cantidad'];
        if ($def['Lotes'] !== 'N/A' && !empty($def['Lotes'])) {
            $lotes_arr = explode(', ', $def['Lotes']);
            $defectos_consolidados[$def['NombreDefecto']]['lotes'] = array_unique(array_merge($defectos_consolidados[$def['NombreDefecto']]['lotes'], $lotes_arr));
        }
    }

    // 4. Construir la respuesta final
    $response['status'] = 'success';
    $response['reporte'] = [
        'titulo' => $tipoReporte === 'parcial' ? 'Reporte Parcial de Contención' : 'Reporte Final de Contención',
        'folio' => $idSolicitud,
        'info' => [
            'numeroParte' => $infoSolicitud['NumeroParte'],
            'responsable' => $infoSolicitud['Responsable'],
            'cantidadTotal' => $infoSolicitud['Cantidad']
        ],
        'resumen' => [
            'inspeccionadas' => $resumen['inspeccionadas'] ?? 0,
            'aceptadas' => $resumen['aceptadas'] ?? 0,
            'rechazadas' => $resumen['rechazadas'] ?? 0,
            'retrabajadas' => $resumen['retrabajadas'] ?? 0,
            'tiempoTotal' => $tiempoTotal
        ],
        'defectos' => array_values($defectos_consolidados)
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conex)) $conex->close();
    echo json_encode($response);
}
?>

