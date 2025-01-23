
<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php'); // Redirige al login si no está autenticado
    exit;
}
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
            <span id="userNameHeader" class="user-name"></span> <!-- Nombre antes de la foto -->
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario">
        </div>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="#">Ver Perfil</a>
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
            <!-- Campo Nombre -->
            <label for="nombre">Nombre del Solicitante</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required>

            <!-- Campo Área -->
            <label for="area">Área</label>
            <input type="text" id="area" name="area" placeholder="Ingresa el área correspondiente" required>

            <!-- Campo Tipo -->
            <label for="tipo">Tipo de Solicitud</label>
            <select id="tipo" name="tipo" required>
                <option value="" disabled selected>Selecciona una opción</option>
                <option value="nuevo">Nuevo puesto</option>
                <option value="reemplazo">Reemplazo</option>
            </select>

            <!-- Campo Reemplazo (solo visible si el tipo es "Reemplazo") -->
            <div id="reemplazoFields" style="display: none;">
                <label for="reemplazoNombre">Nombre de la Persona Reemplazada</label>
                <input type="text" id="reemplazoNombre" name="reemplazoNombre" placeholder="Ingresa el nombre del reemplazo">

                <label for="reemplazoPuesto">Puesto a Reemplazar</label>
                <input type="text" id="reemplazoPuesto" name="reemplazoPuesto" placeholder="Ingresa el puesto a reemplazar">
            </div>
            <!-- Botón para enviar el formulario -->
            <button type="submit" class="btn-submit">Registrar</button>
        </form>
    </section>
</main>

<!-- Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h2>Perfil del Usuario</h2>
        <div class="modal-body">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario" class="user-photo">
            <p><strong>Nombre:</strong> <span id="userName"></span></p>
            <p><strong>Número de Nómina:</strong> <span id="userNumNomina"></span></p>
            <p><strong>Área:</strong> <span id="userArea"></span></p>
        </div>
    </div>
</div>


<!-- Script para mostrar campos condicionales -->
<script>
    const tipoSelect = document.getElementById('tipo');
    const reemplazoFields = document.getElementById('reemplazoFields');

    tipoSelect.addEventListener('change', () => {
        if (tipoSelect.value === 'reemplazo') {
            reemplazoFields.style.display = 'block';
        } else {
            reemplazoFields.style.display = 'none';
        }
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

</script>
<script src="js/funcionamientoModal.js"></script>
</body>
</html>
