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
    <title>Historial de Solicitudes | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosHistoricos.css">
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
    <h1>Historial de Solicitudes</h1>
    <img src="imagenes/solicitud.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="filtros-container">
            <div class="campo-filtro">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroPuesto" placeholder="Buscar por puesto o folio...">
            </div>
            <div class="campo-filtro">
                <i class="fas fa-briefcase"></i>
                <select id="filtroTipo">
                    <option value="">Todos los Tipos</option>
                    <option value="nuevo">Nuevo Puesto</option>
                    <option value="reemplazo">Reemplazo</option>
                </select>
            </div>
        </div>

        <div id="cards-container" class="cards-grid">
            <div class="loader-container">
                <div class="loader"></div>
                <p>Cargando tus solicitudes...</p>
            </div>
        </div>
        <div id="no-results" class="no-results-message" style="display: none;">
            <i class="fas fa-exclamation-circle"></i>
            <p>No se encontraron solicitudes que coincidan con tu búsqueda.</p>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const cardsContainer = document.getElementById('cards-container');
        const filtroPuesto = document.getElementById('filtroPuesto');
        const filtroTipo = document.getElementById('filtroTipo');
        const noResultsMessage = document.getElementById('no-results');
        let todasLasSolicitudes = [];

        function renderizarTarjetas(solicitudes) {
            cardsContainer.innerHTML = '';
            if (solicitudes.length === 0) {
                noResultsMessage.style.display = 'block';
                return;
            }
            noResultsMessage.style.display = 'none';

            solicitudes.forEach(solicitud => {
                let estatusInfo = getEstatusInfo(solicitud.IdEstatus);

                const cardHTML = `
                    <div class="solicitud-card" data-puesto="${solicitud.Puesto.toLowerCase()}" data-folio="${solicitud.FolioSolicitud.toLowerCase()}" data-tipo="${solicitud.TipoContratacion}">
                        <div class="card-header">
                            <h3>${solicitud.Puesto}</h3>
                            <span class="estatus ${estatusInfo.clase}">${estatusInfo.texto}</span>
                        </div>
                        <div class="card-body">
                            <div class="info-item"><i class="fas fa-id-card"></i><strong>Folio:</strong> <span>${solicitud.FolioSolicitud}</span></div>
                            <div class="info-item"><i class="fas fa-building"></i><strong>Área:</strong> <span>${solicitud.NombreArea}</span></div>
                            <div class="info-item"><i class="fas fa-briefcase"></i><strong>Tipo:</strong> <span>${solicitud.TipoContratacion}</span></div>
                            <div class="info-item"><i class="fas fa-calendar-alt"></i><strong>Fecha:</strong> <span>${solicitud.FechaSolicitud}</span></div>
                        </div>
                        <div class="card-footer">
                            <a href="seguimiento.php?folio=${solicitud.FolioSolicitud}" class="btn-ver-mas">Ver Progreso</a>
                        </div>
                    </div>
                `;
                cardsContainer.innerHTML += cardHTML;
            });
        }

        function getEstatusInfo(idEstatus) {
            const id = parseInt(idEstatus);
            switch (id) {
                case 1: return { texto: 'Enviada', clase: 'enviada' };
                case 2: return { texto: 'Aprob. Gerencia', clase: 'aprobada' };
                case 3: return { texto: 'Rechazada', clase: 'rechazada' };
                case 4: return { texto: 'Aprob. Parcial', clase: 'parcial' };
                case 5: return { texto: 'Aprobada', clase: 'aprobada' };
                case 10: return { texto: 'Vacante Creada', clase: 'vacante-creada' };
                default: return { texto: 'Desconocido', clase: 'desconocido' };
            }
        }

        function filtrarTarjetas() {
            const textoBusqueda = filtroPuesto.value.toLowerCase();
            const tipoSeleccionado = filtroTipo.value; // Ya viene en minúsculas desde el HTML

            const solicitudesFiltradas = todasLasSolicitudes.filter(solicitud => {
                const puestoCoincide = solicitud.Puesto.toLowerCase().includes(textoBusqueda);
                const folioCoincide = solicitud.FolioSolicitud.toLowerCase().includes(textoBusqueda);

                // --- LÍNEA CORREGIDA ---
                // Comparamos los datos de la BD (en minúsculas) con el valor del filtro
                const tipoCoincide = tipoSeleccionado === "" || solicitud.TipoContratacion.toLowerCase() === tipoSeleccionado;

                return (puestoCoincide || folioCoincide) && tipoCoincide;
            });

            renderizarTarjetas(solicitudesFiltradas);
        }

        fetch('dao/daoSoli.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    todasLasSolicitudes = data.data;
                    renderizarTarjetas(todasLasSolicitudes);
                } else {
                    cardsContainer.innerHTML = '<p class="error-message">No se pudieron cargar tus solicitudes.</p>';
                }
            })
            .catch(error => {
                console.error("Error al cargar las solicitudes:", error);
                cardsContainer.innerHTML = '<p class="error-message">Error de conexión al servidor.</p>';
            });

        filtroPuesto.addEventListener('keyup', filtrarTarjetas);
        filtroTipo.addEventListener('change', filtrarTarjetas);

        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) window.location.href = 'login.php';
                    });
            });
        }
    });
</script>
</body>
</html>