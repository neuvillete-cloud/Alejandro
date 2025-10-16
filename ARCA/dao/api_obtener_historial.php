<?php
// api_obtener_historial.php

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['user_rol'] != 1) {
    http_response_code(403);
    echo "Acceso denegado.";
    exit();
}

include_once("conexionArca.php");

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "Error: No se proporcionó un ID de solicitud.";
    exit();
}

$idSolicitud = intval($_GET['id']);

$con = new LocalConector();
$conex = $con->conectar();

// --- Lógica para obtener el historial (similar a trabajar_solicitud.php) ---

$stmt_sol = $conex->prepare("SELECT NumeroParte FROM Solicitudes WHERE IdSolicitud = ?");
$stmt_sol->bind_param("i", $idSolicitud);
$stmt_sol->execute();
$solicitud_info = $stmt_sol->get_result()->fetch_assoc();
$numeroPartePrincipal = $solicitud_info['NumeroParte'];
$isVariosPartes = (strtolower($numeroPartePrincipal) === 'varios');
$stmt_sol->close();

// --- INICIO DE CORRECCIÓN: Se cambió NombreInspector a Nombreinspector ---
$reportes_anteriores_query = $conex->prepare("
    SELECT 
        ri.IdReporte, ri.FechaInspeccion, ri.Nombreinspector, ri.PiezasInspeccionadas, ri.PiezasAceptadas,
        (ri.PiezasInspeccionadas - ri.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        ri.PiezasRetrabajadas, 
        ri.RangoHora, 
        ri.Comentarios
    FROM ReportesInspeccion ri
    WHERE ri.IdSolicitud = ? ORDER BY ri.FechaRegistro DESC
");
// --- FIN DE CORRECCIÓN ---
$reportes_anteriores_query->bind_param("i", $idSolicitud);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);

if (count($reportes_raw) === 0) {
    echo "<p style='text-align: center; padding: 20px;'>No hay registros de inspección para esta solicitud.</p>";
    $conex->close();
    exit();
}

$reportes_procesados = [];
foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdReporte'];

    if ($isVariosPartes) {
        $desglose_query = $conex->prepare("SELECT NumeroParte FROM ReporteDesglosePartes WHERE IdReporte = ?");
        $desglose_query->bind_param("i", $reporte_id);
        $desglose_query->execute();
        $partes_desglosadas = [];
        while ($fila = $desglose_query->get_result()->fetch_assoc()) {
            $partes_desglosadas[] = htmlspecialchars($fila['NumeroParte']);
        }
        $reporte['NumeroParteParaMostrar'] = empty($partes_desglosadas) ? 'Varios (Sin Desglose)' : implode("<br>", array_unique($partes_desglosadas));
        $desglose_query->close();
    } else {
        $reporte['NumeroParteParaMostrar'] = $numeroPartePrincipal;
    }

    $defectos_reporte_query = $conex->prepare("
        SELECT rdo.CantidadEncontrada, rdo.Lote, cd.NombreDefecto 
        FROM ReporteDefectosOriginales rdo
        JOIN Defectos d ON rdo.IdDefecto = d.IdDefecto
        JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo
        WHERE rdo.IdReporte = ?
    ");
    $defectos_reporte_query->bind_param("i", $reporte_id);
    $defectos_reporte_query->execute();
    $defectos_con_cantidades = [];
    $lotes_encontrados = [];
    while ($dr = $defectos_reporte_query->get_result()->fetch_assoc()) {
        $defectos_con_cantidades[] = htmlspecialchars($dr['NombreDefecto']) . " (" . htmlspecialchars($dr['CantidadEncontrada']) . ")";
        if (!empty($dr['Lote'])) {
            $lotes_encontrados[] = htmlspecialchars($dr['Lote']);
        }
    }
    $reporte['DefectosConCantidades'] = implode("<br>", $defectos_con_cantidades);
    $reporte['LotesEncontrados'] = empty($lotes_encontrados) ? 'N/A' : implode(", ", array_unique($lotes_encontrados));
    $defectos_reporte_query->close();

    $turno_shift_leader = 'N/A';
    if (isset($reporte['RangoHora'])) {
        preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm))/i', $reporte['RangoHora'], $matches);
        if (!empty($matches[1])) {
            $hora_inicio_timestamp = strtotime($matches[1]);
            if ($hora_inicio_timestamp >= strtotime('06:30 am') && $hora_inicio_timestamp <= strtotime('02:30 pm')) {
                $turno_shift_leader = 'Primer Turno';
            } elseif ($hora_inicio_timestamp >= strtotime('02:40 pm') && $hora_inicio_timestamp <= strtotime('10:30 pm')) {
                $turno_shift_leader = 'Segundo Turno';
            } else {
                $turno_shift_leader = 'Tercer Turno / Otro';
            }
        }
    }
    $reporte['TurnoShiftLeader'] = $turno_shift_leader;

    $reportes_procesados[] = $reporte;
}

$conex->close();
?>

<div class="table-responsive">
    <table class="data-table" id="tabla-historial-exportar">
        <thead>
        <tr>
            <th>ID Reporte</th>
            <th>No. de Parte</th>
            <th>Fecha Inspección</th>
            <th>Rango Hora</th>
            <th>Turno Shift Leader</th>
            <th>Inspector</th>
            <th>Inspeccionadas</th>
            <th>Aceptadas</th>
            <th>Rechazadas</th>
            <th>Retrabajadas</th>
            <th>Defectos (Cant.)</th>
            <th>No. de Lote</th>
            <th>Comentarios</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reportes_procesados as $reporte): ?>
            <tr>
                <td><?php echo "R-" . str_pad($reporte['IdReporte'], 4, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo $reporte['NumeroParteParaMostrar']; ?></td>
                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($reporte['FechaInspeccion']))); ?></td>
                <td><?php echo htmlspecialchars($reporte['RangoHora']); ?></td>
                <td><?php echo htmlspecialchars($reporte['TurnoShiftLeader']); ?></td>
                <td><?php echo htmlspecialchars($reporte['Nombreinspector']); ?></td>
                <td><?php echo htmlspecialchars($reporte['PiezasInspeccionadas']); ?></td>
                <td><?php echo htmlspecialchars($reporte['PiezasAceptadas']); ?></td>
                <td><?php echo htmlspecialchars($reporte['PiezasRechazadasCalculadas']); ?></td>
                <td><?php echo htmlspecialchars($reporte['PiezasRetrabajadas']); ?></td>
                <td><?php echo $reporte['DefectosConCantidades']; ?></td>
                <td><?php echo $reporte['LotesEncontrados']; ?></td>
                <td><?php echo htmlspecialchars($reporte['Comentarios']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

