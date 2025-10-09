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


    // 4. --- NUEVO: OBTENER DESGLOSE DIARIO ---
    $desgloseDiario = [];
    if ($isVariosPartes) {
        $query_diario = "SELECT
                            ri.FechaInspeccion,
                            SUM(ri.PiezasInspeccionadas) AS totalInspeccionadasDia,
                            SUM(ri.PiezasAceptadas) AS totalAceptadasDia,
                            SUM(ri.PiezasRetrabajadas) AS totalRetrabajadasDia,
                            GROUP_CONCAT(CONCAT(rdp.NumeroParte, ':', rdp.Cantidad) SEPARATOR ';') AS desglosePartesDia
                        FROM ReportesInspeccion ri
                        LEFT JOIN ReporteDesglosePartes rdp ON ri.IdReporte = rdp.IdReporte
                        {$whereClause}
                        GROUP BY ri.FechaInspeccion
                        ORDER BY ri.FechaInspeccion ASC";
        $stmt_diario = $conex->prepare($query_diario);
        $stmt_diario->bind_param($types, ...$params);
        $stmt_diario->execute();
        $result_diario = $stmt_diario->get_result();
        while ($row = $result_diario->fetch_assoc()) {
            $partes_del_dia = [];
            if (!empty($row['desglosePartesDia'])) {
                $pares = explode(';', $row['desglosePartesDia']);
                foreach ($pares as $par) {
                    list($numParte, $cantidad) = explode(':', $par);
                    $partes_del_dia[] = ['numeroParte' => $numParte, 'cantidad' => (int)$cantidad];
                }
            }
            $desgloseDiario[] = [
                'fecha' => $row['FechaInspeccion'],
                'inspeccionadas' => (int)$row['totalInspeccionadasDia'],
                'aceptadas' => (int)$row['totalAceptadasDia'],
                'retrabajadas' => (int)$row['totalRetrabajadasDia'],
                'partes' => $partes_del_dia
            ];
        }

    } else { // Desglose para un solo número de parte
        $query_diario = "SELECT
                            ri.FechaInspeccion,
                            SUM(ri.PiezasInspeccionadas) AS inspeccionadas,
                            SUM(ri.PiezasAceptadas) AS aceptadas,
                            SUM(ri.PiezasRetrabajadas) AS retrabajadas
                        FROM ReportesInspeccion ri
                        {$whereClause}
                        GROUP BY ri.FechaInspeccion
                        ORDER BY ri.FechaInspeccion ASC";
        $stmt_diario = $conex->prepare($query_diario);
        $stmt_diario->bind_param($types, ...$params);
        $stmt_diario->execute();
        $desgloseDiario = $stmt_diario->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    // --- FIN DEL DESGLOSE DIARIO ---


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
        'desgloseDiario' => $desgloseDiario // Se añade el nuevo bloque de datos
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

