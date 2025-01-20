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
    </div>
    <div class="header-right">
        <div class="user-profile">
            <img src="user-photo.png" alt="Foto de usuario" class="user-photo">
            <div class="user-menu" id="userMenu">
                <a href="#">Ver Perfil</a>
                <a href="#">Cerrar Sesión</a>
            </div>
        </div>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
</header>
<nav class="side-menu" id="sideMenu">
    <ul>
        <li><a href="#">Inicio</a></li>
        <li><a href="#">Seguimiento</a></li>
        <li><a href="#">Históricos</a></li>
    </ul>
</nav>
<main class="main-content">
    <section class="form-section">
        <h2>Registro de Solicitudes</h2>
        <form class="registro-form">
            <label for="tipo-contratacion">Tipo de Contratación:</label>
            <select id="tipo-contratacion" name="tipo-contratacion">
                <option value="nuevo">Nuevo Puesto</option>
                <option value="reemplazo">Reemplazo</option>
            </select>

            <label for="area">Área:</label>
            <input type="text" id="area" name="area" placeholder="Ejemplo: Recursos Humanos">

            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ejemplo: Juan Pérez">

            <label for="puesto">Puesto:</label>
            <input type="text" id="puesto" name="puesto" placeholder="Ejemplo: Analista">

            <button type="submit" class="btn-registrar">Registrar</button>
        </form>
    </section>
</main>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const sideMenu = document.getElementById('sideMenu');

    menuToggle.addEventListener('click', () => {
        sideMenu.classList.toggle('active');
    });
</script>
</body>
</html>

