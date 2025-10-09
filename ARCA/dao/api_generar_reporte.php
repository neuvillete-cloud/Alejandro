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
    $isVariosPartes = (strtolower($infoSolicitud['NumeroParte']) === 'varios');

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

    // 2. Obtener resumen de inspección (igual para ambos casos)
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

    // 3. Obtener detalle de defectos (lógica del paso anterior)
    // ... (Se mantiene la lógica anterior para el resumen de defectos)
    $defectos_finales = [];
    $numeros_parte_lista = [];

    if ($isVariosPartes) {
        $query_partes = "SELECT DISTINCT rdp.NumeroParte FROM ReporteDesglosePartes rdp JOIN ReportesInspeccion ri ON rdp.IdReporte = ri.IdReporte {$whereClause}";
        $stmt_partes = $conex->prepare($query_partes);
        $stmt_partes->bind_param($types, ...$params);
        $stmt_partes->execute();
        $partes_result = $stmt_partes->get_result();
        while($row = $partes_result->fetch_assoc()) { $numeros_parte_lista[] = $row['NumeroParte']; }
    }


    // 4. --- CORRECCIÓN: OBTENER DESGLOSE DIARIO ---
    $desgloseDiario = [];
    // Primero, obtenemos los totales diarios sin JOINs que dupliquen
    $query_diario_totals = "SELECT
                                ri.FechaInspeccion,
                                SUM(ri.PiezasInspeccionadas) AS totalInspeccionadasDia,
                                SUM(ri.PiezasAceptadas) AS totalAceptadasDia,
                                SUM(ri.PiezasRetrabajadas) AS totalRetrabajadasDia
                            FROM ReportesInspeccion ri
                            {$whereClause}
                            GROUP BY ri.FechaInspeccion
                            ORDER BY ri.FechaInspeccion ASC";
    $stmt_diario_totals = $conex->prepare($query_diario_totals);
    $stmt_diario_totals->bind_param($types, ...$params);
    $stmt_diario_totals->execute();
    $result_diario_totals = $stmt_diario_totals->get_result();
    while ($row = $result_diario_totals->fetch_assoc()) {
        $desgloseDiario[$row['FechaInspeccion']] = [
            'fecha' => $row['FechaInspeccion'],
            'inspeccionadas' => (int)$row['totalInspeccionadasDia'],
            'aceptadas' => (int)$row['totalAceptadasDia'],
            'retrabajadas' => (int)$row['totalRetrabajadasDia'],
            'partes' => [] // Inicializamos el array de partes
        ];
    }

    if ($isVariosPartes) {
        // Ahora, obtenemos el desglose de partes por día
        $query_diario_parts = "SELECT
                                ri.FechaInspeccion,
                                rdp.NumeroParte,
                                SUM(rdp.Cantidad) AS Cantidad
                            FROM ReporteDesglosePartes rdp
                            JOIN ReportesInspeccion ri ON rdp.IdReporte = ri.IdReporte
                            {$whereClause}
                            GROUP BY ri.FechaInspeccion, rdp.NumeroParte
                            ORDER BY ri.FechaInspeccion ASC";
        $stmt_diario_parts = $conex->prepare($query_diario_parts);
        $stmt_diario_parts->bind_param($types, ...$params);
        $stmt_diario_parts->execute();
        $result_diario_parts = $stmt_diario_parts->get_result();
        while ($row = $result_diario_parts->fetch_assoc()) {
            if (isset($desgloseDiario[$row['FechaInspeccion']])) {
                $desgloseDiario[$row['FechaInspeccion']]['partes'][] = [
                    'numeroParte' => $row['NumeroParte'],
                    'cantidad' => (int)$row['Cantidad']
                ];
            }
        }
    }
    $desgloseDiario = array_values($desgloseDiario); // Convertir a array indexado
    // --- FIN DE LA CORRECCIÓN ---


    // 5. Construir la respuesta final
    $response['status'] = 'success';
    $final_report_data = [
        'titulo' => $tipoReporte === 'parcial' ? 'Reporte Parcial de Contención' : 'Reporte Final de Contención',
        'folio' => $idSolicitud,
        'info' => [
            'numeroParte' => $infoSolicitud['NumeroParte'],
            'responsable' => $infoSolicitud['Responsable'],
            'cantidadTotal' => $infoSolicitud['Cantidad']
        ],
        'resumen' => [
            'inspeccionadas' => (int)($resumen['inspeccionadas'] ?? 0),
            'aceptadas' => (int)($resumen['aceptadas'] ?? 0),
            'rechazadas' => (int)($resumen['rechazadas'] ?? 0),
            'retrabajadas' => (int)($resumen['retrabajadas'] ?? 0),
            'tiempoTotal' => $tiempoTotal
        ],
        'desgloseDiario' => $desgloseDiario
    ];

    if ($isVariosPartes) {
        $final_report_data['info']['numerosParteLista'] = $numeros_parte_lista;
    }

    $response['reporte'] = $final_report_data;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    if (isset($conex)) $conex->close();
    echo json_encode($response);
}
?>

