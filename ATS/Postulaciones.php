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
    <title>Administrador</title>

    <link rel="stylesheet" href="css/cargaVacante.css">
</head>
<body>

<!-- Header -->
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

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <ul>
        <li><a href="Administrador.php">Inicio</a></li>
        <li><a href="SAprobadas.php">S. Aprobadas</a></li>
        <li><a href="SeguimientoAdministrador.php">Seguimiento</a></li>
        <li><a href="cargaVacante.php">Carga de Vacantes</a></li>
        <li><a href="Postulaciones.php">Candidatos Postulados</a></li>
    </ul>
</nav>





<!-- Modal Perfil -->
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
        const form = document.getElementById("vacanteForm");
        const modal = document.getElementById("modalVistaPreviaVacante");

        const cerrarBtn = document.getElementById("cerrarModalVacante");
        const cancelarBtn = document.getElementById("cancelarVistaPreviaVacante");
        const confirmarBtn = document.getElementById("confirmarGuardarVacante");

        form.addEventListener("submit", function (e) {
            e.preventDefault();

            // Llenar los datos en el modal
            document.getElementById("previewTitulo").textContent = form.titulo.value;
            document.getElementById("previewArea").textContent = form.area.value;
            document.getElementById("previewTipo").textContent = form.tipo.value;
            document.getElementById("previewHorario").textContent = form.horario.value;
            document.getElementById("previewescolaridad").textContent = form.escolaridad.value;
            document.getElementById("previewIdioma").textContent = form.idioma.value;
            document.getElementById("previewEspecialidad").textContent = form.especialidad.value;
            document.getElementById("previewEspacio").textContent = form.espacio.value;
            document.getElementById("previewSueldo").textContent = form.sueldo.value;
            document.getElementById("previewPais").textContent = form.pais.value;
            document.getElementById("previewEstado").textContent = form.estado.value;
            document.getElementById("previewCiudad").textContent = form.ciudad.value;
            document.getElementById("previewRequisitos").textContent = form.requisitos.value;
            document.getElementById("previewBeneficios").textContent = form.beneficios.value;
            document.getElementById("previewDescripcion").textContent = form.descripcion.value;

            const file = form.imagen.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById("previewImagenVacante").src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById("previewImagenVacante").src = "#";
            }

            modal.style.display = "flex";
        });

        cerrarBtn.addEventListener("click", () => {
            modal.style.display = "none";
        });

        cancelarBtn.addEventListener("click", () => {
            modal.style.display = "none";
        });

        confirmarBtn.addEventListener("click", () => {
            modal.style.display = "none";
            // No se hace submit tradicional aquí porque vacante.js ya lo maneja con fetch
        });


        window.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });

</script>

<!-- Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {

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
<script>
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('imagen');
    const previewImg = document.getElementById('preview');
    const placeholder = dropArea.querySelector('.placeholder-text');

    dropArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', handleFile);

    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropArea.style.backgroundColor = 'rgba(30, 144, 255, 0.2)';
    });

    dropArea.addEventListener('dragleave', () => {
        dropArea.style.backgroundColor = '';
    });

    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.style.backgroundColor = '';
        const file = e.dataTransfer.files[0];
        fileInput.files = e.dataTransfer.files;
        handleFile();
    });

    function handleFile() {
        const file = fileInput.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    }
</script>

<script src="js/funcionamientoModal.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/vacante.js"></script>
</body>
</html>


