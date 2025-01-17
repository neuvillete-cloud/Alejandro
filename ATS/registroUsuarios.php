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
        <img src="logo.png" alt="Logo" class="logo">
        <h1>Regístrate para empezar a usar</h1>
    </header>
    <form class="registro-form">
        <label for="numNomina">Número de Nómina</label>
        <input type="text" id="numNomina" name="numNomina" placeholder="Ingrese su número de nómina" required>

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" placeholder="Ingrese su nombre completo" required>

        <label for="correo">Correo Electrónico</label>
        <input type="email" id="correo" name="correo" placeholder="name@domain.com" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>

        <label for="area">Área</label>
        <input type="text" id="area" name="area" placeholder="Ingrese su área" required>

        <button type="submit" class="btn-registrar">Registrar</button>
    </form>
    <footer class="registro-footer">
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>
    </footer>
</div>
</body>
</html>

