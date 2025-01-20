<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="login-container main">
    <header class="header">
        <div class="logo-container">
            <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Logo" class="logo-img">
            <span class="logo-text">MiAplicación</span>
        </div>
    </header>
    <div class="login-content">
        <h1 class="title">Inicia Sesión</h1>
        <p class="subtitle">Accede a tu cuenta para continuar</p>
        <form id="loginformulario" action="procesarLogin.php" method="POST" class="login-form">
            <label for="NumNomina">Número de Nómina</label>
            <input type="text" id="NumNomina" name="NumNomina" placeholder="Ingrese su número de nómina" required>

            <label for="Contrasena">Contraseña</label>
            <input type="password" id="Contrasena" name="Contrasena" placeholder="Ingrese su contraseña" required>

            <button type="submit" class="button">Iniciar Sesión</button>
        </form>
        <p><a href="recuperarContrasena.php" class="button">¿Olvidaste tu contraseña?</a></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
