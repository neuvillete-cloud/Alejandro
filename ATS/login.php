<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="css/estilosLogin.css">
</head>
<body>
<div class="login-container">
    <header class="login-header">
        <div class="logo-container">
            <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Logo" class="logo">
        </div>
        <h1>Inicia sesión para continuar</h1>
    </header>
    <div class="login-form">
        <form id="loginformulario">
            <input type="hidden" id="redirect_url" name="redirect_url" value="<?= htmlspecialchars($_GET['redirect_url'] ?? '') ?>">
            <label for="NumNomina">Número de Nómina</label>
            <input type="text" id="NumNomina" name="NumNomina" placeholder="Ingrese su número de nómina" required>

            <label for="Contrasena">Contraseña</label>
            <input type="password" id="Contrasena" name="Contrasena" placeholder="Ingrese su contraseña" required>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
    </div>
    <footer class="login-footer">
        <p><a href="solicitar_recuperacion.php">¿Olvidaste tu contraseña?</a></p>
    </footer>
</div>
<script src="js/login.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
