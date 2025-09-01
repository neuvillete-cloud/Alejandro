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
    <title>Candidatos Seleccionados | ATS Grammer</title>
    <link rel="stylesheet" href="css/candidatoSeleccionado.css">
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
    <h1>Candidatos Seleccionados</h1>
    <img src="imagenes/contratacion.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <div class="panel-filtros">
            <div class="campo-filtro">
                <i class="fas fa-briefcase"></i>
                <input type="text" id="filtro-vacante" placeholder="Buscar por vacante...">
            </div>
            <div class="campo-filtro">
                <i class="fas fa-user"></i>
                <input type="text" id="filtro-candidato" placeholder="Buscar por candidato...">
            </div>
            <div class="campo-filtro">
                <i class="fas fa-building"></i>
                <select id="filtro-area">
                    <option value="">Todas las Áreas</option>
                </select>
            </div>
            <button id="btn-buscar" class="btn-accion-filtro btn-buscar"><i class="fas fa-search"></i> Buscar</button>
            <button id="btn-limpiar" class="btn-accion-filtro btn-limpiar"><i class="fas fa-times"></i> Limpiar</button>
        </div>

        <div class="contenedor-candidatos" id="contenedorCandidatos">
        </div>

    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/candidatoSeleccionado.js"></script>
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