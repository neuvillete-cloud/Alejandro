<?php
// Incluye el script que verifica si hay una sesión activa
// include_once("dao/verificar_sesion.php");
// if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }
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
        <span>Bienvenido, <?php /* echo htmlspecialchars($_SESSION['user_nombre']); */ ?> Usuario</span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-chart-pie"></i> Dashboard de Reportes</h1>
    </div>

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

        const btnGenerarParcial = document.getElementById('btn-generar-parcial');
        const btnVerFinal = document.getElementById('btn-ver-final');
        const btnDescargarPdf = document.getElementById('btn-descargar-pdf');
        const reporteContainer = document.getElementById('reporte-generado-container');
        const contenidoReporteDiv = document.getElementById('contenido-reporte');

        // Evento para generar el reporte parcial
        btnGenerarParcial.addEventListener('click', () => {
            const fechaInicio = document.getElementById('fecha-inicio').value;
            const fechaFin = document.getElementById('fecha-fin').value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire('Campos incompletos', 'Por favor, selecciona una fecha de inicio y una fecha de fin.', 'warning');
                return;
            }

            // --- SIMULACIÓN DE LLAMADA AL SERVIDOR (AJAX/Fetch) ---
            // En una aplicación real, aquí harías una llamada fetch a un script PHP
            // que te devuelva los datos de la base de datos en formato JSON.
            // fetch(`dao/api_generar_reporte.php?inicio=${fechaInicio}&fin=${fechaFin}`)
            //   .then(response => response.json())
            //   .then(data => { ... });

            console.log(`Generando reporte de ${fechaInicio} a ${fechaFin}`);
            const mockData = getMockDataParcial(); // Usamos datos de ejemplo
            renderizarReporte(mockData);
            // --- FIN DE LA SIMULACIÓN ---
        });

        // Evento para generar el reporte final
        btnVerFinal.addEventListener('click', () => {
            // --- SIMULACIÓN DE LLAMADA AL SERVIDOR ---
            console.log('Generando reporte final');
            const mockData = getMockDataFinal();
            renderizarReporte(mockData);
            // --- FIN DE LA SIMULACIÓN ---
        });

        // Evento para descargar el PDF
        btnDescargarPdf.addEventListener('click', () => {
            Swal.fire({
                title: 'Generando PDF',
                text: 'Por favor, espera un momento...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const elementoReporte = document.getElementById('contenido-reporte');

            html2canvas(elementoReporte, {
                scale: 2, // Mejora la calidad de la imagen
                useCORS: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;

                // A4: 210mm x 297mm
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4'
                });

                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                const canvasWidth = canvas.width;
                const canvasHeight = canvas.height;
                const ratio = canvasHeight / canvasWidth;

                const imgWidth = pdfWidth - 20; // Ancho con márgenes
                const imgHeight = imgWidth * ratio;

                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save('reporte-de-contencion.pdf');
                Swal.close();
            });
        });


        /**
         * Toma los datos (reales o simulados) y construye el HTML del reporte.
         * @param {object} data - Los datos del reporte.
         */
        function renderizarReporte(data) {
            let html = `
            <div class="report-title">${data.titulo}</div>
            <div class="report-subtitle">Folio de Solicitud: S-${data.folio.toString().padStart(4, '0')}</div>

            <h4><i class="fa-solid fa-circle-info"></i> Información General</h4>
            <div class="info-section">
                <p><strong>No. de Parte:</strong> ${data.info.numeroParte}</p>
                <p><strong>Responsable:</strong> ${data.info.responsable}</p>
                <p><strong>Cantidad Total Solicitada:</strong> ${data.info.cantidadTotal}</p>
                <p><strong>Fecha de Emisión:</strong> ${new Date().toLocaleDateString('es-MX')}</p>
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

            <h4><i class="fa-solid fa-magnifying-glass"></i> Detalle de Defectos Encontrados</h4>
            <table>
                <thead>
                    <tr>
                        <th>Defecto</th>
                        <th>Cantidad</th>
                        <th>No. de Lote(s)</th>
                    </tr>
                </thead>
                <tbody>
        `;
            data.defectos.forEach(defecto => {
                html += `
                <tr>
                    <td>${defecto.nombre}</td>
                    <td>${defecto.cantidad}</td>
                    <td>${defecto.lotes.join(', ')}</td>
                </tr>
            `;
            });
            html += `
                </tbody>
            </table>
        `;

            contenidoReporteDiv.innerHTML = html;
            reporteContainer.style.display = 'block';
            // Hacer scroll suave hacia el reporte
            reporteContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // --- FUNCIONES DE DATOS SIMULADOS ---
        function getMockDataParcial() {
            return {
                titulo: 'Reporte Parcial de Contención',
                folio: 123,
                info: {
                    numeroParte: 'NP-45B-881',
                    responsable: 'Juan Pérez',
                    cantidadTotal: 5000
                },
                resumen: {
                    inspeccionadas: 1250,
                    aceptadas: 1200,
                    rechazadas: 50,
                    retrabajadas: 15,
                    tiempoTotal: '15 horas'
                },
                defectos: [
                    { nombre: 'Rayadura en superficie', cantidad: 35, lotes: ['LOTE-A1', 'LOTE-A2'] },
                    { nombre: 'Componente mal ensamblado', cantidad: 15, lotes: ['LOTE-A2'] }
                ]
            };
        }

        function getMockDataFinal() {
            return {
                titulo: 'Reporte Final de Contención',
                folio: 123,
                info: {
                    numeroParte: 'NP-45B-881',
                    responsable: 'Juan Pérez',
                    cantidadTotal: 5000
                },
                resumen: {
                    inspeccionadas: 5000,
                    aceptadas: 4920,
                    rechazadas: 80,
                    retrabajadas: 45,
                    tiempoTotal: '62 horas'
                },
                defectos: [
                    { nombre: 'Rayadura en superficie', cantidad: 55, lotes: ['LOTE-A1', 'LOTE-A2', 'LOTE-B1'] },
                    { nombre: 'Componente mal ensamblado', cantidad: 25, lotes: ['LOTE-A2', 'LOTE-C1'] }
                ]
            };
        }
    });
</script>
</body>
</html>
