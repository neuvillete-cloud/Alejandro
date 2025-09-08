<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}

// --- CONFIGURACIÓN IMPORTANTE ---
// Define aquí el número de nómina de la gerente de RRHH.
// Asegúrate que coincida con uno de los aprobadores fijos.
define('HR_MANAGER_NOMINA', '00030315');

// Pasamos las nóminas de la sesión y de RRHH a JavaScript.
echo "<script>const currentUserNomina = '" . htmlspecialchars($_SESSION['NumNomina']) . "';</script>";
echo "<script>const hrManagerNomina = '" . HR_MANAGER_NOMINA . "';</script>";
?>
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
            <h3>Contacto</h3>
            <p><i class="fas fa-map-marker-alt"></i> Av. de la Luz #24 Col. satélite , Querétaro, Mexico</p>
            <p><i class="fas fa-phone"></i> +52 (442) 238 4460</p>
        </div>
    </div>
    <div class="sub-footer">
        <p>&copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.</p>
        <p class="developer-credit">Desarrollado por Alejandro Torres</p>
    </div>
</footer>

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
                let actionButtons = '';
                if (currentUserNomina === hrManagerNomina) {
                    actionButtons = `
                        <button class="btn-accion rechazar reject-btn" data-id="${solicitud.IdSolicitud}"><i class="fas fa-times"></i> Rechazar</button>
                        <button class="btn-accion aceptar-normal accept-btn" data-type="normal" data-id="${solicitud.IdSolicitud}"><i class="fas fa-check"></i> Aprobar Normal</button>
                        <button class="btn-accion aceptar-confidencial" data-type="confidential" data-id="${solicitud.IdSolicitud}"><i class="fas fa-lock"></i> Aprobar Confidencial</button>
                    `;
                } else {
                    actionButtons = `
                        <button class="btn-accion rechazar reject-btn" data-id="${solicitud.IdSolicitud}"><i class="fas fa-times"></i> Rechazar</button>
                        <button class="btn-accion aceptar accept-btn" data-type="normal" data-id="${solicitud.IdSolicitud}"><i class="fas fa-check"></i> Aceptar</button>
                    `;
                }

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
                    <div class="card-actions">${actionButtons}</div>
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
            solicitudesFiltradas.sort((a, b) => new Date(b.FechaSolicitud) - new Date(a.FechaSolicitud));
            if (orden === 'antiguas') {
                solicitudesFiltradas.reverse();
            }
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
            const type = target.dataset.type;
            if (target.classList.contains('accept-btn') || target.classList.contains('aceptar-confidencial') || target.classList.contains('aceptar-normal')) {
                handleAceptar(id, type);
            } else if (target.classList.contains('reject-btn')) {
                handleRechazar(id);
            }
        });

        function handleAceptar(id, approvalType) {
            let confirmationText = approvalType === 'confidential'
                ? `¿Marcar como CONFIDENCIAL y aprobar? Solo tú podrás gestionar esta vacante.`
                : `¿Deseas registrar tu APROBACIÓN para la solicitud ID: ${id}?`;

            Swal.fire({
                title: "¿Estás seguro?",
                text: confirmationText,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        // --- INICIO DE DEPURACIÓN EN JAVASCRIPT ---
                        console.log("--- INICIANDO DEPURACIÓN DE ENVÍO ---");
                        console.log("Valor de 'id':", id);
                        console.log("Valor de 'status' (fijo):", 5);
                        console.log("Valor de 'num_nomina' (currentUserNomina):", currentUserNomina);
                        console.log("Valor de 'approval_type':", approvalType);
                        console.log("");
                        // --- FIN DE DEPURACIÓN ---
                        const formData = new URLSearchParams();
                        formData.append("id", id);
                        formData.append("status", 5); // Estatus 2 = Aprobada (decisión)
                        formData.append("num_nomina", currentUserNomina);
                        formData.append("approval_type", approvalType);

                        console.log(formData);

                        // ⚠️ REEMPLAZA ESTA URL POR LA RUTA A TU NUEVO ARCHIVO PHP DE DOBLE APROBACIÓN
                        const response = await fetch('https://grammermx.com/Mailer/mailerActualizarEstatus.php', {
                            method: 'POST',
                            body: formData
                        });
                        const jsonResponse = await response.json();

                        if (jsonResponse.success) {
                            Swal.fire("Éxito", jsonResponse.message, "success");
                            fetchSolicitudes();
                        } else {
                            Swal.fire("Error", jsonResponse.message, "error");
                        }
                    } catch (error) {
                        Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
                    }
                }
            });
        }

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
                        formData.append("status", 3); // 3 = Rechazado
                        formData.append("comentario", comentario);
                        formData.append("num_nomina", currentUserNomina);

                        // ⚠️ REEMPLAZA ESTA URL POR LA RUTA A TU NUEVO ARCHIVO PHP DE DOBLE APROBACIÓN
                        const response = await fetch('https://grammermx.com/Mailer/mailerActualizarEstatus.php', {
                            method: 'POST',
                            body: formData
                        });
                        const jsonResponse = await response.json();

                        if (jsonResponse.success) {
                            Swal.fire("Rechazado", "La solicitud ha sido rechazada con éxito.", "success");
                            fetchSolicitudes();
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