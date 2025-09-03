<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes Aprobadas | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosSeguimientoAdministrador.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

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
    <h1>Descripciones</h1>
    <img src="imagenes/apoyo.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <div class="controles-pagina">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Buscar por Puesto, Área, Solicitante...">
            </div>
            <div class="export-buttons">
                <button id="copyBtn" class="btn btn-secondary"><i class="fas fa-copy"></i> Copiar</button>
                <button id="excelBtn" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</button>
                <button id="pdfBtn" class="btn btn-danger"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>

        <div id="cards-container" class="cards-grid">
        </div>

    </div>
</section>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.1/xlsx.full.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cardsContainer = document.getElementById('cards-container');
        const searchInput = document.getElementById('search-input');
        let todasLasSolicitudes = [];

        function cargarDatos() {
            cardsContainer.innerHTML = '<p>Cargando solicitudes...</p>';
            fetch('dao/daoSolicitudesAprobadas.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        cardsContainer.innerHTML = `<p style="color: red;">Error del servidor: ${data.error}</p>`;
                        return;
                    }
                    todasLasSolicitudes = data.data || [];
                    renderizarCards(todasLasSolicitudes);
                })
                .catch(error => {
                    cardsContainer.innerHTML = '<p>Error de conexión al cargar los datos. Revisa la consola para más detalles.</p>';
                    console.error('Fetch Error:', error);
                });
        }

        function renderizarCards(solicitudes) {
            cardsContainer.innerHTML = '';
            if (solicitudes.length === 0) {
                cardsContainer.innerHTML = '<h4>No hay solicitudes en este momento.</h4>';
                return;
            }

            solicitudes.forEach(solicitud => {
                const card = document.createElement('div');
                card.className = 'solicitud-card';

                let actionsHTML = '';
                let estatusHTML = '';

                switch (parseInt(solicitud.IdEstatus, 10)) {
                    case 2: // 'Aprobada': El admin debe subir la descripción
                        estatusHTML = '<span class="estatus-tag estatus-accion">Acción Requerida</span>';
                        actionsHTML = `
                        <div class="file-upload-wrapper">
                            <input type="file" class="file-upload-hidden" id="file-${solicitud.IdSolicitud}" accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <label for="file-${solicitud.IdSolicitud}" class="btn file-upload-label">
                                <i class="fas fa-paperclip"></i> Seleccionar Archivo
                            </label>
                            <span class="file-name-display">Ningún archivo</span>
                        </div>
                        <button class="btn btn-primary upload-btn">
                            <i class="fas fa-upload"></i> Subir para Aprobar
                        </button>
                    `;
                        break;
                    case 12: // 'Pendiente Aprobación Descripción'
                        estatusHTML = '<span class="estatus-tag estatus-espera">En Espera</span>';
                        actionsHTML = `<div class="status-text"><i class="fas fa-clock"></i> Esperando aprobación del solicitante...</div>`;
                        break;
                    case 13: // 'Descripción Aprobada'
                        estatusHTML = '<span class="estatus-tag estatus-aprobado">Listo para Continuar</span>';
                        actionsHTML = `
                        <button class="btn btn-success go-to-vacante">
                            <i class="fas fa-plus-circle"></i> Crear Vacante
                        </button>
                    `;
                        break;
                    default:
                        actionsHTML = `<p>Estatus desconocido (${solicitud.IdEstatus}).</p>`;
                }

                card.innerHTML = `
                <div class="card-header">
                    <div>
                        <h3>${solicitud.Puesto}</h3>
                        <p>Folio: ${solicitud.FolioSolicitud}</p>
                    </div>
                    ${estatusHTML}
                </div>
                <div class="card-body">
                    <div class="info-item"><strong>Solicitante:</strong> <span>${solicitud.Nombre}</span></div>
                    <div class="info-item"><strong>Área:</strong> <span>${solicitud.NombreArea}</span></div>
                    <div class="info-item"><strong>ID Solicitud:</strong> <span>${solicitud.IdSolicitud}</span></div>
                </div>
                <div class="card-actions" data-id="${solicitud.IdSolicitud}">
                    ${actionsHTML}
                </div>
            `;
                cardsContainer.appendChild(card);
            });
        }

        searchInput.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            const solicitudesFiltradas = todasLasSolicitudes.filter(s => {
                return (s.Puesto && s.Puesto.toLowerCase().includes(termino)) ||
                    (s.NombreArea && s.NombreArea.toLowerCase().includes(termino)) ||
                    (s.Nombre && s.Nombre.toLowerCase().includes(termino)) ||
                    (s.FolioSolicitud && s.FolioSolicitud.toLowerCase().includes(termino));
            });
            renderizarCards(solicitudesFiltradas);
        });

        cardsContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('file-upload-hidden')) {
                const fileNameDisplay = e.target.parentElement.querySelector('.file-name-display');
                if (e.target.files.length > 0) {
                    fileNameDisplay.textContent = e.target.files[0].name;
                } else {
                    fileNameDisplay.textContent = 'Ningún archivo';
                }
            }
        });

        cardsContainer.addEventListener('click', function(e) {
            const button = e.target.closest('button');
            if (!button) return;

            const actionsContainer = button.closest('.card-actions');
            const id = actionsContainer.dataset.id;

            if (button.classList.contains('upload-btn')) {
                const fileInput = actionsContainer.querySelector('.file-upload-hidden');
                if (fileInput.files.length === 0) {
                    Swal.fire("Atención", "Selecciona un archivo antes de subir.", "warning");
                    return;
                }
                const formData = new FormData();
                formData.append('documento', fileInput.files[0]);
                formData.append('idSolicitud', id);

                fetch('https://grammermx.com/Mailer/daoSubirDescripciones.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire("Éxito", data.message, "success");
                            cargarDatos();
                        } else {
                            Swal.fire("Error", data.message || "No se pudo subir el archivo.", "error");
                        }
                    });
            }

            if (button.classList.contains('go-to-vacante')) {
                Swal.fire({
                    title: '¿Continuar?',
                    text: `Se abrirá el formulario para crear la vacante asociada a la solicitud ID ${id}.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `cargaVacante.php?idSolicitud=${id}`;
                    }
                });
            }
        });

        document.getElementById('excelBtn').addEventListener('click', () => exportarDatos('excel'));
        document.getElementById('pdfBtn').addEventListener('click', () => exportarDatos('pdf'));
        document.getElementById('copyBtn').addEventListener('click', () => exportarDatos('copy'));

        function exportarDatos(formato) {
            const headers = ["IdSolicitud", "Area", "Puesto", "Solicitante", "Folio"];
            const data = todasLasSolicitudes.map(s => ({
                IdSolicitud: s.IdSolicitud,
                Area: s.NombreArea,
                Puesto: s.Puesto,
                Solicitante: s.Nombre,
                Folio: s.FolioSolicitud
            }));

            if (formato === 'excel') {
                const worksheet = XLSX.utils.json_to_sheet(data);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "Solicitudes");
                XLSX.writeFile(workbook, "solicitudes_aprobadas.xlsx");
            } else if (formato === 'pdf') {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                doc.autoTable({
                    head: [headers],
                    body: data.map(Object.values)
                });
                doc.save('solicitudes_aprobadas.pdf');
            } else if (formato === 'copy') {
                const text = [headers.join('\t'), ...data.map(d => Object.values(d).join('\t'))].join('\n');
                navigator.clipboard.writeText(text).then(() => Swal.fire('Copiado', 'Datos copiados al portapapeles.', 'success'));
            }
        }

        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) { window.location.href = 'login.php'; }
                    });
            });
        }

        cargarDatos();
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