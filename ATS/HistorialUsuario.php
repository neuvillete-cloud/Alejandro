<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/HistorialUsuario.css">
</head>
<body>

<?php session_start(); ?>

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
            <a href="#">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Escuela de Talentos</a>
            <a href="#">Inclusión y diversidad</a>

            <?php if (isset($_SESSION['NombreCandidato'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
                        <a href="#">Alertas de empleo</a>
                        <a href="#">Historial de solicitudes</a>
                        <a href="#" id="logout">Cerrar sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginATS.php">Inicio de sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Historial de Postulación</h1>
    <img src="imagenes/historial-de-transacciones%20(1).png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="mensaje-usuario">
            <p>¡Gracias por confiar en nosotros! Aquí puedes revisar el estado de tus postulaciones.</p>
        </div>

        <div class="contenedor-cards">
            <!-- Las cards se llenarán dinámicamente con JS -->
        </div>
    </div>
</section>

<!-- Offcanvas Derecho con scroll y backdrop -->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="offcanvasDetalles" aria-labelledby="offcanvasDetallesLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasDetallesLabel">Detalles de la Postulación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body small" id="detallePostulacionBody">
        <p>Selecciona una postulación para ver sus detalles.</p>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        fetch('dao/obtenerHistorialPostulaciones.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                renderizarPostulaciones(data);
            })
            .catch(error => console.error('Error al obtener postulaciones:', error));
    });

    function renderizarPostulaciones(postulaciones) {
        const contenedor = document.querySelector('.contenedor-cards');
        contenedor.innerHTML = '';

        if (postulaciones.length === 0) {
            contenedor.innerHTML = '<p>No tienes postulaciones aún.</p>';
            return;
        }

        postulaciones.forEach(post => {
            const card = document.createElement('div');
            card.classList.add('card-solicitud');

            let claseEstatus = '';
            switch (post.Estatus.toLowerCase()) {
                case 'aprobado': claseEstatus = 'estatus-aprobado'; break;
                case 'rechazado': claseEstatus = 'estatus-rechazado'; break;
                default: claseEstatus = 'estatus-recibido';
            }

            card.innerHTML = `
            <div class="cabecera-card">
                <h3>${post.TituloVacante}</h3>
                <span class="estatus ${claseEstatus}">${post.Estatus}</span>
            </div>
            <p><strong>Área:</strong> ${post.NombreArea}</p>
            <p><strong>Fecha de Postulación:</strong> ${post.FechaPostulacion}</p>
            <p><strong>Modalidad:</strong> ${post.EspacioTrabajo}</p>
            <button class="btn-ver" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDetalles"
                data-titulo="${post.TituloVacante}"
                data-area="${post.NombreArea}"
                data-fecha="${post.FechaPostulacion}"
                data-modalidad="${post.EspacioTrabajo}"
                data-imagen="${post.ImagenVacante}"
                data-descripcion-estatus="${post.DescripcionEstatus}"
            >
                Ver Detalles
            </button>
        `;

            contenedor.appendChild(card);
        });

        agregarEventosVerDetalles();
    }

    function agregarEventosVerDetalles() {
        document.querySelectorAll('.btn-ver').forEach(boton => {
            boton.addEventListener('click', function() {
                const titulo = this.getAttribute('data-titulo');
                const area = this.getAttribute('data-area');
                const fecha = this.getAttribute('data-fecha');
                const modalidad = this.getAttribute('data-modalidad');
                const imagen = this.getAttribute('data-imagen');
                const descripcionEstatus = this.getAttribute('data-descripcion-estatus');

                document.getElementById('detallePostulacionBody').innerHTML = `
                <img src="${imagen}" alt="Imagen de la Vacante" style="max-width:100%; margin-top:10px;">
                <p><strong>Vacante:</strong> ${titulo}</p>
                <p><strong>Área:</strong> ${area}</p>
                <p><strong>Fecha de Postulación:</strong> ${fecha}</p>
                <p><strong>Modalidad:</strong> ${modalidad}</p>
                <p><strong>Descripción del Estatus:</strong> ${descripcionEstatus}</p>

            `;
            });
        });
    }

    const logoutLink = document.getElementById('logout');
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('dao/logout.php', { method: 'POST' })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'loginATS.php';
                    } else {
                        alert('Error al cerrar sesión. Inténtalo nuevamente.');
                    }
                })
                .catch(error => console.error('Error al cerrar sesión:', error));
        });
    }

</script>

</body>
</html>
