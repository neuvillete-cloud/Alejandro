<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosHistoricos.css">
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
            <a href="#">Nueva Solicitud</a>
            <a href="seguimiento.php">Seguimiento</a>
            <a href="historicos.php">Historial de Solicitudes</a>
            <a href="seleccionFinal.php">Candidatos Finales</a>

            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
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
    <h1>Nueva Solicitud de Personal</h1>
    <img src="imagenes/solicitudes-de-empleo.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <!-- Tabla de Solicitudes -->
        <div class="content">
            <h2>Mis Solicitudes</h2>
            <div class="table-container">
                <table id="solicitudesTable" class="display">
                    <thead>
                    <tr>
                        <th>IdSolicitud</th>
                        <th>NumNomina</th>
                        <th>IdArea</th>
                        <th>Puesto</th>
                        <th>TipoContratacion</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr style="display:none;">
                        <th>#</th>
                        <th>Nomina</th>
                        <th>Nombre</th>
                        <th>Fecha</th>
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
    document.addEventListener("DOMContentLoaded", function () {

        // Cerrar sesión con fetch
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
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/SheetJS/0.17.1/xlsx.full.min.js"></script>
<!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- jsPDF AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

<!-- SheetJS (para exportar a Excel) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.1/xlsx.full.min.js"></script>

<script src="js/funcionamientoModal.js"></script>
<script>
    $(document).ready(function () {
        var tabla = $('#solicitudesTable').DataTable({
            "responsive": true,
            "ajax": {
                "url": 'https://grammermx.com/AleTest/ATS/dao/daoSoli.php',
                "dataSrc": "data"
            },
            "columns": [
                { "data": "IdSolicitud" },
                { "data": "NumNomina" },
                { "data": "IdArea" },
                { "data": "Puesto" },
                { "data": "TipoContratacion" },
                {
                    "data": null,
                    "defaultContent": '<button class="btn btn-secondary copy-btn"><i class="fas fa-copy"></i></button>' +
                        '<button class="btn btn-danger pdf-btn"><i class="fas fa-file-pdf"></i></button>' +
                        '<button class="btn btn-success excel-btn"><i class="fas fa-file-excel"></i></button>'
                }
            ],
            "initComplete": function () {
                this.api().columns().every(function () {
                    var that = this;
                    $('input', this.footer()).on('keyup change', function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                });
            },
            "dom": 'lfrtip', // Agrega la barra de búsqueda
            "pageLength": 5,
            lengthMenu: [
                [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 100, -1],
                [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 100, "All"]
            ],
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
            "buttons": [
                {
                    extend: 'copyHtml5',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    exportOptions: { columns: ':visible' },
                    className: 'btn btn-secondary'
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    exportOptions: { columns: ':visible' },
                    className: 'btn btn-success'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    exportOptions: { columns: ':visible' },
                    className: 'btn btn-danger',
                    orientation: 'landscape',
                    pageSize: 'LEGAL'
                }
            ],
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

        // 🔹 Solución para hacer funcionar la barra de búsqueda global correctamente
        $('.dataTables_filter input').on('keyup', function () {
            tabla.search(this.value).draw();
        });

        // Copiar tabla
        $('#solicitudesTable tbody').on('click', '.copy-btn', function () {
            const table = document.querySelector('#solicitudesTable');
            const range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            alert('Tabla copiada al portapapeles');
        });

        // Exportar a PDF
        $('#solicitudesTable tbody').on('click', '.pdf-btn', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.autoTable({ html: '#solicitudesTable' }); // Asegúrate de que autoTable esté disponible
            doc.save('solicitudes.pdf');
        });

        // Exportar a Excel
        $('#solicitudesTable tbody').on('click', '.excel-btn', function () {
            const table = document.querySelector('#solicitudesTable');
            const wb = XLSX.utils.table_to_book(table, { sheet: "Solicitudes" });
            XLSX.writeFile(wb, 'solicitudes.xlsx');
        });

        $('#sear').on('keyup', function () {
            tabla.search(this.value).draw();
        });

        $('.dataTables_filter input').on('keyup', function () {
            console.log("Valor de búsqueda:", this.value); // Verifica si el valor se captura correctamente
            tabla.search(this.value).draw();
        });

    });


</script>
</body>
</html>
