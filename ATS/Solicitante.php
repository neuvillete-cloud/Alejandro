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
        <li><a href="#" data-page="Solicitante.php">Inicio</a></li>
        <li><a href="#" data-page="seguimiento.php">Seguimiento</a></li>
        <li><a href="#" data-page="historicos.php">Históricos</a></li>
        <li><a href="#" data-page="configuraciones.php">Configuraciones</a></li>
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

        // Cargar pestañas sin recargar la página
        const links = document.querySelectorAll('.sidebar a');
        const mainContent = document.getElementById('mainContent');

        if (links.length > 0 && mainContent) {
            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');

                    if (page) {
                        fetch(page)
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');

                                // Buscamos el contenido de la nueva página dentro de .main-content
                                let newContent = doc.querySelector('.main-content');
                                if (!newContent) {
                                    newContent = doc.body; // Si no tiene .main-content, usamos el body entero
                                }

                                if (newContent) {
                                    mainContent.innerHTML = newContent.innerHTML; // Reemplazamos solo el contenido
                                    ejecutarScripts(mainContent);
                                    loadStyles();
                                } else {
                                    console.error('No se encontró contenido en la página cargada.');
                                }
                            })
                            .catch(error => console.error('Error al cargar la página:', error));
                    }
                });
            });
        }

        function ejecutarScripts(container) {
            const scripts = container.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                    newScript.async = true;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.body.appendChild(newScript);
                document.body.removeChild(newScript);
            });

            // 🔥 Volvemos a rellenar los datos después de cambiar de pestaña
            fetchUserData();
        }


        // Función para recargar los estilos y evitar que desaparezcan.
        function loadStyles() {
            let link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = "css/estilosSolicitante.css";
            document.head.appendChild(link);
        }
    });



</script>

<script src="js/funcionamientoModal.js"></script>
<script src="js/jsSolicitante.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
