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
    <!-- Bootstrap CSS -->

    <link rel="stylesheet" href="css/estilosSeguimientoAdministrador.css">
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
        <li><a href="Administrador.php">Inicio</a></li>
        <li><a href="SAprobadas.php">S. Aprobadas</a></li>
        <li><a href="SeguimientoAdministrador.php">Seguimiento</a></li>
        <li><a href="cargaVacante.php">Carga de Vacantes</a></li>
        <li><a href="Postulaciones.php">Candidatos Postulados</a></li>
    </ul>
</nav>

<!-- Tabla de Solicitudes -->
<div class="content">
    <h2>Carga de Descripciones</h2>

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
                <th>Area</th>
                <th>Puesto</th>
                <th>Nombre</th>
                <th>FolioSolicitud</th>
                <th>Acciones</th> <!-- Nueva columna -->

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

<script src="js/funcionamientoModal.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        var tabla = $('#solicitudesTable').DataTable({
            "responsive": true,
            "ajax": {
                "url": 'https://grammermx.com/AleTest/ATS/dao/daoSolicitudesAprobadas.php',
                "dataSrc": "data"
            },
            "columns": [
                { "data": "IdSolicitud" },
                { "data": "NombreArea" },
                { "data": "Puesto" },
                { "data": "Nombre" },
                { "data": "FolioSolicitud" },
                {
                    "data": null,
                    "render": function (data, type, row) {
                        return `
                        <input type="file" class="form-control file-upload" data-id="${row.IdSolicitud}" accept=".pdf,.doc,.docx,.xls,.xlsx">
                        <button class="btn btn-primary btn-sm upload-btn" data-id="${row.IdSolicitud}">
                            <i class="fas fa-upload"></i> Subir
                        </button>
                    `;
                    }
                }
            ],
            "dom": 'lfrtip',
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

        // 🔍 Búsqueda global personalizada
        $('.dataTables_filter input').on('keyup', function () {
            tabla.search(this.value).draw();
        });

        // 📋 Copiar tabla al portapapeles
        $('#copyBtn').on('click', function () {
            let text = "";
            tabla.rows().every(function () {
                let data = this.data();
                text += Object.values(data).join("\t") + "\n";
            });

            navigator.clipboard.writeText(text).then(function () {
                alert('Tabla copiada al portapapeles');
            }).catch(err => console.error('Error al copiar:', err));
        });

        // 📄 Exportar a PDF
        $('#pdfBtn').on('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.autoTable({ html: '#solicitudesTable' });
            doc.save('solicitudes.pdf');
        });

        // 📊 Exportar a Excel
        $('#excelBtn').on('click', function () {
            const table = document.querySelector('#solicitudesTable');
            if (table) {
                const wb = XLSX.utils.table_to_book(table, { sheet: "Solicitudes" });
                XLSX.writeFile(wb, 'solicitudes.xlsx');
            } else {
                alert('No se encontró la tabla para exportar');
            }
        });

        // 📤 Subir archivo de descripción
        $('#solicitudesTable tbody').on('click', '.upload-btn', function () {
            let id = $(this).data('id');
            let fileInput = $(this).siblings('.file-upload')[0];

            if (fileInput.files.length === 0) {
                Swal.fire("Error", "Selecciona un archivo antes de subir", "warning");
                return;
            }

            let formData = new FormData();
            formData.append('documento', fileInput.files[0]);
            formData.append('idSolicitud', id);

            $.ajax({
                url: 'https://grammermx.com/AleTest/ATS/dao/daoSubirDescripciones.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    try {
                        let jsonResponse = typeof response === "object" ? response : JSON.parse(response);
                        if (jsonResponse.status === "success") {
                            Swal.fire("Éxito", "Archivo subido correctamente", "success");

                            // Reemplazar input y botón por botón "Subir Vacante"
                            const uploadButton = $(`button.upload-btn[data-id="${id}"]`);
                            const fileInput = uploadButton.siblings('.file-upload');

                            const subirVacanteBtn = $(`
                            <button class="btn btn-success btn-sm go-to-vacante" data-id="${id}">
                                <i class="fas fa-plus-circle"></i> Subir Vacante
                            </button>
                        `);

                            fileInput.remove();
                            uploadButton.replaceWith(subirVacanteBtn);
                        } else {
                            Swal.fire("Error", jsonResponse.message || "No se pudo subir el archivo", "error");
                        }
                    } catch (error) {
                        Swal.fire("Error", "Respuesta no válida del servidor", "error");
                    }
                },
                error: function () {
                    Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                }
            });
        });

        // 🔁 Redirigir a cargaVacante con el ID de la solicitud
        $('#solicitudesTable tbody').on('click', '.go-to-vacante', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: '¿Estás seguro?',
                text: `Vas a cargar la vacante con ID ${id}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `cargaVacante.php?idSolicitud=${id}`;
                }
            });
        });

    });

</script>
</body>
</html>
