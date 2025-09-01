<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Vacantes | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosDashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <h1>Dashboard de Reclutamiento</h1>
    <img src="imagenes/analitica.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="dashboard-grid">

            <div class="kpi-card">
                <div class="kpi-icon icon-vacantes"><i class="fas fa-briefcase"></i></div>
                <div class="kpi-info">
                    <h3 id="kpi-vacantes">-</h3>
                    <p>Vacantes Abiertas</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-postulaciones"><i class="fas fa-users"></i></div>
                <div class="kpi-info">
                    <h3 id="kpi-postulaciones">-</h3>
                    <p>Postulaciones Totales</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-nuevas"><i class="fas fa-calendar-day"></i></div>
                <div class="kpi-info">
                    <h3 id="kpi-nuevas-hoy">-</h3>
                    <p>Nuevas Hoy</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-contratados"><i class="fas fa-handshake"></i></div>
                <div class="kpi-info">
                    <h3 id="kpi-contratados">-</h3>
                    <p>Contratados este Mes</p>
                </div>
            </div>

            <div id="embudo-container" class="chart-container">
                <h2>Embudo de Reclutamiento</h2>
                <div class="chart-wrapper">
                    <canvas id="chart-funnel"></canvas>
                </div>
            </div>
            <div id="area-container" class="chart-container">
                <h2>Postulaciones por Área</h2>
                <div class="chart-wrapper">
                    <canvas id="chart-area"></canvas>
                </div>
            </div>
            <div id="actividad-container" class="chart-container">
                <h2>Actividad Reciente (Últimos 15 días)</h2>
                <div class="chart-wrapper">
                    <canvas id="chart-activity"></canvas>
                </div>
            </div>
            <div id="top-vacantes-container" class="chart-container">
                <h2>Top 5 Vacantes con más Postulantes</h2>
                <div class="chart-wrapper">
                    <table class="tabla-top-vacantes">
                        <thead><tr><th>Puesto</th><th>Postulantes</th></tr></thead>
                        <tbody id="tabla-top-vacantes-body"></tbody>
                    </table>
                </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch('dao/daoDashboard.php')
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    const data = result.data;

                    // --- 1. Rellenar Tarjetas KPI ---
                    document.getElementById('kpi-vacantes').textContent = data.kpis.vacantes_abiertas;
                    document.getElementById('kpi-postulaciones').textContent = data.kpis.total_postulaciones;
                    document.getElementById('kpi-nuevas-hoy').textContent = data.kpis.nuevas_hoy;
                    document.getElementById('kpi-contratados').textContent = data.kpis.contratados_mes;

                    // --- 2. Crear Gráfica de Embudo (Dona) ---
                    const embudoCtx = document.getElementById('chart-funnel').getContext('2d');
                    new Chart(embudoCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Por Revisar', 'En Proceso', 'Contratados', 'Descartados'],
                            datasets: [{
                                data: [
                                    data.embudoReclutamiento.por_revisar,
                                    data.embudoReclutamiento.en_proceso,
                                    data.embudoReclutamiento.contratados,
                                    data.embudoReclutamiento.descartados
                                ],
                                backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ef4444']
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    // --- 3. Crear Gráfica de Postulaciones por Área (Barras) ---
                    const areaCtx = document.getElementById('chart-area').getContext('2d');
                    new Chart(areaCtx, {
                        type: 'bar',
                        data: {
                            labels: data.postulacionesPorArea.map(item => item.NombreArea),
                            datasets: [{
                                label: 'Nº de Postulantes',
                                data: data.postulacionesPorArea.map(item => item.total),
                                backgroundColor: '#3b82f6'
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y' }
                    });

                    // --- 4. Crear Gráfica de Actividad Reciente (Línea) ---
                    const activityCtx = document.getElementById('chart-activity').getContext('2d');
                    new Chart(activityCtx, {
                        type: 'line',
                        data: {
                            labels: data.actividadReciente.map(item => item.fecha),
                            datasets: [{
                                label: 'Postulaciones por Día',
                                data: data.actividadReciente.map(item => item.total),
                                borderColor: '#8b5cf6',
                                tension: 0.1,
                                fill: false
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    // --- 5. Rellenar Tabla Top 5 Vacantes ---
                    const tablaBody = document.getElementById('tabla-top-vacantes-body');
                    tablaBody.innerHTML = ''; // Limpiar
                    data.topVacantes.forEach(vacante => {
                        const row = `<tr><td>${vacante.TituloVacante}</td><td>${vacante.total}</td></tr>`;
                        tablaBody.innerHTML += row;
                    });

                } else {
                    console.error('Error al cargar datos del dashboard:', result.message);
                }
            });
    });
</script>
<script>
    const logoutLink = document.getElementById('logout');
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('dao/logout.php', { method: 'POST' })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'login.php';
                    }
                })
                .catch(error => console.error('Error al cerrar sesión:', error));
        });
    }
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