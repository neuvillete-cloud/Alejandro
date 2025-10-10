<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

// --- NUEVO: Cargar solicitudes activas para el selector ---
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();
// Buscamos solicitudes "En Proceso" (IdEstatus = 3) o las que ya están "Cerradas" (IdEstatus = 4)
$solicitudes_activas_query = $conex->query("SELECT IdSolicitud, NumeroParte FROM Solicitudes WHERE IdEstatus IN (3, 4) ORDER BY IdSolicitud DESC");
$conex->close();
// --- FIN ---

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

    <style>
        /* Estilos adicionales específicos para la página de dashboard */
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
            display: none; /* Oculto por defecto */
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
            font-family: 'Arial', sans-serif; /* Usamos una fuente más estándar para el reporte */
            color: #333;
        }

        /* Contenedor para las gráficas */
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


        /* Estilos para el contenido del PDF */
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

        /* Sugerencia para evitar cortes en el PDF */
        .chart-box, .info-section, .summary-table, h4, h5, table {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
    <div class="user-info">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-chart-pie"></i> Dashboard de Reportes</h1>
    </div>

    <div class="form-container" style="margin-bottom: 30px;">
        <div class="form-group">
            <label for="solicitud-selector"><i class="fa-solid fa-folder-open"></i> Seleccionar Solicitud de Contención</label>
            <select id="solicitud-selector">
                <option value="">-- Elige una solicitud --</option>
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
                <legend><i class="fa-solid fa-calendar-week"></i> Reportes Parciales</legend>
                <p>Genera un reporte de contención para un rango de fechas específico. Ideal para seguimientos semanales mientras el proceso está activo.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha-inicio">Fecha de Inicio</label>
                        <input type="date" id="fecha-inicio" name="fecha-inicio">
                    </div>
                    <div class="form-group">
                        <label for="fecha-fin">Fecha de Fin</label>
                        <input type="date" id="fecha-fin" name="fecha-fin">
                    </div>
                </div>
                <div class="form-actions">
                    <button id="btn-generar-parcial" class="btn-primary"><i class="fa-solid fa-file-waveform"></i> Generar Reporte</button>
                </div>
            </fieldset>
        </div>
        <div class="form-container">
            <fieldset>
                <legend><i class="fa-solid fa-flag-checkered"></i> Reporte Final de Contención</legend>
                <p>Visualiza y descarga el reporte consolidado con todos los datos del proceso una vez que la contención ha sido finalizada.</p>
                <div class="form-actions" style="justify-content: center; text-align:center;">
                    <button id="btn-ver-final" class="btn-primary"><i class="fa-solid fa-file-pdf"></i> Ver Reporte Final</button>
                </div>
            </fieldset>
        </div>
    </div>

    <div id="reporte-generado-container">
        <div class="reporte-header">
            <h3><i class="fa-solid fa-eye"></i> Vista Previa del Reporte</h3>
            <button id="btn-descargar-pdf" class="btn-secondary"><i class="fa-solid fa-download"></i> Descargar PDF</button>
        </div>
        <div id="contenido-reporte"></div>
    </div>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const solicitudSelector = document.getElementById('solicitud-selector');
        const btnGenerarParcial = document.getElementById('btn-generar-parcial');
        const btnVerFinal = document.getElementById('btn-ver-final');
        const btnDescargarPdf = document.getElementById('btn-descargar-pdf');
        const reporteContainer = document.getElementById('reporte-generado-container');
        const contenidoReporteDiv = document.getElementById('contenido-reporte');
        let paretoChartInstance, weeklyRejectsChartInstance, rejectionRateChartInstance, dailyProgressChartInstance = null;

        const fetchReportData = (url) => {
            Swal.fire({ title: 'Generando Reporte', text: 'Consultando la información...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        renderizarReporte(data.reporte);
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo generar el reporte.', 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
                });
        };

        btnGenerarParcial.addEventListener('click', () => {
            const idSolicitud = solicitudSelector.value;
            const fechaInicio = document.getElementById('fecha-inicio').value;
            const fechaFin = document.getElementById('fecha-fin').value;
            if (!idSolicitud) { Swal.fire('Falta Información', 'Por favor, selecciona una solicitud.', 'warning'); return; }
            if (!fechaInicio || !fechaFin) { Swal.fire('Campos incompletos', 'Por favor, selecciona una fecha de inicio y una fecha de fin.', 'warning'); return; }
            const url = `dao/api_generar_reporte.php?tipo=parcial&idSolicitud=${idSolicitud}&inicio=${fechaInicio}&fin=${fechaFin}`;
            fetchReportData(url);
        });

        btnVerFinal.addEventListener('click', () => {
            const idSolicitud = solicitudSelector.value;
            if (!idSolicitud) { Swal.fire('Falta Información', 'Por favor, selecciona una solicitud.', 'warning'); return; }
            const url = `dao/api_generar_reporte.php?tipo=final&idSolicitud=${idSolicitud}`;
            fetchReportData(url);
        });

        btnDescargarPdf.addEventListener('click', () => {
            Swal.fire({ title: 'Generando PDF', text: 'Por favor, espera un momento...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            const elementoReporte = document.getElementById('contenido-reporte');

            html2canvas(elementoReporte, { scale: 2, useCORS: true }).then(canvas => {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

                const imgData = canvas.toDataURL('image/png');
                const imgProps = pdf.getImageProperties(imgData);

                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();

                const margin = 10;
                const contentWidth = pdfWidth - (margin * 2);
                const pageContentHeight = pdfHeight - (margin * 2);

                // La altura total de la imagen en el PDF, manteniendo la proporción
                const totalImgHeight = (imgProps.height * contentWidth) / imgProps.width;

                let heightLeft = totalImgHeight;
                let position = 0;

                // Añade la primera página
                pdf.addImage(imgData, 'PNG', margin, margin, contentWidth, totalImgHeight);
                heightLeft -= pageContentHeight;

                // Añade páginas subsecuentes si es necesario
                while (heightLeft > 0) {
                    position -= pageContentHeight; // Mueve la imagen "hacia arriba" en cada nueva página
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', margin, position + margin, contentWidth, totalImgHeight);
                    heightLeft -= pageContentHeight;
                }

                pdf.save(`reporte-S${solicitudSelector.value.padStart(4, '0')}.pdf`);
                Swal.close();
            });
        });

        function renderizarReporte(data) {
            const isVarios = strtolower(data.info.numeroParte) === 'varios';

            // --- SECCIÓN DE INFORMACIÓN GENERAL ---
            let infoHtml = `
            <p><strong>No. de Parte:</strong> ${data.info.numeroParte}</p>
            <p><strong>Responsable:</strong> ${data.info.responsable}</p>
            <p><strong>Cantidad Total Solicitada:</strong> ${data.info.cantidadTotal}</p>
            <p><strong>Fecha de Emisión:</strong> ${new Date().toLocaleDateString('es-MX')}</p>
        `;
            if (isVarios && data.info.numerosParteLista && data.info.numerosParteLista.length > 0) {
                infoHtml = `
                <p><strong>Proyecto:</strong> ${data.info.numeroParte}</p>
                <p><strong>Responsable:</strong> ${data.info.responsable}</p>
                <p><strong>Cantidad Total Solicitada:</strong> ${data.info.cantidadTotal}</p>
                <p><strong>Fecha de Emisión:</strong> ${new Date().toLocaleDateString('es-MX')}</p>
                <p style="grid-column: 1 / -1;"><strong>Partes Involucradas:</strong> ${data.info.numerosParteLista.join(', ')}</p>
            `;
            }

            // --- SECCIÓN DE DESGLOSE DIARIO Y HORA X HORA ---
            let desgloseHtml = '';
            if (data.desgloseDiario && data.desgloseDiario.length > 0) {
                desgloseHtml = `<h4><i class="fa-solid fa-calendar-day"></i> Desglose Hora por Hora</h4>`;
                data.desgloseDiario.forEach(dia => {
                    const fechaFormateada = new Date(dia.fecha + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    desgloseHtml += `<h5 style="font-size: 16px; margin-top: 20px; color: #555;">${fechaFormateada}</h5>`;

                    if (dia.totales) {
                        const rechazadasDia = dia.totales.inspeccionadas - dia.totales.aceptadas;
                        desgloseHtml += `<p>Totales del día: <strong>${dia.totales.inspeccionadas}</strong> Inspeccionadas, <strong>${dia.totales.aceptadas}</strong> Aceptadas, <strong>${rechazadasDia}</strong> Rechazadas.</p>`;
                    }

                    if (isVarios) {
                        desgloseHtml += `<table><thead><tr><th>Hora x Hora</th><th>Turno</th><th>Inspeccionadas</th><th>Desglose por Parte</th><th>Comentarios</th></tr></thead><tbody>`;
                        dia.entradas.forEach(entrada => {
                            let partesHtml = '<ul class="part-breakdown">';
                            if(entrada.partes && entrada.partes.length > 0) {
                                entrada.partes.forEach(p => { partesHtml += `<li><strong>${p.numeroParte}:</strong> ${p.cantidad} pzs</li>`; });
                            } else { partesHtml += `<li>N/A</li>`; }
                            partesHtml += '</ul>';
                            desgloseHtml += `<tr><td>${entrada.RangoHora || 'N/A'}</td><td>${entrada.turno || 'N/A'}</td><td>${entrada.PiezasInspeccionadas}</td><td>${partesHtml}</td><td>${entrada.Comentarios || ''}</td></tr>`;
                        });
                        desgloseHtml += `</tbody></table>`;
                    } else {
                        desgloseHtml += `<table><thead><tr><th>Hora x Hora</th><th>Turno</th><th>Inspeccionadas</th><th>Aceptadas</th><th>Rechazadas</th><th>Comentarios</th></tr></thead><tbody>`;
                        dia.entradas.forEach(entrada => {
                            desgloseHtml += `<tr><td>${entrada.RangoHora || 'N/A'}</td><td>${entrada.turno || 'N/A'}</td><td>${entrada.PiezasInspeccionadas}</td><td>${entrada.PiezasAceptadas}</td><td>${parseInt(entrada.PiezasInspeccionadas) - parseInt(entrada.PiezasAceptadas)}</td><td>${entrada.Comentarios || ''}</td></tr>`;
                        });
                        desgloseHtml += `</tbody></table>`;
                    }
                });
            }

            // --- SECCIÓN DE DETALLE DE DEFECTOS ---
            let defectosHtml = `<h4><i class="fa-solid fa-magnifying-glass"></i> Resumen de Defectos del Periodo</h4>`;
            if (isVarios) {
                if (data.defectosPorParte && data.defectosPorParte.length > 0) {
                    data.defectosPorParte.forEach(grupo => {
                        defectosHtml += `<h5 style="font-size: 16px; margin-top: 20px; color: #555;">Total de defectos para: <strong>${grupo.numeroParte}</strong></h5>`;
                        defectosHtml += `<table><thead><tr><th>Defecto</th><th>Cantidad Total</th><th>No. de Lote(s)</th></tr></thead><tbody>`;
                        grupo.defectos.forEach(defecto => {
                            defectosHtml += `<tr><td>${defecto.nombre}</td><td>${defecto.cantidad}</td><td>${defecto.lotes.join(', ') || 'N/A'}</td></tr>`;
                        });
                        defectosHtml += `</tbody></table>`;
                    });
                } else { defectosHtml += `<p style="text-align:center;">No se encontraron defectos para los números de parte en este periodo.</p>`; }
            } else {
                defectosHtml += `<table><thead><tr><th>Defecto</th><th>Cantidad Total</th><th>No. de Lote(s)</th></tr></thead><tbody>`;
                if (data.defectos && data.defectos.length > 0) {
                    data.defectos.forEach(defecto => {
                        defectosHtml += `<tr><td>${defecto.nombre}</td><td>${defecto.cantidad}</td><td>${defecto.lotes.join(', ') || 'N/A'}</td></tr>`;
                    });
                } else { defectosHtml += `<tr><td colspan="3" style="text-align:center;">No se encontraron defectos en este periodo.</td></tr>`; }
                defectosHtml += `</tbody></table>`;
            }

            // --- NUEVO: SECCIÓN DE GRÁFICAS ---
            let dashboardHtml = `
            <h4><i class="fa-solid fa-chart-line"></i> Dashboards Visuales</h4>
            <div class="charts-container">
                <div class="chart-box">
                    <h5>Pareto de Defectos (Top 5)</h5>
                    <canvas id="paretoChart"></canvas>
                </div>
                <div class="chart-box">
                    <h5>Rechazadas por Semana</h5>
                    <canvas id="weeklyRejectsChart"></canvas>
                </div>
                <div class="chart-box">
                    <h5>Aceptadas vs. Rechazadas</h5>
                    <canvas id="rejectionRateChart"></canvas>
                </div>
                <div class="chart-box">
                    <h5>Progreso Diario de Inspección</h5>
                    <canvas id="dailyProgressChart"></canvas>
                </div>
            </div>
        `;

            // --- CONSTRUCCIÓN DEL HTML FINAL ---
            let html = `
            <div class="report-title">${data.titulo}</div>
            <div class="report-subtitle">Folio de Solicitud: S-${data.folio.toString().padStart(4, '0')}</div>
            <h4><i class="fa-solid fa-circle-info"></i> Información General</h4>
            <div class="info-section">${infoHtml}</div>
            <h4><i class="fa-solid fa-chart-simple"></i> Resumen General del Periodo</h4>
            <table class="summary-table">
                <tbody>
                    <tr><td>Piezas Inspeccionadas</td><td>${data.resumen.inspeccionadas}</td></tr>
                    <tr><td>Piezas Aceptadas</td><td>${data.resumen.aceptadas}</td></tr>
                    <tr><td>Piezas Rechazadas</td><td>${data.resumen.rechazadas}</td></tr>
                    <tr><td>Piezas Retrabajadas</td><td>${data.resumen.retrabajadas}</td></tr>
                    <tr><td><strong>Tiempo Total de Inspección:</strong></td><td><strong>${data.resumen.tiempoTotal}</strong></td></tr>
                </tbody>
            </table>
            ${desgloseHtml}
            ${defectosHtml}
            ${dashboardHtml}
        `;

            contenidoReporteDiv.innerHTML = html;
            reporteContainer.style.display = 'block';

            renderizarDashboards(data);

            reporteContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function renderizarDashboards(data) {
            if (paretoChartInstance) paretoChartInstance.destroy();
            if (weeklyRejectsChartInstance) weeklyRejectsChartInstance.destroy();
            if (rejectionRateChartInstance) rejectionRateChartInstance.destroy();
            if (dailyProgressChartInstance) dailyProgressChartInstance.destroy();

            const dashboardData = data.dashboardData;
            const resumen = data.resumen;

            // --- PARETO CHART ---
            const paretoCtx = document.getElementById('paretoChart').getContext('2d');
            if (dashboardData.pareto && dashboardData.pareto.length > 0) {
                const paretoLabels = dashboardData.pareto.map(item => item.defecto);
                const paretoCounts = dashboardData.pareto.map(item => item.cantidad);
                const paretoCumulative = dashboardData.pareto.map(item => item.porcentajeAcumulado);
                paretoChartInstance = new Chart(paretoCtx, {
                    type: 'bar',
                    data: {
                        labels: paretoLabels,
                        datasets: [
                            { label: 'Cantidad', data: paretoCounts, backgroundColor: '#5c85ad', yAxisID: 'y' },
                            { label: '% Acumulado', data: paretoCumulative, type: 'line', borderColor: '#a83232', yAxisID: 'y1' }
                        ]
                    },
                    options: { responsive: true, interaction: { mode: 'index', intersect: false }, scales: { y: { type: 'linear', display: true, position: 'left', beginAtZero: true }, y1: { type: 'linear', display: true, position: 'right', min: 0, max: 100, ticks: { callback: value => value + "%" } } } }
                });
            }

            // --- WEEKLY REJECTS CHART ---
            const weeklyCtx = document.getElementById('weeklyRejectsChart').getContext('2d');
            if (dashboardData.rechazadasPorSemana && dashboardData.rechazadasPorSemana.length > 0) {
                const weeklyLabels = dashboardData.rechazadasPorSemana.map(item => `Semana ${String(item.semana).substring(4)}`);
                const weeklyCounts = dashboardData.rechazadasPorSemana.map(item => item.rechazadas_semana);
                weeklyRejectsChartInstance = new Chart(weeklyCtx, {
                    type: 'line',
                    data: { labels: weeklyLabels, datasets: [{ label: 'Piezas Rechazadas', data: weeklyCounts, borderColor: '#4a6984', backgroundColor: 'rgba(74, 105, 132, 0.2)', fill: true, tension: 0.1 }] },
                    options: { responsive: true, scales: { y: { beginAtZero: true } } }
                });
            }

            // --- REJECTION RATE BAR CHART (HORIZONTAL) ---
            const rejectionCtx = document.getElementById('rejectionRateChart').getContext('2d');
            if (resumen && resumen.inspeccionadas > 0) {
                const rechazoTotal = resumen.rechazadas;
                const aceptadoTotal = resumen.inspeccionadas - rechazoTotal;
                rejectionRateChartInstance = new Chart(rejectionCtx, {
                    type: 'bar',
                    data: {
                        labels: [''],
                        datasets: [
                            { label: 'Rechazadas', data: [rechazoTotal], backgroundColor: '#a83232' },
                            { label: 'Aceptadas', data: [aceptadoTotal], backgroundColor: '#28a745' }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        scales: { x: { stacked: true }, y: { stacked: true } },
                        plugins: { legend: { position: 'top' } }
                    }
                });
            }

            // --- DAILY PROGRESS CHART ---
            const dailyCtx = document.getElementById('dailyProgressChart').getContext('2d');
            const dailyData = { labels: [], inspected: [], accepted: [], rejected: [] };
            (data.desgloseDiario || []).forEach(dia => {
                dailyData.labels.push(new Date(dia.fecha + 'T00:00:00').toLocaleDateString('es-MX', {month: 'short', day: 'numeric'}));
                dailyData.inspected.push(dia.totales.inspeccionadas);
                dailyData.accepted.push(dia.totales.aceptadas);
                dailyData.rejected.push(dia.totales.inspeccionadas - dia.totales.aceptadas);
            });
            dailyProgressChartInstance = new Chart(dailyCtx, {
                type: 'bar',
                data: { labels: dailyData.labels, datasets: [ { label: 'Inspeccionadas', data: dailyData.inspected, backgroundColor: '#8ab4d7' }, { label: 'Aceptadas', data: dailyData.accepted, backgroundColor: '#28a745' }, { label: 'Rechazadas', data: dailyData.rejected, backgroundColor: '#a83232' } ] },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        }

        const strtolower = (str) => String(str).toLowerCase();
    });
</script>
</body>
</html>

