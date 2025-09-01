<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosSolicitante.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
session_start();
if (!isset($_SESSION['NumNomina'])) {
    header('Location: login.php');
    exit;
}
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
                        <a href="perfilUsuarios.php">Perfil</a>
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
    <h1>Nueva Solicitud de Personal</h1>
    <img src="imagenes/solicitudes-de-empleo.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <main class="main-content" id="mainContent">
            <section class="form-container">
                <h1>Registrar Solicitud</h1>
                <form id="solicitudForm">
                    <label for="nombre">Nombre del Solicitante</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required>

                    <label for="area">Área</label>
                    <input type="text" id="area" name="area" placeholder="Ingresa el área correspondiente" required>

                    <label for="puesto">Puesto</label>
                    <input type="text" id="puesto" name="puesto" placeholder="Ingresa el puesto solicitado" required>

                    <label for="tipo">Tipo de Solicitud</label>
                    <select id="tipo" name="tipo" required>
                        <option value="" disabled selected>Selecciona una opción</option>
                        <option value="nuevo">Nuevo puesto</option>
                        <option value="reemplazo">Reemplazo</option>
                    </select>

                    <div id="reemplazoFields" style="display: none;">
                        <label for="reemplazoNombre">Nombre de la Persona Reemplazada</label>
                        <input type="text" id="reemplazoNombre" name="reemplazoNombre" placeholder="Ingresa el nombre del reemplazo">
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Registrar Solicitud
                    </button>
                </form>
            </section>
        </main>
    </div>
</section>

<!-- Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Manejo del cambio de tipo de solicitud
        const tipoSelect = document.getElementById('tipo');
        const reemplazoFields = document.getElementById('reemplazoFields');

        if (tipoSelect) {
            tipoSelect.addEventListener('change', () => {
                reemplazoFields.style.display = tipoSelect.value === 'reemplazo' ? 'block' : 'none';
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

<script src="js/jsSolicitante.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <li><a href="Solicitante.php">Inicio</a></li>
                <li><a href="seguimiento.php">Seguimiento</a></li>
                <li><a href="historicos.php">Historial de Solicitudes</a></li>
                <li><a href="seleccionFinal.php">Candidatos Finales</a></li>
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
    </div>
</footer>
</body>
</html>
