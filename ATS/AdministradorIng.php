<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobación Gerencial</title>
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

// Pasamos el NumNomina de la sesión de PHP a una variable de JavaScript.
echo "<script>const currentUserNomina = '" . htmlspecialchars($_SESSION['NumNomina']) . "';</script>";
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
    <h1>Aprobación Gerencial</h1>
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
        <div class="modal-header">
            <h2><i class="fas fa-comment-dots"></i> Motivo del Rechazo</h2>
            <button class="close-reject-modal">&times;</button>
        </div>
        <div class="modal-body">
            <label for="rejectComment">Por favor, proporciona un comentario claro para el solicitante.</label>
            <textarea id="rejectComment" placeholder="Ej: La vacante se ha puesto en pausa..." rows="5"></textarea>
        </div>
        <div class="modal-footer">
            <button id="confirmRejectBtn" class="btn-accion rechazar">
                <i class="fas fa-times-circle"></i> Confirmar Rechazo
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        let todasLasSolicitudes = [];
        const contenedor = document.getElementById('contenedorSolicitudes');
        const filtroTexto = document.getElementById('filtro-texto');
        const filtroArea = document.getElementById('filtro-area');
        const filtroFecha = document.getElementById('filtro-fecha');
        const btnLimpiar = document.getElementById('limpiar-filtros');
        const btnBuscar = document.getElementById('btn-aplicar-filtros');

        fetchSolicitudes();

        async function fetchSolicitudes() {
            contenedor.innerHTML = '<p>Cargando solicitudes...</p>';
            try {
                const response = await fetch('https://grammermx.com/AleTest/ATS/dao/daoAdminIng.php');
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                const data = await response.json();
                todasLasSolicitudes = data.data || [];
                popularFiltroAreas();
                aplicarFiltros();
            } catch (error) {
                console.error("Error al cargar las solicitudes:", error);
                contenedor.innerHTML = '<p>Error al cargar las solicitudes. Intente de nuevo más tarde.</p>';
            }
        }

        function renderSolicitudes(solicitudes) {
            contenedor.innerHTML = '';
            if (solicitudes.length === 0) {
                contenedor.innerHTML = '<p>No se encontraron solicitudes pendientes para tu aprobación.</p>';
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
                    <div class="info-item"><strong>Solicitante:</strong><div class="valor-con-icono"><i class="fas fa-user"></i><span>${solicitud.Nombre}</span></div></div>
                    <div class="info-item"><strong>Área:</strong> ${solicitud.NombreArea}</div>
                    <div class="info-item"><strong>Folio:</strong> ${solicitud.FolioSolicitud}</div>
                    <div class="info-item"><strong>Contratación:</strong> ${solicitud.TipoContratacion}</div>
                    <div class="info-item"><strong>Fecha Solicitud:</strong> ${solicitud.FechaSolicitud}</div>
                    ${solicitud.NombreReemplazo ? `<div class="info-item"><strong>Reemplaza a:</strong><div class="valor-con-icono"><i class="fas fa-people-arrows"></i><span>${solicitud.NombreReemplazo}</span></div></div>` : ''}
                </div>
                <div class="card-actions">
                    <button class="btn-accion rechazar reject-btn" data-id="${solicitud.IdSolicitud}">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                    <button class="btn-accion aceptar accept-btn" data-id="${solicitud.IdSolicitud}">
                        <i class="fas fa-check"></i> Aceptar
                    </button>
                </div>
            </div>`;
                contenedor.insertAdjacentHTML('beforeend', cardHTML);
            });
        }

        function aplicarFiltros() {
            let solicitudesFiltradas = [...todasLasSolicitudes];
            const texto = filtroTexto.value.toLowerCase().trim();
            if (texto) {
                solicitudesFiltradas = solicitudesFiltradas.filter(s =>
                    s.Puesto.toLowerCase().includes(texto) ||
                    s.FolioSolicitud.toLowerCase().includes(texto) ||
                    s.Nombre.toLowerCase().includes(texto)
                );
            }
            const area = filtroArea.value;
            if (area) {
                solicitudesFiltradas = solicitudesFiltradas.filter(s => s.NombreArea === area);
            }
            const orden = filtroFecha.value;
            solicitudesFiltradas.sort((a, b) => {
                const fechaA = new Date(a.FechaSolicitud);
                const fechaB = new Date(b.FechaSolicitud);
                return orden === 'antiguas' ? fechaA - fechaB : fechaB - fechaA;
            });
            renderSolicitudes(solicitudesFiltradas);
        }

        function popularFiltroAreas() {
            const areas = [...new Set(todasLasSolicitudes.map(s => s.NombreArea))];
            filtroArea.innerHTML = '<option value="">Todas las Áreas</option>';
            areas.sort().forEach(area => {
                const option = document.createElement('option');
                option.value = area;
                option.textContent = area;
                filtroArea.appendChild(option);
            });
        }

        // --- MANEJO DE EVENTOS ---
        btnBuscar.addEventListener('click', aplicarFiltros);
        filtroArea.addEventListener('change', aplicarFiltros);
        filtroFecha.addEventListener('change', aplicarFiltros);
        filtroTexto.addEventListener('keyup', (e) => { if (e.key === 'Enter') aplicarFiltros(); });
        btnLimpiar.addEventListener('click', () => {
            filtroTexto.value = '';
            filtroArea.value = '';
            filtroFecha.value = 'recientes';
            aplicarFiltros();
        });

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

        // --- LÓGICA DE APROBACIÓN ACTUALIZADA ---
        function handleAceptar(id) {
            Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Deseas registrar tu APROBACIÓN para la solicitud ID: ${id}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new URLSearchParams();
                        formData.append("id", id);
                        formData.append("status", 5); // 5 significa "Aprobado"
                        formData.append("num_nomina", currentUserNomina); // <-- DATO AÑADIDO

                        // ⚠️ REEMPLAZA ESTA URL POR LA RUTA A TU NUEVO ARCHIVO PHP DE DOBLE APROBACIÓN
                        const response = await fetch('https://grammermx.com/Mailer/mailerActualizarEstatus.php', {
                            method: 'POST',
                            body: formData
                        });

                        const jsonResponse = await response.json();

                        if (jsonResponse.success) {
                            let successMessage = "Tu aprobación ha sido registrada.";
                            if (jsonResponse.final_status === 5) {
                                successMessage = "¡Aprobación final! La solicitud ha sido aprobada por ambos responsables.";
                            } else if (jsonResponse.final_status === 3) {
                                successMessage = "La solicitud ha sido rechazada.";
                            }
                            Swal.fire("Éxito", successMessage, "success");
                            fetchSolicitudes(); // Recargar la lista de tarjetas
                        } else {
                            Swal.fire("Error", jsonResponse.message || "No se pudo registrar la aprobación.", "error");
                        }
                    } catch (error) {
                        Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                    }
                }
            });
        }

        // --- LÓGICA DE RECHAZO ACTUALIZADA ---
        let rejectSolicitudId = null;
        const rejectModal = document.getElementById('rejectModal');

        function handleRechazar(id) {
            rejectSolicitudId = id;
            document.getElementById('rejectComment').value = '';
            rejectModal.classList.add('show');
        }

        document.getElementById('confirmRejectBtn').addEventListener('click', () => {
            const comentario = document.getElementById('rejectComment').value.trim();
            if (!comentario) {
                Swal.fire("Atención", "Debes ingresar un comentario para rechazar.", "warning");
                return;
            }
            rejectModal.classList.remove('show');

            Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Deseas RECHAZAR la solicitud ID: ${rejectSolicitudId}? Esta acción es final.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, rechazar",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new URLSearchParams();
                        formData.append("id", rejectSolicitudId);
                        formData.append("status", 3); // 3 significa "Rechazado"
                        formData.append("comentario", comentario);
                        formData.append("num_nomina", currentUserNomina); // <-- DATO AÑADIDO

                        // ⚠️ REEMPLAZA ESTA URL POR LA RUTA A TU NUEVO ARCHIVO PHP DE DOBLE APROBACIÓN
                        const response = await fetch('https://grammermx.com/Mailer/mailerActualizarEstatus.php', {
                            method: 'POST',
                            body: formData
                        });

                        const jsonResponse = await response.json();

                        if (jsonResponse.success) {
                            Swal.fire("Rechazado", "La solicitud ha sido rechazada con éxito.", "success");
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

        document.querySelector('.close-reject-modal').addEventListener('click', () => {
            rejectModal.classList.remove('show');
        });

        // Lógica de Cerrar Sesión (sin cambios)
        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) window.location.href = 'login.php';
                        else alert('Error al cerrar sesión.');
                    });
            });
        }
    });
</script>
</body>
</html>