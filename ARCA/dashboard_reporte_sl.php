<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

// --- Cargar solicitudes de Safe Launch activas para el selector ---
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();
// Buscamos solicitudes activas (IdEstatus 3 o 4)
$solicitudes_activas_query = $conex->query("SELECT IdSafeLaunch, NombreProyecto FROM SafeLaunchSolicitudes WHERE IdEstatus IN (3, 4) ORDER BY IdSafeLaunch DESC");
$conex->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Safe Launch - ARCA</title>
    <link rel="stylesheet" href="css/estilosT.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Librerías para Gráficas y PDF -->
    <!-- REEMPLAZO: ApexCharts en lugar de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
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
        .form-actions { text-align: right; margin-top: 20px; }
        .page-header {
            margin-bottom: 20px;
        }
        .page-header h1 {
            font-family: 'Montserrat', sans-serif;
            color: var(--color-primario);
            margin: 0;
        }
        .lang-btn { border: none; background-color: transparent; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 13px; padding: 4px 12px; border-radius: 15px; cursor: pointer; color: #888; transition: all 0.3s ease; }
        .lang-btn:not(.active):hover { background-color: #e9eef2; color: var(--color-primario); }
        .lang-btn.active { background-color: var(--color-secundario); color: var(--color-blanco); cursor: default; }
        .language-selector { display: flex; align-items: center; gap: 5px; background-color: var(--color-fondo); padding: 4px; border-radius: 20px; margin-right: 0; border: 1px solid var(--color-borde); }
        .btn-primary, .btn-secondary { padding: 12px 25px; border-radius: 6px; border: none; font-family: 'Montserrat', sans-serif; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .btn-primary { background-color: var(--color-secundario); color: white; }
        .btn-primary:hover { background-color: var(--color-primario); }
        .btn-secondary { background-color: #e0e0e0; color: #333; }
        .btn-secondary:hover { background-color: #bdbdbd; }


        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        #reporte-generado-container {
            margin-top: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: var(--sombra-suave);
            overflow: hidden;
            display: none;
        }

        .reporte-header {
            padding: 20px 30px;
            background-color: var(--color-fondo);
            border-bottom: 1px solid var(--color-borde);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reporte-header h3 {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            color: var(--color-primario);
        }

        #contenido-reporte {
            padding: 30px;
            font-family: 'Arial', sans-serif;
            color: #333;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .chart-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--color-borde);
            min-height: 400px; /* Altura mínima para ApexCharts */
        }
        .chart-box h5 {
            text-align: center;
            font-family: 'Montserrat';
            margin-top: 0;
            margin-bottom: 15px;
        }
        @media (max-width: 992px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }

        #contenido-reporte .report-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        #contenido-reporte .report-subtitle {
            text-align: center;
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        #contenido-reporte .info-section {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        #contenido-reporte .info-section p {
            margin: 0;
            font-size: 14px;
        }
        #contenido-reporte .info-section strong {
            color: #444;
        }
        #contenido-reporte h4 {
            font-size: 18px;
            color: var(--color-primario);
            border-bottom: 2px solid var(--color-acento);
            padding-bottom: 8px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        #contenido-reporte table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 20px;
        }
        #contenido-reporte th, #contenido-reporte td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: top;
            word-break: break-word; /* Para que el texto largo no rompa la tabla */
        }
        #contenido-reporte th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        #contenido-reporte .summary-table td:first-child {
            font-weight: bold;
            width: 200px;
        }
        /* Estilos para listas de defectos en tablas */
        #contenido-reporte .defect-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
            font-size: 12px;
        }
        #contenido-reporte .defect-list li {
            margin-bottom: 2px;
        }


        .chart-box, .info-section, .summary-table, h4, h5, table {
            page-break-inside: avoid;
        }

        .dashboard-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .dashboard-filter-bar .form-group {
            margin-bottom: 0;
        }
        .dashboard-filter-bar .btn-primary,
        .dashboard-filter-bar .btn-secondary {
            padding: 8px 15px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: auto;
        }
        #btn-limpiar-dashboard-sl {
            background-color: transparent;
            border: 1px solid var(--color-borde);
            color: #555;
        }
        #btn-limpiar-dashboard-sl:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        /* --- NUEVO: Estilos para la tabla de defectos diarios --- */
        .defect-daily-breakdown .table-responsive {
            overflow-x: auto; /* Permite scroll horizontal en tablas grandes */
            margin-bottom: 20px;
        }
        .defect-daily-breakdown table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            min-width: 800px; /* Ancho mínimo para forzar scroll si es necesario */
        }
        .defect-daily-breakdown th, .defect-daily-breakdown td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: top;
            min-width: 80px; /* Ancho mínimo para celdas de fecha */
        }
        .defect-daily-breakdown th {
            background-color: #e9ecef;
            font-weight: bold;
            position: sticky; /* Encabezados fijos */
            top: 0;
            z-index: 1;
        }
        .defect-daily-breakdown tbody td:first-child,
        .defect-daily-breakdown tfoot th:first-child {
            text-align: left; /* Alinea los nombres de defectos a la izquierda */
            font-weight: bold;
            background-color: #f8f9fa;
            position: sticky; /* Columna de defectos fija */
            left: 0;
            z-index: 2;
            min-width: 150px; /* Ancho para la columna de defectos */
            word-break: break-word;
        }
        .defect-daily-breakdown tfoot th {
            background-color: #e9ecef;
            text-align: left;
        }
        .defect-daily-breakdown .total-row th,
        .defect-daily-breakdown .total-row td {
            background-color: #fdfdfd;
            font-weight: bold;
            border-top: 2px solid #ccc;
        }
        .defect-daily-breakdown .metrics-row th,
        .defect-daily-breakdown .metrics-row td {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .defect-daily-breakdown .metrics-row th:first-child {
            background-color: #e9ecef;
        }
        /* --- FIN NUEVO --- */

    </style>
</head>
<body>
<header class="header">
    <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
    <div class="user-info">
        <div class="language-selector">
            <button type="button" class="lang-btn" data-lang="es">ES</button>
            <button type="button" class="lang-btn" data-lang="en">EN</button>
        </div>
        <span><span data-translate-key="welcome">Bienvenido</span>, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'"><span data-translate-key="logout">Cerrar Sesión</span> <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-chart-pie"></i> <span data-translate-key="main_title">Dashboard de Safe Launch</span></h1>
    </div>

    <div class="form-container" style="margin-bottom: 30px;">
        <div class="form-group">
            <label for="safe-launch-selector"><i class="fa-solid fa-folder-open"></i> <span data-translate-key="select_request">Seleccionar Solicitud de Safe Launch</span></label>
            <select id="safe-launch-selector">
                <option value="" data-translate-key="select_request_option">-- Elige una solicitud --</option>
                <?php while($solicitud = $solicitudes_activas_query->fetch_assoc()): ?>
                    <option value="<?php echo $solicitud['IdSafeLaunch']; ?>">
                        SL-<?php echo str_pad($solicitud['IdSafeLaunch'], 4, '0', STR_PAD_LEFT); ?> (<?php echo htmlspecialchars($solicitud['NombreProyecto']); ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="form-container">
            <fieldset>
                <legend><i class="fa-solid fa-calendar-week"></i> <span data-translate-key="partial_report_title">Reportes Parciales</span></legend>
                <p data-translate-key="partial_report_desc">Genera un reporte de Safe Launch para un rango de fechas específico. Ideal para seguimientos semanales.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha-inicio-sl" data-translate-key="start_date">Fecha de Inicio</label>
                        <input type="date" id="fecha-inicio-sl" name="fecha-inicio-sl">
                    </div>
                    <div class="form-group">
                        <label for="fecha-fin-sl" data-translate-key="end_date">Fecha de Fin</label>
                        <input type="date" id="fecha-fin-sl" name="fecha-fin-sl">
                    </div>
                </div>
                <div class="form-actions">
                    <button id="btn-generar-parcial-sl" class="btn-primary"><i class="fa-solid fa-file-waveform"></i> <span data-translate-key="generate_report_btn">Generar Reporte</span></button>
                </div>
            </fieldset>
        </div>
        <div class="form-container">
            <fieldset>
                <legend><i class="fa-solid fa-flag-checkered"></i> <span data-translate-key="final_report_title">Reporte Final de Safe Launch</span></legend>
                <p data-translate-key="final_report_desc">Visualiza y descarga el reporte consolidado con todos los datos del proceso.</p>
                <div class="form-actions" style="justify-content: center; text-align:center;">
                    <button id="btn-ver-final-sl" class="btn-primary"><i class="fa-solid fa-file-pdf"></i> <span data-translate-key="view_final_report_btn">Ver Reporte Final</span></button>
                </div>
            </fieldset>
        </div>
    </div>

    <div id="reporte-generado-container">
        <div class="reporte-header">
            <h3><i class="fa-solid fa-eye"></i> <span data-translate-key="report_preview_title">Vista Previa del Reporte</span></h3>
            <button id="btn-descargar-pdf-sl" class="btn-secondary"><i class="fa-solid fa-download"></i> <span data-translate-key="download_pdf_btn">Descargar PDF</span></button>
        </div>
        <div id="contenido-reporte"></div>
    </div>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const solicitudSelector = document.getElementById('safe-launch-selector');
        const btnGenerarParcial = document.getElementById('btn-generar-parcial-sl');
        const btnVerFinal = document.getElementById('btn-ver-final-sl');
        const btnDescargarPdf = document.getElementById('btn-descargar-pdf-sl');
        const reporteContainer = document.getElementById('reporte-generado-container');
        const contenidoReporteDiv = document.getElementById('contenido-reporte');
        const campoFechaInicio = document.getElementById('fecha-inicio-sl');
        const campoFechaFin = document.getElementById('fecha-fin-sl');

        // Variables para las instancias de ApexCharts
        let paretoChart, weeklyRejectsChart, rejectionRateChart, dailyProgressChart, ppmTrendChart;
        let lastReportData;

        // Función para cargar la primera fecha de inspección
        function fetchPrimeraFecha(idSafeLaunch) {
            campoFechaInicio.disabled = true;
            campoFechaInicio.value = '';

            // Apuntar a la nueva API de Safe Launch
            fetch(`dao/api_get_primera_fecha_sl.php?idSafeLaunch=${idSafeLaunch}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.primeraFecha) {
                        campoFechaInicio.value = data.primeraFecha;
                        // Establecer la fecha final por defecto al día de hoy
                        campoFechaFin.value = new Date().toISOString().split('T')[0];
                    } else {
                        console.warn(data.message || 'No se encontró primera fecha.');
                        campoFechaInicio.value = '';
                        campoFechaFin.value = new Date().toISOString().split('T')[0];
                    }
                })
                .catch(error => {
                    console.error('Error al obtener la primera fecha:', error);
                    campoFechaInicio.value = '';
                })
                .finally(() => {
                    campoFechaInicio.disabled = false;
                });
        }

        solicitudSelector.addEventListener('change', function() {
            const idSolicitudSeleccionada = this.value;
            if (idSolicitudSeleccionada) {
                fetchPrimeraFecha(idSolicitudSeleccionada);
            } else {
                campoFechaInicio.value = '';
                campoFechaFin.value = '';
            }
        });


        const translations = {
            es: {
                welcome: "Bienvenido", logout: "Cerrar Sesión", main_title: "Dashboard de Safe Launch",
                select_request: "Seleccionar Solicitud de Safe Launch", select_request_option: "-- Elige una solicitud --",
                partial_report_title: "Reportes Parciales", partial_report_desc: "Genera un reporte de Safe Launch para un rango de fechas específico. Ideal para seguimientos semanales.",
                start_date: "Fecha de Inicio", end_date: "Fecha de Fin", generate_report_btn: "Generar Reporte",
                final_report_title: "Reporte Final de Safe Launch", final_report_desc: "Visualiza y descarga el reporte consolidado con todos los datos del proceso.",
                view_final_report_btn: "Ver Reporte Final",
                report_preview_title: "Vista Previa del Reporte", download_pdf_btn: "Descargar PDF",
                missing_info: "Falta Información", select_request_warning: "Por favor, selecciona una solicitud de Safe Launch.",
                incomplete_fields: "Campos incompletos", select_dates_warning: "Por favor, selecciona una fecha de inicio y una fecha de fin.",
                generating_pdf: "Generando PDF", please_wait: "Por favor, espera un momento...",
                generating_report_title: "Generando Reporte", generating_report_text: "Consultando la información...",
                error_title: "Error", error_message_default: "No se pudo generar el reporte.",
                connection_error_title: "Error de Conexión", connection_error_message: "No se pudo comunicar con el servidor.",
                request_folio: "Folio de Safe Launch", general_info: "Información General",
                project: "Proyecto", client: "Cliente", responsible: "Responsable", issue_date: "Fecha de Emisión",
                period_summary: "Resumen General del Periodo",
                inspected_pieces: "Piezas Inspeccionadas", accepted_pieces: "Piezas Aceptadas",
                rejected_pieces: "Piezas Rechazadas", reworked_pieces: "Piezas Retrabajadas",
                total_inspection_time: "Tiempo Total de Inspección", pieces_per_hour: "Rate (Piezas / Hora)",
                hourly_breakdown: "Desglose de Inspección por Día",
                day_totals: "Totales del día", inspected: "Inspeccionadas", accepted: "Aceptadas", rejected: "Rechazadas",
                hour_by_hour: "Rango de Hora", shift: "Turno", inspector: "Inspector",
                downtime: "Tiempo Muerto", no: "No", comments: "Comentarios",
                defects_summary: "Resumen de Defectos del Periodo",
                defect: "Defecto", total_qty_defect: "Cantidad Total", defect_type: "Tipo de Defecto",
                new_defects_found: "Nuevos Defectos Encontrados (Opcionales)",
                no_defects_found_period: "No se encontraron defectos en este periodo.",
                visual_dashboards: "Dashboards Visuales",
                pareto_chart_title: "Pareto de Defectos (Top 5)",
                weekly_rejects_title: "Rechazadas por Semana",
                accepted_vs_rejected_title: "Aceptadas vs. Rechazadas",
                daily_progress_title: "Progreso Diario de Inspección",
                ppm_trend_chart_title: "Tendencia PPM (Partes por Millón)",
                chart_qty: "Cantidad", chart_cumulative: "% Acumulado", chart_week: "Semana", chart_ppm: "PPM",
                dashboard_filter_apply: "Filtrar Gráficas", dashboard_filter_clear: "Limpiar Filtro",
                // --- NUEVAS TRADUCCIONES ---
                daily_defects_title: "Detalle Diario de Defectos",
                issues_found: "Problemas Encontrados",
                total_problems: "Total de Problemas",
                quantity_inspected: "Cantidad Inspeccionada",
                ppm: "PPM",
                percent_defect: "% Defecto",
                total: "Total",
                percent: "%",
                defect_type_associated: "Asociado",
                defect_type_optional: "Opcional"
                // --- FIN NUEVAS TRADUCCIONES ---
            },
            en: {
                welcome: "Welcome", logout: "Logout", main_title: "Safe Launch Dashboard",
                select_request: "Select Safe Launch Request", select_request_option: "-- Choose a request --",
                partial_report_title: "Partial Reports", partial_report_desc: "Generate a Safe Launch report for a specific date range. Ideal for weekly follow-ups.",
                start_date: "Start Date", end_date: "End Date", generate_report_btn: "Generate Report",
                final_report_title: "Final Safe Launch Report", final_report_desc: "View and download the consolidated report with all process data.",
                view_final_report_btn: "View Final Report",
                report_preview_title: "Report Preview", download_pdf_btn: "Download PDF",
                missing_info: "Missing Information", select_request_warning: "Please select a Safe Launch request.",
                incomplete_fields: "Incomplete Fields", select_dates_warning: "Please select a start and end date.",
                generating_pdf: "Generating PDF", please_wait: "Please wait a moment...",
                generating_report_title: "Generating Report", generating_report_text: "Querying information...",
                error_title: "Error", error_message_default: "Could not generate report.",
                connection_error_title: "Connection Error", connection_error_message: "Could not connect to the server.",
                request_folio: "Safe Launch Folio", general_info: "General Information",
                project: "Project", client: "Client", responsible: "Responsible", issue_date: "Issue Date",
                period_summary: "General Period Summary",
                inspected_pieces: "Inspected Pieces", accepted_pieces: "Accepted Pieces",
                rejected_pieces: "Rejected Pieces", reworked_pieces: "Reworked Pieces",
                total_inspection_time: "Total Inspection Time", pieces_per_hour: "Rate (Pieces / Hour)",
                hourly_breakdown: "Daily Inspection Breakdown",
                day_totals: "Day's Totals", inspected: "Inspected", accepted: "Accepted", rejected: "Rejected",
                hour_by_hour: "Time Range", shift: "Shift", inspector: "Inspector",
                downtime: "Downtime", no: "No", comments: "Comments",
                defects_summary: "Defects Summary for the Period",
                defect: "Defect", total_qty_defect: "Total Quantity", defect_type: "Defect Type",
                new_defects_found: "New Defects Found (Optional)",
                no_defects_found_period: "No defects found in this period.",
                visual_dashboards: "Visual Dashboards",
                pareto_chart_title: "Defects Pareto (Top 5)",
                weekly_rejects_title: "Weekly Rejects",
                accepted_vs_rejected_title: "Accepted vs. Rejected",
                daily_progress_title: "Daily Inspection Progress",
                ppm_trend_chart_title: "PPM (Parts Per Million) Trend",
                chart_qty: "Quantity", chart_cumulative: "Cumulative %", chart_week: "Week", chart_ppm: "PPM",
                dashboard_filter_apply: "Filter Charts", dashboard_filter_clear: "Clear Filter",
                // --- NEW TRANSLATIONS ---
                daily_defects_title: "Daily Defects Detail",
                issues_found: "Issues Found",
                total_problems: "Total Problems",
                quantity_inspected: "Quantity Inspected",
                ppm: "PPM",
                percent_defect: "% Defect",
                total: "Total",
                percent: "%",
                defect_type_associated: "Associated",
                defect_type_optional: "Optional"
                // --- END NEW TRANSLATIONS ---
            }
        };

        function setLanguage(lang) {
            document.documentElement.lang = lang;
            localStorage.setItem('language', lang);
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.getAttribute('data-translate-key');
                if (translations[lang] && translations[lang][key]) {

                    const span = el.querySelector('span');
                    const icon = el.querySelector('i');

                    if (span && (el.tagName === 'BUTTON' || el.tagName === 'LEGEND' || el.tagName === 'H1' || el.tagName === 'H2' || el.tagName === 'H3')) {
                        // Traduce solo el span si existe (para botones con icono)
                        span.innerText = translations[lang][key];
                    } else if (icon && (el.tagName === 'LEGEND' || el.tagName === 'H1' || el.tagName === 'H2' || el.tagName === 'H3' || el.tagName === 'BUTTON')) {
                        // Si no hay span pero sí icono, añade el texto después del icono
                        el.innerHTML = icon.outerHTML + ' ' + translations[lang][key];
                    } else if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) {
                        // Para inputs
                        el.value = translations[lang][key];
                    } else if (el.tagName === 'OPTION') {
                        // Para options (como el placeholder del select)
                        el.innerText = translations[lang][key];
                    } else if (!el.children.length) {
                        // Para elementos de texto simple (p, label, th, etc.)
                        el.innerText = translations[lang][key];
                    } else if (el.tagName === 'SPAN' && !el.children.length) {
                        el.innerText = translations[lang][key];
                    }
                }
            });
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.lang === lang));
        }

        function getCurrentLanguage() { return localStorage.getItem('language') || 'es'; }
        function translate(key) { const lang = getCurrentLanguage(); return (translations[lang] && translations[lang][key]) ? translations[lang][key] : key; }

        function parsearTiempoAMinutosJS(tiempoStr) {
            if (!tiempoStr) return 0;
            let totalMinutos = 0;
            const horasMatch = tiempoStr.match(/(\d+)\s*hora(s)?/);
            const minutosMatch = tiempoStr.match(/(\d+)\s*minuto(s)?/);
            if (horasMatch) { totalMinutos += parseInt(horasMatch[1], 10) * 60; }
            if (minutosMatch) { totalMinutos += parseInt(minutosMatch[1], 10); }
            return totalMinutos;
        }

        const fetchReportData = (url) => {
            const alertTitle = translate('generating_report_title');
            const alertText = translate('generating_report_text');
            Swal.fire({ title: alertTitle, text: alertText, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            // Apuntar a la nueva API de Safe Launch
            fetch(url.replace('api_generar_reporte.php', 'api_generar_reporte_sl.php'))
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        lastReportData = data.reporte;
                        renderizarReporte(data.reporte);
                    } else {
                        Swal.fire(translate('error_title'), data.message || translate('error_message_default'), 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire(translate('connection_error_title'), translate('connection_error_message') + ": " + error.message, 'error');
                });
        };

        btnGenerarParcial.addEventListener('click', () => {
            const idSafeLaunch = solicitudSelector.value;
            const fechaInicio = campoFechaInicio.value;
            const fechaFin = campoFechaFin.value;
            if (!idSafeLaunch) { Swal.fire(translate('missing_info'), translate('select_request_warning'), 'warning'); return; }
            if (!fechaInicio || !fechaFin) { Swal.fire(translate('incomplete_fields'), translate('select_dates_warning'), 'warning'); return; }
            const url = `dao/api_generar_reporte_sl.php?tipo=parcial&idSafeLaunch=${idSafeLaunch}&inicio=${fechaInicio}&fin=${fechaFin}`;
            fetchReportData(url);
        });

        btnVerFinal.addEventListener('click', () => {
            const idSafeLaunch = solicitudSelector.value;
            if (!idSafeLaunch) { Swal.fire(translate('missing_info'), translate('select_request_warning'), 'warning'); return; }
            const url = `dao/api_generar_reporte_sl.php?tipo=final&idSafeLaunch=${idSafeLaunch}`;
            fetchReportData(url);
        });

        btnDescargarPdf.addEventListener('click', async () => {
            Swal.fire({ title: translate('generating_pdf'), text: translate('please_wait'), allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            const elementoReporte = document.getElementById('contenido-reporte');

            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const margin = 10;
            const contentWidth = pdfWidth - (margin * 2);
            let yPosition = margin;

            const allElements = elementoReporte.querySelectorAll('.report-title, .report-subtitle, .pdf-section');

            for (const element of allElements) {
                // Manejo de scroll en tabla
                let originalOverflow = null;
                let responsiveWrapper = null;
                if (element.id === 'defect-daily-breakdown-container') {
                    responsiveWrapper = element.querySelector('.table-responsive');
                    if (responsiveWrapper) {
                        originalOverflow = responsiveWrapper.style.overflowX;
                        responsiveWrapper.style.overflowX = 'visible';
                    }
                }

                // html2canvas funciona mejor con SVG si se captura el elemento y se usa useCORS
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    width: element.scrollWidth,
                    windowWidth: element.scrollWidth
                });

                if (responsiveWrapper) {
                    responsiveWrapper.style.overflowX = originalOverflow;
                }

                const imgData = canvas.toDataURL('image/png');
                const imgProps = pdf.getImageProperties(imgData);
                const imgHeight = (imgProps.height * contentWidth) / imgProps.width;

                if (yPosition + imgHeight > pdfHeight - margin) {
                    pdf.addPage();
                    yPosition = margin;
                }

                pdf.addImage(imgData, 'PNG', margin, yPosition, contentWidth, imgHeight);
                yPosition += imgHeight + 5;
            }

            pdf.save(`reporte-SL${solicitudSelector.value.padStart(4, '0')}.pdf`);
            Swal.close();
        });


        function renderDailyDefectsTable(data) {
            const currentLang = getCurrentLanguage();
            const dateLocale = currentLang === 'en' ? 'en-US' : 'es-MX';

            let tableHtml = `
                <div class="pdf-section defect-daily-breakdown" id="defect-daily-breakdown-container">
                    <h4><i class="fa-solid fa-table"></i> ${translate('daily_defects_title')}</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>${translate('issues_found')}</th>
            `;

            const allDates = Object.keys(data.defectosDiarios).sort();
            const allDefectNames = new Set();
            allDates.forEach(date => {
                const dayData = data.defectosDiarios[date];
                Object.keys(dayData.defectos).forEach(defectName => allDefectNames.add(defectName));
            });
            const sortedDefectNames = Array.from(allDefectNames).sort();

            allDates.forEach(date => {
                const dateObj = new Date(date + 'T00:00:00');
                const dayOfWeek = dateObj.toLocaleDateString(dateLocale, { weekday: 'short' });
                const monthDay = dateObj.toLocaleDateString(dateLocale, { month: 'short', day: 'numeric' });
                tableHtml += `<th>${monthDay}<br>${dayOfWeek}</th>`;
            });
            tableHtml += `<th>${translate('total')}</th><th>${translate('percent')}</th></tr></thead><tbody>`;

            let grandTotalProblems = 0;
            const defectRowTotals = {};

            sortedDefectNames.forEach(defectName => {
                let totalDefectQty = 0;
                allDates.forEach(date => {
                    const qty = data.defectosDiarios[date]?.defectos[defectName] || 0;
                    totalDefectQty += qty;
                });
                defectRowTotals[defectName] = totalDefectQty;
                grandTotalProblems += totalDefectQty;
            });

            if (sortedDefectNames.length > 0) {
                sortedDefectNames.forEach(defectName => {
                    tableHtml += `<tr><td>${defectName}</td>`;
                    allDates.forEach(date => {
                        const qty = data.defectosDiarios[date]?.defectos[defectName] || 0;
                        tableHtml += `<td>${qty > 0 ? qty : ''}</td>`;
                    });
                    const totalDefectQty = defectRowTotals[defectName] || 0;
                    const percentDefect = grandTotalProblems > 0 ? ((totalDefectQty / grandTotalProblems) * 100).toFixed(2) : '0.00';
                    tableHtml += `<td>${totalDefectQty}</td><td>${percentDefect}%</td></tr>`;
                });
            } else {
                tableHtml += `<tr><td colspan="${allDates.length + 3}" style="text-align:center;">${translate('no_defects_found_period')}</td></tr>`;
            }

            tableHtml += `</tbody><tfoot>`;

            // Fila de TOTAL PROBLEMS
            tableHtml += `<tr class="total-row"><th>${translate('total_problems')}</th>`;
            let grandTotalProblemsForPercent = 0;
            allDates.forEach(date => {
                const dayTotal = data.defectosDiarios[date]?.totalDefectosDia || 0;
                tableHtml += `<td>${dayTotal > 0 ? dayTotal : ''}</td>`;
                grandTotalProblemsForPercent += dayTotal;
            });
            const totalInspectedOverall = data.resumen.inspeccionadas;
            const grandPercentDefect = totalInspectedOverall > 0 ? ((grandTotalProblemsForPercent / totalInspectedOverall) * 100).toFixed(2) : '0.00';
            tableHtml += `<td>${grandTotalProblemsForPercent}</td><td>${grandPercentDefect}%</td></tr>`;

            // Fila de QUANTITY INSPECTED
            tableHtml += `<tr class="metrics-row"><th>${translate('quantity_inspected')}</th>`;
            allDates.forEach(date => {
                const qtyInspected = data.defectosDiarios[date]?.piezasInspeccionadas || 0;
                tableHtml += `<td>${qtyInspected}</td>`;
            });
            tableHtml += `<td colspan="2">${totalInspectedOverall}</td></tr>`;

            // Fila de PPM
            tableHtml += `<tr class="metrics-row"><th>${translate('ppm')}</th>`;
            allDates.forEach(date => {
                const ppmDay = data.defectosDiarios[date]?.ppmDia || 0;
                tableHtml += `<td>${Math.round(ppmDay)}</td>`;
            });
            const ppmGlobal = totalInspectedOverall > 0 ? (grandTotalProblemsForPercent / totalInspectedOverall) * 1000000 : 0;
            tableHtml += `<td colspan="2">${Math.round(ppmGlobal)}</td></tr>`;

            // Fila de % DEFECTO
            tableHtml += `<tr class="metrics-row"><th>${translate('percent_defect')}</th>`;
            allDates.forEach(date => {
                const dayData = data.defectosDiarios[date];
                const percentDay = (dayData.piezasInspeccionadas > 0) ? ((dayData.totalDefectosDia / dayData.piezasInspeccionadas) * 100).toFixed(2) : '0.00';
                tableHtml += `<td>${percentDay}%</td>`;
            });
            tableHtml += `<td colspan="2">${grandPercentDefect}%</td></tr>`;

            tableHtml += `</tfoot></table></div></div>`;
            return tableHtml;
        }

        function renderizarReporte(data) {
            const currentLang = getCurrentLanguage();

            let infoHtml = `
            <p><strong>${translate('project')}:</strong> ${data.info.nombreProyecto}</p>
            <p><strong>${translate('client')}:</strong> ${data.info.cliente}</p>
            <p><strong>${translate('responsible')}:</strong> ${data.info.responsable}</p>
            <p><strong>${translate('issue_date')}:</strong> ${new Date().toLocaleDateString(currentLang === 'en' ? 'en-US' : 'es-MX')}</p>
            `;

            let desgloseHtml = `<div class="pdf-section"><h4><i class="fa-solid fa-calendar-day"></i> ${translate('hourly_breakdown')}</h4>`;
            if (data.desgloseDiario && data.desgloseDiario.length > 0) {
                data.desgloseDiario.forEach(dia => {
                    const dateLocale = currentLang === 'en' ? 'en-US' : 'es-MX';
                    const fechaFormateada = new Date(dia.fecha + 'T00:00:00').toLocaleDateString(dateLocale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

                    let diaHtml = `<div class="daily-breakdown-item">`;
                    diaHtml += `<h5 style="font-size: 16px; margin-top: 20px; color: #555;">${fechaFormateada}</h5>`;

                    if (dia.totales) {
                        diaHtml += `<p>${translate('day_totals')}: <strong>${dia.totales.inspeccionadas}</strong> ${translate('inspected')}, <strong>${dia.totales.aceptadas}</strong> ${translate('accepted')}, <strong>${dia.totales.rechazadas}</strong> ${translate('rejected')}.</p>`;
                    }

                    diaHtml += `<table><thead><tr>
                                    <th>${translate('hour_by_hour')}</th>
                                    <th>${translate('shift')}</th>
                                    <th>${translate('inspector')}</th>
                                    <th>${translate('inspected')}</th>
                                    <th>${translate('accepted')}</th>
                                    <th>${translate('rejected')}</th>
                                    <th>${translate('comments')}</th>
                                </tr></thead><tbody>`;
                    dia.entradas.forEach(entrada => {
                        diaHtml += `<tr>
                                    <td>${entrada.RangoHora || 'N/A'}</td>
                                    <td>${entrada.turno || 'N/A'}</td>
                                    <td>${entrada.NombreInspector || 'N/A'}</td>
                                    <td>${entrada.PiezasInspeccionadas}</td>
                                    <td>${entrada.PiezasAceptadas}</td>
                                    <td>${parseInt(entrada.PiezasInspeccionadas) - parseInt(entrada.PiezasAceptadas)}</td>
                                    <td>${entrada.Comentarios || ''}</td>
                                </tr>`;
                    });
                    diaHtml += `</tbody></table></div>`;
                    desgloseHtml += diaHtml;
                });
            } else {
                desgloseHtml += `<p>No hay registros de historial.</p>`;
            }
            desgloseHtml += `</div>`;


            let defectosHtml = `<div class="pdf-section"><h4><i class="fa-solid fa-magnifying-glass"></i> ${translate('defects_summary')}</h4>`;
            defectosHtml += `<table><thead><tr><th>${translate('defect')}</th><th>${translate('total_qty_defect')}</th><th>${translate('defect_type')}</th></tr></thead><tbody>`;
            if (data.defectos && data.defectos.length > 0) {
                data.defectos.forEach(defecto => {
                    defectosHtml += `<tr>
                                        <td>${defecto.nombre}</td>
                                        <td>${defecto.cantidad}</td>
                                        <td>${defecto.tipo === 'Asociado' ? translate('defect_type_associated') : translate('defect_type_optional')}</td>
                                    </tr>`;
                });
            } else {
                defectosHtml += `<tr><td colspan="3" style="text-align:center;">${translate('no_defects_found_period')}</td></tr>`;
            }
            defectosHtml += `</tbody></table></div>`;

            const dailyDefectsTableHtml = (data.defectosDiarios && Object.keys(data.defectosDiarios).length > 0) ? renderDailyDefectsTable(data) : '';


            // CAMBIO: DIVS para ApexCharts en lugar de CANVAS
            let dashboardHtml = `
            <div class="pdf-section">
                <h4><i class="fa-solid fa-chart-line"></i> ${translate('visual_dashboards')}</h4>

                <div class="dashboard-filter-bar">
                    <div class="form-group">
                        <label for="dashboard-fecha-inicio-sl" data-translate-key="start_date">Fecha de Inicio</label>
                        <input type="date" id="dashboard-fecha-inicio-sl">
                    </div>
                    <div class="form-group">
                        <label for="dashboard-fecha-fin-sl" data-translate-key="end_date">Fecha de Fin</label>
                        <input type="date" id="dashboard-fecha-fin-sl">
                    </div>
                    <button id="btn-filtrar-dashboard-sl" class="btn-primary"><i class="fa-solid fa-filter"></i> <span data-translate-key="dashboard_filter_apply">Filtrar Gráficas</span></button>
                    <button id="btn-limpiar-dashboard-sl" class="btn-secondary"><i class="fa-solid fa-times"></i> <span data-translate-key="dashboard_filter_clear">Limpiar Filtro</span></button>
                </div>

                <div class="charts-container">
                    <div class="chart-box">
                        <h5>${translate('pareto_chart_title')}</h5>
                        <div id="paretoChart"></div>
                    </div>
                    <div class="chart-box">
                        <h5>${translate('weekly_rejects_title')}</h5>
                        <div id="weeklyRejectsChart"></div>
                    </div>
                    <div class="chart-box">
                        <h5>${translate('accepted_vs_rejected_title')}</h5>
                        <div id="rejectionRateChart"></div>
                    </div>
                    <div class="chart-box">
                        <h5>${translate('daily_progress_title')}</h5>
                        <div id="dailyProgressChart"></div>
                    </div>
                    <div class="chart-box" style="grid-column: 1 / -1;">
                        <h5>${translate('ppm_trend_chart_title')}</h5>
                        <div id="ppmTrendChart"></div>
                    </div>
                </div>
            </div>
            `;

            const totalMinutos = parsearTiempoAMinutosJS(data.resumen.tiempoTotal);
            let piezasPorHora = '0.00';
            if (totalMinutos > 0) {
                const totalHoras = totalMinutos / 60;
                piezasPorHora = (data.resumen.inspeccionadas / totalHoras).toFixed(2);
            }

            let html = `
            <div class="report-title">${data.titulo}</div>
            <div class="report-subtitle">${translate('request_folio')}: SL-${data.folio.toString().padStart(4, '0')}</div>
            <div class="pdf-section">
                <h4><i class="fa-solid fa-circle-info"></i> ${translate('general_info')}</h4>
                <div class="info-section">${infoHtml}</div>
            </div>
            <div class="pdf-section">
                <h4><i class="fa-solid fa-chart-simple"></i> ${translate('period_summary')}</h4>
                <table class="summary-table">
                    <tbody>
                        <tr><td>${translate('inspected_pieces')}</td><td>${data.resumen.inspeccionadas}</td></tr>
                        <tr><td>${translate('accepted_pieces')}</td><td>${data.resumen.aceptadas}</td></tr>
                        <tr><td>${translate('rejected_pieces')}</td><td>${data.resumen.rechazadas}</td></tr>
                        <tr><td>${translate('reworked_pieces')}</td><td>${data.resumen.retrabajadas}</td></tr>
                        <tr><td><strong>${translate('total_inspection_time')}:</strong></td><td><strong>${data.resumen.tiempoTotal}</strong></td></tr>
                        <tr><td><strong>${translate('pieces_per_hour')}:</strong></td><td><strong>${piezasPorHora}</strong></td></tr>
                    </tbody>
                </table>
            </div>
            ${dailyDefectsTableHtml}
            ${desgloseHtml}
            ${defectosHtml}
            ${dashboardHtml}
            `;

            contenidoReporteDiv.innerHTML = html;
            reporteContainer.style.display = 'block';

            renderizarDashboards(data);

            document.getElementById('btn-filtrar-dashboard-sl').addEventListener('click', () => {
                const startDate = document.getElementById('dashboard-fecha-inicio-sl').value;
                const endDate = document.getElementById('dashboard-fecha-fin-sl').value;
                if(lastReportData){
                    filtrarYRenderizarDashboards(startDate, endDate);
                }
            });

            document.getElementById('btn-limpiar-dashboard-sl').addEventListener('click', () => {
                document.getElementById('dashboard-fecha-inicio-sl').value = '';
                document.getElementById('dashboard-fecha-fin-sl').value = '';
                if(lastReportData){
                    renderizarReporte(lastReportData);
                }
            });

            reporteContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setLanguage(getCurrentLanguage());
        }

        function filtrarYRenderizarDashboards(startDate, endDate) {
            if (!lastReportData) return;

            const filteredData = JSON.parse(JSON.stringify(lastReportData));
            let dailyAggregates = {};
            let defectAggregatesFiltered = {};
            let inspeccionadasTotal = 0;
            let aceptadasTotal = 0;
            let retrabajadasTotal = 0;
            let rechazadasTotal = 0;
            const rechazoSemanal = {};

            if (startDate && endDate) {
                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(endDate + 'T23:59:59');

                filteredData.desgloseDiario = lastReportData.desgloseDiario.filter(dia => {
                    const diaDate = new Date(dia.fecha + 'T00:00:00');
                    return diaDate >= start && diaDate <= end;
                });

                filteredData.defectosDiarios = {};
                Object.keys(lastReportData.defectosDiarios).forEach(dateKey => {
                    const diaDate = new Date(dateKey + 'T00:00:00');
                    if (diaDate >= start && diaDate <= end) {
                        filteredData.defectosDiarios[dateKey] = lastReportData.defectosDiarios[dateKey];
                    }
                });
            }

            if (filteredData.desgloseDiario.length === 0 && Object.keys(filteredData.defectosDiarios).length === 0) {
                Swal.fire(translate('missing_info'), 'No se encontraron datos en el rango de fechas seleccionado.', 'info');
                return;
            }

            // Recalcular datos
            filteredData.desgloseDiario.forEach(dia => {
                const diaRechazadas = dia.totales.inspeccionadas - dia.totales.aceptadas;
                inspeccionadasTotal += dia.totales.inspeccionadas;
                aceptadasTotal += dia.totales.aceptadas;
                rechazadasTotal += diaRechazadas;
                dia.entradas.forEach(entrada => {
                    retrabajadasTotal += (parseInt(entrada.PiezasRetrabajadas) || 0);
                });

                dailyAggregates[dia.fecha] = {
                    inspeccionadas: dia.totales.inspeccionadas,
                    rechazadas: diaRechazadas
                };

                const fecha = new Date(dia.fecha + 'T00:00:00');
                const year = fecha.getFullYear();
                const week = Math.ceil((((fecha - new Date(year, 0, 1)) / 86400000) + new Date(year, 0, 1).getDay() + 1) / 7);
                const weekKey = `${year}${String(week).padStart(2, '0')}`;
                if (!rechazoSemanal[weekKey]) rechazoSemanal[weekKey] = 0;
                rechazoSemanal[weekKey] += diaRechazadas;
            });

            Object.values(filteredData.defectosDiarios).forEach(dayData => {
                Object.entries(dayData.defectos).forEach(([defectName, qty]) => {
                    if (!defectAggregatesFiltered[defectName]) defectAggregatesFiltered[defectName] = { nombre: defectName, cantidad: 0 };
                    defectAggregatesFiltered[defectName].cantidad += qty;
                });
            });

            const defectosListaFiltrada = Object.values(defectAggregatesFiltered).sort((a, b) => b.cantidad - a.cantidad);
            const totalDefectosFiltrados = defectosListaFiltrada.reduce((sum, d) => sum + d.cantidad, 0);
            let acumuladoFiltrado = 0;
            filteredData.dashboardData.pareto = [];
            if (totalDefectosFiltrados > 0) {
                const top5Filtrado = defectosListaFiltrada.slice(0, 5);
                top5Filtrado.forEach(defecto => {
                    acumuladoFiltrado += defecto.cantidad;
                    filteredData.dashboardData.pareto.push({
                        defecto: defecto.nombre,
                        cantidad: defecto.cantidad,
                        porcentajeAcumulado: Math.round((acumuladoFiltrado / totalDefectosFiltrados) * 100)
                    });
                });
            }

            filteredData.resumen.inspeccionadas = inspeccionadasTotal;
            filteredData.resumen.aceptadas = aceptadasTotal;
            filteredData.resumen.rechazadas = rechazadasTotal;
            filteredData.resumen.retrabajadas = retrabajadasTotal;
            filteredData.resumen.ppmGlobal = (inspeccionadasTotal > 0) ? (rechazadasTotal / inspeccionadasTotal) * 1000000 : 0;

            filteredData.dashboardData.rechazadasPorSemana = Object.keys(rechazoSemanal).map(semana => ({
                semana: semana,
                rechazadas_semana: rechazoSemanal[semana]
            })).sort((a, b) => a.semana.localeCompare(b.semana));

            filteredData.dashboardData.dailyPPM = Object.keys(dailyAggregates).map(fecha => ({
                fecha: fecha,
                ppm: (dailyAggregates[fecha].inspeccionadas > 0) ? (dailyAggregates[fecha].rechazadas / dailyAggregates[fecha].inspeccionadas) * 1000000 : 0
            })).sort((a, b) => a.fecha.localeCompare(b.fecha));


            renderizarDashboards(filteredData);

            const newTableHtml = renderDailyDefectsTable(filteredData);
            const oldTableContainer = contenidoReporteDiv.querySelector('#defect-daily-breakdown-container');
            if (oldTableContainer) {
                oldTableContainer.outerHTML = newTableHtml;
            }

            setLanguage(getCurrentLanguage());
        }

        function renderizarDashboards(data) {
            // Destruir instancias de ApexCharts
            if (paretoChart) paretoChart.destroy();
            if (weeklyRejectsChart) weeklyRejectsChart.destroy();
            if (rejectionRateChart) rejectionRateChart.destroy();
            if (dailyProgressChart) dailyProgressChart.destroy();
            if (ppmTrendChart) ppmTrendChart.destroy();

            const dashboardData = data.dashboardData;
            const resumen = data.resumen;
            const currentLang = getCurrentLanguage();
            const dateLocale = currentLang === 'en' ? 'en-US' : 'es-MX';

            // --- PALETA DE COLORES ACTUALIZADA ---
            const colorPalette = {
                primary: '#003D70', // Azul Oscuro (Pedido por usuario)
                warning: '#E9E6DD', // Beige/Crema (Pedido por usuario - usado para líneas secundarias)
                success: '#69A032', // Verde (Pedido por usuario)
                danger: '#a83232',  // Rojo (Usando el rojo definido en CSS para rechazados)
                dark: '#263238'
            };
            const fontFamily = 'Montserrat, sans-serif';

            // --- 1. PARETO DE DEFECTOS (ApexCharts - Column + Line) ---
            if (dashboardData.pareto && dashboardData.pareto.length > 0) {
                const paretoLabels = dashboardData.pareto.map(item => item.defecto);
                const paretoCounts = dashboardData.pareto.map(item => item.cantidad);
                const paretoCumulative = dashboardData.pareto.map(item => item.porcentajeAcumulado);

                const paretoOptions = {
                    series: [{
                        name: translate('chart_qty'),
                        type: 'column',
                        data: paretoCounts
                    }, {
                        name: translate('chart_cumulative'),
                        type: 'line',
                        data: paretoCumulative
                    }],
                    chart: {
                        height: 350,
                        type: 'line',
                        fontFamily: fontFamily,
                        toolbar: { show: true },
                        animations: { enabled: false },
                        dropShadow: { enabled: true, top: 0, left: 0, blur: 3, opacity: 0.1 }
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '60%',
                            borderRadius: 4,
                            dataLabels: { position: 'top' }
                        }
                    },
                    stroke: {
                        width: [0, 4],
                        curve: 'smooth'
                    },
                    fill: {
                        type: ['gradient', 'solid'],
                        gradient: {
                            shade: 'light',
                            type: "vertical",
                            shadeIntensity: 0.5,
                            gradientToColors: [colorPalette.primary],
                            inverseColors: true,
                            opacityFrom: 0.9,
                            opacityTo: 0.9,
                            stops: [0, 100]
                        }
                    },
                    title: { text: undefined },
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [0, 1],
                        style: { fontSize: '11px', colors: ['#333', colorPalette.primary] }
                    },
                    labels: paretoLabels,
                    yaxis: [{
                        title: { text: translate('chart_qty'), style: { fontWeight: 600 } },
                    }, {
                        opposite: true,
                        title: { text: translate('chart_cumulative'), style: { fontWeight: 600 } },
                        max: 100
                    }],
                    colors: [colorPalette.primary, colorPalette.warning], // Azul barras, Beige línea
                    grid: { borderColor: '#f1f1f1' }
                };
                paretoChart = new ApexCharts(document.querySelector("#paretoChart"), paretoOptions);
                paretoChart.render();
            }


            // --- 2. RECHAZADAS POR SEMANA (ApexCharts - Area Gradient) ---
            if (dashboardData.rechazadasPorSemana && dashboardData.rechazadasPorSemana.length > 0) {
                const weeklyLabels = dashboardData.rechazadasPorSemana.map(item => `${translate('chart_week')} ${String(item.semana).substring(4)}`);
                const weeklyCounts = dashboardData.rechazadasPorSemana.map(item => item.rechazadas_semana);

                const weeklyOptions = {
                    series: [{
                        name: translate('rejected_pieces'),
                        data: weeklyCounts
                    }],
                    chart: {
                        height: 350,
                        type: 'area',
                        fontFamily: fontFamily,
                        zoom: { enabled: true },
                        toolbar: { show: true },
                        animations: { enabled: false },
                        dropShadow: { enabled: true, top: 2, left: 0, blur: 4, opacity: 0.15 }
                    },
                    dataLabels: { enabled: true, style: { colors: [colorPalette.danger] } },
                    stroke: { curve: 'smooth', width: 3 },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.2,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: { categories: weeklyLabels },
                    colors: [colorPalette.danger], // Usar ROJO para rechazadas
                    grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
                };
                weeklyRejectsChart = new ApexCharts(document.querySelector("#weeklyRejectsChart"), weeklyOptions);
                weeklyRejectsChart.render();
            }

            // --- 3. ACEPTADAS VS RECHAZADAS % (ApexCharts - Horizontal Stacked 100%) ---
            if (resumen && resumen.inspeccionadas > 0) {
                const porcentajeRechazadas = parseFloat(((resumen.rechazadas / resumen.inspeccionadas) * 100).toFixed(2));
                const porcentajeAceptadas = parseFloat(((resumen.aceptadas / resumen.inspeccionadas) * 100).toFixed(2));

                const rejectionOptions = {
                    series: [{
                        name: translate('accepted'),
                        data: [porcentajeAceptadas]
                    }, {
                        name: translate('rejected'),
                        data: [porcentajeRechazadas]
                    }],
                    chart: {
                        type: 'bar',
                        height: 250,
                        fontFamily: fontFamily,
                        stacked: true,
                        stackType: '100%',
                        toolbar: { show: true },
                        animations: { enabled: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 8,
                            barHeight: '60%',
                        },
                    },
                    fill: { opacity: 1 },
                    colors: [colorPalette.success, colorPalette.danger], // Verde vs Rojo
                    dataLabels: {
                        enabled: true,
                        style: { fontSize: '14px', fontWeight: 'bold', colors: ['#fff'] },
                        formatter: function (val) {
                            if(val < 5) return "";
                            return val.toFixed(2) + "%";
                        }
                    },
                    xaxis: {
                        categories: [translate('period_summary')],
                        labels: { show: false },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: { show: false },
                    legend: { position: 'top', horizontalAlign: 'center' },
                    grid: { show: false }
                };
                rejectionRateChart = new ApexCharts(document.querySelector("#rejectionRateChart"), rejectionOptions);
                rejectionRateChart.render();
            }


            // --- 4. PROGRESO DIARIO (ApexCharts - Stacked Column Chart) ---
            const dailyLabels = [];
            const dailyAccepted = [];
            const dailyRejected = [];

            (data.desgloseDiario || []).forEach(dia => {
                const fecha = new Date(dia.fecha + 'T00:00:00').toLocaleDateString(dateLocale, {month: 'short', day: 'numeric'});
                dailyLabels.push(fecha);
                dailyAccepted.push(dia.totales.aceptadas);
                dailyRejected.push(dia.totales.rechazadas);
            });

            if(dailyLabels.length > 0) {
                const dailyOptions = {
                    series: [{
                        name: translate('accepted'),
                        data: dailyAccepted
                    }, {
                        name: translate('rejected'),
                        data: dailyRejected
                    }],
                    chart: {
                        type: 'bar',
                        height: 350,
                        fontFamily: fontFamily,
                        stacked: true,
                        toolbar: { show: true },
                        zoom: { enabled: true }, // Habilitar Zoom explícitamente
                        animations: { enabled: false },
                        dropShadow: { enabled: true, top: 0, left: 0, blur: 3, opacity: 0.1 }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '60%',
                            borderRadius: 4,
                            dataLabels: {
                                total: {
                                    enabled: true,
                                    style: {
                                        fontSize: '13px',
                                        fontWeight: 900
                                    }
                                }
                            }
                        },
                    },
                    dataLabels: {
                        enabled: true,
                        style: { fontSize: '12px', fontWeight: 'bold' }
                    },
                    stroke: {
                        show: true,
                        width: 1,
                        colors: ['#fff']
                    },
                    xaxis: { categories: dailyLabels },
                    yaxis: {
                        title: { text: translate('chart_qty') },
                    },
                    fill: { opacity: 1 },
                    colors: [colorPalette.success, colorPalette.danger], // Verde vs Rojo
                    grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
                    tooltip: {
                        shared: true,
                        intersect: false
                    },
                    legend: { position: 'top' }
                };
                dailyProgressChart = new ApexCharts(document.querySelector("#dailyProgressChart"), dailyOptions);
                dailyProgressChart.render();
            }

            // --- 5. TENDENCIA PPM (ApexCharts - Line Chart) ---
            if (dashboardData.dailyPPM && dashboardData.dailyPPM.length > 0) {
                const ppmLabels = dashboardData.dailyPPM.map(item => new Date(item.fecha + 'T00:00:00').toLocaleDateString(dateLocale, {month: 'short', day: 'numeric'}));
                const ppmValues = dashboardData.dailyPPM.map(item => item.ppm);

                const ppmOptions = {
                    series: [{
                        name: translate('chart_ppm'),
                        data: ppmValues
                    }],
                    chart: {
                        height: 350,
                        type: 'line',
                        fontFamily: fontFamily,
                        zoom: { enabled: true },
                        toolbar: { show: true },
                        animations: { enabled: false },
                        dropShadow: { enabled: true, top: 2, left: 0, blur: 4, opacity: 0.15 }
                    },
                    dataLabels: {
                        enabled: true,
                        background: { enabled: true, borderRadius: 2 },
                        formatter: (value) => Math.round(value).toLocaleString()
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    markers: {
                        size: 5,
                        hover: { size: 7 }
                    },
                    xaxis: { categories: ppmLabels },
                    yaxis: {
                        labels: {
                            formatter: (value) => value.toLocaleString()
                        }
                    },
                    colors: [colorPalette.danger], // PPM alto es malo, usamos Rojo
                    grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
                };
                ppmTrendChart = new ApexCharts(document.querySelector("#ppmTrendChart"), ppmOptions);
                ppmTrendChart.render();
            }
        }

        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                setLanguage(btn.dataset.lang);
                if (reporteContainer.style.display === 'block' && lastReportData) {
                    renderizarReporte(lastReportData);
                }
            });
        });

        setLanguage(getCurrentLanguage());
    });
</script>
</body>
</html>