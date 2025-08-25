<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosSeguimiento.css">
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
            <a href="Solicitante.php">Nueva Solicitud</a>
            <a href="#">Seguimiento</a>
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
    <h1>Seguimiento de tu Solicitud</h1>
    <img src="imagenes/solicitudes-de-empleo.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco" id="seguimiento-container">
        <div class="loader"></div>
        <p class="loading-text">Cargando estado de tu última solicitud...</p>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const percentages = [75, 50, 90]; // Porcentajes a llenar
        const circles = document.querySelectorAll(".circle");

        circles.forEach((circle, index) => {
            const progressCircle = circle.querySelector(".progress");
            const percentageText = circle.querySelector(".percentage");

            const radius = progressCircle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;

            progressCircle.style.strokeDasharray = `${circumference}`;
            progressCircle.style.strokeDashoffset = circumference;

            let progress = 0;
            const target = percentages[index];

            const interval = setInterval(() => {
                if (progress <= target) {
                    const offset = circumference - (progress / 100) * circumference;
                    progressCircle.style.strokeDashoffset = offset;
                    percentageText.textContent = `${progress}%`;
                    progress++;
                } else {
                    clearInterval(interval);
                }
            }, 20); // Velocidad de animación
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        // Función para cargar y mostrar el estado de la solicitud
        function cargarEstadoSolicitud() {
            const container = document.getElementById('seguimiento-container');

            fetch('dao/daoEstadoSolicitud.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Si se encontró una solicitud, dibujamos la línea de tiempo
                        const solicitud = data.data;
                        dibujarTimeline(container, solicitud);
                    } else if (data.status === 'not_found') {
                        container.innerHTML = `<h2>Aún no tienes solicitudes</h2><p>${data.message}</p>`;
                    } else {
                        container.innerHTML = `<h2>Error</h2><p>${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `<h2>Error</h2><p>No se pudo conectar con el servidor.</p>`;
                });
        }

        // Función que crea el HTML de la línea de tiempo
        function dibujarTimeline(container, solicitud) {
            const estatusId = parseInt(solicitud.IdEstatus);

            // Definimos los pasos y sus estados
            const pasos = [
                { id: 1, label: 'Enviada', icon: 'fa-paper-plane' },
                { id: 4, label: 'Aprob. Parcial', icon: 'fa-user-check' },
                { id: 2, label: 'Aprob. Gerencia', icon: 'fa-users' },
                { id: 10, label: 'Vacante Creada', icon: 'fa-bullhorn' }
            ];

            let timelineHTML = `
                <h2>Seguimiento de tu última solicitud</h2>
                <p class="folio-solicitud">Folio: ${solicitud.FolioSolicitud}</p>
                <div class="timeline">
            `;

            let haSidoRechazado = (estatusId === 3);
            let pasoActivoEncontrado = false;

            pasos.forEach((paso, index) => {
                let claseEstado = '';

                if (haSidoRechazado) {
                    claseEstado = 'rejected';
                } else {
                    if (pasoActivoEncontrado) {
                        claseEstado = ''; // Pasos futuros quedan en gris
                    } else if (estatusId === paso.id) {
                        claseEstado = 'active'; // El paso actual
                        pasoActivoEncontrado = true;
                    } else {
                        // Suponemos que si el estatus actual es mayor que el del paso, ya se completó
                        // Esta lógica simple funciona si los IDs de estatus son secuenciales en el proceso.
                        // Ej: si el estatus es 4, el paso 1 se marca como completado.
                        // ¡IMPORTANTE! Ajusta esta lógica si tus IDs de estatus no siguen el flujo del proceso.
                        const flujoDeEstatus = [1, 4, 2, 10];
                        const indiceActual = flujoDeEstatus.indexOf(estatusId);
                        const indicePaso = flujoDeEstatus.indexOf(paso.id);

                        if (indiceActual > indicePaso) {
                            claseEstado = 'completed';
                        }
                    }
                }

                timelineHTML += `
                    <div class="timeline-step ${claseEstado}">
                        <div class="icon"><i class="fas ${paso.icon}"></i></div>
                        <div class="label">${paso.label}</div>
                    </div>
                `;
            });

            timelineHTML += `</div>`;
            container.innerHTML = timelineHTML;
        }

        // --- INICIAR TODO ---
        cargarEstadoSolicitud();


        // --- CÓDIGO DE LOGOUT (sin cambios) ---
        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) {
                            window.location.href = 'login.php';
                        }
                    });
            });
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
        </div>

    </div>
    <div class="sub-footer">
        <p>&copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.</p>
    </div>
</footer>
</body>
</html>
