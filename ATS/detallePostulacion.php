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
                        <div class="progreso" id="barraProgreso" style="width: 100%;"></div>
                    </div>
                </div>
                <form id="formPostulacion" style="padding: 20px;">
                    <h2>CV del candidato</h2>
                    <div id="vistaPreviaPDF" style="margin-top: 20px;">
                        <p>Cargando CV...</p>
                    </div>

                    <!-- Botones Rechazar y Aprobar -->
                    <div class="botones-postulacion">
                        <button type="button" class="btn-continuar btn-rechazar" id="btnRechazar">Rechazar</button>
                        <button type="button" class="btn-continuar" id="btnAprobar">Aprobar</button>
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

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const btnAprobar = document.getElementById("btnAprobar");
        const btnRechazar = document.getElementById("btnRechazar");
        const params = new URLSearchParams(window.location.search);
        const IdPostulacion = params.get("IdPostulacion");

        let estatusActual = null;

        // Obtener el estatus actual al cargar la página
        fetch(`dao/ObtenerEstatusPostulacion.php?IdPostulacion=${IdPostulacion}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    estatusActual = parseInt(data.IdEstatus);
                } else {
                    console.error("No se pudo obtener el estatus");
                }
            })
            .catch(error => console.error("Error al obtener el estatus:", error));

        function confirmarYActualizar(accion, nuevoEstatus) {
            if (estatusActual === 3 || estatusActual === 4) {
                Swal.fire({
                    icon: 'info',
                    title: 'Acción no permitida',
                    text: 'Este candidato ya ha sido ' + (estatusActual === 3 ? 'rechazado' : 'aprobado') + '.'
                });
                return;
            }

            Swal.fire({
                title: `¿Estás seguro que deseas ${accion} este candidato?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: accion === 'aprobar' ? '#28a745' : '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Sí, ${accion}`,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    actualizarEstatus(nuevoEstatus);
                }
            });
        }

        function actualizarEstatus(nuevoEstatus) {
            const formData = new FormData();
            formData.append("id", IdPostulacion);
            formData.append("status", nuevoEstatus);

            fetch("dao/ActualizarEstatusPostulacion.php", {
                method: "POST",
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Listo!',
                            text: data.message
                        }).then(() => {
                            window.location.href = "Postulaciones.php";
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo conectar con el servidor.'
                    });
                });
        }

        btnAprobar.addEventListener("click", () => confirmarYActualizar('aprobar', 4));
        btnRechazar.addEventListener("click", () => confirmarYActualizar('rechazar', 3));
    });
</script>


</body>
</html>
