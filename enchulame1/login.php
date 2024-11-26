<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/estilosLogin.css">
</head>
<body>
<div class="container">
    <div class="back-button">
        <a href="index.php">
            <div class="circle">
                <span>&larr; Regresar</span>
            </div>
        </a>
    </div>

    <div class="form-container">
        <h1>Login</h1>
        <form id="loginForm">
            <label for="NumNomina">Número de Nómina</label>
            <input type="text" id="NumNomina" placeholder="12345678" required>

            <label for="Contrasena">Contraseña</label>
            <input type="password" id="Contrasena" placeholder="******" required>

            <button type="submit">Iniciar Sesión</button>
        </form>
        <p class="forgot-password">¿Olvidaste tu contraseña? <a href="recuperaContrasena.php">Recupérala aquí</a></p>
    </div>
</div>
<script src="js/validacionesLogin.js"></script>
</body>
</html>
