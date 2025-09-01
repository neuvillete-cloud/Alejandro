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

            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    Seguimiento de la vacante <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu-nav">
                    <a href="SAprobadas.php">Solicitudes Aprobadas</a>
                    <a href="SeguimientoAdministrador.php">Seguimiento de Postulantes</a>
                    <a href="cargaVacante.php">Cargar/Editar Vacantes</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    Progreso en los candidatos <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu-nav">
                    <a href="Postulaciones.php">Candidatos Postulados</a>
                    <a href="candidatoSeleccionado.php">Candidatos Seleccionados</a>
                </div>
            </div>
            <div class="nav-item dropdown">
                <a href="#" class="dropdown-toggle">
                    Dashboard <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu-nav">
                    <a href="EstadisticasVacantes.php">Panel de Vacantes</a>
                    <a href="dashbord.php">Dashboard de Reclutamiento</a>
                </div>
            </div>


            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfilUsuarios.php">Perfil</a>
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
                        window.location.href = 'login.php';
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
<footer class="main-footer">
    <div class="footer-container">

        <div class="footer-column">
            <div class="logo">
                <img src="imagenes/logo_blanco.png" alt="Logo Grammer Blanco" class="logo-img">
                <div class="logo-texto">
                    <h1>Grammer</h1>
                    <span>Automotive</span>
                </div>
            </div>
            <p class="footer-about">
                Sistema de Seguimiento de Candidatos (ATS) para la gestión de talento y requisiciones de personal.
            </p>
        </div>

        <div class="footer-column">
            <h3>Enlaces Rápidos</h3>
            <ul class="footer-links">
                <li><a href="Administrador.php">Inicio</a></li>
                <li><a href="SAprobadas.php">Solicitudes Aprobadas</a></li>
                <li><a href="SeguimientoAdministrador.php">Seguimiento</a></li>
                <li><a href="cargaVacante.php">Carga de Vacantes</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Contacto</h3>
            <p><i class="fas fa-map-marker-alt"></i> Av. de la Luz #24 Col. satélite , Querétaro, Mexico</p>
            <p><i class="fas fa-phone"></i> +52 (442) 238 4460</p>
            <div class="social-icons">
                <a href="https://www.google.com/url?sa=t&rct=j&q=&esrc=s&source=web&cd=&cad=rja&uact=8&ved=2ahUKEwiA6MqY0KaPAxUmlGoFHX01AXwQFnoECD0QAQ&url=https%3A%2F%2Fwww.facebook.com%2Fgrammermexico%2F%3Flocale%3Des_LA&usg=AOvVaw1Jg2xRElzuIF1PIZ6Ip_Ms&opi=89978449" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://mx.linkedin.com/company/grammer-automotive-puebla-s-a-de-c-v-" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/grammerqro/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>            </div>
        </div>

    </div>
    <div class="sub-footer">
        <p>&copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.</p>
        <p class="developer-credit">Desarrollado con <i class="fas fa-heart"></i> por Alejandro Torres Jimenez</p>
    </div>
</footer>
</body>
</html>

