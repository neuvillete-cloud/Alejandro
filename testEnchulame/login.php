<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Reportes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            color: #6A1B9A; /* Color personalizado */
        }
        .btn-custom {
            background-color: #6A1B9A; /* Color personalizado */
            color: white;
        }
        .btn-custom:hover {
            background-color: #5c0e87; /* Color más oscuro para el hover */
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="login-title">Iniciar Sesión</h2>
    <form action="login.php" method="POST" id="loginForm">
        <div class="form-group">
            <label for="nomina">Número de Nómina</label>
            <input type="text" class="form-control" id="nomina" name="nomina" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn-custom btn-block">Iniciar Sesión</button>
    </form>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        const nomina = document.getElementById('nomina').value;
        const nombre = document.getElementById('nombre').value;
        const email = document.getElementById('email').value;

        // Validar campos vacíos
        if (!nomina || !nombre || !email) {
            event.preventDefault(); // Evitar el envío del formulario
            alert('Por favor, complete todos los campos.');
            return;
        }

        // Validar longitud de nómina
        if (nomina.length < 8) {
            alert('El número de nómina debe tener 8 caracteres. Se completará con ceros.');
            nomina = nomina.padStart(8, '0'); // Completar con ceros
            document.getElementById('nomina').value = nomina; // Actualizar el campo
        }
    });
</script>

</body>
</html>
