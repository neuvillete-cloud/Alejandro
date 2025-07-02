<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postulación - Vista previa CV</title>
    <link rel="stylesheet" href="css/Postulacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
session_start();
?>

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
    <h1>Detalles</h1>
    <img src="imagenes/documento.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="formulario-postulacion">
            <!-- Columna izquierda: vista previa dinámica -->
            <div class="columna-formulario">

                <div class="barra-progreso-container">
                    <div class="barra-progreso">
                        <div class="progreso" id="barraProgreso" style="width: 25%;"></div>
                    </div>
                </div>
                <form id="formPostulacion" style="padding: 20px;">
                    <h2>Tu CV cargado</h2>
                    <div id="vistaPreviaPDF" style="margin-top: 20px;">
                        <p>Cargando CV...</p>
                    </div>
                </form>
            </div>

            <!-- Columna derecha: resumen de vacante -->
            <div class="columna-vacante">
                <div class="tarjeta-vacante" id="vacanteDetalle">
                    <p>Cargando vacante...</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const logoutLink = document.getElementById('logout');

    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('dao/logout.php', {method: 'POST'})
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

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const contenedor = document.getElementById("vacanteDetalle");
        const params = new URLSearchParams(window.location.search);
        const IdPostulacion = params.get("IdPostulacion");

        if (!IdPostulacion) {
            contenedor.innerHTML = "<p>No se proporcionó un ID de postulación.</p>";
            return;
        }

        fetch(`dao/ObtenerPostulacion.php?IdPostulacion=${IdPostulacion}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    contenedor.innerHTML = `<p>${data.error}</p>`;
                    return;
                }

                contenedor.innerHTML = `
                <h3>${data.TituloVacante}</h3>
                <p><strong>Área:</strong> ${data.Area}</p>
                <p><strong>${data.Ciudad}, ${data.Estado}</strong></p>
                <hr>

                <div class="resumen-vacante">
                    <p><strong>Rol y responsabilidades:</strong><br>${recortarTexto(data.Descripcion)}</p>
                </div>

                <div class="contenido-completo-animado">
                    <div class="contenido-interno">
                        <p><strong>Rol y responsabilidades:</strong><br>${data.Descripcion.replace(/\n/g, "<br>")}</p>
                        <p><strong>Requisitos:</strong>${textoAListaHTML(data.Requisitos)}</p>
                        <p><strong>Beneficios:</strong>${textoAListaHTML(data.Beneficios)}</p>
                        <p><strong>Horario:</strong> ${data.Horario} / <strong>Modalidad:</strong> ${data.EspacioTrabajo}</p>
                        <p><strong>Publicado:</strong> ${data.FechaPublicacion}</p>
                    </div>
                </div>

                <a href="#" class="ver-mas">Ver descripción completa del empleo</a>
                `;

                const linkVerMas = contenedor.querySelector(".ver-mas");
                const contenidoAnimado = contenedor.querySelector(".contenido-completo-animado");
                const resumen = contenedor.querySelector(".resumen-vacante");

                if (linkVerMas && contenidoAnimado) {
                    linkVerMas.addEventListener("click", function (e) {
                        e.preventDefault();

                        const expandido = contenidoAnimado.classList.toggle("expandido");
                        resumen.style.display = expandido ? "none" : "block";
                        linkVerMas.textContent = expandido
                            ? "Ver menos"
                            : "Ver descripción completa del empleo";
                    });
                }
            })
            .catch(error => {
                console.error("Error al cargar la vacante:", error);
                contenedor.innerHTML = "<p>Error al cargar la vacante.</p>";
            });
    });

    function textoAListaHTML(texto) {
        if (!texto) return "<ul><li>No disponible</li></ul>";
        const items = texto.split('\n').filter(l => l.trim() !== '');
        return "<ul>" + items.map(item => `<li>${item.trim()}</li>`).join('') + "</ul>";
    }

    function recortarTexto(texto, limite = 200) {
        if (!texto) return "No disponible";
        return texto.length > limite
            ? texto.slice(0, limite) + "..."
            : texto;
    }
</script>

<!-- Script para cargar dinámicamente el CV -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const contenedorCV = document.getElementById("vistaPreviaPDF");
        const params = new URLSearchParams(window.location.search);
        const IdPostulacion = params.get("IdPostulacion");

        if (!IdPostulacion) {
            contenedorCV.innerHTML = "<p>No se proporcionó un ID de postulación.</p>";
            return;
        }

        fetch(`dao/ConsultarCv.php?IdPostulacion=${IdPostulacion}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    contenedorCV.innerHTML = `<p>${data.error}</p>`;
                    return;
                }

                const extension = data.RutaCV.split('.').pop().toLowerCase();
                const extensionesVisibles = ['pdf'];

                if (extensionesVisibles.includes(extension)) {
                    contenedorCV.innerHTML = `
                        <iframe
                            width="100%"
                            height="500px"
                            style="border: 1px solid #ccc; border-radius: 10px;"
                            src="${data.RutaCV}"
                        ></iframe>
                    `;
                } else {
                    contenedorCV.innerHTML = `
                        <p>Tu CV fue cargado correctamente pero no puede visualizarse aquí por su formato (${extension}).</p>
                        <a href="${data.RutaCV}" target="_blank" class="btn-descargar">Descargar CV</a>
                    `;
                }
            })
            .catch(error => {
                console.error("Error al obtener el CV:", error);
                contenedorCV.innerHTML = "<p>Error al cargar el CV.</p>";
            });
    });
</script>

</body>
</html>
