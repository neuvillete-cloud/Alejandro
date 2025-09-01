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
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="indexAts.php">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Escuela de Talentos</a>
            <a href="inclusionDiversidad.php">Inclusión y diversidad</a>

            <?php if (isset($_SESSION['NombreCandidato'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="#">Perfil</a>
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
    <div class="perfil-icono">
        <img src="imagenes/perfil.png" alt="Imagen decorativa" class="imagen-banner">
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
                <li><a href="indexAts.php">Inicio</a></li>
                <li><a href="aboutUs.php">Acerca de Nosotros</a></li>
                <li><a href="practicantes.php">Escuela de Talentos</a></li>
                <li><a href="inclusionDiversidad.php">Inclusion y Diversidad</a></li>
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
