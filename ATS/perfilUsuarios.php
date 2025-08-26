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
            <a href="#">Nueva Solicitud</a>
            <a href="seguimiento.php">Seguimiento</a>
            <a href="historicos.php">Historial de Solicitudes</a>

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
                    <i class="fas fa-user-shield"></i>
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

<footer class="main-footer">
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Este script obtiene los datos del usuario y los pone en el nuevo layout
        fetch('dao/daoPerfilUsuario.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const usuario = data.data;
                    // Rellenar barra lateral
                    document.getElementById('infoNombre').textContent = usuario.Nombre;
                    document.getElementById('infoCorreo').textContent = usuario.Correo;
                    document.getElementById('infoRol').textContent = usuario.NombreRol;
                    // Rellenar detalles principales
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

        // Lógica para cerrar sesión
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

</body>
</html>