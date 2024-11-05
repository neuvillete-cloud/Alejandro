<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/estilosRestablecerContraseña.css"> <!-- Archivo CSS general -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container-reset-password">
    <div class="header-reset">
        <h2>Restablecer Contraseña</h2>
    </div>

    <form id="restablecerContrasena" method="POST">
        <input type="hidden" id="numNomina" name="numNomina">
        <input type="hidden" id="token" name="token">

        <label for="nuevaContrasena">Nueva Contraseña</label>
        <input type="password" id="nuevaContrasena" name="nuevaContrasena" required placeholder="Ingresa tu nueva contraseña">

        <label for="confirmaContrasena">Confirmar Contraseña</label>
        <input type="password" id="confirmaContrasena" name="confirmaContrasena" required placeholder="Confirma tu nueva contraseña">

        <button type="button" id="restablecerContrasenaBtn" name="restablecerContrasenaBtn" class="btn login" onclick="actualizarPassword()">Actualizar contraseña</button>
    </form>

    <p id="errorMessage" style="display: none; color: red;">Las contraseñas no coinciden.</p>

    <button id="loginRedirectBtn" style="display: none;" class="btn login" onclick="redireccionarLogin()">Ir a Iniciar Sesión</button>
</div>

<script src="js/RestableceContrasena.js"></script>
</body>
</html>



