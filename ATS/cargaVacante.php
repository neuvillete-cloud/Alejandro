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
            <div class="input-group titulo-full">
                <label for="titulo">Título del puesto:</label>
                <input type="text" id="titulo" name="titulo" required />
            </div>


            <div class="input-group">
                <label for="area">Área / Departamento:</label>
                <select id="area" name="area" required>
                    <option value="">Selecciona un área</option>
                    <option value="Seguridad e Higiene">Seguridad e Higiene</option>
                    <option value="GPS">GPS</option>
                    <option value="IT">IT</option>
                    <option value="RH">RH</option>
                    <option value="Calidad">Calidad</option>
                    <option value="Ingenieria">Ingenieria</option>
                    <option value="Controlling">Controlling</option>
                    <option value="Logistica">Logistica</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="Producción (APU)">Producción (APU)</option>
                    <option value="Finanzas">Finanzas</option>
                    <option value="Compras">Compras</option>
                    <option value="Regionales">Regionales</option>
                </select>
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
                <label for="escolaridad">Escolaridad Minima:</label>
                <input type="text" id="escolaridad" name="escolaridad" required />
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

            <div class="input-group">
                <label for="espacio">Espacio de trabajo:</label>
                <input type="text" id="espacio" name="espacio" required />
            </div>

            <div class="input-group">
                <label for="idioma">Idioma:</label>
                <input type="text" id="idioma" name="idioma" required />
            </div>

            <div class="input-group">
                <label for="especialidad">Especialidad:</label>
                <input type="text" id="especialidad" name="especialidad" required />
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

<!-- MODAL DE VISTA PREVIA DE VACANTE -->
<div class="modal-vista-previa-vacante" id="modalVistaPreviaVacante">
    <div class="modal-contenido-vacante">
        <span class="cerrar-modal-vacante" id="cerrarModalVacante">&times;</span>

        <div class="preview-body-vacante">
            <!-- NUEVO Encabezado con imagen e info al lado -->
            <div class="encabezado-vacante-flex">
                <!-- Imagen -->
                <div class="preview-imagen-vacante">
                    <img id="previewImagenVacante" src="#" alt="Imagen de Vacante">
                </div>

                <!-- Encabezado de la vacante al lado de la imagen -->
                <div class="preview-header-vacante">
                    <h2 id="previewTitulo">Título de la Vacante</h2>
                    <div class="preview-subinfo-vacante">
                        <div class="empresa-fija-vacante">Grammer Automotive Puebla S.A. de C.V.</div>
                        <div>
                            <span id="previewCiudad">Ciudad</span>,
                            <span id="previewEstado">Estado</span>,
                            <span id="previewPais">Pais</span>
                        </div>
                    </div>
                    <div class="preview-sueldo-tipo-vacante">
                        <span id="previewSueldo">$XX,XXX</span> ·
                        <span id="previewTipo">Tipo de Contrato</span>
                    </div>
                    <div class="preview-actions-vacante">
                        <button class="boton-postular-vacante">Postularse ahora</button>
                        <button class="boton-icono-vacante">🔖</button>
                        <button class="boton-icono-vacante">🚫</button>
                        <button class="boton-icono-vacante">🔗</button>
                    </div>
                </div>
            </div>

            <!-- Sección: Sobre el empleo -->
            <div class="seccion-empleo-vacante">
                <h3 class="titulo-seccion-vacante">Sobre el empleo</h3>

                <div class="info-empleo-fila">
                    <div class="info-item-vacante">
                        <strong>Área / Departamento:</strong>
                        <span id="previewArea"></span>
                    </div>

                    <div class="info-item-vacante">
                        <strong>Escolaridad mínima:</strong>
                        <span id="previewescolaridad"></span>
                    </div>
                </div>

                <div class="info-item-vacante">
                    <strong>Idioma:</strong> <span id="previewIdioma"></span>
                </div>

                <div class="info-item-vacante">
                    <strong>Especialidad:</strong> <span id="previewEspecialidad"></span>
                </div>

                <hr class="linea-divisoria-vacante">
            </div>


            <!-- DETALLES DEL EMPLEO -->
            <div class="seccion-empleo-vacante">
                <h3 class="titulo-seccion-vacante">Detalles del empleo</h3>

                <div class="info-item-vacante">
                    <strong>Horario:</strong> <span id="previewHorario"></span>
                </div>
                <div class="info-item-vacante">
                    <strong>Espacio de trabajo:</strong> <span id="previewEspacio"></span>
                </div>

                <hr class="linea-divisoria-vacante">
            </div>



            <div class="preview-seccion-vacante">
                <h3>Requisitos</h3>
                <p id="previewRequisitos"></p>
            </div>
            <hr class="linea-divisoria-vacante">

            <div class="preview-seccion-vacante">
                <h3>Beneficios</h3>
                <p id="previewBeneficios"></p>
            </div>
            <hr class="linea-divisoria-vacante">

            <div class="preview-seccion-vacante">
                <h3>Descripción del puesto</h3>
                <p id="previewDescripcion"></p>
            </div>
            <hr class="linea-divisoria-vacante">


            <!-- Botones -->
            <div class="botones-modal-vacante">
                <button id="cancelarVistaPreviaVacante">Cancelar</button>
                <button id="confirmarGuardarVacante">Confirmar</button>
            </div>
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

