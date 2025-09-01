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
            <a href="indexAts.php">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Escuela de Talentos</a>
            <a href="inclusionDiversidad.php">Inclusión y diversidad</a>

            <?php if (isset($_SESSION['NombreCandidato'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['NombreCandidato']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
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
                <p>${titulo}</p>
                <img src="${imagen}" alt="Imagen de la Vacante" style="max-width:100%; margin-top:10px; margin-bottom:10px;">
                <p><strong>Área:</strong> ${area}</p>
                <p><strong>Fecha de Postulación:</strong> ${fecha}</p>
                <p><strong>Modalidad:</strong> ${modalidad}</p>
                <p><strong>Actualizaciones hasta el momento:</strong> ${descripcionEstatus}</p>

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
<footer class="main-footer">
    <div class="footer-container">

        <div class="footer-column">
            <div class="logo">
                <img src="imagenes/logo_blanco.png" alt="Logo Grammer Blanco" class="logo-img">
                <div class="logo-texto">
                    <h1>Grammer</h1>
                    <span>Automotive</span>
                </div>
            </div>
            <p class="footer-about">
                Sistema de Seguimiento de Candidatos (ATS) para la gestión de talento y requisiciones de personal.
            </p>
        </div>

        <div class="footer-column">
            <h3>Enlaces Rápidos</h3>
            <ul class="footer-links">
                <li><a href="indexAts.php">Inicio</a></li>
                <li><a href="aboutUs.php">Acerca de Nosotros</a></li>
                <li><a href="practicantes.php">Escuela de Talentos</a></li>
                <li><a href="inclusionDiversidad.php">Inclusion y Diversidad</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Contacto</h3>
            <p><i class="fas fa-map-marker-alt"></i> Av. de la Luz #24 Col. satélite , Querétaro, Mexico</p>
            <p><i class="fas fa-phone"></i> +52 (442) 238 4460</p>
            <div class="social-icons">
                <a href="https://www.google.com/url?sa=t&rct=j&q=&esrc=s&source=web&cd=&cad=rja&uact=8&ved=2ahUKEwiA6MqY0KaPAxUmlGoFHX01AXwQFnoECD0QAQ&url=https%3A%2F%2Fwww.facebook.com%2Fgrammermexico%2F%3Flocale%3Des_LA&usg=AOvVaw1Jg2xRElzuIF1PIZ6Ip_Ms&opi=89978449" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://mx.linkedin.com/company/grammer-automotive-puebla-s-a-de-c-v-" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/grammerqro/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>            </div>
        </div>

    </div>
    <div class="sub-footer">
        <p>&copy; <?= date('Y') ?> Grammer Automotive de México. Todos los derechos reservados.</p>
        <p class="developer-credit">Desarrollado con <i class="fas fa-heart"></i> por Alejandro Torres Jimenez</p>
    </div>
</footer>
</body>
</html>
