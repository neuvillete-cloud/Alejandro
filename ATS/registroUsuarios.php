<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="css/estilosRegistroUsuarios.css">
</head>
<body>
<div class="registro-container">
    <header class="registro-header">
        <div class="logo-container">
            <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Logo" class="logo">
        </div>
        <h1>Regístrate para empezar a usar</h1>
    </header>
    <form id="registro-form">
        <label for="NumNomina">Número de Nómina</label>
        <input type="text" id="NumNomina" name="NumNomina" placeholder="Ingrese su número de nómina" required>

        <label for="Nombre">Nombre</label>
        <input type="text" id="Nombre" name="Nombre" placeholder="Ingrese su nombre completo" required>

        <label for="Correo">Correo Electrónico</label>
        <input type="email" id="Correo" name="Correo" placeholder="name@domain.com" required>

        <label for="Contrasena">Contraseña</label>
        <input type="password" id="Contrasena" name="Contrasena" placeholder="Ingrese su contraseña" required>

        <label for="Area">Área</label>
        <select id="Area" name="Area" required>
            <option value="" disabled selected>Seleccione su área</option>
            <option value="Seguridad e Higiene">Seguridad e Higiene</option>
            <option value="GPS">GPS</option>
            <option value="IT">IT</option>
            <option value="RH">RH</option>
            <option value="Calidad">Calidad</option>
            <option value="Ingenieria">Ingeniería</option>
            <option value="Controlling">Controlling</option>
            <option value="Logistica">Logística</option>
            <option value="Mantenimiento">Mantenimiento</option>
            <option value="Producción (APU)">Producción (APU)</option>
            <option value="Finanzas">Finanzas</option>
            <option value="Compras">Compras</option>
            <option value="Regionales">Regionales</option>
        </select>

        <button type="submit" class="btn-registrar">Registrar</button>
    </form>
    <footer class="registro-footer">
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>
    </footer>
</div>
<script src="js/registroUsuarios.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
