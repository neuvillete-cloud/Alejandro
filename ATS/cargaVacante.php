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
    </ul>
</nav>

<!-- Contenido Principal -->
<main class="vacante-container">
    <section class="formulario-vacante">
        <h2>Cargar Nueva Vacante</h2>
        <form id="vacanteForm" class="form-cv-layout grid-cv">
            <!-- FOTO -->
            <div class="foto-cv" id="drop-area">
                <img id="preview" class="preview-img" src="#" alt="Preview" />
                <span class="placeholder-text">Haz clic o arrastra tu imagen aquí</span>
                <input type="file" id="imagen" name="imagen" accept="image/*" />
            </div>

            <!-- Fila 1 -->
            <div class="input-group full-width">
                <label for="titulo">Título del puesto:</label>
                <input type="text" id="titulo" name="titulo" required />
            </div>


            <div class="input-group">
                <label for="area">Área / Departamento:</label>
                <input type="text" id="area" name="area" required />
            </div>

            <!-- Fila 2 -->
            <div class="input-group">
                <label for="tipo">Tipo de contrato:</label>
                <select id="tipo" name="tipo">
                    <option value="Tiempo completo">Tiempo completo</option>
                    <option value="Medio tiempo">Medio tiempo</option>
                    <option value="Temporal">Temporal</option>
                </select>
            </div>

            <div class="input-group">
                <label for="horario">Horario:</label>
                <input type="text" id="horario" name="horario" />
            </div>

            <!-- Fila 3 -->
            <div class="input-group">
                <label for="sueldo">Sueldo:</label>
                <input type="text" id="sueldo" name="sueldo" />
            </div>

            <div class="input-group">
                <label for="pais">País / Región:</label>
                <input type="text" id="pais" name="pais" required />
            </div>

            <!-- Fila 4 -->
            <div class="input-group">
                <label for="estado">Estado / Provincia:</label>
                <input type="text" id="estado" name="estado" required />
            </div>

            <div class="input-group">
                <label for="ciudad">Ciudad:</label>
                <input type="text" id="ciudad" name="ciudad" required />
            </div>

            <!-- Requisitos y Beneficios -->
            <div class="input-group textarea-group">
                <label for="requisitos">Requisitos:</label>
                <textarea id="requisitos" name="requisitos"></textarea>
            </div>

            <div class="input-group textarea-group">
                <label for="beneficios">Beneficios:</label>
                <textarea id="beneficios" name="beneficios"></textarea>
            </div>

            <!-- Descripción -->
            <div class="input-group textarea-group full-width">
                <label for="descripcion">Descripción del puesto:</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
            </div>

            <button type="submit">Guardar Vacante</button>
        </form>
    </section>
</main>




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
        // Vista previa en tiempo real
        const campos = [
            { id: 'titulo', prev: 'prevTitulo' },
            { id: 'area', prev: 'prevArea' },
            { id: 'tipo', prev: 'prevTipo' },
            { id: 'horario', prev: 'prevHorario' },
            { id: 'sueldo', prev: 'prevSueldo' },
            { id: 'requisitos', prev: 'prevRequisitos' },
            { id: 'beneficios', prev: 'prevBeneficios' },
            { id: 'descripcion', prev: 'prevDescripcion' }
        ];
        campos.forEach(campo => {
            document.getElementById(campo.id).addEventListener('input', function () {
                document.getElementById(campo.prev).textContent = this.value;
            });
        });

        // Vista previa ubicación combinada
        function actualizarUbicacion() {
            const pais = document.getElementById('pais').value;
            const estado = document.getElementById('estado').value;
            const ciudad = document.getElementById('ciudad').value;
            const ubicacionFinal = `${ciudad}, ${estado}, ${pais}`;
            document.getElementById('prevUbicacion').textContent = ubicacionFinal;
        }
        ['pais', 'estado', 'ciudad'].forEach(id => {
            document.getElementById(id).addEventListener('input', actualizarUbicacion);
        });

        // Vista previa de imagen
        document.getElementById('imagen').addEventListener('change', function () {
            const file = this.files[0];
            const previewImg = document.getElementById('previewImg');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
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
</body>
</html>

