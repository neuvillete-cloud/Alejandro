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

                    <!-- Paso 2 (ejemplo adicional) -->
                    <div class="form-step" data-step="2">
                        <h2>Sube tu CV</h2>

                        <label>Archivo CV</label>
                        <input type="file" name="cv">

                        <button type="button" onclick="prevStep()">Atrás</button>
                        <button type="submit" class="btn-continuar">Enviar</button>
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
<script src="js/vacanteDinamica.js"></script>
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

    function showStep(step) {
        document.querySelectorAll('.form-step').forEach(div => {
            div.classList.remove('active');
            if (parseInt(div.dataset.step) === step) {
                div.classList.add('active');
            }
        });

        const progreso = Math.round((step / totalSteps) * 100);
        barra.style.width = progreso + "%";
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
                <p><strong>Rol y responsabilidades:</strong><br>${data.Descripcion.replace(/\n/g, "<br>")}</p>
                <p><strong>Requisitos:</strong>${textoAListaHTML(data.Requisitos)}</p>
                <p><strong>Beneficios:</strong>${textoAListaHTML(data.Beneficios)}</p>
                <p><strong>Horario:</strong> ${data.Horario} / <strong>Modalidad:</strong> ${data.EspacioTrabajo}</p>
                <p><strong>Publicado:</strong> ${data.FechaPublicacion}</p>
                <a href="#">Ver descripción completa del empleo</a>
            `;
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

</script>
</body>
</html>

