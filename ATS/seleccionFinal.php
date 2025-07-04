<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatos Finales</title>
    <link rel="stylesheet" href="css/seleccionFinal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>

<header class="header">
    <div class="header-left">
        <img src="imagenes/grammer.png" alt="Icono de Solicitudes" class="header-icon">
        <h1>R.H Admin</h1>
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
        <li><a href="Solicitante.php">Inicio</a></li>
        <li><a href="seguimiento.php">Seguimiento</a></li>
        <li><a href="historicos.php" id="historicosLink">Históricos</a></li>
        <li><a href="seleccionFinal.php">Candidatos Finales</a></li>
    </ul>
</nav>

<div class="content">
    <div class="titulo-candidatos">
        <h2>Candidatos Finales</h2>
    </div>

    <!-- Contenedor tipo tabla -->
    <div class="cards-container">
        <div class="cards-header">
            <div class="col col-icono"></div>
            <div class="col col-nombre">Nombre</div>
            <div class="col col-vacante">Vacante</div>
            <div class="col col-estatus">Estatus</div>
            <div class="col col-acciones">Acciones</div>
        </div>

        <div id="candidatosContainer" class="cards-body"></div>
    </div>
</div>

<!-- Modal de Perfil -->
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/funcionamientoModal.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const profilePic = document.getElementById('profilePic');
        const profileDropdown = document.getElementById('profileDropdown');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        if (profilePic && profileDropdown) {
            profilePic.addEventListener('click', () => {
                profileDropdown.classList.toggle('active');
            });

            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target) && !profilePic.contains(e.target)) {
                    profileDropdown.classList.remove('active');
                }
            });
        }

        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', function (e) {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(res => {
                        if (res.ok) {
                            window.location.href = 'login.php';
                        }
                    });
            });
        }

        function obtenerClaseEstatus(nombreEstatus) {
            switch (nombreEstatus.toLowerCase()) {
                case 'recibido': return 'estatus-recibido';
                case 'aprobado': return 'estatus-aprobado';
                case 'rechazado': return 'estatus-rechazado';
                default: return 'estatus-default';
            }
        }

        function renderizarCandidatos(data) {
            const contenedor = document.getElementById('candidatosContainer');
            contenedor.innerHTML = '';

            data.forEach(candidato => {
                const clase = obtenerClaseEstatus(candidato.NombreEstatus);
                const card = `
                <div class="candidato-card">
                    <div class="col col-icono">
    <div class="card-icon">
        <i class="fas fa-user"></i>
    </div>
</div>
<div class="col col-nombre">
    <div class="nombre-texto">${candidato.Nombre}</div>
</div>


                    <div class="col col-vacante">${candidato.TituloVacante}</div>
                    <div class="col col-estatus ${clase}">${candidato.NombreEstatus}</div>
                    <div class="col col-acciones">
                        <a href="detallePostulacion.php?IdPostulacion=${candidato.IdPostulacion}" class="ver-detalles-btn">
                            Ver Detalles
                        </a>
                    </div>
                </div>`;
                contenedor.insertAdjacentHTML('beforeend', card);
            });
        }

        fetch('https://grammermx.com/AleTest/ATS/dao/CandidatosFinales.php')
            .then(res => res.json())
            .then(json => {
                if (json && json.data) {
                    renderizarCandidatos(json.data);
                }
            })
            .catch(err => console.error("Error:", err));
    });
</script>

</body>
</html>
