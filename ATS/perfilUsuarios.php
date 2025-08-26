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
    <title>Mi Perfil | ATS Grammer</title>
    <link rel="stylesheet" href="css/estilosPerfilATS.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

            <?php
            // Verificamos si la variable de Rol existe en la sesión
            if (isset($_SESSION['Rol'])) {

                // Si el Rol es 1 (Administrador)
                if ($_SESSION['Rol'] == 1) {
                    ?>
                    <a href="Administrador.php">Inicio</a>
                    <a href="SAprobadas.php">S.Aprobadas</a>
                    <a href="SeguimientoAdministrador.php">Seguimiento</a>
                    <a href="cargaVacante.php">Carga de Vacantes</a>
                    <a href="candidatoSeleccionado.php">Candidatos Seleccionados</a>
                    <?php
                    // Si el Rol es 2 (Solicitante)
                } elseif ($_SESSION['Rol'] == 2) {
                    ?>
                    <a href="Solicitante.php">Nueva Solicitud</a>
                    <a href="seguimiento.php">Seguimiento</a>
                    <a href="historicos.php">Historial de Solicitudes</a>
                    <a href="seleccionFinal.php">Candidatos Finales</a>
                    <?php
                }
                // Aquí podrías añadir más 'elseif' para otros roles en el futuro
            }
            ?>
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
    <h1>Mi Perfil de Usuario</h1>
    <img src="imagenes/perfil.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<section class="area-blanca">
    <div class="contenido-blanco">
        <div class="profile-container">

            <aside class="profile-sidebar">
                <div class="avatar-display">
                    <img
                            src="https://grammermx.com/Fotos/<?php echo htmlspecialchars($_SESSION['NumNomina']); ?>.png"
                            alt="Foto de Perfil"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <i class="fas fa-user-shield" style="display: none;"></i>
                </div>
                <h2 id="infoNombre">Cargando...</h2>
                <p id="infoCorreo">Cargando...</p>
                <hr>
                <div class="sidebar-info">
                    <strong>Rol en Sistema:</strong>
                    <span id="infoRol">Cargando...</span>
                </div>
            </aside>

            <main class="profile-details">
                <div class="details-section">
                    <h3>Información de la Cuenta</h3>
                    <dl>
                        <dt>Nombre Completo</dt>
                        <dd id="infoNombreCompleto">Cargando...</dd>

                        <dt>Número de Nómina</dt>
                        <dd id="infoNomina">Cargando...</dd>
                    </dl>
                </div>

                <div class="details-section">
                    <h3>Información Laboral</h3>
                    <dl>
                        <dt>Área de Adscripción</dt>
                        <dd id="infoArea">Cargando...</dd>
                    </dl>
                </div>

                <div class="details-section">
                    <h3>Acciones de la Cuenta</h3>
                    <div class="acciones-container">
                        <button class="btn-accion btn-primario"><i class="fas fa-edit"></i> Editar Información</button>
                        <button class="btn-accion btn-secundario"><i class="fas fa-key"></i> Cambiar Contraseña</button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch('dao/daoPerfilUsuario.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const usuario = data.data;
                    document.getElementById('infoNombre').textContent = usuario.Nombre;
                    document.getElementById('infoCorreo').textContent = usuario.Correo;
                    document.getElementById('infoRol').textContent = usuario.NombreRol;
                    document.getElementById('infoNombreCompleto').textContent = usuario.Nombre;
                    document.getElementById('infoNomina').textContent = usuario.NumNomina;
                    document.getElementById('infoArea').textContent = usuario.NombreArea;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error al cargar el perfil:", error);
                alert("No se pudo cargar la información del perfil.");
            });

        const logoutLink = document.getElementById('logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetch('dao/logout.php', { method: 'POST' })
                    .then(response => { if (response.ok) window.location.href = 'login.php'; });
            });
        }
    });
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
                <li><a href="Administrador.php">Inicio</a></li>
                <li><a href="SAprobadas.php">Solicitudes Aprobadas</a></li>
                <li><a href="SeguimientoAdministrador.php">Seguimiento</a></li>
                <li><a href="cargaVacante.php">Carga de Vacantes</a></li>
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