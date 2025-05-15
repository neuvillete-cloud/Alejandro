<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/perfil.css">
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
                        <a href="#">Perfil</a>
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
    <div class="perfil-icono">
        <i class="fas fa-user-circle"></i>
    </div>
    <div class="perfil-texto">
        <span>MI PERFIL</span>
        <h1><?= htmlspecialchars($_SESSION['NombreCandidato'] . ' ' . $_SESSION['ApellidosCandidato']) ?></h1>
    </div>
</section>

<section class="perfil-contenido">
    <div class="info-izquierda">
        <h2><i class="fas fa-user"></i> Información básica</h2>

        <p><strong>Nombre</strong><br><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></p>
        <p><strong>Apellidos</strong><br><?= htmlspecialchars($_SESSION['ApellidosCandidato']) ?></p>

        <p><strong>De acuerdo con lo establecido en los Avisos de privacidad, autorizo el uso de mis datos personales para procesos de selección:</strong><br>
            Mis datos podrán ser compartidos con los departamentos de recursos humanos de Grammer Automotive a nivel global, con el fin de ser considerado(a) en oportunidades laborales que coincidan con mi perfil profesional.</p>

        <hr>

        <h2><i class="fas fa-comment-dots"></i> Información de contacto</h2>
        <p><strong>Correo electrónico</strong><br><?= htmlspecialchars($_SESSION['CorreoCandidato']) ?></p>
        <p><strong>Móvil</strong><br><?= htmlspecialchars($_SESSION['TelefonoCandidato']) ?></p>
    </div>

    <div class="acciones-derecha">
        <button class="btn-editar">Editar perfil</button>
        <button class="btn-reset">Restablecer contraseña</button>
    </div>
</section>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-contact">
            <button class="contact-button"><i class="fas fa-envelope"></i> Contáctenos</button>
            <p><strong>Solo en MX:</strong> Consulta las adaptaciones disponibles para personas con discapacidad</p>
            <p><a href="#">Solicitud para condiciones laborales</a></p>
            <p><a href="https://grammer.com">grammer.com</a> Página web global</p>
        </div>

        <div class="footer-links">
            <a href="#">Información de la empresa</a>
            <a href="#">Política de privacidad</a>
            <a href="#">Aviso sobre cookies</a>
            <a href="#">Condiciones de uso</a>
            <a href="#">ID digital</a>
        </div>

        <div class="footer-social">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-x-twitter"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
        </div>
    </div>

    <hr class="footer-divider">

    <div class="footer-bottom">
        <p>Grammer Automotive es una marca comercial registrada de Grammer AG.</p>
        <p>© Grammer Automotive, 2020 - 2025</p>
    </div>
</footer>
</body>
</html>
