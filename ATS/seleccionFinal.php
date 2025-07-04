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
    <title>Candidatos Finales</title>
    <link rel="stylesheet" href="css/seleccionFinal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<header class="header">
    <div class="header-left">
        <img src="imagenes/grammer.png" alt="Icono de Solicitudes" class="header-icon">
        <h1>R.H Admind</h1>
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
        <li><a href="Solicitante.php" >Inicio</a></li>
        <li><a href="seguimiento.php">Seguimiento</a></li>
        <li><a href="historicos.php" id="historicosLink">Históricos</a></li>
        <li><a href="seleccionFinal.php">Candidatos Finales</a></li>
    </ul>
</nav>

<div class="content">
    <div class="titulo-candidatos">
        <h2>Candidatos Finales</h2>
    </div>

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

<!-- Offcanvas Panel -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasRightLabel">Detalle del Candidato</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <p id="nombreCandidato" class="fw-bold fs-5" style="margin-left: 20px;"></p>

    <div class="offcanvas-body">
        <div id="vistaPreviaPDF"><p>Cargando CV...</p></div>
        <div class="botones-postulacion mb-4">
            <button type="button" class="btn btn-danger me-2" id="btnRechazar">Rechazar</button>
            <button type="button" class="btn btn-success" id="btnAprobar">Aprobar</button>
        </div>
    </div>
</div>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', function (e) {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(res => res.ok && (window.location.href = 'login.php'));
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
                contenedor.insertAdjacentHTML('beforeend', `
          <div class="candidato-card">
            <div class="col col-icono"><div class="card-icon"><i class="fas fa-user"></i></div></div>
            <div class="col col-nombre"><div class="nombre-texto">${candidato.Nombre}</div></div>
            <div class="col col-vacante">${candidato.TituloVacante}</div>
            <div class="col col-estatus"><span class="${clase}">${candidato.NombreEstatus}</span></div>
            <div class="col col-acciones">
              <a href="#" class="ver-detalles-btn" data-id="${candidato.IdPostulacion}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight">Ver Detalles</a>
            </div>
          </div>`);
            });
        }

        fetch('https://grammermx.com/AleTest/ATS/dao/CandidatosFinales.php')
            .then(res => res.json())
            .then(json => json?.data && renderizarCandidatos(json.data))
            .catch(err => console.error("Error:", err));

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('ver-detalles-btn')) {
                e.preventDefault();
                const IdPostulacion = e.target.dataset.id;
                const nombre = e.target.closest('.candidato-card').querySelector('.nombre-texto').textContent;
                document.getElementById("nombreCandidato").textContent = nombre;

                fetch(`dao/ConsultarCv.php?IdPostulacion=${IdPostulacion}`)
                    .then(res => res.json())
                    .then(data => {
                        const contenedorCV = document.getElementById("vistaPreviaPDF");
                        if (data.error) {
                            contenedorCV.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            const ext = data.RutaCV.split('.').pop().toLowerCase();
                            if (ext === 'pdf') {
                                contenedorCV.innerHTML = `<iframe width="100%" height="400" src="${data.RutaCV}" style="border:1px solid #ccc; border-radius:10px;"></iframe>`;
                            } else {
                                contenedorCV.innerHTML = `<p>Formato no soportado: ${ext}</p><a href="${data.RutaCV}" target="_blank" class="btn btn-primary">Descargar CV</a>`;
                            }
                        }
                    });
                document.getElementById("btnAprobar").onclick = () => {
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: 'Vas a aprobar a este candidato.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, aprobar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            actualizarEstatus(IdPostulacion, 9);
                        }
                    });
                };

                document.getElementById("btnRechazar").onclick = () => {
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: 'Vas a rechazar a este candidato.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, rechazar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            actualizarEstatus(IdPostulacion, 3);
                        }
                    });
                };

            }
        });

        function textoAListaHTML(texto) {
            if (!texto) return "<ul><li>No disponible</li></ul>";
            return "<ul>" + texto.split('\n').map(l => `<li>${l.trim()}</li>`).join('') + "</ul>";
        }

        function actualizarEstatus(id, status) {
            fetch(`dao/ActualizarEstatusPostulacion.php`, {
                method: "POST",
                body: new URLSearchParams({ id, status })
            })
                .then(res => res.json())
                .then(data => {
                    Swal.fire(data.message).then(() => location.reload());
                })
                .catch(() => Swal.fire("Error al actualizar estatus"));
        }
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

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
</body>
</html>
