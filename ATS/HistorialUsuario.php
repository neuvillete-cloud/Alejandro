<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/HistorialUsuario.css">
</head>
<body>

<?php
session_start();
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
                        <a href="#">Historial de solicitudes</a>
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
    <h1>Historial de Postulación</h1>
    <img src="imagenes/historial-de-transacciones%20(1).png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <div class="mensaje-usuario">
            <p>¡Gracias por confiar en nosotros! Aquí puedes revisar el estado de tus postulaciones.</p>
        </div>

        <div class="contenedor-cards">
            <div class="card-solicitud">
                <div class="cabecera-card">
                    <h3>Operador de Ensamble</h3>
                    <span class="estatus estatus-recibido">Recibido</span>
                </div>
                <p><strong>Área:</strong> Producción</p>
                <p><strong>Fecha de Postulación:</strong> 10/07/2025</p>
                <p><strong>Modalidad:</strong> Presencial</p>
                <button class="btn-ver" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDetalles">Ver Detalles</button>
            </div>

            <div class="card-solicitud">
                <div class="cabecera-card">
                    <h3>Ingeniero de Calidad</h3>
                    <span class="estatus estatus-aprobado">Aprobado</span>
                </div>
                <p><strong>Área:</strong> Ingeniería</p>
                <p><strong>Fecha de Postulación:</strong> 02/07/2025</p>
                <p><strong>Modalidad:</strong> Híbrido</p>
                <button class="btn-ver" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDetalles">Ver Detalles</button>
            </div>

            <div class="card-solicitud">
                <div class="cabecera-card">
                    <h3>Diseñador CAD</h3>
                    <span class="estatus estatus-rechazado">Rechazado</span>
                </div>
                <p><strong>Área:</strong> Diseño</p>
                <p><strong>Fecha de Postulación:</strong> 28/06/2025</p>
                <p><strong>Modalidad:</strong> Remota</p>
                <button class="btn-ver" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDetalles">Ver Detalles</button>
            </div>
        </div>

    </div>
</section>

<!-- Offcanvas Inferior -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasDetalles" aria-labelledby="offcanvasDetallesLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasDetallesLabel">Detalles de la Postulación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body small">
        <p><strong>Vacante:</strong> Operador de Ensamble</p>
        <p><strong>Área:</strong> Producción</p>
        <p><strong>Fecha de Postulación:</strong> 10/07/2025</p>
        <p><strong>Modalidad:</strong> Presencial</p>
        <p><strong>Descripción:</strong> Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus eget...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
