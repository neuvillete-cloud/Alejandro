<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="css/estilosrecuperarContraseña.css"> <!-- Asegúrate de que el CSS esté vinculado correctamente -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 para mensajes -->
    <script src="js/recuperarPassword.js" defer></script> <!-- JavaScript para manejar el formulario -->
</head>
<body>
<div class="container">
    <div class="back-button">
        <a href="login.php">
            <div class="circle">
                <span>&larr; Regresar</span>
            </div>
        </a>
    </div>

    <div class="form-container">
        <h1>Recuperar Contraseña</h1>
        <!-- Formulario para la recuperación de contraseña -->
        <form id="formRecuperarPassword">
            <label for="correoRecuperacion">Correo Electrónico</label>
            <input type="text" id="correoRecuperacion" name="correoRecuperacion"  placeholder="Correo electrónico" required>
            <button type="button" id="recuperarP" name="recuperarP"  class="btn login" onclick="recuperarPassword()">Recuperar Contraseña</button>
        </form>
    </div>
</div>
</body>
</html>
