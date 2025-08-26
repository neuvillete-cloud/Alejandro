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
</header>

<section class="section-title">
    <div class="perfil-icono">
        <img src="imagenes/perfil.png" alt="Ícono de Perfil" class="imagen-banner">
    </div>
    <div class="perfil-texto">
        <span>MI PERFIL</span>
        <h1 id="headerNombre">Cargando...</h1>
    </div>
</section>
<main class="profile-main-content">
    <div class="profile-grid">
        <div class="profile-card">
            <div class="card-header">
                <h3><i class="fas fa-user-circle"></i> Información de la Cuenta</h3>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Nombre Completo</span>
                    <span class="info-value" id="infoNombre">Cargando...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Número de Nómina</span>
                    <span class="info-value" id="infoNomina">Cargando...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo Electrónico</span>
                    <span class="info-value" id="infoCorreo">Cargando...</span>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="card-header">
                <h3><i class="fas fa-briefcase"></i> Información Laboral</h3>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Rol en el Sistema</span>
                    <span class="info-value" id="infoRol">Cargando...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Área</span>
                    <span class="info-value" id="infoArea">Cargando...</span>
                </div>
            </div>
        </div>

        <div class="profile-card card-acciones">
            <div class="card-header">
                <h3><i class="fas fa-cogs"></i> Acciones</h3>
            </div>
            <div class="card-body">
                <button class="btn-accion btn-primario"><i class="fas fa-edit"></i> Editar Información</button>
                <button class="btn-accion btn-secundario"><i class="fas fa-key"></i> Cambiar Contraseña</button>
            </div>
        </div>
    </div>
</main>

<footer class="main-footer">
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch('dao/daoPerfilUsuario.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const usuario = data.data;
                    document.getElementById('headerNombre').textContent = usuario.Nombre;
                    document.getElementById('infoNombre').textContent = usuario.Nombre;
                    document.getElementById('infoNomina').textContent = usuario.NumNomina;
                    document.getElementById('infoCorreo').textContent = usuario.Correo;
                    document.getElementById('infoRol').textContent = usuario.NombreRol;
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

</body>
</html>