<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/estilosRestablecerContraseña.css"> <!-- Archivo CSS general -->
</head>
<body>
<div class="container-reset-password">
    <div class="header-reset">
        <h2>Restablecer Contraseña</h2>
    </div>

    <form id="resetPasswordForm" method="POST">
        <label for="newPassword">Nueva Contraseña</label>
        <input type="password" id="newPassword" name="newPassword" required placeholder="Ingresa tu nueva contraseña">

        <label for="confirmPassword">Confirmar Contraseña</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Confirma tu nueva contraseña">

        <button type="submit">Restablecer Contraseña</button>
    </form>

    <p id="errorMessage" style="display: none; color: red;">Las contraseñas no coinciden.</p>
</div>


</body>
</html>

