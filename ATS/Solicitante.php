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
            <a href="SAprobadas.php">S.Aprobadas</a>
            <a href="SeguimientoAdministrador.php">Seguimiento</a>
            <a href="Postulaciones.php">Candidatos Postulados</a>

            <?php if (isset($_SESSION['Nombre'])): ?>
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['Nombre']) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="perfil.php">Perfil</a>
                        <a href="#">Alertas de empleo</a>
                        <a href="HistorialUsuario.php">Historial de solicitudes</a>
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
</body>
</html>
