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
    <link rel="stylesheet" href="css/estilosHistoricos.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <img src="imagenes/grammer.png" alt="Icono de Solicitudes" class="header-icon">
        <h1>Solicitudes</h1>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
    <div class="header-right">
        <div class="user-profile" id="profilePic">
            <img src="https://grammermx.com/Fotos/<?php echo $_SESSION['NumNomina']; ?>.png" alt="Foto de Usuario">
        </div>
        <div class="user-name" id="userNameHeader"></div>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="#">Ver Perfil</a>
            <a href="#" id="logout">Cerrar Sesión</a>
        </div>
    </div>
</header>
<nav class="sidebar" id="sidebar">
    <ul>
        <li><a href="#" data-page="Solicitante.php" >Inicio</a></li>
        <li><a href="seguimiento.php">Seguimiento</a></li>
        <li><a href="historicos.php" id="historicosLink">Históricos</a></li>
        <li><a href="seleccionFinal.php">Candidatos Finales</a></li>
    </ul>
</nav>
<main class="main-content" id="mainContent">
    <section class="form-container">
        <h1>Registrar Solicitud</h1>
        <form id="solicitudForm">
            <label for="nombre">Nombre del Solicitante</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required>

            <label for="area">Área</label>
            <input type="text" id="area" name="area" placeholder="Ingresa el área correspondiente" required>

            <label for="puesto">Puesto</label>
            <input type="text" id="puesto" name="puesto" placeholder="Ingresa el puesto solicitado" required>

            <label for="tipo">Tipo de Solicitud</label>
            <select id="tipo" name="tipo" required>
                <option value="" disabled selected>Selecciona una opción</option>
                <option value="nuevo">Nuevo puesto</option>
                <option value="reemplazo">Reemplazo</option>
            </select>

            <div id="reemplazoFields" style="display: none;">
                <label for="reemplazoNombre">Nombre de la Persona Reemplazada</label>
                <input type="text" id="reemplazoNombre" name="reemplazoNombre" placeholder="Ingresa el nombre del reemplazo">
            </div>

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

<!-- Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Manejo del cambio de tipo de solicitud
        const tipoSelect = document.getElementById('tipo');
        const reemplazoFields = document.getElementById('reemplazoFields');

        if (tipoSelect) {
            tipoSelect.addEventListener('change', () => {
                reemplazoFields.style.display = tipoSelect.value === 'reemplazo' ? 'block' : 'none';
            });
        }

        // Menú lateral (sidebar)
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Menú de perfil
        const userProfile = document.getElementById('profilePic');
        const profileDropdown = document.getElementById('profileDropdown');

        if (userProfile && profileDropdown) {
            userProfile.addEventListener('click', () => {
                profileDropdown.classList.toggle('active');
            });

            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target) && !userProfile.contains(e.target)) {
                    profileDropdown.classList.remove('active');
                }
            });
        }

        // Cerrar sesión con fetch
        const logoutLink = document.getElementById('logout');

        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) {
                            window.location.href = 'login.php';
                        } else {
                            alert('Error al cerrar sesión. Inténtalo nuevamente.');
                        }
                    })
                    .catch(error => console.error('Error al cerrar sesión:', error));
            });
        }

    });
</script>

<script src="js/funcionamientoModal.js"></script>
<script src="js/jsSolicitante.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
