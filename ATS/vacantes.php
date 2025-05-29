<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/vacantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
session_start();
?>

<header>
    <div class="header-container">
        <div class="logo">
            <h1>Grammer</h1>
            <span>Automotive</span>
        </div>
        <nav>
            <a href="#">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Programa de posgrado</a>
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
                        <a href="#">Historial de solicitudes</a>
                        <a href="cerrarSesion.php">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginATS.php">Inicio de sesión</a>
            <?php endif; ?>

            <a href="#">🌐 Español ▾</a>
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
                    <input type="text" placeholder="practicante de ingeniería">
                    <i class="fas fa-times cerrar-busqueda"></i>
                </div>

                <div class="campo-ubicacion">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" placeholder="Querétaro">
                    <i class="fas fa-times cerrar-ubicacion"></i>
                </div>

                <button class="btn-buscar">Buscar empleo</button>
            </div>

            <div class="filtros">
                <button class="filtro">$5,000 - $10,000 <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Fecha <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Presencial/Desde casa <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Tipo de Contratación <i class="fas fa-chevron-down"></i></button>
                <button class="filtro">Educación <i class="fas fa-chevron-down"></i></button>
                <button class="filtro limpiar">Limpiar filtros</button>
            </div>
        </div>

    </div>
</section>



</body>
</html>
