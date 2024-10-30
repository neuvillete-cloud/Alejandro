<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="css/estilosRegistro.css">
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
        <h1>Crear Cuenta Nueva</h1>
        <form id="registrationForm">
            <label for="NumNomina">Número de Nómina</label>
            <input type="text" id="NumNomina" placeholder="12345678" required>

            <label for="Nombre">Nombre</label>
            <input type="text" id="Nombre" placeholder="Tu Nombre" required>

            <label for="Correo">Correo Electrónico</label>
            <input type="email" id="Correo" placeholder="correo@ejemplo.com" required>

            <label for="Contrasena">Contraseña</label>
            <input type="password" id="Contrasena" placeholder="******" required>

            <button type="submit">Registrarse</button>
        </form>
        <p class="login-prompt">¿Ya tienes una cuenta? <a href="login.php">Iniciar Sesión</a></p>
    </div>
</div>
<script src="js/validacionesRegistrate.js"></script>
</body>
</html>
