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

        <p><strong>Tal como se describe en los Avisos de privacidad de datos, se pueden utilizar mis datos personales:</strong><br>
            Hacer que mis datos sean accesibles a todas las empresas relevantes del grupo Siemens Energy en todo el mundo para que me consideren para puestos vacantes que se ajusten a mi perfil.</p>

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




</body>
</html>
