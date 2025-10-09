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

    <!-- Librerías para generar PDF -->
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
        }
        #contenido-reporte th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        #contenido-reporte .summary-table td:first-child {
            font-weight: bold;
            width: 200px;
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

    <!-- --- NUEVO: Selector de Solicitud --- -->
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
    <!-- --- FIN DEL SELECTOR --- -->

    <div class="dashboard-grid">

        <!-- Panel para Reportes Parciales -->
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

        <!-- Panel para Reporte Final -->
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

    <!-- Contenedor donde se mostrará el reporte generado -->
    <div id="reporte-generado-container">
        <div class="reporte-header">
            <h3><i class="fa-solid fa-eye"></i> Vista Previa del Reporte</h3>
            <button id="btn-descargar-pdf" class="btn-secondary"><i class="fa-solid fa-download"></i> Descargar PDF</button>
        </div>
        <div id="contenido-reporte">
            <!-- El contenido del reporte se inyectará aquí con JavaScript -->
        </div>
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

        const fetchReportData = (url) => {
            Swal.fire({
                title: 'Generando Reporte',
                text: 'Consultando la información...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

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
                    Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
                });
        };

        btnGenerarParcial.addEventListener('click', () => {
            const idSolicitud = solicitudSelector.value;
            const fechaInicio = document.getElementById('fecha-inicio').value;
            const fechaFin = document.getElementById('fecha-fin').value;

            if (!idSolicitud) {
                Swal.fire('Falta Información', 'Por favor, selecciona una solicitud.', 'warning');
                return;
            }
            if (!fechaInicio || !fechaFin) {
                Swal.fire('Campos incompletos', 'Por favor, selecciona una fecha de inicio y una fecha de fin.', 'warning');
                return;
            }

            const url = `dao/api_generar_reporte.php?tipo=parcial&idSolicitud=${idSolicitud}&inicio=${fechaInicio}&fin=${fechaFin}`;
            fetchReportData(url);
        });

        btnVerFinal.addEventListener('click', () => {
            const idSolicitud = solicitudSelector.value;
            if (!idSolicitud) {
                Swal.fire('Falta Información', 'Por favor, selecciona una solicitud.', 'warning');
                return;
            }

            const url = `dao/api_generar_reporte.php?tipo=final&idSolicitud=${idSolicitud}`;
            fetchReportData(url);
        });

        btnDescargarPdf.addEventListener('click', () => {
            Swal.fire({
                title: 'Generando PDF',
                text: 'Por favor, espera un momento...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const elementoReporte = document.getElementById('contenido-reporte');

            html2canvas(elementoReporte, { scale: 2, useCORS: true }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const canvasWidth = canvas.width;
                const canvasHeight = canvas.height;
                const ratio = canvasHeight / canvasWidth;
                const imgWidth = pdfWidth - 20;
                const imgHeight = imgWidth * ratio;
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save(`reporte-S${solicitudSelector.value.padStart(4, '0')}.pdf`);
                Swal.close();
            });
        });

        function renderizarReporte(data) {
            const isVarios = data.info.numerosParteLista && data.info.numerosParteLista.length > 0;

            let infoHtml = `
            <p><strong>No. de Parte:</strong> ${data.info.numeroParte}</p>
            <p><strong>Responsable:</strong> ${data.info.responsable}</p>
            <p><strong>Cantidad Total Solicitada:</strong> ${data.info.cantidadTotal}</p>
            <p><strong>Fecha de Emisión:</strong> ${new Date().toLocaleDateString('es-MX')}</p>
        `;
            if (isVarios) {
                infoHtml = `
                <p><strong>Proyecto:</strong> ${data.info.numeroParte}</p>
                <p><strong>Responsable:</strong> ${data.info.responsable}</p>
                <p><strong>Cantidad Total Solicitada:</strong> ${data.info.cantidadTotal}</p>
                <p><strong>Fecha de Emisión:</strong> ${new Date().toLocaleDateString('es-MX')}</p>
                <p style="grid-column: 1 / -1;"><strong>Partes Involucradas:</strong> ${data.info.numerosParteLista.join(', ')}</p>
            `;
            }

            let defectosHtml = `<h4><i class="fa-solid fa-magnifying-glass"></i> Detalle de Defectos Encontrados</h4>`;
            if (isVarios) {
                if (data.defectosPorParte && data.defectosPorParte.length > 0) {
                    data.defectosPorParte.forEach(grupo => {
                        defectosHtml += `
                        <h5 style="font-size: 16px; margin-top: 20px; color: #555;">Detalle para: <strong>${grupo.numeroParte}</strong></h5>
                        <table><thead><tr><th>Defecto</th><th>Cantidad</th><th>No. de Lote(s)</th></tr></thead><tbody>`;
                        grupo.defectos.forEach(defecto => {
                            defectosHtml += `<tr><td>${defecto.nombre}</td><td>${defecto.cantidad}</td><td>${defecto.lotes.join(', ') || 'N/A'}</td></tr>`;
                        });
                        defectosHtml += `</tbody></table>`;
                    });
                } else {
                    defectosHtml += `<p style="text-align:center;">No se encontraron defectos para los números de parte en este periodo.</p>`;
                }
            } else {
                defectosHtml += `<table><thead><tr><th>Defecto</th><th>Cantidad</th><th>No. de Lote(s)</th></tr></thead><tbody>`;
                if (data.defectos && data.defectos.length > 0) {
                    data.defectos.forEach(defecto => {
                        defectosHtml += `<tr><td>${defecto.nombre}</td><td>${defecto.cantidad}</td><td>${defecto.lotes.join(', ') || 'N/A'}</td></tr>`;
                    });
                } else {
                    defectosHtml += `<tr><td colspan="3" style="text-align:center;">No se encontraron defectos en este periodo.</td></tr>`;
                }
                defectosHtml += `</tbody></table>`;
            }

            let html = `
            <div class="report-title">${data.titulo}</div>
            <div class="report-subtitle">Folio de Solicitud: S-${data.folio.toString().padStart(4, '0')}</div>
            <h4><i class="fa-solid fa-circle-info"></i> Información General</h4>
            <div class="info-section">
                ${infoHtml}
            </div>
            <h4><i class="fa-solid fa-chart-simple"></i> Resumen de Inspección</h4>
            <table class="summary-table">
                <tbody>
                    <tr><td>Piezas Inspeccionadas</td><td>${data.resumen.inspeccionadas}</td></tr>
                    <tr><td>Piezas Aceptadas</td><td>${data.resumen.aceptadas}</td></tr>
                    <tr><td>Piezas Rechazadas</td><td>${data.resumen.rechazadas}</td></tr>
                    <tr><td>Piezas Retrabajadas</td><td>${data.resumen.retrabajadas}</td></tr>
                    <tr><td><strong>Tiempo Total de Inspección:</strong></td><td><strong>${data.resumen.tiempoTotal}</strong></td></tr>
                </tbody>
            </table>
            ${defectosHtml}
        `;

            contenidoReporteDiv.innerHTML = html;
            reporteContainer.style.display = 'block';
            reporteContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
</script>
</body>
</html>

