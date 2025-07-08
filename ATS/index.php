<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATS</title>
    <link rel="stylesheet" href="css/estilos.css">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php session_start(); ?>

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
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="registroUsuarios.php">Regístrate</a>

            <?php if (isset($_SESSION['NombreCandidato'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php"><i class="fas fa-id-badge"></i> Perfil</a>
                        <a href="#"><i class="fas fa-bell"></i> Alertas de empleo</a>
                        <a href="#"><i class="fas fa-history"></i> Historial de solicitudes</a>
                        <a href="#" id="logout"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Inicia Sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="main">
    <h1 class="title"><i class="fas fa-users"></i> Sistema de Reclutamiento y Selección</h1>
    <p class="subtitle">Bienvenido de Nuevo a tu ATS</p>
    <a href="login.php" class="button">Ingresar de nuevo</a>
</main>
</body>
</html>
