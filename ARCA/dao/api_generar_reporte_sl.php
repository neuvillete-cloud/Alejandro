<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

function parsearTiempoAMinutos($tiempoStr) {
    if (empty($tiempoStr)) return 0;
    $totalMinutos = 0;
    if (preg_match('/(\d+)\s*hora(s)?/', $tiempoStr, $matches)) {
        $totalMinutos += intval($matches[1]) * 60;
    }
    if (preg_match('/(\d+)\s*minuto(s)?/', $tiempoStr, $matches)) {
        $totalMinutos += intval($matches[1]);
    }
    return $totalMinutos;
}

function formatarMinutosATiempo($totalMinutos) {
    if ($totalMinutos <= 0) return "0 minutos";
    $horas = floor($totalMinutos / 60);
    $minutos = $totalMinutos % 60;
    $partes = [];
    if ($horas > 0) $partes[] = $horas . " hora(s)";
    if ($minutos > 0) $partes[] = $minutos . " minuto(s)";
    return empty($partes) ? "0 minutos" : implode(" ", $partes);
}

function calcularTurno($rangoHoraStr) {
    if (empty($rangoHoraStr)) return 'N/A';
    preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm))/i', $rangoHoraStr, $matches);
    if (empty($matches[1])) return 'N/A';

    $hora_inicio_timestamp = strtotime($matches[1]);
    if (strtotime('06:30 am') <= $hora_inicio_timestamp && $hora_inicio_timestamp <= strtotime('02:30 pm')) return 'Primer Turno';
    if (strtotime('02:40 pm') <= $hora_inicio_timestamp && $hora_inicio_timestamp <= strtotime('10:30 pm')) return 'Segundo Turno';
    return 'Tercer Turno / Otro';
}

$response = ['status' => 'error', 'message' => 'Solicitud no válida.'];
$idSafeLaunch = $_GET['idSafeLaunch'] ?? null;
$tipoReporte = $_GET['tipo'] ?? 'parcial';

if (!$idSafeLaunch) {
    $response['message'] = 'ID de Safe Launch no proporcionado.';
    echo json_encode($response);
    exit;
}
$idSafeLaunch = intval($idSafeLaunch);

$con = new LocalConector();
$conex = $con->conectar();

try {
    $reporteData = [
        'titulo' => '',
        'folio' => $idSafeLaunch,
        'info' => [],
        'resumen' => [
            'inspeccionadas' => 0, 'aceptadas' => 0, 'rechazadas' => 0,
            'retrabajadas' => 0, 'tiempoTotal' => '0 minutos', 'ppmGlobal' => 0
        ],
        'desgloseDiario' => [],
        'defectos' => [],
        'defectosDiarios' => [], // <-- NUEVO: Para la tabla de defectos diarios
        'dashboardData' => [
            'pareto' => [],
            'rechazadasPorSemana' => [],
            'dailyPPM' => []
        ]
    ];

    // 1. Obtener Info General
    $stmt_info = $conex->prepare("SELECT sl.NombreProyecto, sl.Cliente, u.Nombre AS Responsable
                                  FROM SafeLaunchSolicitudes sl
                                  JOIN Usuarios u ON sl.IdUsuario = u.IdUsuario
                                  WHERE sl.IdSafeLaunch = ?");
    $stmt_info->bind_param("i", $idSafeLaunch);
    $stmt_info->execute();
    $info = $stmt_info->get_result()->fetch_assoc();
    $reporteData['info'] = [
        'nombreProyecto' => $info['NombreProyecto'] ?? 'N/A',
        'cliente' => $info['Cliente'] ?? 'N/A',
        'responsable' => $info['Responsable'] ?? 'N/A'
    ];
    $stmt_info->close();

    // 2. Definir rango de fechas y Título
    $dateFilter = "";
    $params = [$idSafeLaunch];
    $types = "i";
    if ($tipoReporte === 'parcial' && isset($_GET['inicio']) && isset($_GET['fin'])) {
        $reporteData['titulo'] = "Reporte Parcial de Safe Launch";
        // --- CORRECCIÓN AQUÍ: Usar 'sri' en lugar de 'r' ---
        $dateFilter = " AND sri.FechaInspeccion BETWEEN ? AND ?";
        $params[] = $_GET['inicio'];
        $params[] = $_GET['fin'];
        $types .= "ss";
    } else {
        $reporteData['titulo'] = "Reporte Final de Safe Launch";
        // No se añade filtro de fecha, se traen todos
    }

    // 3. Obtener todos los reportes de inspección
    // --- CORRECCIÓN AQUÍ: Usar 'sri' en lugar de 'r' ---
    $sql_reportes = "SELECT sri.* FROM SafeLaunchReportesInspeccion sri
                     WHERE sri.IdSafeLaunch = ? $dateFilter
                     ORDER BY sri.FechaInspeccion ASC, sri.RangoHora ASC";
    $stmt_reportes = $conex->prepare($sql_reportes);
    $stmt_reportes->bind_param($types, ...$params);
    $stmt_reportes->execute();
    $result_reportes = $stmt_reportes->get_result();

    $desglosePorFecha = [];
    $totalMinutos = 0;
    $dailyAggregates = []; // Para PPM y Progreso
    $weeklyRejects = []; // Para Rechazos Semanales
    $defectAggregates = []; // Para Pareto

    // --- NUEVO: Inicializar para defectos diarios ---
    $dailyDefectSummary = [];

    while ($row = $result_reportes->fetch_assoc()) {
        $fecha = $row['FechaInspeccion'];
        $idSLReporte = $row['IdSLReporte'];
        $inspeccionadas = (int)$row['PiezasInspeccionadas'];
        $aceptadas = (int)$row['PiezasAceptadas'];
        $rechazadas = $inspeccionadas - $aceptadas;
        $retrabajadas = (int)$row['PiezasRetrabajadas'];
        $minutos = parsearTiempoAMinutos($row['TiempoInspeccion']);

        // Sumas para el Resumen General
        $reporteData['resumen']['inspeccionadas'] += $inspeccionadas;
        $reporteData['resumen']['aceptadas'] += $aceptadas;
        $reporteData['resumen']['retrabajadas'] += $retrabajadas;
        $totalMinutos += $minutos;

        // Agrupar para Desglose por Día
        if (!isset($desglosePorFecha[$fecha])) {
            $desglosePorFecha[$fecha] = ['fecha' => $fecha, 'totales' => ['inspeccionadas' => 0, 'aceptadas' => 0, 'rechazadas' => 0], 'entradas' => []];
        }
        $row['turno'] = calcularTurno($row['RangoHora']);
        $desglosePorFecha[$fecha]['entradas'][] = $row;
        $desglosePorFecha[$fecha]['totales']['inspeccionadas'] += $inspeccionadas;
        $desglosePorFecha[$fecha]['totales']['aceptadas'] += $aceptadas;
        $desglosePorFecha[$fecha]['totales']['rechazadas'] += $rechazadas;

        // Agrupar para Dashboards
        if (!isset($dailyAggregates[$fecha])) $dailyAggregates[$fecha] = ['inspeccionadas' => 0, 'rechazadas' => 0];
        $dailyAggregates[$fecha]['inspeccionadas'] += $inspeccionadas;
        $dailyAggregates[$fecha]['rechazadas'] += $rechazadas;

        $f = new DateTime($fecha);
        $year = $f->format('Y');
        $week = $f->format('W');
        $weekKey = $year . $week;
        if (!isset($weeklyRejects[$weekKey])) $weeklyRejects[$weekKey] = 0;
        $weeklyRejects[$weekKey] += $rechazadas;

        // --- NUEVO: Recopilar datos de defectos por día para la tabla ---
        if (!isset($dailyDefectSummary[$fecha])) {
            $dailyDefectSummary[$fecha] = [
                'piezasInspeccionadas' => 0,
                'totalDefectosDia' => 0,
                'defectos' => [] // [ 'NombreDefecto' => Cantidad ]
            ];
        }
        $dailyDefectSummary[$fecha]['piezasInspeccionadas'] += $inspeccionadas;

        // Obtener defectos de la cuadrícula para este reporte
        $stmt_defectos_grid_diarios = $conex->prepare("SELECT slcd.NombreDefecto, srd.CantidadEncontrada 
                                                        FROM SafeLaunchReporteDefectos srd
                                                        JOIN SafeLaunchCatalogoDefectos slcd ON srd.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
                                                        WHERE srd.IdSLReporte = ?");
        $stmt_defectos_grid_diarios->bind_param("i", $idSLReporte);
        $stmt_defectos_grid_diarios->execute();
        $result_defectos_grid_diarios = $stmt_defectos_grid_diarios->get_result();
        while ($defecto_row = $result_defectos_grid_diarios->fetch_assoc()) {
            $nombreDefecto = $defecto_row['NombreDefecto'];
            $cantidad = (int)$defecto_row['CantidadEncontrada'];
            if (!isset($dailyDefectSummary[$fecha]['defectos'][$nombreDefecto])) {
                $dailyDefectSummary[$fecha]['defectos'][$nombreDefecto] = 0;
            }
            $dailyDefectSummary[$fecha]['defectos'][$nombreDefecto] += $cantidad;
            $dailyDefectSummary[$fecha]['totalDefectosDia'] += $cantidad;
        }
        $stmt_defectos_grid_diarios->close();

        // Obtener defectos opcionales para este reporte
        $stmt_defectos_nuevos_diarios = $conex->prepare("SELECT slcd.NombreDefecto, snd.Cantidad 
                                                         FROM SafeLaunchNuevosDefectos snd
                                                         JOIN SafeLaunchCatalogoDefectos slcd ON snd.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
                                                         WHERE snd.IdSLReporte = ?");
        $stmt_defectos_nuevos_diarios->bind_param("i", $idSLReporte);
        $stmt_defectos_nuevos_diarios->execute();
        $result_defectos_nuevos_diarios = $stmt_defectos_nuevos_diarios->get_result();
        while ($defecto_row = $result_defectos_nuevos_diarios->fetch_assoc()) {
            $nombreDefecto = $defecto_row['NombreDefecto'];
            $cantidad = (int)$defecto_row['Cantidad'];
            if (!isset($dailyDefectSummary[$fecha]['defectos'][$nombreDefecto])) {
                $dailyDefectSummary[$fecha]['defectos'][$nombreDefecto] = 0;
            }
            $dailyDefectSummary[$fecha]['defectos'][$nombreDefecto] += $cantidad;
            $dailyDefectSummary[$fecha]['totalDefectosDia'] += $cantidad;
        }
        $stmt_defectos_nuevos_diarios->close();
    } // Fin del while $row

    $reporteData['resumen']['rechazadas'] = $reporteData['resumen']['inspeccionadas'] - $reporteData['resumen']['aceptadas'];
    $reporteData['resumen']['tiempoTotal'] = formatarMinutosATiempo($totalMinutos);
    // --- NUEVO: Calcular PPM Global (usa rechazadas, no todos los defectos) ---
    // (PPM se basa en piezas RECHAZADAS, no en el total de defectos individuales)
    $reporteData['resumen']['ppmGlobal'] = ($reporteData['resumen']['inspeccionadas'] > 0) ? ($reporteData['resumen']['rechazadas'] / $reporteData['resumen']['inspeccionadas']) * 1000000 : 0;

    $reporteData['desgloseDiario'] = array_values($desglosePorFecha);
    $stmt_reportes->close();

    // 4. Obtener Datos de Defectos (de ambas tablas) para el Resumen General de Defectos
    $params_defectos = $params;
    $types_defectos = $types;

    // Defectos de la Cuadrícula
    $sql_defectos_grid = "SELECT slcd.NombreDefecto, SUM(srd.CantidadEncontrada) as Cantidad
                          FROM SafeLaunchReporteDefectos srd
                          JOIN SafeLaunchReportesInspeccion sri ON srd.IdSLReporte = sri.IdSLReporte
                          JOIN SafeLaunchCatalogoDefectos slcd ON srd.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
                          WHERE sri.IdSafeLaunch = ? $dateFilter
                          GROUP BY slcd.NombreDefecto";
    $stmt_defectos_grid = $conex->prepare($sql_defectos_grid);
    $stmt_defectos_grid->bind_param($types_defectos, ...$params_defectos);
    $stmt_defectos_grid->execute();
    $result_defectos_grid = $stmt_defectos_grid->get_result();
    while ($row = $result_defectos_grid->fetch_assoc()) {
        $nombre = $row['NombreDefecto'];
        $cantidad = (int)$row['Cantidad'];
        if (!isset($defectAggregates[$nombre])) $defectAggregates[$nombre] = ['nombre' => $nombre, 'cantidad' => 0, 'tipo' => 'Asociado'];
        $defectAggregates[$nombre]['cantidad'] += $cantidad;
    }
    $stmt_defectos_grid->close();

    // Defectos Opcionales (Nuevos)
    $sql_defectos_nuevos = "SELECT slcd.NombreDefecto, SUM(snd.Cantidad) as Cantidad
                            FROM SafeLaunchNuevosDefectos snd
                            JOIN SafeLaunchReportesInspeccion sri ON snd.IdSLReporte = sri.IdSLReporte
                            JOIN SafeLaunchCatalogoDefectos slcd ON snd.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
                            WHERE sri.IdSafeLaunch = ? $dateFilter
                            GROUP BY slcd.NombreDefecto";
    $stmt_defectos_nuevos = $conex->prepare($sql_defectos_nuevos);
    $stmt_defectos_nuevos->bind_param($types_defectos, ...$params_defectos);
    $stmt_defectos_nuevos->execute();
    $result_defectos_nuevos = $stmt_defectos_nuevos->get_result();
    while ($row = $result_defectos_nuevos->fetch_assoc()) {
        $nombre = $row['NombreDefecto'];
        $cantidad = (int)$row['Cantidad'];
        if (!isset($defectAggregates[$nombre])) $defectAggregates[$nombre] = ['nombre' => $nombre, 'cantidad' => 0, 'tipo' => 'Opcional'];
        $defectAggregates[$nombre]['cantidad'] += $cantidad;
    }
    $stmt_defectos_nuevos->close();

    // Ordenar defectos agregados por cantidad
    $defectos_lista = array_values($defectAggregates);
    usort($defectos_lista, function($a, $b) {
        return $b['cantidad'] <=> $a['cantidad'];
    });
    $reporteData['defectos'] = $defectos_lista;

    // --- NUEVO: Calcular PPM por día y agregar a dailyDefectSummary ---
    // (Nota: $dailyDefectSummary usa el *total de defectos*, no solo rechazadas)
    // El PPM de la tabla de ejemplo usa el "Total Problems", así que usaremos 'totalDefectosDia'
    foreach ($dailyDefectSummary as $fecha => &$data) {
        $data['ppmDia'] = ($data['piezasInspeccionadas'] > 0) ? ($data['totalDefectosDia'] / $data['piezasInspeccionadas']) * 1000000 : 0;
    }
    unset($data); // Romper la referencia
    ksort($dailyDefectSummary); // Ordenar por fecha
    $reporteData['defectosDiarios'] = $dailyDefectSummary;

    // 5. Procesar Datos para Dashboards
    // Pareto
    $totalDefectos = array_sum(array_column($defectos_lista, 'cantidad'));
    $acumulado = 0;
    $paretoData = [];
    if ($totalDefectos > 0) {
        $top5 = array_slice($defectos_lista, 0, 5);
        foreach ($top5 as $defecto) {
            $acumulado += $defecto['cantidad'];
            $paretoData[] = [
                'defecto' => $defecto['nombre'],
                'cantidad' => $defecto['cantidad'],
                'porcentajeAcumulado' => round(($acumulado / $totalDefectos) * 100)
            ];
        }
    }
    $reporteData['dashboardData']['pareto'] = $paretoData;

    // Rechazos por Semana
    ksort($weeklyRejects);
    foreach ($weeklyRejects as $semana => $rechazadas) {
        $reporteData['dashboardData']['rechazadasPorSemana'][] = [
            'semana' => $semana,
            'rechazadas_semana' => $rechazadas
        ];
    }

    // PPM Diario (del dashboard, usa piezas rechazadas)
    ksort($dailyAggregates);
    foreach ($dailyAggregates as $fecha => $data) {
        $ppm = ($data['inspeccionadas'] > 0) ? ($data['rechazadas'] / $data['inspeccionadas']) * 1000000 : 0;
        $reporteData['dashboardData']['dailyPPM'][] = [
            'fecha' => $fecha,
            'ppm' => round($ppm)
        ];
    }

    $response['status'] = 'success';
    $response['reporte'] = $reporteData;

} catch (Exception $e) {
    $response['message'] = "Error en la API: " . $e->getMessage() . " (Línea: " . $e->getLine() . ")";
}

$conex->close();
echo json_encode($response);
?>

