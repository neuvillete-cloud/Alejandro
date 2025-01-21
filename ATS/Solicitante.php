<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitantes</title>
    <link rel="stylesheet" href="css/estilosSolicitante.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1>Solicitantes</h1>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
    <div class="header-right">
        <div class="user-profile">
            <img src="user-photo.png" alt="Foto de usuario" class="user-photo">
            <div class="user-menu" id="userMenu">
                <a href="#">Ver Perfil</a>
                <a href="#">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</header>
<nav class="sidebar" id="sidebar">
    <ul>
        <li><a href="#">Inicio</a></li>
        <li><a href="#">Seguimiento</a></li>
        <li><a href="#">Históricos</a></li>
        <li><a href="#">Configuraciones</a></li>
    </ul>
</nav>
<main class="main-content">
    <section class="welcome-section">
        <h2>Bienvenido, Usuario</h2>
        <p>Aquí puedes gestionar tus solicitudes y realizar un seguimiento de las mismas.</p>
    </section>
    <section class="widgets-section">
        <div class="widget">
            <h3>Resumen de Solicitudes</h3>
            <p>Últimos cambios realizados en tus solicitudes.</p>
            <a href="#">Ver detalles</a>
        </div>
        <div class="widget">
            <h3>Solicitudes Recientes</h3>
            <p>No hay solicitudes nuevas en este momento.</p>
            <a href="#">Ver historial</a>
        </div>
        <div class="widget">
            <h3>Estado del Sistema</h3>
            <p>Todo está funcionando correctamente.</p>
            <a href="#">Más información</a>
        </div>
    </section>
</main>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
</script>
</body>
</html>
