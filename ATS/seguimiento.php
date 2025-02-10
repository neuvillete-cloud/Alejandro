<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php'); // Redirige al login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento - Porcentajes</title>
    <link rel="stylesheet" href="css/estilosSeguimiento.css">
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
        <li><a href="Solicitante.php" >Inicio</a></li>
        <li><a href="seguimiento.php">Seguimiento</a></li>
        <li><a href="historicos.php" id="historicosLink">Históricos</a></li>
        <li><a href="#" data-page="configuraciones.php">Configuraciones</a></li>
    </ul>
</nav>
<h1>Seguimiento de Progreso</h1>
<div class="progress-container">
    <div class="circle">
        <svg>
            <circle cx="50" cy="50" r="45"></circle>
            <circle cx="50" cy="50" r="45" class="progress"></circle>
        </svg>
        <div class="percentage">0%</div>
    </div>
    <div class="circle">
        <svg>
            <circle cx="50" cy="50" r="45"></circle>
            <circle cx="50" cy="50" r="45" class="progress"></circle>
        </svg>
        <div class="percentage">0%</div>
    </div>
    <div class="circle">
        <svg>
            <circle cx="50" cy="50" r="45"></circle>
            <circle cx="50" cy="50" r="45" class="progress"></circle>
        </svg>
        <div class="percentage">0%</div>
    </div>
</div>


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
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const percentages = [75, 50, 90]; // Porcentajes a llenar
        const circles = document.querySelectorAll(".circle");

        circles.forEach((circle, index) => {
            const progressCircle = circle.querySelector(".progress");
            const percentageText = circle.querySelector(".percentage");

            const radius = progressCircle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;

            progressCircle.style.strokeDasharray = `${circumference}`;
            progressCircle.style.strokeDashoffset = circumference;

            let progress = 0;
            const target = percentages[index];

            const interval = setInterval(() => {
                if (progress <= target) {
                    const offset = circumference - (progress / 100) * circumference;
                    progressCircle.style.strokeDashoffset = offset;
                    percentageText.textContent = `${progress}%`;
                    progress++;
                } else {
                    clearInterval(interval);
                }
            }, 20); // Velocidad de animación
        });
    });
</script>

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
