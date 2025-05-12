<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siemens Energy Clone Mejorado</title>
    <link rel="stylesheet" href="css/vacantes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
session_start(); // Importante para poder leer las variables de sesión
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
                <a href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['NombreCandidato']) ?></a>
            <?php else: ?>
                <a href="loginATS.php">Inicio de sesión</a>
            <?php endif; ?>

            <a href="#">🌐 Español ▾</a>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Vacantes en Grammer Automotive MX</h1>
</section>

<!-- Aquí puedes seguir con el resto del contenido de la página -->

</body>
</html>
