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

/**
 * Función para calcular el turno del Shift Leader.
 */
function calcularTurnoShiftLeader($rangoHoraStr) {
    if (empty($rangoHoraStr)) return 'N/A';
    preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm))/i', $rangoHoraStr, $matches);
    if (empty($matches[1])) return 'N/A';
    $hora_inicio_timestamp = strtotime($matches[1]);
    $primer_turno_inicio = strtotime('06:30 am');
    $primer_turno_fin = strtotime('02:30 pm');
    $segundo_turno_inicio = strtotime('02:40 pm');
    $segundo_turno_fin = strtotime('10:30 pm');
    if ($hora_inicio_timestamp >= $primer_turno_inicio && $hora_inicio_timestamp <= $primer_turno_fin) { return 'Primer Turno'; }
    elseif ($hora_inicio_timestamp >= $segundo_turno_inicio && $hora_inicio_timestamp <= $segundo_turno_fin) { return 'Segundo Turno'; }
    else { return 'Tercer Turno / Otro'; }
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
    $stmt_solicitud = $conex->prepare("SELECT s.NumeroParte, u.Nombre AS Responsable, s.Cantidad, s.TiempoTotalInspeccion FROM Solicitudes s JOIN Usuarios u ON s.IdUsuario = u.IdUsuario WHERE s.IdSolicitud = ?");
    $stmt_solicitud->bind_param("i", $idSolicitud);
    $stmt_solicitud->execute();
    $infoSolicitud = $stmt_solicitud->get_result()->fetch_assoc();
    if (!$infoSolicitud) { throw new Exception("Solicitud no encontrada."); }
    $isVariosPartes = (strtolower($infoSolicitud['NumeroParte']) === 'varios');

    $whereClause = "WHERE ri.IdSolicitud = ?";
    $params = [$idSolicitud];
    $types = "i";

    if ($tipoReporte === 'parcial') {
        $fechaInicio = $_GET['inicio'] ?? null;
        $fechaFin = $_GET['fin'] ?? null;
        if (!$fechaInicio || !$fechaFin) { throw new Exception("Debe proporcionar un rango de fechas para el reporte parcial."); }
        $whereClause .= " AND ri.FechaInspeccion BETWEEN ? AND ?";
        array_push($params, $fechaInicio, $fechaFin);
        $types .= "ss";
    }

    // 2. Obtener resumen de inspección
    $query_resumen = "SELECT SUM(ri.PiezasInspeccionadas) AS inspeccionadas, SUM(ri.PiezasAceptadas) AS aceptadas, SUM(ri.PiezasRetrabajadas) AS retrabajadas, COUNT(ri.IdReporte) AS total_horas FROM ReportesInspeccion ri {$whereClause}";
    $stmt_resumen = $conex->prepare($query_resumen);
    $stmt_resumen->bind_param($types, ...$params);
    $stmt_resumen->execute();
    $resumen = $stmt_resumen->get_result()->fetch_assoc();
    $resumen['rechazadas'] = ($resumen['inspeccionadas'] ?? 0) - ($resumen['aceptadas'] ?? 0);
    $tiempoTotal = ($tipoReporte === 'final' && !empty($infoSolicitud['TiempoTotalInspeccion'])) ? $infoSolicitud['TiempoTotalInspeccion'] : ($resumen['total_horas'] ?? 0) . ' hora(s)';

    // 3. OBTENER DETALLE DE DEFECTOS
    $defectos_finales = [];
    $numeros_parte_lista = [];

    if ($isVariosPartes) {
        $query_partes = "SELECT DISTINCT rdp.NumeroParte FROM ReporteDesglosePartes rdp JOIN ReportesInspeccion ri ON rdp.IdReporte = ri.IdReporte {$whereClause}";
        $stmt_partes = $conex->prepare($query_partes);
        $stmt_partes->bind_param($types, ...$params);
        $stmt_partes->execute();
        $partes_result = $stmt_partes->get_result();
        while($row = $partes_result->fetch_assoc()) { $numeros_parte_lista[] = $row['NumeroParte']; }

        $query_defectos_varios = "(SELECT rdo.NumeroParte, cd.NombreDefecto, SUM(rdo.CantidadEncontrada) AS Cantidad, GROUP_CONCAT(DISTINCT rdo.Lote SEPARATOR ', ') AS Lotes FROM ReporteDefectosOriginales rdo JOIN Defectos d ON rdo.IdDefecto = d.IdDefecto JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo JOIN ReportesInspeccion ri ON rdo.IdReporte = ri.IdReporte {$whereClause} AND rdo.NumeroParte IS NOT NULL GROUP BY rdo.NumeroParte, cd.NombreDefecto) UNION ALL (SELECT de.NumeroParte, cd.NombreDefecto, SUM(de.Cantidad) AS Cantidad, 'N/A' AS Lotes FROM DefectosEncontrados de JOIN CatalogoDefectos cd ON de.IdDefectoCatalogo = cd.IdDefectoCatalogo JOIN ReportesInspeccion ri ON de.IdReporte = ri.IdReporte {$whereClause} AND de.NumeroParte IS NOT NULL GROUP BY de.NumeroParte, cd.NombreDefecto)";
        $stmt_defectos = $conex->prepare($query_defectos_varios);
        $union_params = array_merge($params, $params);
        $union_types = $types . $types;
        $stmt_defectos->bind_param($union_types, ...$union_params);
        $stmt_defectos->execute();
        $defectos_result = $stmt_defectos->get_result()->fetch_all(MYSQLI_ASSOC);
        $defectos_por_parte = [];
        foreach($defectos_result as $def) {
            if (!isset($defectos_por_parte[$def['NumeroParte']])) { $defectos_por_parte[$def['NumeroParte']] = ['numeroParte' => $def['NumeroParte'], 'defectos' => []]; }
            $defectos_por_parte[$def['NumeroParte']]['defectos'][] = ['nombre' => $def['NombreDefecto'], 'cantidad' => (int)$def['Cantidad'], 'lotes' => ($def['Lotes'] !== 'N/A' && !empty($def['Lotes'])) ? explode(', ', $def['Lotes']) : []];
        }
        $defectos_finales = array_values($defectos_por_parte);
    } else {
        $query_defectos_single = "(SELECT cd.NombreDefecto, SUM(rdo.CantidadEncontrada) AS Cantidad, GROUP_CONCAT(DISTINCT rdo.Lote SEPARATOR ', ') AS Lotes FROM ReporteDefectosOriginales rdo JOIN Defectos d ON rdo.IdDefecto = d.IdDefecto JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo JOIN ReportesInspeccion ri ON rdo.IdReporte = ri.IdReporte {$whereClause} GROUP BY cd.NombreDefecto) UNION ALL (SELECT cd.NombreDefecto, SUM(de.Cantidad) AS Cantidad, 'N/A' AS Lotes FROM DefectosEncontrados de JOIN CatalogoDefectos cd ON de.IdDefectoCatalogo = cd.IdDefectoCatalogo JOIN ReportesInspeccion ri ON de.IdReporte = ri.IdReporte {$whereClause} GROUP BY cd.NombreDefecto)";
        $stmt_defectos = $conex->prepare($query_defectos_single);
        $union_params = array_merge($params, $params);
        $union_types = $types . $types;
        $stmt_defectos->bind_param($union_types, ...$union_params);
        $stmt_defectos->execute();
        $defectos_result = $stmt_defectos->get_result()->fetch_all(MYSQLI_ASSOC);
        $defectos_consolidados = [];
        foreach ($defectos_result as $def) {
            if (!isset($defectos_consolidados[$def['NombreDefecto']])) { $defectos_consolidados[$def['NombreDefecto']] = ['nombre' => $def['NombreDefecto'], 'cantidad' => 0, 'lotes' => []]; }
            $defectos_consolidados[$def['NombreDefecto']]['cantidad'] += $def['Cantidad'];
            if ($def['Lotes'] !== 'N/A' && !empty($def['Lotes'])) { $defectos_consolidados[$def['NombreDefecto']]['lotes'] = array_unique(array_merge($defectos_consolidados[$def['NombreDefecto']]['lotes'], explode(', ', $def['Lotes']))); }
        }
        $defectos_finales = array_values($defectos_consolidados);
    }

    // 4. OBTENER DESGLOSE DIARIO Y HORA X HORA
    $totales_por_dia = [];
    $query_diario_totals = "SELECT ri.FechaInspeccion, SUM(ri.PiezasInspeccionadas) AS totalInspeccionadasDia, SUM(ri.PiezasAceptadas) AS totalAceptadasDia, SUM(ri.PiezasRetrabajadas) AS totalRetrabajadasDia FROM ReportesInspeccion ri {$whereClause} GROUP BY ri.FechaInspeccion ORDER BY ri.FechaInspeccion ASC";
    $stmt_diario_totals = $conex->prepare($query_diario_totals);
    $stmt_diario_totals->bind_param($types, ...$params);
    $stmt_diario_totals->execute();
    $result_diario_totals = $stmt_diario_totals->get_result();
    while ($row = $result_diario_totals->fetch_assoc()) {
        $totales_por_dia[$row['FechaInspeccion']] = [ 'inspeccionadas' => (int)$row['totalInspeccionadasDia'], 'aceptadas' => (int)$row['totalAceptadasDia'], 'retrabajadas' => (int)$row['totalRetrabajadasDia'] ];
    }

    $query_entradas = "SELECT ri.IdReporte, ri.FechaInspeccion, ri.RangoHora, ri.Comentarios, ri.PiezasInspeccionadas, ri.PiezasAceptadas, ri.PiezasRetrabajadas, ri.NombreInspector, ctm.Razon AS RazonTiempoMuerto FROM ReportesInspeccion ri LEFT JOIN CatalogoTiempoMuerto ctm ON ri.IdTiempoMuerto = ctm.IdTiempoMuerto {$whereClause} ORDER BY ri.FechaInspeccion ASC, ri.RangoHora ASC";
    $stmt_entradas = $conex->prepare($query_entradas);
    $stmt_entradas->bind_param($types, ...$params);
    $stmt_entradas->execute();
    $result_entradas = $stmt_entradas->get_result();
    $entradas_por_dia = [];
    $reporte_ids = [];
    while ($row = $result_entradas->fetch_assoc()) {
        $fecha = $row['FechaInspeccion'];
        if (!isset($entradas_por_dia[$fecha])) { $entradas_por_dia[$fecha] = []; }
        $row['turno'] = calcularTurnoShiftLeader($row['RangoHora']);
        $entradas_por_dia[$fecha][] = $row;
        $reporte_ids[] = $row['IdReporte'];
    }

    $partes_por_reporte = [];
    if ($isVariosPartes && count($reporte_ids) > 0) {
        $ids_string = implode(',', $reporte_ids);
        $query_partes_detalle = "SELECT IdReporte, NumeroParte, Cantidad FROM ReporteDesglosePartes WHERE IdReporte IN ({$ids_string})";
        $result_partes_detalle = $conex->query($query_partes_detalle);
        while ($row = $result_partes_detalle->fetch_assoc()) {
            if (!isset($partes_por_reporte[$row['IdReporte']])) { $partes_por_reporte[$row['IdReporte']] = []; }
            $partes_por_reporte[$row['IdReporte']][] = ['numeroParte' => $row['NumeroParte'], 'cantidad' => $row['Cantidad']];
        }
    }

    $desgloseDiario = [];
    foreach ($entradas_por_dia as $fecha => $entradas) {
        $entradas_con_partes = [];
        foreach ($entradas as $entrada) {
            if ($isVariosPartes) { $entrada['partes'] = $partes_por_reporte[$entrada['IdReporte']] ?? []; }
            $entradas_con_partes[] = $entrada;
        }
        $desgloseDiario[] = [ 'fecha' => $fecha, 'totales' => $totales_por_dia[$fecha] ?? ['inspeccionadas' => 0, 'aceptadas' => 0, 'retrabajadas' => 0], 'entradas' => $entradas_con_partes ];
    }

    // --- NUEVO: 5. OBTENER DATOS PARA DASHBOARDS ---
    $dashboardData = [];
    // 5.1 Pareto de Defectos
    $todos_los_defectos = [];
    if ($isVariosPartes) {
        foreach ($defectos_finales as $grupo) {
            foreach ($grupo['defectos'] as $defecto) {
                if (!isset($todos_los_defectos[$defecto['nombre']])) { $todos_los_defectos[$defecto['nombre']] = 0; }
                $todos_los_defectos[$defecto['nombre']] += $defecto['cantidad'];
            }
        }
    } else {
        foreach ($defectos_finales as $defecto) { $todos_los_defectos[$defecto['nombre']] = $defecto['cantidad']; }
    }
    arsort($todos_los_defectos);
    $totalDefectos = array_sum($todos_los_defectos);
    $paretoData = [];
    $cumulativePercentage = 0;
    if ($totalDefectos > 0) {
        $top5_defectos = array_slice($todos_los_defectos, 0, 5, true);
        foreach ($top5_defectos as $nombre => $cantidad) {
            $percentage = ($cantidad / $totalDefectos) * 100;
            $cumulativePercentage += $percentage;
            $paretoData[] = [ 'defecto' => $nombre, 'cantidad' => $cantidad, 'porcentajeAcumulado' => round($cumulativePercentage) ];
        }
    }
    $dashboardData['pareto'] = $paretoData;

    // 5.2 Rechazadas por Semana
    $query_semanal = "SELECT YEARWEEK(ri.FechaInspeccion, 1) as semana, SUM(ri.PiezasInspeccionadas - ri.PiezasAceptadas) as rechazadas_semana FROM ReportesInspeccion ri {$whereClause} GROUP BY semana ORDER BY semana ASC";
    $stmt_semanal = $conex->prepare($query_semanal);
    $stmt_semanal->bind_param($types, ...$params);
    $stmt_semanal->execute();
    $rechazadas_semanal = $stmt_semanal->get_result()->fetch_all(MYSQLI_ASSOC);
    $dashboardData['rechazadasPorSemana'] = $rechazadas_semanal;

    // 6. Construir la respuesta final
    $response['status'] = 'success';
    $final_report_data = [
        'titulo' => $tipoReporte === 'parcial' ? 'Reporte Parcial de Contención' : 'Reporte Final de Contención',
        'folio' => $idSolicitud,
        'info' => [ 'numeroParte' => $infoSolicitud['NumeroParte'], 'responsable' => $infoSolicitud['Responsable'], 'cantidadTotal' => $infoSolicitud['Cantidad'] ],
        'resumen' => [ 'inspeccionadas' => (int)($resumen['inspeccionadas'] ?? 0), 'aceptadas' => (int)($resumen['aceptadas'] ?? 0), 'rechazadas' => (int)($resumen['rechazadas'] ?? 0), 'retrabajadas' => (int)($resumen['retrabajadas'] ?? 0), 'tiempoTotal' => $tiempoTotal ],
        'desgloseDiario' => $desgloseDiario,
        'dashboardData' => $dashboardData // Se añaden los datos para las gráficas
    ];

    if ($isVariosPartes) {
        $final_report_data['info']['numerosParteLista'] = $numeros_parte_lista;
        $final_report_data['defectosPorParte'] = $defectos_finales;
    } else {
        $final_report_data['defectos'] = $defectos_finales;
    }

    $response['reporte'] = $final_report_data;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conex)) $conex->close();
    echo json_encode($response);
}
?>
