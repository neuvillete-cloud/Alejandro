<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

// --- Cargar solicitudes activas para el selector ---
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();
// Buscamos solicitudes "En Proceso" (IdEstatus = 3) o las que ya están "Cerradas" (IdEstatus = 4)
$solicitudes_activas_query = $conex->query("SELECT IdSolicitud, NumeroParte FROM Solicitudes WHERE IdEstatus IN (3, 4) ORDER BY IdSolicitud DESC");
$conex->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Reportes - ARCA</title>
    <link rel="stylesheet" href="css/estilosT.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Librerías para Gráficas y PDF -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- *** NUEVO: Plugin para Data Labels en Chart.js *** -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <style>
        /* Estilos CSS (sin cambios) */
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
        }
        #contenido-reporte th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        #contenido-reporte .summary-table td:first-child {
            font-weight: bold;
            width: 200px;
        }
        #contenido-reporte .part-breakdown {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }
        #contenido-reporte .part-breakdown li {
            font-size: 12px;
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
        #btn-limpiar-dashboard {
            background-color: transparent;
            border: 1px solid var(--color-borde);
            color: #555;
        }
        #btn-limpiar-dashboard:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }
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
        <h1><i class="fa-solid fa-chart-pie"></i> <span data-translate-key="main_title">Dashboard de Reportes</span></h1>
    </div>

    <div class="form-container" style="margin-bottom: 30px;">
        <div class="form-group">
            <label for="solicitud-selector"><i class="fa-solid fa-folder-open"></i> <span data-translate-key="select_request">Seleccionar Solicitud de Contención</span></label>
            <select id="solicitud-selector">
                <option value="" data-translate-key="select_request_option">-- Elige una solicitud --</option>
                <?php while($solicitud = $solicitudes_activas_query->fetch_assoc()): ?>
                    <option value="<?php echo $solicitud['IdSolicitud']; ?>">
                        S-<?php echo str_pad($solicitud['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?> (<?php echo htmlspecialchars($solicitud['NumeroParte']); ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="form-container">
            <fieldset>
                <legend><i class="fa-solid fa-calendar-week"></i> <span data-translate-key="partial_report_title">Reportes Parciales</span></legend>
                <p data-translate-key="partial_report_desc">Genera un reporte de contención para un rango de fechas específico. Ideal para seguimientos semanales mientras el proceso está activo.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha-inicio" data-translate-key="start_date">Fecha de Inicio</label>
                        <input type="date" id="fecha-inicio" name="fecha-inicio">
                    </div>
                    <div class="form-group">
                        <label for="fecha-fin" data-translate-key="end_date">Fecha de Fin</label>
                        <input type="date" id="fecha-fin" name="fecha-fin">
                    </div>
                </div>
                <div class="form-actions">
                    <button id="btn-generar-parcial" class="btn-primary"><i class="fa-solid fa-file-waveform"></i> <span data-translate-key="generate_report_btn">Generar Reporte</span></button>
                </div>
            </fieldset>
        </div>
        <div class="form-container">
            <fieldset>
                <legend><i class="fa-solid fa-flag-checkered"></i> <span data-translate-key="final_report_title">Reporte Final de Contención</span></legend>
                <p data-translate-key="final_report_desc">Visualiza y descarga el reporte consolidado con todos los datos del proceso una vez que la contención ha sido finalizada.</p>
                <div class="form-actions" style="justify-content: center; text-align:center;">
                    <button id="btn-ver-final" class="btn-primary"><i class="fa-solid fa-file-pdf"></i> <span data-translate-key="view_final_report_btn">Ver Reporte Final</span></button>
                </div>
            </fieldset>
        </div>
    </div>

    <div id="reporte-generado-container">
        <div class="reporte-header">
            <h3><i class="fa-solid fa-eye"></i> <span data-translate-key="report_preview_title">Vista Previa del Reporte</span></h3>
            <button id="btn-descargar-pdf" class="btn-secondary"><i class="fa-solid fa-download"></i> <span data-translate-key="download_pdf_btn">Descargar PDF</span></button>
        </div>
        <div id="contenido-reporte"></div>
    </div>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // --- NUEVO: Registrar el plugin de datalabels globalmente ---
        Chart.register(ChartDataLabels);

        const solicitudSelector = document.getElementById('solicitud-selector');
        const btnGenerarParcial = document.getElementById('btn-generar-parcial');
        const btnVerFinal = document.getElementById('btn-ver-final');
        const btnDescargarPdf = document.getElementById('btn-descargar-pdf');
        const reporteContainer = document.getElementById('reporte-generado-container');
        const contenidoReporteDiv = document.getElementById('contenido-reporte');
        let paretoChartInstance, weeklyRejectsChartInstance, rejectionRateChartInstance, dailyProgressChartInstance = null;
        let lastReportData;

        // --- INICIO DE CÓDIGO AÑADIDO ---

        const campoFechaInicio = document.getElementById('fecha-inicio');

        /**
         * Busca la primera fecha de inspección para una solicitud y la pone en el campo de fecha de inicio.
         * @param {string} idSolicitud El ID de la solicitud seleccionada.
         */
        function fetchPrimeraFecha(idSolicitud) {
            // Mostramos un "cargando" visualmente deshabilitando el campo
            campoFechaInicio.disabled = true;
            campoFechaInicio.value = ''; // Limpiamos valor anterior

            fetch(`dao/api_get_primera_fecha.php?idSolicitud=${idSolicitud}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.primeraFecha) {
                        // ¡Éxito! Asignamos la fecha.
                        // El formato YYYY-MM-DD de MySQL es compatible con <input type="date">
                        campoFechaInicio.value = data.primeraFecha;
                    } else {
                        // No se encontró fecha o hubo un error, lo dejamos vacío
                        console.warn(data.message || 'No se encontró primera fecha.');
                        campoFechaInicio.value = '';
                    }
                })
                .catch(error => {
                    console.error('Error al obtener la primera fecha:', error);
                    campoFechaInicio.value = '';
                })
                .finally(() => {
                    // Volvemos a habilitar el campo
                    campoFechaInicio.disabled = false;
                });
        }

        // Añadimos el "oyente" de eventos al selector de solicitudes
        solicitudSelector.addEventListener('change', function() {
            const idSolicitudSeleccionada = this.value;

            if (idSolicitudSeleccionada) {
                // Si el usuario seleccionó una solicitud válida, buscamos su fecha
                fetchPrimeraFecha(idSolicitudSeleccionada);
            } else {
                // Si el usuario seleccionó "-- Elige una solicitud --", limpiamos el campo
                campoFechaInicio.value = '';
            }
        });

        // --- FIN DE CÓDIGO AÑADIDO ---


        const translations = {
            es: { welcome: "Bienvenido", logout: "Cerrar Sesión", main_title: "Dashboard de Reportes", select_request: "Seleccionar Solicitud de Contención", select_request_option: "-- Elige una solicitud --", partial_report_title: "Reportes Parciales", partial_report_desc: "Genera un reporte de contención para un rango de fechas específico. Ideal para seguimientos semanales mientras el proceso está activo.", start_date: "Fecha de Inicio", end_date: "Fecha de Fin", generate_report_btn: "Generar Reporte", final_report_title: "Reporte Final de Contención", final_report_desc: "Visualiza y descarga el reporte consolidado con todos los datos del proceso una vez que la contención ha sido finalizada.", view_final_report_btn: "Ver Reporte Final", report_preview_title: "Vista Previa del Reporte", download_pdf_btn: "Descargar PDF", missing_info: "Falta Información", select_request_warning: "Por favor, selecciona una solicitud.", incomplete_fields: "Campos incompletos", select_dates_warning: "Por favor, selecciona una fecha de inicio y una fecha de fin.", generating_pdf: "Generando PDF", please_wait: "Por favor, espera un momento...", generating_report_title: "Generando Reporte", generating_report_text: "Consultando la información...", error_title: "Error", error_message_default: "No se pudo generar el reporte.", connection_error_title: "Error de Conexión", connection_error_message: "No se pudo comunicar con el servidor.", request_folio: "Folio de Solicitud", general_info: "Información General", part_number: "No. de Parte", responsible: "Responsable", total_qty: "Cantidad Total Solicitada", issue_date: "Fecha de Emisión", project: "Proyecto", involved_parts: "Partes Involucradas", period_summary: "Resumen General del Periodo", inspected_pieces: "Piezas Inspeccionadas", accepted_pieces: "Piezas Aceptadas", rejected_pieces: "Piezas Rechazadas", reworked_pieces: "Piezas Retrabajadas", total_inspection_time: "Tiempo Total de Inspección", pieces_per_hour: "Rate (Piezas / Hora)", hourly_breakdown: "Desglose Hora por Hora", day_totals: "Totales del día", inspected: "Inspeccionadas", accepted: "Aceptadas", rejected: "Rechazadas", hour_by_hour: "Hora por Hora", shift: "Turno", inspector: "Inspector", part_breakdown: "Desglose por Parte", downtime: "Tiempo Muerto", no: "No", comments: "Comentarios", defects_summary: "Resumen de Defectos del Periodo", total_defects_for: "Total de defectos para", defect: "Defecto", total_qty_defect: "Cantidad Total", lot_numbers: "No. de Lote(s)", no_defects_found_parts: "No se encontraron defectos para los números de parte en este periodo.", no_defects_found_period: "No se encontraron defectos en este periodo.", visual_dashboards: "Dashboards Visuales", pareto_chart_title: "Pareto de Defectos (Top 5)", weekly_rejects_title: "Rechazadas por Semana", accepted_vs_rejected_title: "Aceptadas vs. Rechazadas", daily_progress_title: "Progreso Diario de Inspección", chart_qty: "Cantidad", chart_cumulative: "% Acumulado", chart_week: "Semana", dashboard_filter_apply: "Filtrar Gráficas", dashboard_filter_clear: "Limpiar Filtro" },
            en: { welcome: "Welcome", logout: "Logout", main_title: "Reports Dashboard", select_request: "Select Containment Request", select_request_option: "-- Choose a request --", partial_report_title: "Partial Reports", partial_report_desc: "Generate a containment report for a specific date range. Ideal for weekly follow-ups while the process is active.", start_date: "Start Date", end_date: "End Date", generate_report_btn: "Generate Report", final_report_title: "Final Containment Report", final_report_desc: "View and download the consolidated report with all process data once the containment has been finalized.", view_final_report_btn: "View Final Report", report_preview_title: "Report Preview", download_pdf_btn: "Download PDF", missing_info: "Missing Information", select_request_warning: "Please select a request.", incomplete_fields: "Incomplete Fields", select_dates_warning: "Please select a start and end date.", generating_pdf: "Generating PDF", please_wait: "Please wait a moment...", generating_report_title: "Generating Report", generating_report_text: "Querying information...", error_title: "Error", error_message_default: "Could not generate report.", connection_error_title: "Connection Error", connection_error_message: "Could not connect to the server.", request_folio: "Request Folio", general_info: "General Information", part_number: "Part Number", responsible: "Responsible", total_qty: "Total Quantity Requested", issue_date: "Issue Date", project: "Project", involved_parts: "Involved Parts", period_summary: "General Period Summary", inspected_pieces: "Inspected Pieces", accepted_pieces: "Accepted Pieces", rejected_pieces: "Rejected Pieces", reworked_pieces: "Reworked Pieces", total_inspection_time: "Total Inspection Time", pieces_per_hour: "Rate (Pieces / Hour)", hourly_breakdown: "Hour by Hour Breakdown", day_totals: "Day's Totals", inspected: "Inspected", accepted: "Accepted", rejected: "Rejected", hour_by_hour: "Hour by Hour", shift: "Shift", inspector: "Inspector", part_breakdown: "Part Breakdown", downtime: "Downtime", no: "No", comments: "Comments", defects_summary: "Defects Summary for the Period", total_defects_for: "Total defects for", defect: "Defecto", total_qty_defect: "Total Quantity", lot_numbers: "Lot Number(s)", no_defects_found_parts: "No defects found for the part numbers in this period.", no_defects_found_period: "No defects found in this period.", visual_dashboards: "Visual Dashboards", pareto_chart_title: "Defects Pareto (Top 5)", weekly_rejects_title: "Weekly Rejects", accepted_vs_rejected_title: "Accepted vs. Rejected", daily_progress_title: "Daily Inspection Progress", chart_qty: "Quantity", chart_cumulative: "Cumulative %", chart_week: "Week", dashboard_filter_apply: "Filter Charts", dashboard_filter_clear: "Clear Filter" }
        };

        function setLanguage(lang) {
            document.documentElement.lang = lang;
            localStorage.setItem('language', lang);
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.getAttribute('data-translate-key');
                if (translations[lang] && translations[lang][key]) {
                    el.innerText = translations[lang][key];
                }
            });
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.lang === lang));
        }

        function getCurrentLanguage() { return localStorage.getItem('language') || 'es'; }
        function translate(key) { const lang = getCurrentLanguage(); return translations[lang][key] || key; }

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
            fetch(url)
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
                    Swal.fire(translate('connection_error_title'), translate('connection_error_message'), 'error');
                });
        };

        btnGenerarParcial.addEventListener('click', () => {
            const idSolicitud = solicitudSelector.value;
            const fechaInicio = document.getElementById('fecha-inicio').value;
            const fechaFin = document.getElementById('fecha-fin').value;
            if (!idSolicitud) { Swal.fire(translate('missing_info'), translate('select_request_warning'), 'warning'); return; }
            if (!fechaInicio || !fechaFin) { Swal.fire(translate('incomplete_fields'), translate('select_dates_warning'), 'warning'); return; }
            const url = `dao/api_generar_reporte.php?tipo=parcial&idSolicitud=${idSolicitud}&inicio=${fechaInicio}&fin=${fechaFin}`;
            fetchReportData(url);
        });

        btnVerFinal.addEventListener('click', () => {
            const idSolicitud = solicitudSelector.value;
            if (!idSolicitud) { Swal.fire(translate('missing_info'), translate('select_request_warning'), 'warning'); return; }
            const url = `dao/api_generar_reporte.php?tipo=final&idSolicitud=${idSolicitud}`;
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

            const allElements = elementoReporte.querySelectorAll('.pdf-section, .report-title, .report-subtitle');

            for (const element of allElements) {
                const canvas = await html2canvas(element, { scale: 2, useCORS: true });
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

            pdf.save(`reporte-S${solicitudSelector.value.padStart(4, '0')}.pdf`);
            Swal.close();
        });


        function renderizarReporte(data) {
            const isVarios = strtolower(data.info.numeroParte) === 'varios';
            const currentLang = getCurrentLanguage();

            let infoHtml = `
            <p><strong>${translate('part_number')}:</strong> ${data.info.numeroParte}</p>
            <p><strong>${translate('responsible')}:</strong> ${data.info.responsable}</p>
            <p><strong>${translate('total_qty')}:</strong> ${data.info.cantidadTotal}</p>
            <p><strong>${translate('issue_date')}:</strong> ${new Date().toLocaleDateString(currentLang === 'en' ? 'en-US' : 'es-MX')}</p>
        `;
            if (isVarios && data.info.numerosParteLista && data.info.numerosParteLista.length > 0) {
                infoHtml = `
                <p><strong>${translate('project')}:</strong> ${data.info.numeroParte}</p>
                <p><strong>${translate('responsible')}:</strong> ${data.info.responsable}</p>
                <p><strong>${translate('total_qty')}:</strong> ${data.info.cantidadTotal}</p>
                <p><strong>${translate('issue_date')}:</strong> ${new Date().toLocaleDateString(currentLang === 'en' ? 'en-US' : 'es-MX')}</p>
                <p style="grid-column: 1 / -1;"><strong>${translate('involved_parts')}:</strong> ${data.info.numerosParteLista.join(', ')}</p>
            `;
            }

            let desgloseHtml = '';
            if (data.desgloseDiario && data.desgloseDiario.length > 0) {
                desgloseHtml = `<div class="pdf-section"><h4><i class="fa-solid fa-calendar-day"></i> ${translate('hourly_breakdown')}</h4></div>`;
                data.desgloseDiario.forEach(dia => {
                    const dateLocale = currentLang === 'en' ? 'en-US' : 'es-MX';
                    const fechaFormateada = new Date(dia.fecha + 'T00:00:00').toLocaleDateString(dateLocale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

                    let diaHtml = `<div class="pdf-section">`;
                    diaHtml += `<h5 style="font-size: 16px; margin-top: 20px; color: #555;">${fechaFormateada}</h5>`;

                    if (dia.totales) {
                        const rechazadasDia = dia.totales.inspeccionadas - dia.totales.aceptadas;
                        diaHtml += `<p>${translate('day_totals')}: <strong>${dia.totales.inspeccionadas}</strong> ${translate('inspected')}, <strong>${dia.totales.aceptadas}</strong> ${translate('accepted')}, <strong>${rechazadasDia}</strong> ${translate('rejected')}.</p>`;
                    }

                    if (isVarios) {
                        diaHtml += `<table><thead><tr><th>${translate('hour_by_hour')}</th><th>${translate('shift')}</th><th>${translate('inspector')}</th><th>${translate('inspected')}</th><th>${translate('part_breakdown')}</th><th>${translate('downtime')}</th><th>${translate('comments')}</th></tr></thead><tbody>`;
                        dia.entradas.forEach(entrada => {
                            let partesHtml = '<ul class="part-breakdown">';
                            if(entrada.partes && entrada.partes.length > 0) {
                                entrada.partes.forEach(p => { partesHtml += `<li><strong>${p.numeroParte}:</strong> ${p.cantidad} pzs</li>`; });
                            } else { partesHtml += `<li>N/A</li>`; }
                            partesHtml += '</ul>';
                            diaHtml += `<tr><td>${entrada.RangoHora || 'N/A'}</td><td>${entrada.turno || 'N/A'}</td><td>${entrada.NombreInspector || 'N/A'}</td><td>${entrada.PiezasInspeccionadas}</td><td>${partesHtml}</td><td>${entrada.RazonTiempoMuerto || translate('no')}</td><td>${entrada.Comentarios || ''}</td></tr>`;
                        });
                        diaHtml += `</tbody></table>`;
                    } else {
                        diaHtml += `<table><thead><tr><th>${translate('hour_by_hour')}</th><th>${translate('shift')}</th><th>${translate('inspector')}</th><th>${translate('inspected')}</th><th>${translate('accepted')}</th><th>${translate('rejected')}</th><th>${translate('downtime')}</th><th>${translate('comments')}</th></tr></thead><tbody>`;
                        dia.entradas.forEach(entrada => {
                            diaHtml += `<tr><td>${entrada.RangoHora || 'N/A'}</td><td>${entrada.turno || 'N/A'}</td><td>${entrada.NombreInspector || 'N/A'}</td><td>${entrada.PiezasInspeccionadas}</td><td>${entrada.PiezasAceptadas}</td><td>${parseInt(entrada.PiezasInspeccionadas) - parseInt(entrada.PiezasAceptadas)}</td><td>${entrada.RazonTiempoMuerto || translate('no')}</td><td>${entrada.Comentarios || ''}</td></tr>`;
                        });
                        diaHtml += `</tbody></table>`;
                    }
                    diaHtml += `</div>`;
                    desgloseHtml += diaHtml;
                });
            }

            let defectosHtml = `<div class="pdf-section"><h4><i class="fa-solid fa-magnifying-glass"></i> ${translate('defects_summary')}</h4>`;
            if (isVarios) {
                if (data.defectosPorParte && data.defectosPorParte.length > 0) {
                    data.defectosPorParte.forEach(grupo => {
                        defectosHtml += `<h5 style="font-size: 16px; margin-top: 20px; color: #555;">${translate('total_defects_for')}: <strong>${grupo.numeroParte}</strong></h5>`;
                        defectosHtml += `<table><thead><tr><th>${translate('defect')}</th><th>${translate('total_qty_defect')}</th><th>${translate('lot_numbers')}</th></tr></thead><tbody>`;
                        grupo.defectos.forEach(defecto => {
                            defectosHtml += `<tr><td>${defecto.nombre}</td><td>${defecto.cantidad}</td><td>${defecto.lotes.join(', ') || 'N/A'}</td></tr>`;
                        });
                        defectosHtml += `</tbody></table>`;
                    });
                } else { defectosHtml += `<p style="text-align:center;">${translate('no_defects_found_parts')}</p>`; }
            } else {
                defectosHtml += `<table><thead><tr><th>${translate('defect')}</th><th>${translate('total_qty_defect')}</th><th>${translate('lot_numbers')}</th></tr></thead><tbody>`;
                if (data.defectos && data.defectos.length > 0) {
                    data.defectos.forEach(defecto => {
                        defectosHtml += `<tr><td>${defecto.nombre}</td><td>${defecto.cantidad}</td><td>${defecto.lotes.join(', ') || 'N/A'}</td></tr>`;
                    });
                } else { defectosHtml += `<tr><td colspan="3" style="text-align:center;">${translate('no_defects_found_period')}</td></tr>`; }
                defectosHtml += `</tbody></table>`;
            }
            defectosHtml += `</div>`;

            let dashboardHtml = `
            <div class="pdf-section">
                <h4><i class="fa-solid fa-chart-line"></i> ${translate('visual_dashboards')}</h4>

                <div class="dashboard-filter-bar">
                    <div class="form-group">
                        <label for="dashboard-fecha-inicio" data-translate-key="start_date">Fecha de Inicio</label>
                        <input type="date" id="dashboard-fecha-inicio">
                    </div>
                    <div class="form-group">
                        <label for="dashboard-fecha-fin" data-translate-key="end_date">Fecha de Fin</label>
                        <input type="date" id="dashboard-fecha-fin">
                    </div>
                    <button id="btn-filtrar-dashboard" class="btn-primary"><i class="fa-solid fa-filter"></i> <span data-translate-key="dashboard_filter_apply">Filtrar Gráficas</span></button>
                    <button id="btn-limpiar-dashboard" class="btn-secondary"><i class="fa-solid fa-times"></i> <span data-translate-key="dashboard_filter_clear">Limpiar Filtro</span></button>
                </div>

                <div class="charts-container">
                    <div class="chart-box">
                        <h5>${translate('pareto_chart_title')}</h5>
                        <canvas id="paretoChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h5>${translate('weekly_rejects_title')}</h5>
                        <canvas id="weeklyRejectsChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h5>${translate('accepted_vs_rejected_title')}</h5>
                        <canvas id="rejectionRateChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h5>${translate('daily_progress_title')}</h5>
                        <canvas id="dailyProgressChart"></canvas>
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
            <div class="report-subtitle">${translate('request_folio')}: S-${data.folio.toString().padStart(4, '0')}</div>
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
            ${desgloseHtml}
            ${defectosHtml}
            ${dashboardHtml}
        `;

            contenidoReporteDiv.innerHTML = html;
            reporteContainer.style.display = 'block';

            renderizarDashboards(data);

            document.getElementById('btn-filtrar-dashboard').addEventListener('click', () => {
                const startDate = document.getElementById('dashboard-fecha-inicio').value;
                const endDate = document.getElementById('dashboard-fecha-fin').value;
                if(lastReportData){
                    filtrarYRenderizarDashboards(startDate, endDate);
                }
            });

            document.getElementById('btn-limpiar-dashboard').addEventListener('click', () => {
                document.getElementById('dashboard-fecha-inicio').value = '';
                document.getElementById('dashboard-fecha-fin').value = '';
                if(lastReportData){
                    renderizarDashboards(lastReportData);
                }
            });

            reporteContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function filtrarYRenderizarDashboards(startDate, endDate) {
            if (!lastReportData) return;

            const filteredData = JSON.parse(JSON.stringify(lastReportData));

            if (startDate && endDate) {
                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(endDate + 'T23:59:59');

                filteredData.desgloseDiario = lastReportData.desgloseDiario.filter(dia => {
                    const diaDate = new Date(dia.fecha + 'T00:00:00');
                    return diaDate >= start && diaDate <= end;
                });
            }

            if (filteredData.desgloseDiario.length === 0) {
                Swal.fire(translate('missing_info'), 'No se encontraron datos en el rango de fechas seleccionado para las gráficas.', 'info');
                return;
            }

            // --- RECALCULAR TODOS LOS DATOS PARA LOS DASHBOARDS ---

            // 1. Recalcular Resumen
            let inspeccionadas = 0, aceptadas = 0, retrabajadas = 0;
            filteredData.desgloseDiario.forEach(dia => {
                inspeccionadas += dia.totales.inspeccionadas;
                aceptadas += dia.totales.aceptadas;
                dia.entradas.forEach(entrada => {
                    retrabajadas += entrada.PiezasRetrabajadas;
                });
            });
            const rechazadas = inspeccionadas - aceptadas;
            filteredData.resumen = {
                ...filteredData.resumen,
                inspeccionadas,
                aceptadas,
                rechazadas,
                retrabajadas
            };

            // 2. Recalcular Datos de Defectos (para Pareto)
            const defectosMap = new Map();
            const allDefects = lastReportData.defectos || []; // Usar el agregado original

            // Crear un mapa de defectos con las cantidades filtradas
            filteredData.desgloseDiario.forEach(dia => {
                // Aquí necesitaríamos una forma de saber los defectos por día/entrada
                // Como no tenemos esa info, el pareto no se puede filtrar con precisión.
                // Se mostrará el pareto del periodo completo.
            });
            // Por ahora, el pareto usará los datos completos como fallback.
            filteredData.dashboardData.pareto = lastReportData.dashboardData.pareto;


            // 3. Recalcular Rechazadas por Semana
            const rechazoSemanal = {};
            filteredData.desgloseDiario.forEach(dia => {
                const fecha = new Date(dia.fecha + 'T00:00:00');
                const year = fecha.getFullYear();
                const week = Math.ceil((((fecha - new Date(year, 0, 1)) / 86400000) + new Date(year, 0, 1).getDay() + 1) / 7);
                const weekKey = `${year}${String(week).padStart(2, '0')}`;

                if (!rechazoSemanal[weekKey]) {
                    rechazoSemanal[weekKey] = 0;
                }
                rechazoSemanal[weekKey] += (dia.totales.inspeccionadas - dia.totales.aceptadas);
            });
            filteredData.dashboardData.rechazadasPorSemana = Object.keys(rechazoSemanal).map(semana => ({
                semana: semana,
                rechazadas_semana: rechazoSemanal[semana]
            })).sort((a, b) => a.semana.localeCompare(b.semana));


            renderizarDashboards(filteredData);
        }


        function renderizarDashboards(data) {
            if (paretoChartInstance) paretoChartInstance.destroy();
            if (weeklyRejectsChartInstance) weeklyRejectsChartInstance.destroy();
            if (rejectionRateChartInstance) rejectionRateChartInstance.destroy();
            if (dailyProgressChartInstance) dailyProgressChartInstance.destroy();

            const dashboardData = data.dashboardData;
            const resumen = data.resumen;

            const paretoCtx = document.getElementById('paretoChart').getContext('2d');
            if (dashboardData.pareto && dashboardData.pareto.length > 0) {
                const paretoLabels = dashboardData.pareto.map(item => item.defecto);
                const paretoCounts = dashboardData.pareto.map(item => item.cantidad);
                // const paretoCumulative = dashboardData.pareto.map(item => item.porcentajeAcumulado); // No se usa en horizontal
                paretoChartInstance = new Chart(paretoCtx, {
                    type: 'bar',
                    data: {
                        labels: paretoLabels,
                        datasets: [
                            { label: translate('chart_qty'), data: paretoCounts, backgroundColor: '#003D70' }
                            // Se quita la línea de % acumulado
                        ]
                    },
                    options: {
                        indexAxis: 'y', // <-- MODIFICACIÓN: Gráfica horizontal
                        animation: false, // <-- MODIFICACIÓN: Para PDF
                        responsive: true,
                        scales: {
                            x: { beginAtZero: true } // Eje X es ahora el de cantidad
                        },
                        plugins: {
                            datalabels: {
                                align: 'end',
                                anchor: 'end',
                                color: '#444',
                                font: { weight: 'bold', size: 10 },
                                formatter: (value) => value
                            }
                        }
                    }
                });
            }

            const weeklyCtx = document.getElementById('weeklyRejectsChart').getContext('2d');
            if (dashboardData.rechazadasPorSemana && dashboardData.rechazadasPorSemana.length > 0) {
                const weeklyLabels = dashboardData.rechazadasPorSemana.map(item => `${translate('chart_week')} ${String(item.semana).substring(4)}`);
                const weeklyCounts = dashboardData.rechazadasPorSemana.map(item => item.rechazadas_semana);
                weeklyRejectsChartInstance = new Chart(weeklyCtx, {
                    type: 'line',
                    data: { labels: weeklyLabels, datasets: [{ label: translate('rejected_pieces'), data: weeklyCounts, borderColor: '#003D70', backgroundColor: 'rgba(0, 61, 112, 0.2)', fill: true, tension: 0.1 }] },
                    options: {
                        animation: false, // <-- MODIFICACIÓN: Para PDF
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true },
                            x: { offset: true } // <-- MODIFICACIÓN: Añade espacio
                        },
                        plugins: {
                            datalabels: {
                                align: 'end',
                                anchor: 'end',
                                backgroundColor: 'rgba(0, 61, 112, 0.7)',
                                color: 'white',
                                borderRadius: 4,
                                font: { size: 10 },
                                formatter: (value) => value,
                                padding: 4
                            }
                        }
                    }
                });
            }

            // --- INICIO DE LA MODIFICACIÓN ---
            // Modificación del gráfico "Aceptadas vs Rechazadas" para mostrar porcentajes

            const rejectionCtx = document.getElementById('rejectionRateChart').getContext('2d');
            if (resumen && resumen.inspeccionadas > 0) {
                const rechazoTotal = resumen.rechazadas;
                const aceptadoTotal = resumen.aceptadas;
                const totalInspeccionadas = resumen.inspeccionadas; // Base para el 100%

                // 1. Calcular porcentajes
                const porcentajeRechazadas = parseFloat(((rechazoTotal / totalInspeccionadas) * 100).toFixed(2));
                const porcentajeAceptadas = parseFloat(((aceptadoTotal / totalInspeccionadas) * 100).toFixed(2));

                rejectionRateChartInstance = new Chart(rejectionCtx, {
                    type: 'bar',
                    data: {
                        labels: [''], // Sigue siendo una sola barra horizontal
                        datasets: [
                            {
                                label: translate('rejected'),
                                data: [porcentajeRechazadas], // <-- Usamos el porcentaje
                                backgroundColor: '#a83232'
                            },
                            {
                                label: translate('accepted'),
                                data: [porcentajeAceptadas], // <-- Usamos el porcentaje
                                backgroundColor: '#69A032'
                            }
                        ]
                    },
                    options: {
                        animation: false, // <-- MODIFICACIÓN: Para PDF
                        indexAxis: 'y', // Mantenemos la barra horizontal
                        responsive: true,
                        scales: {
                            x: {
                                stacked: true, // Mantenemos el apilado
                                min: 0,
                                max: 100, // <-- Eje X forzado de 0 a 100
                                ticks: {
                                    // Añadir '%' a las etiquetas del eje X
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            y: { stacked: true }
                        },
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    // Personalizar el tooltip para mostrar porcentaje
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.raw;
                                        // El valor ya es un porcentaje, solo formateamos
                                        return `${label}: ${value.toFixed(2)}%`;
                                    },
                                    // Ocultar el título del tooltip (que solo dice '')
                                    title: function(context) {
                                        return '';
                                    }
                                }
                            },
                            // --- NUEVO: Configuración de Datalabels ---
                            datalabels: {
                                color: 'white',
                                font: { weight: 'bold', size: 12 },
                                formatter: (value, context) => {
                                    if (value < 5) return '';
                                    return value.toFixed(2) + '%';
                                }
                            }
                        }
                    }
                });
            }
            // --- FIN DE LA MODIFICACIÓN ---


            const dailyCtx = document.getElementById('dailyProgressChart').getContext('2d');
            const dailyData = { labels: [], inspected: [], accepted: [], rejected: [] };
            (data.desgloseDiario || []).forEach(dia => {
                const dateLocale = getCurrentLanguage() === 'en' ? 'en-US' : 'es-MX';
                dailyData.labels.push(new Date(dia.fecha + 'T00:00:00').toLocaleDateString(dateLocale, {month: 'short', day: 'numeric'}));
                dailyData.inspected.push(dia.totales.inspeccionadas);
                dailyData.accepted.push(dia.totales.aceptadas);
                dailyData.rejected.push(dia.totales.inspeccionadas - dia.totales.aceptadas);
            });
            dailyProgressChartInstance = new Chart(dailyCtx, {
                type: 'bar',
                data: { labels: dailyData.labels, datasets: [ { label: translate('inspected'), data: dailyData.inspected, backgroundColor: '#E9E6DD' }, { label: translate('accepted'), data: dailyData.accepted, backgroundColor: '#69A032' }, { label: translate('rejected'), data: dailyData.rejected, backgroundColor: '#a83232' } ] },
                options: {
                    animation: false, // <-- MODIFICACIÓN: Para PDF
                    responsive: true,
                    scales: { y: { beginAtZero: true } },
                    // --- NUEVO: Configuración de Datalabels ---
                    plugins: {
                        datalabels: {
                            align: 'top',
                            anchor: 'end',
                            color: '#444',
                            font: { size: 9 },
                            rotation: -90,
                            offset: 8,
                            formatter: (value) => value,
                            display: (context) => context.dataset.data[context.dataIndex] > 0
                        }
                    }
                }
            });
        }

        const strtolower = (str) => String(str).toLowerCase();

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