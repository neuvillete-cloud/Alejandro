<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);
$idUsuarioActual = $_SESSION['user_id'];

// Validar que se recibió un ID de Safe Launch
if (!isset($_GET['id'])) {
    die("Error: No se proporcionó un ID de Safe Launch.");
}
$idSafeLaunch = intval($_GET['id']);

// Conexión y carga de datos para la página
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Obtenemos los datos de la solicitud Safe Launch
$stmt = $conex->prepare("SELECT sl.*, u.Nombre AS NombreCreador
                         FROM SafeLaunchSolicitudes sl
                         LEFT JOIN Usuarios u ON sl.IdUsuario = u.IdUsuario
                         WHERE sl.IdSafeLaunch = ?");
$stmt->bind_param("i", $idSafeLaunch);
$stmt->execute();
$solicitudSL = $stmt->get_result()->fetch_assoc();
$stmt->close(); // Cerrar el statement anterior

if (!$solicitudSL) { die("Error: Safe Launch no encontrado."); }

// --- Datos para la cabecera del formulario ---
$nombreResponsable = htmlspecialchars($solicitudSL['NombreCreador']);
$nombreProyecto = htmlspecialchars($solicitudSL['NombreProyecto']);
$cliente = htmlspecialchars($solicitudSL['Cliente']);
// Ya no necesitamos $cantidadSolicitada ni $nombresDefectosStr aquí

// --- Catálogo de Defectos para Safe Launch ---
$catalogo_defectos_sl_query = $conex->prepare("SELECT IdSLDefectoCatalogo, NombreDefecto FROM SafeLaunchCatalogoDefectos ORDER BY NombreDefecto ASC");
$catalogo_defectos_sl_query->execute();
$catalogo_defectos_sl_result = $catalogo_defectos_sl_query->get_result()->fetch_all(MYSQLI_ASSOC);
$catalogo_defectos_sl_query->close(); // Cerrar statement

// Preparar opciones HTML para los <select>
$defectos_options_html = "";
$todos_los_defectos_sl = []; // Array para la tabla final
foreach($catalogo_defectos_sl_result as $row) {
    $defectos_options_html .= "<option value='{$row['IdSLDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
    $todos_los_defectos_sl[$row['IdSLDefectoCatalogo']] = ['NombreDefecto' => htmlspecialchars($row['NombreDefecto']), 'Total' => 0]; // Inicializar total
}

// --- Obtener Historial de Reportes de Inspección para este Safe Launch ---
function parsearTiempoAMinutosSL($tiempoStr) {
    if (empty($tiempoStr)) return 0;
    $totalMinutos = 0;
    if (preg_match('/(\d+)\s*hora(s)?/', $tiempoStr, $matches)) { $totalMinutos += intval($matches[1]) * 60; }
    if (preg_match('/(\d+)\s*minuto(s)?/', $tiempoStr, $matches)) { $totalMinutos += intval($matches[1]); }
    if ($totalMinutos === 0 && str_contains(strtolower($tiempoStr), 'hora')) { $totalMinutos = intval(filter_var($tiempoStr, FILTER_SANITIZE_NUMBER_INT)) * 60; }
    return $totalMinutos;
}

function formatarMinutosATiempoSL($totalMinutos) {
    if ($totalMinutos <= 0) return "0 minutos";
    $horas = floor($totalMinutos / 60);
    $minutos = $totalMinutos % 60;
    $partes = [];
    if ($horas > 0) { $partes[] = $horas . " hora(s)"; }
    if ($minutos > 0) { $partes[] = $minutos . " minuto(s)"; }
    return empty($partes) ? "0 minutos" : implode(" ", $partes);
}

$reportes_anteriores_query = $conex->prepare("
    SELECT
        slri.IdSLReporte, slri.FechaInspeccion, slri.NombreInspector, slri.PiezasInspeccionadas, slri.PiezasAceptadas,
        (slri.PiezasInspeccionadas - slri.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        slri.PiezasRetrabajadas,
        slri.RangoHora,
        slri.Comentarios,
        slri.TiempoInspeccion
    FROM SafeLaunchReportesInspeccion slri
    WHERE slri.IdSafeLaunch = ? ORDER BY slri.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idSafeLaunch);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);
$reportes_anteriores_query->close(); // Cerrar statement

$reportes_procesados = [];
$totalMinutosRegistrados = 0;
$totalPiezasInspeccionadasYa = 0;
$totalDefectosGlobal = 0; // Para PPM

// Preparar statements para obtener defectos por reporte (más eficiente dentro del bucle)
$defectos_reporte_stmt = $conex->prepare("
    SELECT slrd.CantidadEncontrada, slrd.BachLote, slcd.NombreDefecto, slrd.IdSLDefectoCatalogo
    FROM SafeLaunchReporteDefectos slrd
    JOIN SafeLaunchCatalogoDefectos slcd ON slrd.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
    WHERE slrd.IdSLReporte = ?
");

foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdSLReporte'];
    $totalMinutosRegistrados += parsearTiempoAMinutosSL($reporte['TiempoInspeccion']);
    $totalPiezasInspeccionadasYa += (int)$reporte['PiezasInspeccionadas'];

    // Obtener defectos para este reporte
    $defectos_reporte_stmt->bind_param("i", $reporte_id);
    $defectos_reporte_stmt->execute();
    $defectos_reporte_result = $defectos_reporte_stmt->get_result();

    $reporte['DefectosDetallados'] = []; // Array para almacenar detalles por defecto para esta fila
    // Inicializar contadores para esta fila
    foreach ($todos_los_defectos_sl as $idDef => $defData) {
        $reporte['DefectosDetallados'][$idDef] = ['Cantidad' => 0, 'Lotes' => []];
    }

    $lotes_encontrados_reporte = [];
    $total_defectos_este_reporte = 0;

    while ($dr = $defectos_reporte_result->fetch_assoc()) {
        $id_defecto_catalogo = $dr['IdSLDefectoCatalogo'];
        $cantidad_encontrada = (int)$dr['CantidadEncontrada'];
        $total_defectos_este_reporte += $cantidad_encontrada;
        $totalDefectosGlobal += $cantidad_encontrada; // Sumar al total global

        if (isset($reporte['DefectosDetallados'][$id_defecto_catalogo])) {
            $reporte['DefectosDetallados'][$id_defecto_catalogo]['Cantidad'] += $cantidad_encontrada;
            if (!empty($dr['BachLote'])) {
                $reporte['DefectosDetallados'][$id_defecto_catalogo]['Lotes'][] = htmlspecialchars($dr['BachLote']);
                $lotes_encontrados_reporte[] = htmlspecialchars($dr['BachLote']); // También para la columna general de lotes
            }
            // Acumular el total por defecto en el array global
            if (isset($todos_los_defectos_sl[$id_defecto_catalogo])) {
                $todos_los_defectos_sl[$id_defecto_catalogo]['Total'] += $cantidad_encontrada;
            }
        }
    }
    $reporte['LotesEncontrados'] = empty($lotes_encontrados_reporte) ? 'N/A' : implode(", ", array_unique($lotes_encontrados_reporte));
    $reporte['TotalDefectosReporte'] = $total_defectos_este_reporte; // Total de defectos solo para esta fila

    // Calcular Turno (igual que antes)
    $turno_shift_leader = 'N/A';
    if (isset($reporte['RangoHora'])) {
        $rangoHoraStr = $reporte['RangoHora'];
        preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm))/i', $rangoHoraStr, $matches);
        if (!empty($matches[1])) {
            $hora_inicio_str = $matches[1];
            $hora_inicio_timestamp = strtotime($hora_inicio_str);
            $primer_turno_inicio = strtotime('06:30 am'); $primer_turno_fin = strtotime('02:30 pm');
            $segundo_turno_inicio = strtotime('02:40 pm'); $segundo_turno_fin = strtotime('10:30 pm');
            if ($hora_inicio_timestamp >= $primer_turno_inicio && $hora_inicio_timestamp <= $primer_turno_fin) { $turno_shift_leader = 'Primer Turno'; }
            elseif ($hora_inicio_timestamp >= $segundo_turno_inicio && $hora_inicio_timestamp <= $segundo_turno_fin) { $turno_shift_leader = 'Segundo Turno'; }
            else { $turno_shift_leader = 'Tercer Turno / Otro'; }
        }
    }
    $reporte['TurnoShiftLeader'] = $turno_shift_leader;

    $reportes_procesados[] = $reporte;
}
$defectos_reporte_stmt->close(); // Cerrar statement preparado de defectos

$tiempoTotalFormateado = formatarMinutosATiempoSL($totalMinutosRegistrados);

// Calcular PPM y % Defectuoso
$ppm = ($totalPiezasInspeccionadasYa > 0) ? ($totalDefectosGlobal / $totalPiezasInspeccionadasYa) * 1000000 : 0;
$porcentajeDefectuoso = ($totalPiezasInspeccionadasYa > 0) ? ($totalDefectosGlobal / $totalPiezasInspeccionadasYa) * 100 : 0;

$conex->close(); // Cerrar la conexión principal

$mostrarVisorInstruccion = !empty($solicitudSL['RutaInstruccion']);

// Determinar si mostrar el formulario principal
// En Safe Launch, asumimos que siempre se puede reportar hasta que se cierre manualmente.
// Podríamos añadir una lógica similar a la de Contenciones si fuera necesario.
$mostrarFormularioPrincipal = ($solicitudSL['Estatus'] == 'Activo');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inspección Safe Launch - ARCA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Incluir los mismos estilos que trabajar_solicitud.php -->
    <style>
        /* =================================================================
           HOJA DE ESTILOS PRINCIPAL PARA EL PROYECTO ARCA
           ================================================================= */

        /* --- 1. Variables Globales y Estilos Base --- */
        :root {
            --color-primario: #4a6984;
            --color-secundario: #5c85ad;
            --color-acento: #8ab4d7;
            --color-fondo: #f4f6f9;
            --color-blanco: #ffffff;
            --color-texto: #333333;
            --color-borde: #dbe1e8;
            --color-exito: #28a745;
            --color-error: #a83232;
            --sombra-suave: 0 4px 12px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- 2. Estilos del Layout Principal (Header) --- */
        .header {
            background-color: var(--color-blanco);
            box-shadow: var(--sombra-suave);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
        }

        .logo i { margin-right: 10px; }

        .user-info {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 15px;
        }

        .user-info span {
            margin-right: 0;
            font-weight: 700;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--color-secundario);
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover { color: var(--color-primario); }
        .logout-btn i { margin-left: 8px; }

        /* --- 3. Estilos para el Formulario --- */
        .form-container { background-color: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: var(--sombra-suave); }
        .form-container h1 { font-family: 'Montserrat', sans-serif; margin-top: 0; margin-bottom: 30px; font-size: 24px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        fieldset { border: none; padding: 0; margin-bottom: 25px; border-bottom: 1px solid #e0e0e0; padding-bottom: 25px; }
        fieldset:last-of-type { border-bottom: none; }
        legend { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 18px; color: var(--color-primario, #4a6984); margin-bottom: 20px; }
        legend i { margin-right: 10px; color: var(--color-acento); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; margin-bottom: 15px; min-width: 200px; }
        .form-group label { margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; font-family: 'Lato', sans-serif; box-sizing: border-box; }
        .hidden-section { display: none; margin-top: 15px; }
        .select-with-button { display: flex; align-items: flex-end; gap: 10px; }
        .select-with-button select { flex-grow: 1; }
        .form-actions { text-align: right; margin-top: 20px; }
        .form-row .form-group.w-50 { flex-basis: calc(50% - 10px); }
        .form-row .form-group.w-25 { flex-basis: calc(25% - 15px); }

        /* --- 4. Componentes y Secciones Específicas --- */
        .info-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; padding: 15px 0; border-bottom: 1px solid var(--color-borde); font-size: 15px; }
        .info-row p { margin: 0; flex-basis: calc(50% - 10px); color: var(--color-texto); }
        .info-row p strong { color: var(--color-primario); margin-right: 5px; }
        .notification-box { padding: 15px 20px; margin-bottom: 25px; border-radius: 8px; display: flex; align-items: center; gap: 15px; font-weight: 600; font-size: 15px; border: 1px solid; }
        .notification-box i { font-size: 20px; }
        .notification-box.warning { background-color: #fff3e0; border-color: #ffe0b2; color: #b75c09; }
        .notification-box.error { background-color: #fdecea; border-color: #f5c2c7; color: var(--color-error); }
        .notification-box.info { background-color: #e3f2fd; border-color: #bbdefb; color: #0d47a1; }
        .info-text { font-size: 14px; color: #666; margin-top: -10px; margin-bottom: 20px; font-style: italic; }

        /* Estilos para la sección de clasificación de defectos */
        .defect-classification-list .form-group {
            border-bottom: 1px solid var(--color-borde);
            padding-bottom: 15px;
            margin-bottom: 15px;
            display: flex; /* Usar flex para alinear etiqueta y inputs */
            flex-wrap: wrap; /* Permitir que los inputs bajen si no caben */
            align-items: flex-end; /* Alinear abajo */
            gap: 15px;
        }
        .defect-classification-list .form-group label {
            font-weight: 700;
            color: var(--color-primario);
            flex-basis: 200px; /* Ancho fijo para la etiqueta */
            flex-shrink: 0; /* No encoger la etiqueta */
            margin-bottom: 0; /* Quitar margen inferior */
        }
        .defect-classification-list .input-pair {
            display: flex;
            gap: 10px;
            flex-grow: 1; /* Ocupar el espacio restante */
        }
        .defect-classification-list .input-pair .form-group {
            flex: 1; /* Cada input ocupa la mitad del espacio */
            border-bottom: none; /* Quitar borde interno */
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .piezas-rechazadas-info { font-size: 15px; margin-bottom: 20px; padding: 10px 15px; background-color: #eaf2f8; border-left: 5px solid var(--color-secundario); border-radius: 4px; }
        /* #tiempoMuertoSection eliminado */

        .pdf-viewer-container {
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
            height: 75vh;
        }
        #pdfViewerWrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* --- 5. Selector de Idioma --- */
        .language-selector { display: flex; align-items: center; gap: 5px; background-color: var(--color-fondo); padding: 4px; border-radius: 20px; margin-right: 0; border: 1px solid var(--color-borde); }
        .lang-btn { border: none; background-color: transparent; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 13px; padding: 4px 12px; border-radius: 15px; cursor: pointer; color: #888; transition: all 0.3s ease; }
        .lang-btn:not(.active):hover { background-color: #e9eef2; color: var(--color-primario); }
        .lang-btn.active { background-color: var(--color-secundario); color: var(--color-blanco); cursor: default; }

        /* --- 6. Botones y Utilerías --- */
        .btn-primary, .btn-secondary { padding: 12px 25px; border-radius: 6px; border: none; font-family: 'Montserrat', sans-serif; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .btn-primary { background-color: var(--color-secundario); color: white; }
        .btn-primary:hover { background-color: var(--color-primario); }
        .btn-secondary { background-color: #e0e0e0; color: #333; }
        .btn-secondary:hover { background-color: #bdbdbd; }
        .btn-add { width: 45px; height: 45px; border-radius: 50%; border: none; background-color: var(--color-primario); color: white; font-size: 24px; font-weight: bold; cursor: pointer; flex-shrink: 0; }
        .btn-small { padding: 6px 12px; font-size: 14px; border-radius: 4px; }
        .btn-danger { background-color: var(--color-error); color: var(--color-blanco); }
        button:disabled { opacity: 0.6; cursor: not-allowed; }

        /* --- 7. Subida de Archivos (eliminado) --- */

        /* --- 8. Tabla de Historial --- */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 20px; margin-bottom: 40px; border: 1px solid var(--color-borde); border-radius: 8px; box-shadow: var(--sombra-suave); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; /* Ajustar min-width según columnas */ }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; vertical-align: middle; }
        .data-table th { background-color: var(--color-primario); color: var(--color-blanco); font-weight: 600; text-transform: uppercase; white-space: nowrap; }
        .data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tbody tr:hover { background-color: #f0f4f8; }
        .data-table td .btn-small { margin: 0 2px; }
        .data-table th.defect-col, .data-table td.defect-col { text-align: center; } /* Centrar columnas de defectos */
        .data-table td.total-col { font-weight: bold; } /* Resaltar totales */
        .data-table td.ppm-col { font-weight: bold; color: var(--color-error); } /* Resaltar PPM */
        .data-table td.percent-col { font-weight: bold; color: var(--color-secundario); } /* Resaltar % */

        /* --- 9. Estilos Responsivos --- */
        @media (max-width: 992px) {
            .info-row p { flex-basis: 100%; }
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: center; }
            .user-info { flex-direction: column; justify-content: center; width: 100%; }
            .container { padding: 15px; }
            .form-container { padding: 20px 25px; }
            .form-container h1 { font-size: 20px; line-height: 1.4; }
            .form-row { flex-direction: column; gap: 0; }
            .form-row .form-group.w-50, .form-row .form-group.w-25 { flex-basis: 100%; }
            .data-table { font-size: 12px; }
            .data-table th, .data-table td { padding: 8px 10px; }
            .pdf-viewer-container { overflow-x: auto; -webkit-overflow-scrolling: touch; height: 600px; }
            #pdfViewerWrapper iframe { min-width: 600px; height: 100%; }
            .defect-classification-list .form-group { flex-direction: column; align-items: stretch; }
            .defect-classification-list .input-pair { flex-direction: column; gap: 5px; }
        }

        /* Estilos para Nuevos Defectos */
        .defecto-item { border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 15px; background-color: #fafafa; }
        .defecto-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .defecto-header h4 { margin: 0; font-family: 'Montserrat', sans-serif; }
        .btn-remove-defecto { background: none; border: none; color: var(--color-error); font-size: 24px; font-weight: bold; cursor: pointer; padding: 0 5px; line-height: 1; }
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
    <div class="user-info">
        <div class="language-selector">
            <button type="button" class="lang-btn active" data-lang="es">ES</button>
            <button type="button" class="lang-btn" data-lang="en">EN</button>
        </div>
        <span><span data-translate-key="welcome">Bienvenido</span>, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'"><span data-translate-key="logout">Cerrar Sesión</span> <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-hammer"></i> <span data-translate-key="main_title">Reporte de Inspección Safe Launch</span> - <span data-translate-key="folio">Folio</span> SL-<?php echo str_pad($solicitudSL['IdSafeLaunch'], 4, '0', STR_PAD_LEFT); ?></h1>

        <div class="info-row">
            <p><strong data-translate-key="project_name">Nombre del Proyecto:</strong> <span><?php echo $nombreProyecto; ?></span></p>
            <p><strong data-translate-key="client">Cliente:</strong> <span><?php echo $cliente; ?></span></p>
            <p><strong data-translate-key="responsible">Responsable:</strong> <span><?php echo $nombreResponsable; ?></span></p>
            <!-- Puedes añadir más info si es necesario -->
        </div>

        <?php if ($mostrarVisorInstruccion): ?>
            <fieldset>
                <legend><i class="fa-solid fa-file-alt"></i> <span data-translate-key="instruction_title">Instrucción de Trabajo</span></legend>
                <div class="form-actions" style="margin-bottom: 15px;">
                    <button type="button" id="togglePdfViewerBtn" class="btn-secondary"><i class="fa-solid fa-eye"></i> <span data-translate-key="view_instruction_btn">Ver Instrucción</span></button>
                </div>
                <div id="pdfViewerWrapper" style="display: none;">
                    <div class="pdf-viewer-container">
                        <iframe src="<?php echo htmlspecialchars($solicitudSL['RutaInstruccion']); ?>#view=FitH" frameborder="0"></iframe>
                    </div>
                </div>
            </fieldset>
        <?php else: ?>
            <div class='notification-box warning'><i class='fa-solid fa-triangle-exclamation'></i> <span data-translate-key='no_instruction_attached'>No hay instrucción de trabajo adjunta para este Safe Launch.</span></div>
        <?php endif; ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteSLForm" action="dao/guardar_reporte_safe_launch.php" method="POST">
                <input type="hidden" name="idSafeLaunch" value="<?php echo $idSafeLaunch; ?>">
                <input type="hidden" name="idSLReporte" id="idSLReporte" value=""> <!-- Para edición -->

                <fieldset>
                    <legend><i class="fa-solid fa-chart-simple"></i> <span data-translate-key="summary_title">Resumen de Inspección</span></legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label data-translate-key="total_inspected">Total de Piezas Inspeccionadas</label>
                            <input type="number" name="piezasInspeccionadas" id="piezasInspeccionadas" min="0" required>
                        </div>
                        <div class="form-group"><label data-translate-key="accepted_pieces">Piezas Aceptadas</label><input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required></div>
                        <div class="form-group"><label data-translate-key="reworked_pieces">Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" id="piezasRetrabajadas" min="0" value="0" required></div>
                        <div class="form-group"><label data-translate-key="rejected_pieces_calc">Piezas Rechazadas (Cálculo)</label><input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label data-translate-key="inspector_name">Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label data-translate-key="inspection_date">Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label data-translate-key="start_time">Hora de Inicio</label>
                            <input type="time" name="horaInicio" id="horaInicio" required>
                        </div>
                        <div class="form-group">
                            <label data-translate-key="end_time">Hora de Fin</label>
                            <input type="time" name="horaFin" id="horaFin" required>
                        </div>
                    </div>
                    <input type="hidden" name="rangoHoraCompleto" id="rangoHoraCompleto">
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> <span data-translate-key="defects_classification_title">Clasificación de Defectos</span></legend>
                    <div class="defect-classification-list">
                        <p class="piezas-rechazadas-info"><span data-translate-key="available_to_classify">Piezas rechazadas disponibles para clasificar:</span> <span id="piezasRechazadasRestantes" style="font-weight: bold; color: var(--color-error);">0</span></p>
                        <?php if (!empty($todos_los_defectos_sl)): ?>
                            <?php foreach($todos_los_defectos_sl as $idDefecto => $defectoData): ?>
                                <div class="form-group">
                                    <label><?php echo $defectoData['NombreDefecto']; ?></label>
                                    <div class="input-pair">
                                        <div class="form-group">
                                            <input type="number" class="defecto-cantidad" name="defectos[<?php echo $idDefecto; ?>][cantidad]" placeholder="Cantidad..." value="0" min="0" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="defecto-lote" name="defectos[<?php echo $idDefecto; ?>][lote]" placeholder="Bach/Lote...">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p data-translate-key="no_defects_catalog">No hay defectos registrados en el catálogo de Safe Launch.</p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <!-- INICIO: Nueva Sección de Nuevos Defectos -->
                <fieldset>
                    <legend><i class="fa-solid fa-magnifying-glass-plus"></i> <span data-translate-key="new_defects_title_sl">Nuevos Defectos Encontrados (Opcional)</span></legend>
                    <div id="nuevos-defectos-sl-container">
                        <!-- Aquí se agregarán dinámicamente -->
                    </div>
                    <button type="button" id="btn-add-nuevo-defecto-sl" class="btn-secondary">
                        <i class="fa-solid fa-plus"></i> <span data-translate-key="add_new_defect_btn_sl">Añadir Nuevo Defecto</span>
                    </button>
                </fieldset>
                <!-- FIN: Nueva Sección de Nuevos Defectos -->

                <fieldset><legend><i class="fa-solid fa-stopwatch"></i> <span data-translate-key="session_time_comments_title">Tiempos y Comentarios de la Sesión</span></legend>
                    <div class="form-group">
                        <label data-translate-key="inspection_time_session">Tiempo de Inspección (Esta Sesión)</label>
                        <input type="text" name="tiempoInspeccion" id="tiempoInspeccion" value="" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="form-group"><label data-translate-key="additional_comments">Comentarios Adicionales de la Sesión</label><textarea name="comentarios" id="comentarios" rows="4"></textarea></div>
                    <!-- Sección de Tiempo Muerto eliminada -->
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="btnGuardarReporteSL"><span data-translate-key="save_session_report_btn_sl">Guardar Reporte de Sesión</span></button>
                    <!-- Botón Cancelar Edición (se añadirá dinámicamente si es necesario) -->
                </div>
            </form>
        <?php else: ?>
            <div class='notification-box error' style='margin-top: 20px;'><i class='fa-solid fa-lock'></i> <strong data-translate-key="sl_closed_title">Safe Launch Cerrado:</strong> <span data-translate-key="sl_closed_desc">Este Safe Launch ya ha sido marcado como 'Cerrado' y no se pueden registrar nuevos reportes.</span></div>
        <?php endif; ?>

        <!-- Formulario Finalizar eliminado -->

        <hr style="margin-top: 40px; margin-bottom: 30px; border-color: var(--color-borde);">

        <h2 style="margin-top: 40px;"><i class="fa-solid fa-list-check"></i> <span data-translate-key="history_title_sl">Historial de Registros de Inspección (Safe Launch)</span></h2>
        <?php if (count($reportes_procesados) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-translate-key="th_report_id_sl">ID Reporte</th>
                        <th data-translate-key="th_inspection_date">Fecha Insp.</th>
                        <th data-translate-key="th_time_range">Rango Hora</th>
                        <th data-translate-key="th_shift_leader">Turno</th>
                        <th data-translate-key="th_inspector">Inspector</th>
                        <th data-translate-key="th_inspected">Insp.</th>
                        <th data-translate-key="th_accepted">Acep.</th>
                        <th data-translate-key="th_rejected">Rech.</th>
                        <th data-translate-key="th_reworked">Retrab.</th>
                        <!-- Columnas dinámicas para cada defecto -->
                        <?php foreach ($todos_los_defectos_sl as $idDefecto => $defectoData): ?>
                            <th class="defect-col"><?php echo $defectoData['NombreDefecto']; ?></th>
                        <?php endforeach; ?>
                        <th class="total-col" data-translate-key="th_total_defects">Total Def.</th>
                        <th data-translate-key="th_lot_number">No. Lote(s)</th>
                        <th data-translate-key="th_comments">Comentarios</th>
                        <th data-translate-key="th_actions">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes_procesados as $reporte): ?>
                        <tr>
                            <td><?php echo "RSL-" . str_pad($reporte['IdSLReporte'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($reporte['FechaInspeccion']))); ?></td>
                            <td><?php echo htmlspecialchars($reporte['RangoHora']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['TurnoShiftLeader']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['NombreInspector']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasInspeccionadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasAceptadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRechazadasCalculadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRetrabajadas']); ?></td>
                            <!-- Celdas dinámicas para cantidades de defectos -->
                            <?php foreach ($todos_los_defectos_sl as $idDefecto => $defectoData): ?>
                                <td class="defect-col"><?php echo $reporte['DefectosDetallados'][$idDefecto]['Cantidad'] ?? 0; ?></td>
                            <?php endforeach; ?>
                            <td class="total-col"><?php echo $reporte['TotalDefectosReporte']; ?></td>
                            <td><?php echo $reporte['LotesEncontrados']; ?></td>
                            <td><?php echo htmlspecialchars($reporte['Comentarios']); ?></td>
                            <td>
                                <button class="btn-edit-reporte-sl btn-primary btn-small" data-id="<?php echo $reporte['IdSLReporte']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn-delete-reporte-sl btn-danger btn-small" data-id="<?php echo $reporte['IdSLReporte']; ?>"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr style="background-color: #f0f4f8; font-weight: bold;">
                        <td colspan="5" style="text-align: right;">Totales:</td>
                        <td><?php echo $totalPiezasInspeccionadasYa; ?></td>
                        <td><!-- Aceptadas Total --></td>
                        <td><!-- Rechazadas Total --></td>
                        <td><!-- Retrabajadas Total --></td>
                        <?php foreach ($todos_los_defectos_sl as $idDefecto => $defectoData): ?>
                            <td class="defect-col"><?php echo $defectoData['Total']; ?></td>
                        <?php endforeach; ?>
                        <td class="total-col"><?php echo $totalDefectosGlobal; ?></td>
                        <td colspan="3"></td> <!-- Lotes, Comentarios, Acciones -->
                    </tr>
                    <tr style="background-color: #e9ecef; font-weight: bold;">
                        <td colspan="<?php echo 9 + count($todos_los_defectos_sl); ?>" style="text-align: right;">PPM:</td>
                        <td class="ppm-col" colspan="4"><?php echo number_format($ppm, 2); ?></td>
                    </tr>
                    <tr style="background-color: #e9ecef; font-weight: bold;">
                        <td colspan="<?php echo 9 + count($todos_los_defectos_sl); ?>" style="text-align: right;">% Defectuoso:</td>
                        <td class="percent-col" colspan="4"><?php echo number_format($porcentajeDefectuoso, 2); ?>%</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <p data-translate-key="no_history_records_sl">Aún no hay registros de inspección para este Safe Launch.</p>
        <?php endif; ?>

    </div>
</main>

<script>
    // Pasar el catálogo de defectos SL a JavaScript
    const opcionesDefectosSL = `<?php echo addslashes($defectos_options_html); ?>`;
    // Mapa de defectos originales ya no es necesario aquí
    // const defectosOriginalesMapa = <?php //echo json_encode($defectos_originales_para_js); ?>;
    // Cantidad total ya no aplica de la misma forma
    // const cantidadTotalSolicitada = <?php //echo intval($cantidadSolicitada); ?>;
    // Total inspeccionado previamente sí es útil para validaciones futuras si se implementan
    const totalPiezasInspeccionadasAnteriormente = <?php echo $totalPiezasInspeccionadasYa; ?>;
    // isVariosPartes ya no aplica
    // const isVariosPartes = <?php //echo json_encode($isVariosPartes); ?>;
    let nuevoDefectoCounterSL = 0; // Renombrado para evitar conflictos
    let editandoReporteSL = false; // Renombrado
    let valorOriginalInspeccionadoAlEditarSL = 0; // Renombrado


    document.addEventListener('DOMContentLoaded', function() {
        // --- INICIO: LÓGICA DE TRADUCCIÓN ---
        const translations = {
            es: {
                welcome: "Bienvenido", logout: "Cerrar Sesión",
                main_title: "Reporte de Inspección Safe Launch", folio: "Folio",
                project_name: "Nombre del Proyecto", client: "Cliente", responsible: "Responsable",
                instruction_title: "Instrucción de Trabajo",
                view_instruction_btn: "Ver Instrucción", hide_instruction_btn: "Ocultar Instrucción", download_instruction_btn: "Descargar Instrucción",
                no_instruction_attached: "No hay instrucción de trabajo adjunta para este Safe Launch.",
                sl_closed_title: "Safe Launch Cerrado:", sl_closed_desc:"Este Safe Launch ya ha sido marcado como 'Cerrado' y no se pueden registrar nuevos reportes.",
                summary_title: "Resumen de Inspección",
                total_inspected: "Total de Piezas Inspeccionadas", accepted_pieces: "Piezas Aceptadas",
                reworked_pieces: "Piezas Retrabajadas", rejected_pieces_calc: "Piezas Rechazadas (Cálculo)",
                inspector_name: "Nombre del Inspector", inspection_date: "Fecha de Inspección",
                start_time: "Hora de Inicio", end_time: "Hora de Fin",
                defects_classification_title: "Clasificación de Defectos",
                available_to_classify: "Piezas rechazadas disponibles para clasificar:",
                no_defects_catalog: "No hay defectos registrados en el catálogo de Safe Launch.",
                new_defects_title_sl: "Nuevos Defectos Encontrados (Opcional)", // Clave nueva
                add_new_defect_btn_sl: "Añadir Nuevo Defecto", // Clave nueva
                session_time_comments_title: "Tiempos y Comentarios de la Sesión",
                inspection_time_session: "Tiempo de Inspección (Esta Sesión)",
                additional_comments: "Comentarios Adicionales de la Sesión",
                save_session_report_btn_sl: "Guardar Reporte de Sesión", // Clave nueva
                update_session_report_btn_sl: "Actualizar Reporte de Sesión", // Clave nueva
                cancel_edit_btn: "Cancelar Edición",
                history_title_sl: "Historial de Registros de Inspección (Safe Launch)", // Clave nueva
                th_report_id_sl: "ID Reporte", // Clave nueva
                th_inspection_date: "Fecha Insp.", th_time_range: "Rango Hora", th_shift_leader: "Turno",
                th_inspector: "Inspector", th_inspected: "Insp.", th_accepted: "Acep.",
                th_rejected: "Rech.", th_reworked: "Retrab.",
                th_total_defects: "Total Def.", th_lot_number: "No. Lote(s)",
                th_comments: "Comentarios", th_actions: "Acciones",
                no_history_records_sl: "Aún no hay registros de inspección para este Safe Launch.", // Clave nueva
                new_defect_header_sl: "Nuevo Defecto", // Clave nueva
                defect_type_label: "Tipo de Defecto", select_defect_option: "Seleccione un defecto",
                qty_label: "Cantidad", qty_placeholder: "Cantidad...",
                // Nuevas claves para la tabla de historial y totales
                th_total: "Total", th_ppm: "PPM", th_defect_percent: "% Defectuoso"
            },
            en: {
                welcome: "Welcome", logout: "Logout",
                main_title: "Safe Launch Inspection Report", folio: "Folio",
                project_name: "Project Name", client: "Client", responsible: "Responsible",
                instruction_title: "Work Instruction",
                view_instruction_btn: "View Instruction", hide_instruction_btn: "Hide Instruction", download_instruction_btn: "Download Instruction",
                no_instruction_attached: "No work instruction attached for this Safe Launch.",
                sl_closed_title: "Safe Launch Closed:", sl_closed_desc: "This Safe Launch has been marked as 'Closed' and new reports cannot be registered.",
                summary_title: "Inspection Summary",
                total_inspected: "Total Inspected Pieces", accepted_pieces: "Accepted Pieces",
                reworked_pieces: "Reworked Pieces", rejected_pieces_calc: "Rejected Pieces (Calculated)",
                inspector_name: "Inspector's Name", inspection_date: "Inspection Date",
                start_time: "Start Time", end_time: "End Time",
                defects_classification_title: "Defects Classification",
                available_to_classify: "Rejected pieces available for classification:",
                no_defects_catalog: "No defects registered in the Safe Launch catalog.",
                new_defects_title_sl: "New Defects Found (Optional)", // New key
                add_new_defect_btn_sl: "Add New Defect", // New key
                session_time_comments_title: "Session Times and Comments",
                inspection_time_session: "Inspection Time (This Session)",
                additional_comments: "Additional Session Comments",
                save_session_report_btn_sl: "Save Session Report", // New key
                update_session_report_btn_sl: "Update Session Report", // New key
                cancel_edit_btn: "Cancel Edit",
                history_title_sl: "Inspection Records History (Safe Launch)", // New key
                th_report_id_sl: "Report ID", // New key
                th_inspection_date: "Insp. Date", th_time_range: "Time Range", th_shift_leader: "Shift",
                th_inspector: "Inspector", th_inspected: "Insp.", th_accepted: "Acc.",
                th_rejected: "Rej.", th_reworked: "Rew.",
                th_total_defects: "Total Def.", th_lot_number: "Lot No(s).",
                th_comments: "Comments", th_actions: "Actions",
                no_history_records_sl: "There are no inspection records for this Safe Launch yet.", // New key
                new_defect_header_sl: "New Defect", // New key
                defect_type_label: "Defect Type", select_defect_option: "Select a defect",
                qty_label: "Quantity", qty_placeholder: "Quantity...",
                // New keys for history table and totals
                th_total: "Total", th_ppm: "PPM", th_defect_percent: "% Defective"
            }
        };

        function setLanguage(lang) { /* ... (código de traducción sin cambios) ... */ }
        function getCurrentLanguage() { return localStorage.getItem('language') || 'es'; }
        function translate(key) { const lang = getCurrentLanguage(); return translations[lang] ? (translations[lang][key] || key) : key; }

        document.querySelectorAll('.lang-btn').forEach(btn => { /* ... (listener sin cambios) ... */ });
        // --- FIN: LÓGICA DE TRADUCCIÓN ---

        const reporteForm = document.getElementById('reporteSLForm'); // ID actualizado
        if (reporteForm) {
            const idSLReporteInput = document.getElementById('idSLReporte'); // ID actualizado
            const piezasInspeccionadasInput = document.getElementById('piezasInspeccionadas');
            const piezasAceptadasInput = document.getElementById('piezasAceptadas');
            const piezasRetrabajadasInput = document.getElementById('piezasRetrabajadas');
            const piezasRechazadasCalculadasInput = document.getElementById('piezasRechazadasCalculadas');
            const piezasRechazadasRestantesSpan = document.getElementById('piezasRechazadasRestantes');
            const defectosClasificacionContainer = document.querySelector('.defect-classification-list'); // Clase actualizada
            const btnGuardarReporte = document.getElementById('btnGuardarReporteSL'); // ID actualizado
            const nuevosDefectosContainer = document.getElementById('nuevos-defectos-sl-container'); // ID NUEVO
            const btnAddNuevoDefecto = document.getElementById('btn-add-nuevo-defecto-sl'); // ID NUEVO
            const fechaInspeccionInput = document.querySelector('input[name="fechaInspeccion"]');
            const horaInicioInput = document.getElementById('horaInicio');
            const horaFinInput = document.getElementById('horaFin');
            const rangoHoraCompletoInput = document.getElementById('rangoHoraCompleto');
            const tiempoInspeccionInput = document.getElementById('tiempoInspeccion');
            // Tiempo Muerto eliminado
            const comentariosTextarea = document.getElementById('comentarios');
            // Desglose de partes eliminado

            // --- Calcular Tiempo (sin cambios) ---
            function calcularYActualizarTiempo() { /* ... (código sin cambios) ... */ }
            horaInicioInput.addEventListener('change', calcularYActualizarTiempo);
            horaFinInput.addEventListener('change', calcularYActualizarTiempo);

            // --- Actualizar Contadores (Adaptado) ---
            function actualizarContadores() {
                if (!piezasInspeccionadasInput) return;

                const inspeccionadas = parseInt(piezasInspeccionadasInput.value) || 0;
                const aceptadas = parseInt(piezasAceptadasInput.value) || 0;
                const retrabajadas = parseInt(piezasRetrabajadasInput.value) || 0;

                // Validación de máximo (si aplica en SL, si no, se puede quitar)
                // const piezasDeOtrosReportes = editandoReporteSL ? (totalPiezasInspeccionadasAnteriormente - valorOriginalInspeccionadoAlEditarSL) : totalPiezasInspeccionadasAnteriormente;
                // const maximoPermitidoParaEsteReporte = cantidadTotalSolicitada - piezasDeOtrosReportes; // Necesitaría cantidadTotalSolicitada si aplica
                // const esCantidadInvalida = false; // Ajustar si hay límite
                // if (esCantidadInvalida && cantidadTotalSolicitada > 0) { /* ... */ } else { piezasInspeccionadasInput.setCustomValidity(''); }

                const rechazadasBrutas = inspeccionadas - aceptadas;
                piezasRechazadasCalculadasInput.value = Math.max(0, rechazadasBrutas);
                const esRetrabajoInvalido = retrabajadas > rechazadasBrutas;

                if (esRetrabajoInvalido) {
                    piezasRetrabajadasInput.setCustomValidity('Las piezas retrabajadas no pueden exceder las piezas rechazadas.');
                    piezasRetrabajadasInput.reportValidity();
                } else {
                    piezasRetrabajadasInput.setCustomValidity('');
                }

                // Sumar defectos clasificados (del catálogo) y nuevos defectos
                const rechazadasDisponibles = rechazadasBrutas - retrabajadas;
                let sumDefectosClasificados = 0;
                defectosClasificacionContainer.querySelectorAll('.defecto-cantidad').forEach(input => { sumDefectosClasificados += parseInt(input.value) || 0; });
                nuevosDefectosContainer.querySelectorAll('.nuevo-defecto-cantidad-sl').forEach(input => { sumDefectosClasificados += parseInt(input.value) || 0; }); // Clase nueva

                const restantes = rechazadasDisponibles - sumDefectosClasificados;
                piezasRechazadasRestantesSpan.textContent = Math.max(0, restantes);

                const sonDefectosInvalidos = restantes !== 0;
                if (restantes < 0) {
                    piezasRechazadasRestantesSpan.style.color = 'var(--color-error)';
                } else if (restantes > 0) {
                    piezasRechazadasRestantesSpan.style.color = 'orange';
                } else {
                    piezasRechazadasRestantesSpan.style.color = 'var(--color-exito)';
                }

                // Deshabilitar botón si hay errores
                let deshabilitar = false;
                let titulo = '';
                // if (esCantidadInvalida && cantidadTotalSolicitada > 0) { /* ... */ }
                if (esRetrabajoInvalido) {
                    deshabilitar = true;
                    titulo = 'Las piezas retrabajadas no pueden exceder las piezas rechazadas.';
                } else if (sonDefectosInvalidos) {
                    deshabilitar = true;
                    titulo = restantes > 0 ? 'Aún faltan piezas por clasificar.' : 'La suma de defectos no puede exceder las piezas rechazadas disponibles.';
                }

                btnGuardarReporte.disabled = deshabilitar;
                btnGuardarReporte.title = titulo;
            }

            // --- Listeners para Contadores (Adaptado) ---
            if (piezasInspeccionadasInput) {
                piezasInspeccionadasInput.addEventListener('input', actualizarContadores);
                piezasAceptadasInput.addEventListener('input', actualizarContadores);
                piezasRetrabajadasInput.addEventListener('input', actualizarContadores);
                // Listener para defectos del catálogo
                defectosClasificacionContainer.addEventListener('input', function(e) {
                    if (e.target.classList.contains('defecto-cantidad')) {
                        actualizarContadores();
                    }
                });
                // Listener para NUEVOS defectos
                nuevosDefectosContainer.addEventListener('input', function(e) {
                    if (e.target.classList.contains('nuevo-defecto-cantidad-sl')) { // Clase nueva
                        actualizarContadores();
                    }
                });
                actualizarContadores(); // Llamada inicial
            }

            // --- INICIO: Función para Añadir Nuevo Defecto (Adaptada) ---
            function addNuevoDefectoBlockSL(id = null, idDefectoCatalogo = '', cantidad = '') {
                nuevoDefectoCounterSL++;
                const currentCounter = nuevoDefectoCounterSL;

                // HTML sin foto y sin número de parte (a menos que se necesite, se quitaría isVariosPartes)
                const defectoHTML = `
                <div class="defecto-item" id="nuevo-defecto-sl-${currentCounter}">
                    <div class="defecto-header">
                        <h4><span data-translate-key="new_defect_header_sl">Nuevo Defecto</span> #${currentCounter}</h4>
                        <button type="button" class="btn-remove-nuevo-defecto-sl" data-defecto-id="${currentCounter}">&times;</button>
                    </div>
                    <div class="form-row">
                        <div class="form-group w-50">
                            <label data-translate-key="defect_type_label">Tipo de Defecto</label>
                            <select name="nuevos_defectos[${currentCounter}][id]" required>
                                <option value="" disabled selected>${translate('select_defect_option')}</option>
                                ${opcionesDefectosSL} <!-- Usar las opciones correctas -->
                            </select>
                        </div>
                        <div class="form-group w-50">
                            <label data-translate-key="qty_label">Cantidad</label>
                            <input type="number" class="nuevo-defecto-cantidad-sl" name="nuevos_defectos[${currentCounter}][cantidad]" placeholder="${translate('qty_placeholder')}" min="0" required value="${cantidad || ''}">
                        </div>
                    </div>
                     ${id ? `<input type="hidden" name="nuevos_defectos[${currentCounter}][idSLReporteDefecto]" value="${id}">` : ''} <!-- Para identificar en edición -->
                </div>`;
                nuevosDefectosContainer.insertAdjacentHTML('beforeend', defectoHTML);

                const newBlock = document.getElementById(`nuevo-defecto-sl-${currentCounter}`);
                if (idDefectoCatalogo) {
                    newBlock.querySelector(`select[name="nuevos_defectos[${currentCounter}][id]"]`).value = idDefectoCatalogo;
                }
                // Traducir el nuevo bloque
                setLanguage(getCurrentLanguage());
                return newBlock;
            }

            btnAddNuevoDefecto?.addEventListener('click', function() {
                addNuevoDefectoBlockSL();
                actualizarContadores();
            });

            nuevosDefectosContainer?.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('btn-remove-nuevo-defecto-sl')) { // Clase nueva
                    const defectoItem = document.getElementById(`nuevo-defecto-sl-${e.target.dataset.defectoId}`);
                    if (defectoItem) {
                        // Lógica de eliminación al editar (si aplica) - Adaptar si es necesario guardar qué eliminar
                        const idDefectoExistenteInput = defectoItem.querySelector('input[name*="[idSLReporteDefecto]"]');
                        if (editandoReporteSL && idDefectoExistenteInput && idDefectoExistenteInput.value) {
                            // Marcar para eliminar en el backend
                            const inputEliminar = document.createElement('input');
                            inputEliminar.type = 'hidden';
                            inputEliminar.name = `nuevos_defectos_a_eliminar[]`; // Nombre para el backend
                            inputEliminar.value = idDefectoExistenteInput.value;
                            reporteForm.appendChild(inputEliminar);
                            console.log("Marcado para eliminar defecto nuevo ID:", idDefectoExistenteInput.value);
                        }
                        defectoItem.remove();
                        actualizarContadores();
                    }
                }
            });
            // --- FIN: Función para Añadir Nuevo Defecto ---

            // --- Tiempo Muerto eliminado ---

            // --- Submit del Formulario (Adaptado) ---
            reporteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Formatear rango de hora
                if (horaInicioInput.value && horaFinInput.value) { /* ... (código sin cambios) ... */ }
                else { rangoHoraCompletoInput.value = ''; }

                const form = this;
                const formData = new FormData(form);

                Swal.fire({ title: 'Guardando Reporte...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                // La acción del form debe apuntar a guardar_reporte_safe_launch.php o actualizar_reporte_sl.php
                fetch(form.action, { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('¡Éxito!', data.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => { console.error("Fetch Error:", error); Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error'); });
            });

            // --- Lógica para Cargar Reporte para Edición (Adaptada) ---
            async function cargarReporteParaEdicionSL(idSLReporte) { // Nombre nuevo
                const reporteForm = document.getElementById('reporteSLForm'); // ID nuevo
                // Mostrar formulario si estaba oculto
                reporteForm.style.display = 'block';
                // Ocultar mensaje de completado si existe
                document.getElementById('mensajeInspeccionCompletada')?.remove(); // Ocultarlo o removerlo

                Swal.fire({ title: 'Cargando Reporte...', text: 'Obteniendo datos para edición.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                try {
                    // Apuntar al nuevo DAO
                    const response = await fetch(`dao/obtener_reporte_sl_para_edicion.php?idSLReporte=${idSLReporte}`);
                    const data = await response.json();

                    if (data.status === 'success') {
                        const reporte = data.reporte;
                        const defectosGuardados = data.defectos; // Solo hay un tipo de defecto ahora

                        valorOriginalInspeccionadoAlEditarSL = parseInt(reporte.PiezasInspeccionadas, 10) || 0;

                        idSLReporteInput.value = reporte.IdSLReporte;
                        piezasInspeccionadasInput.value = reporte.PiezasInspeccionadas;
                        piezasAceptadasInput.value = reporte.PiezasAceptadas;
                        piezasRetrabajadasInput.value = reporte.PiezasRetrabajadas;
                        fechaInspeccionInput.value = reporte.FechaInspeccion; // Ya viene formateada Y-m-d
                        comentariosTextarea.value = reporte.Comentarios || '';

                        // Rellenar horas
                        if (reporte.RangoHora) { /* ... (código para convertir 12h a 24h sin cambios) ... */ }
                        else { horaInicioInput.value = ''; horaFinInput.value = ''; }
                        calcularYActualizarTiempo(); // Calcular tiempoInspeccion

                        // Rellenar defectos del catálogo
                        defectosClasificacionContainer.querySelectorAll('.form-group[data-id-sl-defecto-catalogo]').forEach(group => {
                            const idDefecto = group.dataset.idSlDefectoCatalogo;
                            const cantidadInput = group.querySelector('.defecto-cantidad');
                            const loteInput = group.querySelector('.defecto-lote');
                            const defectoGuardado = defectosGuardados.find(d => d.IdSLDefectoCatalogo == idDefecto);

                            cantidadInput.value = defectoGuardado ? defectoGuardado.CantidadEncontrada : 0;
                            loteInput.value = defectoGuardado ? (defectoGuardado.BachLote || '') : '';
                        });

                        // Rellenar nuevos defectos (si los hubiera en el reporte guardado - ASUMIENDO que se guardan en SafeLaunchReporteDefectos también)
                        nuevosDefectosContainer.innerHTML = ''; // Limpiar contenedor
                        nuevoDefectoCounterSL = 0; // Reiniciar contador
                        // --- ESTA PARTE NECESITA QUE EL DAO DEVUELVA LOS "NUEVOS" ---
                        // Si el DAO `obtener_reporte_sl_para_edicion` se modifica para devolver un array `nuevosDefectos` (ej. filtrando por algún flag o si simplemente todos van a SafeLaunchReporteDefectos)
                        /*
                        if (data.nuevosDefectos && data.nuevosDefectos.length > 0) {
                             data.nuevosDefectos.forEach(defecto => {
                                 addNuevoDefectoBlockSL(
                                     defecto.IdSLReporteDefecto, // ID del registro en la tabla de defectos
                                     defecto.IdSLDefectoCatalogo,
                                     defecto.CantidadEncontrada
                                     // No hay foto ni parte
                                 );
                             });
                        }
                        */
                        // --- FIN PARTE NUEVOS DEFECTOS ---


                        editandoReporteSL = true; // Marcar como edición
                        actualizarContadores(); // Recalcular todo

                        // Cambiar botón y acción del form
                        btnGuardarReporte.querySelector('span').innerText = translate('update_session_report_btn_sl');
                        reporteForm.action = 'dao/actualizar_reporte_sl.php'; // Apuntar al DAO de actualizar

                        // Añadir botón Cancelar Edición
                        const formActions = btnGuardarReporte.parentElement;
                        let cancelButton = formActions.querySelector('.btn-cancel-edit');
                        if (!cancelButton) { /* ... (código para crear botón Cancelar sin cambios) ... */ }

                        Swal.close();
                        window.scrollTo({ top: 0, behavior: 'smooth' });

                    } else {
                        Swal.fire('Error', data.message || 'No se pudieron cargar los datos del reporte.', 'error');
                    }
                } catch (error) {
                    console.error("Error al cargar reporte SL para edición:", error);
                    Swal.fire('Error de Conexión', 'No se pudo cargar el reporte para edición.', 'error');
                }
            }

            // --- Listener para Botón Editar (Adaptado) ---
            document.querySelectorAll('.btn-edit-reporte-sl').forEach(button => { // Clase nueva
                button.addEventListener('click', function() {
                    cargarReporteParaEdicionSL(this.dataset.id); // Función nueva
                });
            });

            // --- Listener para Botón Eliminar (Adaptado) ---
            document.querySelectorAll('.btn-delete-reporte-sl').forEach(button => { // Clase nueva
                button.addEventListener('click', function() {
                    const idReporteAEliminar = this.dataset.id;
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "¡No podrás revertir esto! Se eliminará este registro de inspección.", // Texto simplificado
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({ title: 'Eliminando Reporte...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            fetch('dao/eliminar_reporte_sl.php', { // Apuntar al DAO nuevo
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `idSLReporte=${idReporteAEliminar}` // ID nuevo
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire('¡Eliminado!', data.message, 'success').then(() => window.location.reload());
                                    } else {
                                        Swal.fire('Error', data.message, 'error');
                                    }
                                })
                                .catch(error => { console.error("Error al eliminar:", error); Swal.fire('Error de Conexión', 'No se pudo eliminar el reporte.', 'error'); });
                        }
                    });
                });
            });

        } // Fin if (reporteForm)

        // --- Lógica para subir/corregir método/instrucción (Adaptada) ---
        const instruccionForm = document.getElementById('instruccionForm'); // Asumiendo un ID si se crea ese form
        const btnSubirInstruccion = document.getElementById('btnSubirInstruccion'); // Asumiendo un ID

        if (instruccionForm && btnSubirInstruccion) {
            btnSubirInstruccion.addEventListener('click', function() {
                // Similar a la lógica de subir método, pero apuntando a un DAO diferente
                // dao/upload_instruccion_sl.php o dao/resubir_instruccion_sl.php
                // Y usando los campos correctos (idSafeLaunch, tituloInstruccion, fileInstruccion)
                Swal.fire('Funcionalidad Pendiente', 'La lógica para subir/resubir instrucciones aún no está implementada.', 'info');
            });
        }
        // Lógica para actualizar nombre de archivo (igual que antes)
        function updateFileNameLabel(e) { /* ... (código sin cambios) ... */ }
        document.querySelectorAll('input[type="file"]').forEach(input => input.addEventListener('change', updateFileNameLabel));


        // --- Lógica Visor PDF (Adaptada) ---
        const togglePdfBtn = document.getElementById('togglePdfViewerBtn');
        const pdfWrapper = document.getElementById('pdfViewerWrapper');
        const pdfUrl = '<?php echo $mostrarVisorInstruccion ? htmlspecialchars($solicitudSL['RutaInstruccion']) : ''; ?>'; // Usar RutaInstruccion

        function adjustPdfButtonText() {
            if (!togglePdfBtn) return;
            const isMobile = window.innerWidth <= 768;
            const span = togglePdfBtn.querySelector('span');
            const icon = togglePdfBtn.querySelector('i');
            const keyView = 'view_instruction_btn'; // Clave nueva
            const keyHide = 'hide_instruction_btn'; // Clave nueva
            const keyDownload = 'download_instruction_btn'; // Clave nueva

            if (isMobile) {
                span.innerText = translate(keyDownload);
                icon.className = 'fa-solid fa-download';
                if (pdfWrapper && pdfWrapper.style.display !== 'none') { /* ... (código ocultar sin cambios) ... */ }
            } else {
                const isHidden = pdfWrapper ? pdfWrapper.style.display === 'none' : true;
                span.innerText = isHidden ? translate(keyView) : translate(keyHide);
                icon.className = isHidden ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
                if (isHidden) { /* ... (código clases sin cambios) ... */ } else { /* ... */ }
            }
        }

        if (togglePdfBtn && pdfUrl) {
            togglePdfBtn.addEventListener('click', function() { /* ... (lógica click sin cambios, usa pdfUrl) ... */ });
            adjustPdfButtonText();
            window.addEventListener('resize', adjustPdfButtonText);
        }
        // --- Fin Lógica Visor PDF ---

        // Lógica add/remove batch eliminada

        // Carga inicial del idioma
        setLanguage(getCurrentLanguage());
    });
</script>
</body>
</html>

