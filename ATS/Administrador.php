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
    <title>Solicitudes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/estilosAdministrador.css">
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
                <a href="SAprobadas.php" class="dropdown-toggle">
                    Vacantes <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu-nav">
                    <a href="SAprobadas.php">Solicitudes Aprobadas</a>
                    <a href="SeguimientoAdministrador.php">Seguimiento de Postulantes</a>
                    <a href="cargaVacante.php">Cargar/Editar Vacantes</a>
                </div>
            </div>
            <a href="candidatoSeleccionado.php">Candidatos Seleccionados</a>

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
    <h1>Solicitudes de Personal</h1>
    <img src="imagenes/demanda%20(1).png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="buscador-vacantes" style="margin-bottom: 40px;">
            <div class="fila-superior">
                <div class="campo-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" id="filtro-texto" placeholder="Buscar por Puesto, Folio o Solicitante..." autocomplete="off">
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
        <main id="contenedorSolicitudes">
        </main>
    </div>
</section>

<div id="customEmailModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-paper-plane"></i> Enviar Correos de Notificación</h2>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <p>La solicitud ha sido aprobada. Ingresa los correos a los que se enviará la notificación para continuar el proceso.</p>
            <label for="email1">Correo 1 (obligatorio):</label>
            <input type="email" id="email1" required>
            <label for="email2">Correo 2 (opcional):</label>
            <input type="email" id="email2">
            <label for="email3">Correo 3 (opcional):</label>
            <input type="email" id="email3">
        </div>
        <div class="modal-footer">
            <button id="sendEmailsBtn" class="btn-accion aceptar">
                <i class="fas fa-check"></i> Enviar
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- REFERENCIAS A ELEMENTOS DEL DOM ---
        const contenedor = document.getElementById('contenedorSolicitudes');
        const emailModal = document.getElementById('customEmailModal');
        const filtroTexto = document.getElementById('filtro-texto');
        const filtroArea = document.getElementById('filtro-area');
        const filtroFecha = document.getElementById('filtro-fecha');
        const btnLimpiar = document.getElementById('limpiar-filtros');
        const btnBuscar = document.getElementById('btn-aplicar-filtros');

        let todasLasSolicitudes = []; // Caché para los datos del servidor

        // --- LÓGICA PRINCIPAL ---
        fetchSolicitudes();

        // --- FUNCIONES DE RENDERIZADO Y FILTRADO ---
        async function fetchSolicitudes() {
            contenedor.innerHTML = '<p>Cargando solicitudes...</p>';
            try {
                // ⚠️ OJO: Se usa el endpoint de este archivo específico
                const response = await fetch('https://grammermx.com/AleTest/ATS/dao/daoAdmin.php');
                if (!response.ok) throw new Error('Error en la respuesta del servidor');

                const data = await response.json();
                todasLasSolicitudes = data.data || [];

                popularFiltroAreas();
                aplicarFiltros();

            } catch (error) {
                console.error("Error al cargar solicitudes:", error);
                contenedor.innerHTML = '<p>Error al cargar las solicitudes. Intente de nuevo.</p>';
            }
        }

        function renderSolicitudes(solicitudes) {
            contenedor.innerHTML = '';
            if (solicitudes.length === 0) {
                contenedor.innerHTML = '<p>No se encontraron solicitudes con los filtros actuales.</p>';
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
            <div class="info-item"><strong>Nómina:</strong><div class="valor-con-icono"><i class="fas fa-id-card"></i><span>${solicitud.NumNomina}</span></div></div>
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
                filtroArea.innerHTML += `<option value="${area}">${area}</option>`;
            });
        }

        // --- MANEJO DE EVENTOS ---
        btnBuscar.addEventListener('click', aplicarFiltros);
        filtroArea.addEventListener('change', aplicarFiltros);
        filtroFecha.addEventListener('change', aplicarFiltros);
        btnLimpiar.addEventListener('click', () => {
            filtroTexto.value = '';
            filtroArea.value = '';
            filtroFecha.value = 'recientes';
            aplicarFiltros();
        });

        // Delegación de eventos para los botones de las tarjetas
        contenedor.addEventListener('click', e => {
            const target = e.target.closest('button');
            if (!target) return;

            const id = target.dataset.id;
            if (target.classList.contains('accept-btn')) {
                handleAceptar(id);
            } else if (target.classList.contains('reject-btn')) {
                handleRechazar(id);
            }
        });

        // Lógica de Aceptar
        async function handleAceptar(id) {
            const result = await Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Aprobar la solicitud ID: ${id}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar"
            });

            if (result.isConfirmed) {
                const formData = new URLSearchParams({ id: id, status: 2 }); // OJO: status 2 para aprobar
                try {
                    const response = await fetch('https://grammermx.com/AleTest/ATS/dao/daoActualizarEstatus.php', { method: 'POST', body: formData });
                    const jsonResponse = await response.json();

                    if (jsonResponse.success) {
                        Swal.fire("Aprobado", "Solicitud aprobada con éxito. Por favor, envía los correos.", "success");
                        emailModal.querySelector('#sendEmailsBtn').setAttribute('data-id', id);
                        emailModal.classList.add('show');
                    } else {
                        Swal.fire("Error", jsonResponse.message || "No se pudo aprobar la solicitud", "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                }
            }
        }

        // Lógica de Rechazar
        async function handleRechazar(id) {
            const result = await Swal.fire({
                title: "¿Estás seguro?",
                text: `¿Rechazar la solicitud ID: ${id}?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, rechazar",
                cancelButtonText: "Cancelar"
            });

            if (result.isConfirmed) {
                const formData = new URLSearchParams({ id: id, status: 3 }); // status 3 para rechazar
                try {
                    const response = await fetch('https://grammermx.com/AleTest/ATS/dao/daoActualizarEstatus.php', { method: 'POST', body: formData });
                    const jsonResponse = await response.json();

                    if (jsonResponse.success) {
                        Swal.fire("Rechazado", "Solicitud rechazada con éxito", "success");
                        fetchSolicitudes(); // Recargar tarjetas
                    } else {
                        Swal.fire("Error", jsonResponse.message || "No se pudo rechazar la solicitud", "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "No se pudo conectar con el servidor", "error");
                }
            }
        }

        // Lógica del modal de correos
        document.getElementById('sendEmailsBtn').addEventListener('click', async function () {
            const button = this;
            const solicitudId = button.getAttribute('data-id');
            const emails = [
                document.getElementById('email1').value.trim(),
                document.getElementById('email2').value.trim(),
                document.getElementById('email3').value.trim()
            ].filter(Boolean); // Filtra los correos vacíos

            if (!solicitudId || emails.length === 0) {
                Swal.fire("Error", "El ID de la solicitud y al menos un correo son obligatorios", "error");
                return;
            }

            button.disabled = true;
            button.textContent = "Enviando...";

            const formData = new URLSearchParams({ id: solicitudId });
            emails.forEach((email, index) => formData.append(`email${index + 1}`, email));

            try {
                const response = await fetch('https://grammermx.com/Mailer/mailerEnvioCorreos.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.status === "success") {
                    Swal.fire("Enviado", "El correo fue enviado correctamente", "success");
                    emailModal.classList.remove('show');
                    fetchSolicitudes(); // Recargar las tarjetas
                } else {
                    Swal.fire("Error", data.message || "No se pudo enviar el correo", "error");
                }
            } catch (error) {
                Swal.fire("Error", "No se pudo conectar con el servidor", "error");
            } finally {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check"></i> Enviar';
            }
        });

        // Cerrar el modal de correos
        emailModal.querySelector('.close-modal').addEventListener('click', () => {
            emailModal.classList.remove('show');
        });

        // Cerrar sesión
        document.getElementById('logout')?.addEventListener('click', e => {
            e.preventDefault();
            fetch('dao/logout.php', { method: 'POST' }).then(response => {
                if (response.ok) window.location.href = 'login.php';
            });
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