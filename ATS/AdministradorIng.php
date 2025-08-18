<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/estilosAdministradorIng.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    <h1>Candidatos Seleccionados</h1>
    <img src="imagenes/contratacion.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="buscador-vacantes">
            <div class="fila-superior">
                <div class="campo-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" id="filtro-texto" placeholder="Buscar por Puesto, Folio o Nombre..." autocomplete="off">
                </div>
                <button class="btn-buscar" id="btn-aplicar-filtros">Buscar</button>
            </div>
            <div class="filtros">
                <select id="filtro-area" class="filtro">
                    <option value="">Todas las Áreas</option>
                </select>
                <select id="filtro-fecha" class="filtro">
                    <option value="recientes">Más recientes</option>
                    <option value="antiguas">Más antiguas</option>
                </select>
                <button id="limpiar-filtros" class="filtro limpiar">Limpiar filtros</button>
            </div>
        </div>

        <main class="main-candidatos">
            <div id="contenedorSolicitudes" style="margin-top: 30px;">
            </div>
        </main>
    </div>
</section>

<div id="rejectModal" class="custom-modal">
    <div class="custom-modal-content">
        <span class="close-reject-modal">&times;</span>
        <h2>Comentario de Rechazo</h2>
        <textarea id="rejectComment" placeholder="Escribe el motivo del rechazo aquí..." rows="5" style="width:100%; margin-bottom: 15px;"></textarea>
        <button id="confirmRejectBtn" class="btn-accion rechazar">Confirmar Rechazo</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- VARIABLES GLOBALES Y REFERENCIAS A ELEMENTOS ---
        let todasLasSolicitudes = []; // Caché para guardar los datos del servidor
        const contenedor = document.getElementById('contenedorSolicitudes');
        const filtroTexto = document.getElementById('filtro-texto');
        const filtroArea = document.getElementById('filtro-area');
        const filtroFecha = document.getElementById('filtro-fecha');
        const btnLimpiar = document.getElementById('limpiar-filtros');
        const btnBuscar = document.getElementById('btn-aplicar-filtros');

        // --- LÓGICA PRINCIPAL ---

        // 1. Cargar las solicitudes al iniciar la página
        fetchSolicitudes();

        // --- FUNCIONES ---

        /**
         * Obtiene las solicitudes del servidor y las muestra.
         */
        async function fetchSolicitudes() {
            contenedor.innerHTML = '<p>Cargando solicitudes...</p>';
            try {
                const response = await fetch('https://grammermx.com/AleTest/ATS/dao/daoAdminIng.php');
                if (!response.ok) throw new Error('Error en la respuesta del servidor');

                const data = await response.json();
                todasLasSolicitudes = data.data || [];

                // Llenar el filtro de áreas dinámicamente
                popularFiltroAreas();

                // Renderizar las tarjetas
                aplicarFiltros();

            } catch (error) {
                console.error("Error al cargar las solicitudes:", error);
                contenedor.innerHTML = '<p>Error al cargar las solicitudes. Intente de nuevo más tarde.</p>';
            }
        }

        /**
         * Dibuja las tarjetas de solicitud en el contenedor.
         * @param {Array} solicitudes - El array de solicitudes a mostrar.
         */
        function renderSolicitudes(solicitudes) {
            contenedor.innerHTML = ''; // Limpiar contenedor
            if (solicitudes.length === 0) {
                contenedor.innerHTML = '<p>No se encontraron solicitudes que coincidan con los filtros.</p>';
                return;
            }

            solicitudes.forEach(solicitud => {
                const estatusClase = solicitud.NombreEstatus.toLowerCase().replace(/\s+/g, '');
                const cardHTML = `
                <div class="card-solicitud">
                    <div class="card-header">
                        <h3>${solicitud.Puesto}</h3>
                        <span class="estatus ${estatusClase}">${solicitud.NombreEstatus}</span>
                    </div>
                    <div class="card-body">
                        <div class="info-item"><strong>Solicitante:</strong> ${solicitud.Nombre}</div>
                        <div class="info-item"><strong>Área:</strong> ${solicitud.NombreArea}</div>
                        <div class="info-item"><strong>Folio:</strong> ${solicitud.FolioSolicitud}</div>
                        <div class="info-item"><strong>Contratación:</strong> ${solicitud.TipoContratacion}</div>
                        <div class="info-item"><strong>Fecha Solicitud:</strong> ${solicitud.FechaSolicitud}</div>
                        ${solicitud.NombreReemplazo ? `<div class="info-item"><strong>Reemplaza a:</strong> ${solicitud.NombreReemplazo}</div>` : ''}
                    </div>
                    <div class="card-actions">
                        <button class="btn-accion rechazar reject-btn" data-id="${solicitud.IdSolicitud}">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                        <button class="btn-accion aceptar accept-btn" data-id="${solicitud.IdSolicitud}">
                            <i class="fas fa-check"></i> Aceptar
                        </button>
                    </div>
                </div>
            `;
                contenedor.innerHTML += cardHTML;
            });
        }

        /**
         * Filtra y ordena las solicitudes y luego las renderiza.
         */
        function aplicarFiltros() {
            let solicitudesFiltradas = [...todasLasSolicitudes];

            // Filtro por texto
            const texto = filtroTexto.value.toLowerCase().trim();
            if (texto) {
                solicitudesFiltradas = solicitudesFiltradas.filter(s =>
                    s.Puesto.toLowerCase().includes(texto) ||
                    s.FolioSolicitud.toLowerCase().includes(texto) ||
                    s.Nombre.toLowerCase().includes(texto)
                );
            }

            // Filtro por área
            const area = filtroArea.value;
            if (area) {
                solicitudesFiltradas = solicitudesFiltradas.filter(s => s.NombreArea === area);
            }

            // Ordenar por fecha
            const orden = filtroFecha.value;
            solicitudesFiltradas.sort((a, b) => {
                const fechaA = new Date(a.FechaSolicitud);
                const fechaB = new Date(b.FechaSolicitud);
                return orden === 'antiguas' ? fechaA - fechaB : fechaB - fechaA;
            });

            renderSolicitudes(solicitudesFiltradas);
        }

        /**
         * Extrae áreas únicas y las añade al <select> de filtros.
         */
        function popularFiltroAreas() {
            const areas = [...new Set(todasLasSolicitudes.map(s => s.NombreArea))];
            filtroArea.innerHTML = '<option value="">Todas las Áreas</option>'; // Reset
            areas.sort().forEach(area => {
                const option = document.createElement('option');
                option.value = area;
                option.textContent = area;
                filtroArea.appendChild(option);
            });
        }


        // --- MANEJO DE EVENTOS ---

        // Eventos de los filtros
        btnBuscar.addEventListener('click', aplicarFiltros);
        filtroArea.addEventListener('change', aplicarFiltros);
        filtroFecha.addEventListener('change', aplicarFiltros);
        filtroTexto.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') aplicarFiltros();
        });

        btnLimpiar.addEventListener('click', () => {
            filtroTexto.value = '';
            filtroArea.value = '';
            filtroFecha.value = 'recientes';
            aplicarFiltros();
        });

        // Eventos para botones de Aceptar/Rechazar (usando delegación de eventos)
        contenedor.addEventListener('click', (e) => {
            const target = e.target.closest('button');
            if (!target) return;

            const id = target.dataset.id;

            if (target.classList.contains('accept-btn')) {
                handleAceptar(id);
            } else if (target.classList.contains('reject-btn')) {
                handleRechazar(id);
            }
        });

        /**
         * Lógica para Aceptar una solicitud
         */
        function handleAceptar(id) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Deseas aprobar la solicitud ID: ${id}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new URLSearchParams();
                        formData.append("id", id);
                        formData.append("status", 5); // 5 = Aprobado

                        const response = await fetch('https://grammermx.com/Mailer/mailerActualizarEstatus.php', {
                            method: 'POST',
                            body: formData
                        });

                        const jsonResponse = await response.json();

                        if (jsonResponse.success) {
                            Swal.fire("Aprobado", "La solicitud fue aprobada con éxito.", "success");
                            fetchSolicitudes(); // Recargar la lista de tarjetas
                        } else {
                            Swal.fire("Error", jsonResponse.message || "No se pudo aprobar la solicitud.", "error");
                        }
                    } catch (error) {
                        Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                    }
                }
            });
        }

        /**
         * Lógica para Rechazar una solicitud (abre el modal)
         */
        let rejectSolicitudId = null;
        const rejectModal = document.getElementById('rejectModal');

        function handleRechazar(id) {
            rejectSolicitudId = id;
            document.getElementById('rejectComment').value = '';
            rejectModal.classList.add('show');
        }

        // Confirmar rechazo desde el modal
        document.getElementById('confirmRejectBtn').addEventListener('click', () => {
            const comentario = document.getElementById('rejectComment').value.trim();
            if (!comentario) {
                Swal.fire("Atención", "Debes ingresar un comentario para rechazar.", "warning");
                return;
            }

            rejectModal.classList.remove('show');

            Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Deseas rechazar la solicitud ID: ${rejectSolicitudId}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, rechazar",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new URLSearchParams();
                        formData.append("id", rejectSolicitudId);
                        formData.append("status", 3); // 3 = Rechazado
                        formData.append("comentario", comentario);

                        const response = await fetch('https://grammermx.com/Mailer/mailerActualizarEstatus.php', {
                            method: 'POST',
                            body: formData
                        });

                        const jsonResponse = await response.json();

                        if (jsonResponse.success) {
                            Swal.fire("Rechazado", "La solicitud fue rechazada.", "success");
                            fetchSolicitudes(); // Recargar la lista de tarjetas
                        } else {
                            Swal.fire("Error", jsonResponse.message || "No se pudo rechazar la solicitud.", "error");
                        }
                    } catch (error) {
                        Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                    }
                }
            });
        });

        // Cerrar modal con la X
        document.querySelector('.close-reject-modal').addEventListener('click', () => {
            rejectModal.classList.remove('show');
        });


        // Lógica de Cerrar Sesión
        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) {
                            window.location.href = 'login.php';
                        } else {
                            alert('Error al cerrar sesión.');
                        }
                    });
            });
        }
    });
</script>
</body>
</html>