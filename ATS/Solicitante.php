<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Solicitudes</title>
    <link rel="stylesheet" href="css/estilosSolicitante.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1>Solicitudes</h1>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
    <div class="header-right">
        <div class="user-profile" id="profilePic">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario">
        </div>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="#" id="viewProfile">Ver Perfil</a>
            <a href="#">Cerrar Sesión</a>
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
    <section class="form-container">
        <h1>Registrar Solicitud</h1>
        <form id="solicitudForm">
            <label for="nombre">Nombre del Solicitante</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required>

            <label for="area">Área</label>
            <input type="text" id="area" name="area" placeholder="Ingresa el área correspondiente" required>

            <label for="tipo">Tipo de Solicitud</label>
            <select id="tipo" name="tipo" required>
                <option value="" disabled selected>Selecciona una opción</option>
                <option value="nuevo">Nuevo puesto</option>
                <option value="reemplazo">Reemplazo</option>
            </select>

            <div id="reemplazoFields" style="display: none;">
                <label for="reemplazoNombre">Nombre de la Persona Reemplazada</label>
                <input type="text" id="reemplazoNombre" name="reemplazoNombre" placeholder="Ingresa el nombre del reemplazo">

                <label for="reemplazoPuesto">Puesto a Reemplazar</label>
                <input type="text" id="reemplazoPuesto" name="reemplazoPuesto" placeholder="Ingresa el puesto a reemplazar">
            </div>

            <button type="submit" class="btn-submit">Registrar</button>
        </form>
    </section>
</main>

<!-- Modal para ver el perfil -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span id="closeModal" class="close">&times;</span>
        <h2>Perfil de Usuario</h2>
        <p>Nombre: <?php echo $_SESSION['nombre']; ?></p>
        <p>Número de Nómina: <?php echo $_SESSION['NumNomina']; ?></p>
        <p>Correo: <?php echo $_SESSION['correo']; ?></p>
    </div>
</div>

<script>
    const tipoSelect = document.getElementById('tipo');
    const reemplazoFields = document.getElementById('reemplazoFields');

    tipoSelect.addEventListener('change', () => {
        reemplazoFields.style.display = tipoSelect.value === 'reemplazo' ? 'block' : 'none';
    });

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    const userProfile = document.getElementById('profilePic');
    const profileDropdown = document.getElementById('profileDropdown');

    userProfile.addEventListener('click', () => {
        profileDropdown.classList.toggle('active');
    });

    const viewProfile = document.getElementById('viewProfile');
    const profileModal = document.getElementById('profileModal');
    const closeModal = document.getElementById('closeModal');

    viewProfile.addEventListener('click', (e) => {
        e.preventDefault();
        profileModal.style.display = 'flex';
    });

    closeModal.addEventListener('click', () => {
        profileModal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === profileModal) {
            profileModal.style.display = 'none';
        }
    });
</script>
</body>
</html>
