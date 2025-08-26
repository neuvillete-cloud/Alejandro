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
    <title>Candidatos Finales | ATS Grammer</title>
    <link rel="stylesheet" href="css/seleccionFinal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="#">Nueva Solicitud</a>
            <a href="seguimiento.php">Seguimiento</a>
            <a href="historicos.php">Historial de Solicitudes</a>
            <a href="seleccionFinal.php">Candidatos Finales</a>

            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
                        <a href="#" id="logout">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Inicio de sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Candidatos Finales</h1>
    <img src="imagenes/contratacion.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">

        <h2>Selección de Candidatos</h2>
        <p class="subtitulo">Revisa los perfiles de los candidatos que han llegado a la fase final de selección.</p>

        <div id="candidatos-grid" class="candidatos-grid-container">
        </div>

        <div id="mensajeSinCandidatos" class="mensaje-vacio" style="display: none;">
            <i class="fas fa-users-slash fa-3x mb-3"></i>
            <p class="fw-bold fs-5">No hay candidatos disponibles para aprobar en este momento.</p>
        </div>

    </div>
</section>

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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const gridContainer = document.getElementById('candidatos-grid');
        const mensajeVacio = document.getElementById('mensajeSinCandidatos');
        const logoutLink = document.getElementById('logout');

        function obtenerClaseEstatus(nombreEstatus) {
            switch (nombreEstatus.toLowerCase()) {
                case 'recibido': return { clase: 'estatus-recibido', texto: 'Recibido' };
                case 'aprobado': return { clase: 'estatus-aprobado', texto: 'Aprobado' };
                case 'rechazado': return { clase: 'estatus-rechazado', texto: 'Rechazado' };
                default: return { clase: 'estatus-default', texto: nombreEstatus };
            }
        }

        function renderizarCandidatos(data) {
            gridContainer.innerHTML = '';
            if (!data || data.length === 0) {
                mensajeVacio.style.display = 'block';
                return;
            }

            mensajeVacio.style.display = 'none';

            data.forEach(candidato => {
                const estatusInfo = obtenerClaseEstatus(candidato.NombreEstatus);
                const cardHTML = `
                    <div class="candidato-card">
                        <div class="card-header">
                            <div class="avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info">
                                <h3 class="nombre-candidato">${candidato.Nombre}</h3>
                                <p class="vacante-aplicada">${candidato.TituloVacante}</p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <span class="estatus ${estatusInfo.clase}">${estatusInfo.texto}</span>
                            <a href="#" class="btn-ver-detalles" data-id="${candidato.IdPostulacion}" data-nombre="${candidato.Nombre}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                        </div>
                    </div>
                `;
                gridContainer.insertAdjacentHTML('beforeend', cardHTML);
            });
        }

        fetch('https://grammermx.com/AleTest/ATS/dao/CandidatosFinales.php')
            .then(res => res.json())
            .then(json => {
                if (json && json.data) {
                    renderizarCandidatos(json.data);
                } else {
                    renderizarCandidatos([]); // Llama a la función con un array vacío para mostrar el mensaje
                }
            })
            .catch(err => {
                console.error("Error al cargar candidatos:", err);
                gridContainer.innerHTML = '<p>Error al cargar los candidatos. Por favor, intente más tarde.</p>';
            });

        document.addEventListener('click', function (e) {
            if (e.target.closest('.btn-ver-detalles')) {
                e.preventDefault();
                const boton = e.target.closest('.btn-ver-detalles');
                const IdPostulacion = boton.dataset.id;
                const nombre = boton.dataset.nombre;

                document.getElementById("nombreCandidato").textContent = nombre;

                const contenedorCV = document.getElementById("vistaPreviaPDF");
                contenedorCV.innerHTML = '<p>Cargando CV...</p>';

                fetch(`dao/ConsultarCv.php?IdPostulacion=${IdPostulacion}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            contenedorCV.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            const ext = data.RutaCV.split('.').pop().toLowerCase();
                            if (ext === 'pdf') {
                                contenedorCV.innerHTML = `<iframe width="100%" height="400" src="${data.RutaCV}" style="border:1px solid #ccc; border-radius:10px;"></iframe>`;
                            } else {
                                contenedorCV.innerHTML = `<p>El formato del archivo (${ext}) no se puede previsualizar.</p><a href="${data.RutaCV}" target="_blank" class="btn btn-primary">Descargar CV</a>`;
                            }
                        }
                    });

                document.getElementById("btnAprobar").onclick = () => {
                    Swal.fire({
                        title: '¿Estás seguro?', text: `Vas a APROBAR a ${nombre}.`, icon: 'warning',
                        showCancelButton: true, confirmButtonText: 'Sí, aprobar', cancelButtonText: 'Cancelar'
                    }).then(result => result.isConfirmed && actualizarEstatus(IdPostulacion, 9));
                };

                document.getElementById("btnRechazar").onclick = () => {
                    Swal.fire({
                        title: '¿Estás seguro?', text: `Vas a RECHAZAR a ${nombre}.`, icon: 'warning',
                        showCancelButton: true, confirmButtonText: 'Sí, rechazar', cancelButtonText: 'Cancelar'
                    }).then(result => result.isConfirmed && actualizarEstatus(IdPostulacion, 3));
                };
            }
        });

        function actualizarEstatus(id, status) {
            fetch(`dao/ActualizarEstatusPostulacion.php`, {
                method: "POST",
                body: new URLSearchParams({ id, status })
            })
                .then(res => res.json())
                .then(data => {
                    Swal.fire({ icon: 'success', title: data.message }).then(() => location.reload());
                })
                .catch(() => Swal.fire({ icon: 'error', title: "Error", text: "No se pudo actualizar el estatus." }));
        }

        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => {
                        if (response.ok) window.location.href = 'login.php';
                    });
            });
        }
    });
</script>
</body>
</html>