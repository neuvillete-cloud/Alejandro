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
                <th>Estatus</th>
                <th>Acciones</th> <!-- Nueva columna -->

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

<div id="customEmailModal" class="custom-modal">
    <div class="custom-modal-content">
        <span class="close-modal">&times;</span>
        <h2>Enviar correos</h2>

        <label for="email1">Correo 1 (obligatorio):</label>
        <input type="email" id="email1" required>

        <label for="email2">Correo 2 (opcional):</label>
        <input type="email" id="email2">

        <label for="email3">Correo 3 (opcional):</label>
        <input type="email" id="email3">

        <button id="sendEmailsBtn">Enviar</button>
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
                "url": 'https://grammermx.com/AleTest/ATS/dao/daoAdmin.php',
                "dataSrc": "data"
            },
            "columns": [
                { "data": "IdSolicitud" },
                { "data": "NumNomina" },
                { "data": "NombreArea" },
                { "data": "Puesto" },
                { "data": "TipoContratacion" },
                { "data": "Nombre" },
                { "data": "NombreReemplazo" },
                { "data": "FechaSolicitud" },
                { "data": "FolioSolicitud" },
                { "data": "NombreEstatus" },
                {
                    "data": null,
                    "render": function (data, type, row) {
                        return `
                        <button class="btn btn-success btn-sm accept-btn" data-id="${row.IdSolicitud}">
                            <i class="fas fa-check"></i> Aceptar
                        </button>
                        <button class="btn btn-danger btn-sm reject-btn" data-id="${row.IdSolicitud}">
                            <i class="fas fa-times"></i> Rechazar
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

        // 🔹 Solución para hacer funcionar la barra de búsqueda global correctamente
        $('.dataTables_filter input').on('keyup', function () {
            tabla.search(this.value).draw();
        });

        // Funcionalidad de botones

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

        // Funcionalidad de botones
        $('#solicitudesTable tbody').on('click', '.accept-btn', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Aprobar la solicitud ID: ${id}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('https://grammermx.com/AleTest/ATS/dao/daoActualizarEstatus.php', { id: id, status: 2 })
                        .done(function (response) {
                            let jsonResponse;
                            try {
                                jsonResponse = typeof response === "object" ? response : JSON.parse(response);
                            } catch (error) {
                                Swal.fire("Error", "Respuesta no válida del servidor", "error");
                                return;
                            }

                            if (jsonResponse.success) {
                                Swal.fire("Aprobado", "Solicitud aprobada con éxito", "success").then(() => {
                                    let modal = document.getElementById('customEmailModal');
                                    if (modal) {
                                        modal.classList.add('show'); // Mostrar modal correctamente
                                        document.getElementById('sendEmailsBtn').setAttribute('data-id', id);
                                    } else {
                                        console.error("🔴 No se encontró el modal en el DOM");
                                    }
                                });
                            } else {
                                Swal.fire("Error", jsonResponse.message || "No se pudo aprobar la solicitud", "error");
                            }
                        })
                        .fail(function (jqXHR, textStatus, errorThrown) {
                            Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                        });
                }
            });
        });

        // Evento para enviar el correo
        document.getElementById('sendEmailsBtn').addEventListener('click', function () {
            let solicitudId = this.getAttribute('data-id');
            let email1 = document.getElementById('email1').value;
            let email2 = document.getElementById('email2').value;
            let email3 = document.getElementById('email3').value;

            if (!email1) {
                Swal.fire("Error", "El primer correo es obligatorio", "error");
                return;
            }

            // Petición AJAX para enviar el correo
            fetch('https://grammermx.com/AleTest/ATS/dao/daoEnviarCorreo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${solicitudId}&email1=${encodeURIComponent(email1)}&email2=${encodeURIComponent(email2)}&email3=${encodeURIComponent(email3)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire("Enviado", "El correo fue enviado correctamente", "success");
                        document.getElementById('customEmailModal').classList.remove('show'); // Ocultar modal correctamente
                    } else {
                        Swal.fire("Error", data.message || "No se pudo enviar el correo", "error");
                    }
                })
                .catch(error => {
                    console.error("❌ Error en la petición:", error);
                    Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                });
        });

        // Cerrar el modal al hacer clic en la 'X'
        document.querySelector('.close-modal').addEventListener('click', function () {
            document.getElementById('customEmailModal').classList.remove('show'); // Ocultar modal
        });

        // Cerrar el modal si se hace clic fuera del contenido
        window.onclick = function (event) {
            let modal = document.getElementById('customEmailModal');
            if (event.target === modal) {
                modal.classList.remove('show'); // Ocultar modal
            }
        };

        // Evento para botón Rechazar
        $('#solicitudesTable tbody').on('click', '.reject-btn', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Rechazar la solicitud ID: ${id}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, rechazar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('https://grammermx.com/AleTest/ATS/dao/updateStatus.php', { id: id, status: 3 })
                        .done(function (response) {
                            let jsonResponse = JSON.parse(response);
                            if (jsonResponse.success) {
                                Swal.fire("Rechazado", "Solicitud rechazada con éxito", "success");
                                tabla.ajax.reload();
                            } else {
                                Swal.fire("Error", "No se pudo rechazar la solicitud", "error");
                            }
                        });
                }
            });
        });
    });
</script>
</body>
</html>