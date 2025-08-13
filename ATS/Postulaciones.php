<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/postulaciones.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
</head>
<body>

<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}
?>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="Administrador.php">Inicio</a>
            <a href="SAprobadas.php">S.Aprobadas</a>
            <a href="SeguimientoAdministrador.php">Seguimiento</a>
            <a href="cargaVacante.php">Carga de Vacantes</a>
            <a href="Postulaciones.php">Candidatos Postulados</a>

            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
                        <a href="#">Alertas de empleo</a>
                        <a href="HistorialUsuario.php">Historial de solicitudes</a>
                        <a href="#" id="logout">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Inicio de sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Postulaciones</h1>
    <img src="imagenes/demanda%20(1).png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <!-- 📊 TARJETAS DE ESTADÍSTICAS -->
    <div class="kpi-container">
        <div class="kpi-card azul">
            <h3>Total Postulaciones</h3>
            <p id="totalPostulaciones">0</p>
        </div>
        <div class="kpi-card verde">
            <h3>Aprobadas</h3>
            <p id="totalAprobadas">0</p>
        </div>
        <div class="kpi-card rojo">
            <h3>Rechazadas</h3>
            <p id="totalRechazadas">0</p>
        </div>
        <div class="kpi-card amarillo">
            <h3>Pendientes</h3>
            <p id="totalPendientes">0</p>
        </div>
    </div>

    <!-- 📈 GRÁFICO Y TIMELINE -->
    <div class="panel-analitico">
        <div class="grafico">
            <canvas id="estatusChart"></canvas>
        </div>
        <div class="timeline">
            <h3>Actividad Reciente</h3>
            <ul id="actividadLista">
                <!-- Se llena con JS -->
            </ul>
        </div>
    </div>

    <div class="contenido-blanco">
<!-- Tabla de Solicitudes -->
        <div class="content">
            <h2>Candidatos Postulados</h2>

            <!-- Contenedor de botones de exportación -->
            <div class="export-buttons">
                <button id="copyBtn" class="btn btn-secondary"><i class="fas fa-copy"></i> Copiar</button>
                <button id="excelBtn" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</button>
                <button id="pdfBtn" class="btn btn-danger"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
            <div class="table-container">
                <table id="solicitudesTable" class="display">
                    <thead>
                    <tr>
                        <th>IdPostulacion</th>
                        <th>Nombre Candidato</th>
                        <th>Nombre Vacante</th>
                        <th>Nombre Solicitante</th>
                        <th>Fecha de Postulacion</th>
                        <th>Estatus</th>
                        <th>Detalles</th>

                    </tr>
                    </thead>
                    <tfoot>
                    <tr style="display:none;">
                        <th>#</th>
                        <th>Nomina</th>
                        <th>Nombre</th>
                        <th>Pregunta</th>
                        <th>Pregunta</th>
                        <th>Pregunta</th>
                        <th>Pregunta</th>

                    </tr>
                    </tfoot>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<!-- Scripts -->
<script>
    const logoutLink = document.getElementById('logout');
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('dao/logout.php', { method: 'POST' })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'loginATS.php';
                    } else {
                        alert('Error al cerrar sesión. Inténtalo nuevamente.');
                    }
                })
                .catch(error => console.error('Error al cerrar sesión:', error));
        });
    }
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS (Asegúrate de incluirlo) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/SheetJS/0.17.1/xlsx.full.min.js"></script>
<!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- jsPDF AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<!-- SheetJS (para exportar a Excel) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.1/xlsx.full.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function obtenerClaseEstatus(nombreEstatus) {
        switch (String(nombreEstatus).toLowerCase()) {
            case 'recibido': return 'estatus-recibido';
            case 'aprobado': return 'estatus-aprobado';
            case 'rechazado': return 'estatus-rechazado';
            default: return 'estatus-default';
        }
    }

    // Helpers para KPIs/Gráfico/Timeline
    let estatusChart = null;

    function kpisDesdeArray(arr) {
        const total = arr.length;
        let aprobadas = 0, rechazadas = 0, pendientes = 0;
        arr.forEach(d => {
            const s = String(d.NombreEstatus || '').toLowerCase();
            if (s === 'aprobado') aprobadas++;
            else if (s === 'rechazado') rechazadas++;
            else pendientes++;
        });
        return { total, aprobadas, rechazadas, pendientes };
    }

    function pintarKpis({ total, aprobadas, rechazadas, pendientes }) {
        document.getElementById('totalPostulaciones').textContent = total;
        document.getElementById('totalAprobadas').textContent = aprobadas;
        document.getElementById('totalRechazadas').textContent = rechazadas;
        document.getElementById('totalPendientes').textContent = pendientes;
    }

    function actualizarGrafico({ aprobadas, rechazadas, pendientes }) {
        const ctx = document.getElementById('estatusChart');
        if (!ctx) return;
        if (!estatusChart) {
            estatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aprobadas', 'Rechazadas', 'Pendientes'],
                    datasets: [{
                        data: [aprobadas, rechazadas, pendientes],
                        backgroundColor: ['#28A745', '#DC3545', '#FFC107']
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        } else {
            estatusChart.data.datasets[0].data = [aprobadas, rechazadas, pendientes];
            estatusChart.update();
        }
    }

    function llenarTimeline(arr) {
        const lista = document.getElementById('actividadLista');
        if (!lista) return;
        lista.innerHTML = '';
        arr.slice(0, 5).forEach(item => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${item.NombreCandidato}</strong> - ${item.NombreEstatus} (${item.FechaPostulacion})`;
            lista.appendChild(li);
        });
    }

    $(document).ready(function () {
        var tabla = $('#solicitudesTable').DataTable({
            "responsive": true,
            "ajax": {
                "url": 'https://grammermx.com/AleTest/ATS/dao/daoPostulaciones.php',
                "dataSrc": "data"
            },
            "columns": [
                { "data": "IdPostulacion" },
                { "data": "NombreCandidato" },
                { "data": "TituloVacante" },
                { "data": "NombreSolicitante" },
                { "data": "FechaPostulacion" },
                {
                    "data": "NombreEstatus",
                    "render": function (data, type, row) {
                        const clase = obtenerClaseEstatus(data);
                        return `<span class="estatus-span ${clase}">${data}</span>`;
                    }
                },
                {
                    "data": null,
                    "render": function (data, type, row) {
                        return `
                            <a href="detallePostulacion.php?IdPostulacion=${row.IdPostulacion}" class="ver-detalles-btn">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>`;
                    }
                }
            ],
            "dom": 'lfrtip',
            "pageLength": 10,
            "language": {
                "search": "Buscar:",
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "loadingRecords": "Cargando...",
            "deferRender": true,
            "search": {
                "regex": true,
                "caseInsensitive": false
            }
        });

        // 🔹 Mantener la barra de búsqueda global funcionando
        $('.dataTables_filter input').on('keyup', function () {
            tabla.search(this.value).draw();
        });

        // 📌 KPIs/Gráfico/Timeline al terminar de cargar AJAX
        tabla.on('xhr.dt', function (e, settings, json) {
            if (!json || !json.data) return;
            const k = kpisDesdeArray(json.data);
            pintarKpis(k);
            actualizarGrafico(k);
            llenarTimeline(json.data);
        });

        // 📌 Actualizar KPIs/Gráfico con los datos filtrados/buscados
        tabla.on('draw.dt', function () {
            const datosFiltrados = tabla.rows({ search: 'applied' }).data().toArray();
            const k = kpisDesdeArray(datosFiltrados);
            pintarKpis(k);
            actualizarGrafico(k);
        });

        // Funcionalidad de botones
        $('#copyBtn').on('click', function () {
            let text = "";
            tabla.rows({ search: 'applied' }).every(function () {
                let data = this.data();
                text += Object.values(data).join("\t") + "\n";
            });

            navigator.clipboard.writeText(text)
                .then(function () { alert('Tabla copiada al portapapeles'); })
                .catch(err => console.error('Error al copiar:', err));
        });

        $('#pdfBtn').on('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.autoTable({ html: '#solicitudesTable' });
            doc.save('solicitudes.pdf');
        });

        $('#excelBtn').on('click', function () {
            const table = document.querySelector('#solicitudesTable');
            if (table) {
                const wb = XLSX.utils.table_to_book(table, { sheet: "Solicitudes" });
                XLSX.writeFile(wb, 'solicitudes.xlsx');
            } else {
                alert('No se encontró la tabla para exportar');
            }
        });

        // Evento para el botón "Ir a Seguimiento"
        $('#solicitudesTable tbody').on('click', '.go-to-page-btn', function () {
            window.location.href = 'SeguimientoAdministrador.php';
        });
    });
</script>
</body>
</html>

