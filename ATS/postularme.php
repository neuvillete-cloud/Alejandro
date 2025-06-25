<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacantes en Grammer Automotive</title>
    <link rel="stylesheet" href="css/postularme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.17/css/intlTelInput.css"/>

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
            <a href="practicantes.php"> Escuela de Talentos</a>
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
    <h1>Postularme</h1>
    <img src="imagenes/documento.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="formulario-postulacion">
            <!-- Columna izquierda: datos del candidato -->
            <div class="columna-formulario">
                <!-- Barra de progreso -->
                <div class="barra-progreso-container">
                    <div class="barra-progreso">
                        <div class="progreso" id="barraProgreso" style="width: 25%;"></div>
                    </div>
                </div>

                <form id="formPostulacion">
                    <!-- Paso 1 -->
                    <div class="form-step active" data-step="1">
                        <h2>Agrega tu información de contacto</h2>

                        <label>Nombre *</label>
                        <input type="text" name="nombre" value="<?= $_SESSION['NombreCandidato'] ?? '' ?>" required>

                        <label>Apellido *</label>
                        <input type="text" name="apellido" value="<?= $_SESSION['ApellidosCandidato'] ?? '' ?>" required>

                        <label>Email</label>
                        <div class="campo-email">
                            <input type="email" name="email" value="<?= $_SESSION['CorreoCandidato'] ?? '' ?>" readonly>
                            <div class="tooltip">
                                <i class="fas fa-info-circle"></i>
                                <span class="tooltip-text">Este es el email de tu cuenta. Para cambiarlo, ve a la configuración de la cuenta.</span>
                            </div>
                        </div>

                        <label>País</label>
                        <div class="dato-estatico">México</div>

                        <label>Ciudad, estado</label>
                        <input type="text" name="ciudad" value="<?= $_SESSION['UbicacionCandidato'] ?? '' ?>">

                        <label for="telefono">Número de teléfono</label>
                        <input id="telefono" type="tel" name="telefono" value="<?= $_SESSION['TelefonoCandidato'] ?? '' ?>" placeholder="442-864-4068">


                        <button type="button" class="btn-continuar" onclick="nextStep()">Continuar</button>
                    </div>

                    <!-- Botón de Atrás flotante arriba a la izquierda -->
                    <button type="button" class="btn-volver-flotante" onclick="prevStep()" id="btnAtrasFlotante">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>

                    <!-- Paso del formulario -->
                    <div class="form-step" data-step="2">
                        <h2>Añade un CV para la empresa</h2>

                        <!-- Caja clickable para subir archivo -->
                        <label for="cvFile" class="opcion-cv clickable-upload">
                            <i class="fas fa-file-arrow-up icono-cv"></i>
                            <div class="texto-cv" id="infoArchivo">
                                <h3>Subir CV</h3>
                                <p>Los formatos de archivos que se admiten son PDF, DOCX, RTF o TXT.</p>
                        </div>
                        <input type="file" id="cvFile" name="cv" accept=".pdf,.doc,.docx,.rtf,.txt" hidden>
                        </label>

                    <!-- Aquí se mostrará la vista previa -->
                    <div id="vistaPreviaPDF" style="display: none; margin-top: 20px;">
                        <iframe id="iframePDF" width="100%" height="500px" style="border: 1px solid #ccc; border-radius: 10px;"></iframe>
                    </div>


                        <!-- Botón de continuar al final -->
                        <div class="botones-accion solo-continuar">
                            <button type="submit" class="btn-continuar">
                                Continuar <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>


                </form>
            </div>

            <!-- Columna derecha: resumen de la vacante -->
            <div class="columna-vacante">
                <div class="tarjeta-vacante" id="vacanteDetalle">
                    <p>Cargando vacante...</p>
                </div>
            </div>

        </div>






    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.17/js/intlTelInput.min.js"></script>

<?php if (isset($_SESSION['IdCandidato'])): ?>
    <script>
        const usuario = {
            sueldoEsperado: <?= intval($_SESSION['SueldoEsperado']) ?>,
            ubicacion: "<?= addslashes($_SESSION['UbicacionCandidato']) ?>",
            escolaridad: "<?= addslashes($_SESSION['Escolaridad']) ?>",
            area: "<?= addslashes($_SESSION['AreaInteres']) ?>"
        };
    </script>
<?php else: ?>
    <script>
        const usuario = null;
    </script>
<?php endif; ?>

<script>
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
<script>
    let currentStep = 1;
    const totalSteps = document.querySelectorAll('.form-step').length;
    const barra = document.getElementById('barraProgreso');
    const botonAtrasFlotante = document.getElementById('btnAtrasFlotante');

    function showStep(step) {
        document.querySelectorAll('.form-step').forEach(div => {
            div.classList.remove('active');
            if (parseInt(div.dataset.step) === step) {
                div.classList.add('active');
            }
        });

        // Actualizar la barra de progreso
        const progreso = Math.round((step / totalSteps) * 100);
        barra.style.width = progreso + "%";

        // Mostrar u ocultar el botón "Atrás" flotante solo en el paso 2
        if (step === 2) {
            botonAtrasFlotante.style.display = "block";
        } else {
            botonAtrasFlotante.style.display = "none";
        }
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        showStep(currentStep);
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        showStep(currentStep); // Ya está en tu script

        // Inicializa intl-tel-input
        const input = document.querySelector("#telefono");
        window.intlTelInput(input, {
            initialCountry: "mx",
            preferredCountries: ["mx", "us"],
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.17/js/utils.js"
        });
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const contenedor = document.getElementById("vacanteDetalle");
        const params = new URLSearchParams(window.location.search);
        const idVacante = params.get("id");

        if (!idVacante) {
            contenedor.innerHTML = "<p>No se proporcionó un ID de vacante.</p>";
            return;
        }

        fetch(`dao/obtenerVacanteId.php?id=${idVacante}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    contenedor.innerHTML = `<p>${data.error}</p>`;
                    return;
                }

                contenedor.innerHTML = `
                    <h3>${data.Titulo}</h3>
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

                // Cambiar el título del documento y encabezado si existe
                document.title = `${data.Titulo} - Grammer Automotive`;
                const tituloPagina = document.getElementById("tituloPagina");
                if (tituloPagina) {
                    tituloPagina.textContent = `Postularme a: ${data.Titulo}`;
                }

                // Agregar funcionalidad al botón "ver más"
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
<script>
document.getElementById("cvFile").addEventListener("change", function () {
  const archivo = this.files[0];
  const info = document.getElementById("infoArchivo");
  const vistaPrevia = document.getElementById("vistaPreviaPDF");
  const iframe = document.getElementById("iframePDF");

  if (archivo) {
    const nombre = archivo.name;

    // Reemplaza el texto con el nombre del archivo
    info.innerHTML = `
      <h3><i class="fas fa-check-circle" style="color:green;"></i> ${nombre}</h3>
      <p style="color: gray;">Archivo de CV subido</p>
    `;

    // Si es PDF, generar vista previa
    if (archivo.type === "application/pdf") {
      const reader = new FileReader();
      reader.onload = function (e) {
        iframe.src = e.target.result;
        vistaPrevia.style.display = "block";
      };
      reader.readAsDataURL(archivo);
    } else {
      iframe.src = "";
      vistaPrevia.style.display = "none";
    }
  }
});
</script>

</body>
</html>

