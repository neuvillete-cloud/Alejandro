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
        <div class="buscador-vacantes">
            <div class="fila-superior">
                <div class="campo-busqueda">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="practicante de ingeniería" autocomplete="off">
                    <i class="fas fa-times cerrar-busqueda"></i>
                    <ul class="historial-busquedas"></ul>
                </div>

                <div class="campo-ubicacion">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="Nombre del candidato" autocomplete="off">
                    <i class="fas fa-times cerrar-ubicacion"></i>
                    <ul class="historial-ubicaciones"></ul>
                </div>


                <button class="btn-buscar">Buscar Candidato</button>
            </div>

            <!-- FILTROS FUNCIONALES -->
            <div class="filtros">
                <!-- Área -->
                <select id="filtro-area" class="filtro">
                    <option value="" disabled selected>Área</option>
                    <option value="Ingeniería">Ingeniería</option>
                    <option value="Calidad">Calidad</option>
                    <option value="Producción">Producción</option>
                    <option value="Recursos Humanos">Recursos Humanos</option>
                </select>

                <!-- Fecha -->
                <select id="filtro-fecha" class="filtro">
                    <option value="" disabled selected>Ordenar por</option>
                    <option value="recientes">Más recientes</option>
                    <option value="antiguas">Más antiguas</option>
                </select>

                <!-- Botón limpiar -->
                <button id="limpiar-filtros" class="filtro limpiar">Limpiar filtros</button>
            </div>
        </div>

        <main class="main-candidatos">
            <h2 style="text-align: center; margin-top: -10px; margin-bottom: 30px;">
                Candidatos Seleccionados para Contratación
            </h2>
            <div class="contenedor-candidatos" id="contenedorCandidatos">
                <!-- Se insertan desde JS -->
            </div>
        </main>

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
