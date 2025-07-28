<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/candidatoSeleccionado.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php'); // Redirige al login si no está autenticado
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
            <a href="#">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php"> Escuela de Talentos</a>
            <a href="#">Inclusión y diversidad</a>

            <?php if (isset($_SESSION['NombreCandidato'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></span>
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
                <a href="loginATS.php">Inicio de sesión</a>
            <?php endif; ?>

        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Vacantes en Grammer Automotive</h1>
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="buscador-vacantes">
            <div class="fila-superior">
                <div class="campo-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="practicante de ingeniería" autocomplete="off">
                    <i class="fas fa-times cerrar-busqueda"></i>

                    <!-- Historial de búsqueda -->
                    <ul class="historial-busquedas"></ul>
                </div>

                <div class="campo-ubicacion">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" placeholder="Querétaro" autocomplete="off">
                    <i class="fas fa-times cerrar-ubicacion"></i>

                    <!-- Historial de ubicaciones -->
                    <ul class="historial-ubicaciones"></ul>
                </div>

                <button class="btn-buscar">Buscar empleo</button>
            </div>

            <div class="filtros">
                <!-- Salario -->
                <select id="filtro-salario" class="filtro">
                    <option value="" disabled selected>Salario</option>
                    <option value="0-4999">Menos de $5,000</option>
                    <option value="5000-10000">$5,000 - $10,000</option>
                    <option value="10001-15000">$10,001 - $15,000</option>
                    <option value="15001-99999">Más de $15,000</option>
                </select>

                <!-- Fecha -->
                <select id="filtro-fecha" class="filtro">
                    <option value="" disabled selected>Ordenar por</option>
                    <option value="recientes">Más recientes</option>
                    <option value="antiguas">Más antiguas</option>
                </select>

                <!-- Modalidad -->
                <select id="filtro-modalidad" class="filtro">
                    <option value="" disabled selected>Modalidad</option>
                    <option value="presencial">Presencial</option>
                    <option value="remoto">Desde casa</option>
                    <option value="hibrido">Híbrido</option>
                </select>

                <!-- Tipo de contratación -->
                <select id="filtro-contrato" class="filtro">
                    <option value="" disabled selected>Tipo de contratación</option>
                    <option value="becario">Becario/Prácticas</option>
                    <option value="temporal">Temporal</option>
                    <option value="Tiempo completo">Tiempo completo</option>
                </select>

                <!-- Educación -->
                <select id="filtro-educacion" class="filtro">
                    <option value="" disabled selected>Educación</option>
                    <option value="secundaria">Secundaria</option>
                    <option value="preparatoria">Preparatoria</option>
                    <option value="tecnico">Técnico</option>
                    <option value="licenciatura">Licenciatura</option>
                    <option value="maestria">Maestría</option>
                </select>

                <!-- Botón limpiar -->
                <button id="limpiar-filtros" class="filtro limpiar">Limpiar filtros</button>
            </div>



        </div>

        <!-- Contenido principal -->
        <main class="main-candidatos">
        <h2 style="text-align: center; margin-bottom: 30px;">Candidatos Seleccionados para Contratación</h2>
            <div class="contenedor-candidatos" id="contenedorCandidatos">
                <!-- Se insertan desde JS -->
            </div>
        </main>

    </div>
</section>
<script src="js/vacanteDinamica.js"></script>
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
                        window.location.href = 'loginATS.php';
                    } else {
                        alert('Error al cerrar sesión. Inténtalo nuevamente.');
                    }
                })
                .catch(error => console.error('Error al cerrar sesión:', error));
        });
    }
</script>

</body>
</html>
