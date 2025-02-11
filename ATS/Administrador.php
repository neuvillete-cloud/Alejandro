<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador</title>
    <link rel="stylesheet" href="css/estilosAdministrador.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <img src="imagenes/grammer.png" alt="Icono de Solicitudes" class="header-icon">
        <h1>R.H Admin</h1>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
    <div class="header-right">
        <div class="user-profile" id="profilePic">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario">
        </div>
        <div class="user-name" id="userNameHeader"></div>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="#">Ver Perfil</a>
            <a href="#" id="logout">Cerrar Sesión</a>
        </div>
    </div>
</header>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <ul>
        <li><a href="Solicitante.php">Inicio</a></li>
        <li><a href="seguimiento.php">Seguimiento</a></li>
        <li><a href="historicos.php" id="historicosLink">Históricos</a></li>
        <li><a href="configuraciones.php">Configuraciones</a></li>
    </ul>
</nav>

<!-- Tabla de Solicitudes -->
<div class="content">
    <h2>Solicitudes</h2>

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
                <th>IdSolicitud</th>
                <th>NumNomina</th>
                <th>IdArea</th>
                <th>Puesto</th>
                <th>TipoContratacion</th>
                <th>Nombre</th>
                <th>NombreReemplazo</th>
                <th>FechaSolicitud</th>
                <th>FolioSolicitud</th>

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


<!-- Modal Perfil -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Perfil del Usuario</h2>
        <div class="modal-body">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario" class="user-photo">
            <p><strong>Nombre:</strong> <span id="userName"></span></p>
            <p><strong>Número de Nómina:</strong> <span id="userNumNomina"></span></p>
            <p><strong>Área:</strong> <span id="userArea"></span></p>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {

        // Menú lateral (sidebar)
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Menú de perfil
        const userProfile = document.getElementById('profilePic');
        const profileDropdown = document.getElementById('profileDropdown');

        if (userProfile && profileDropdown) {
            userProfile.addEventListener('click', () => {
                profileDropdown.classList.toggle('active');
            });

            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target) && !userProfile.contains(e.target)) {
                    profileDropdown.classList.remove('active');
                }
            });
        }

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
<script src="js/jquery.dataTables.min.js"></script>
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
                "url": 'https://grammermx.com/AleTest/ATS/dao/daoAdmin.php',
                "dataSrc": "data"
            },
            "columns": [
                { "data": "IdSolicitud" },
                { "data": "NumNomina" },
                { "data": "IdArea" },
                { "data": "Puesto" },
                { "data": "TipoContratacion" },
                { "data": "Nombre" },
                { "data": "NombreReemplazo" },
                { "data": "FechaSolicitud" },
                { "data": "FolioSolicitud" }

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
            "pageLength": 2,
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

        // 🔹 Solución para hacer funcionar la barra de búsqueda global correctamente
        $('.dataTables_filter input').on('keyup', function () {
            tabla.search(this.value).draw();
        });

        // Funcionalidad de botones
        $('#copyBtn').on('click', function () {
            let text = "";
            tabla.rows().data().each(function (value) {
                text += value.join("\t") + "\n";
            });

            navigator.clipboard.writeText(text).then(function () {
                alert('Tabla copiada al portapapeles');
            });
        });

        $('#pdfBtn').on('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.autoTable({ html: '#solicitudesTable' });
            doc.save('solicitudes.pdf');
        });

        $('#excelBtn').on('click', function () {
            const wb = XLSX.utils.table_to_book(document.querySelector('#solicitudesTable'), { sheet: "Solicitudes" });
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