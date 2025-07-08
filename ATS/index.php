<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Música para todos</title>
    <link rel="stylesheet" href="css/estilos.css">
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
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="registroUsuarios.php">Registrate</a>

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
                <a href="login.php">Inicia Sesión</a>
            <?php endif; ?>

        </nav>
    </div>
</header>
<main class="main">
    <h1 class="title">Sistema de Reclutamiento Y Seleccion</h1>
    <p class="subtitle">Bienvenido de Nuevo a tu ATS</p>
    <a href="#" class="button">ingresar de nuevo</a>
</main>
</body>
</html>


