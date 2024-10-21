<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Reportes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .register-title {
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

<div class="register-container">
    <h2 class="register-title">Registrarse</h2>
    <form action="register.php" method="POST" id="registerForm">
        <div class="form-group">
            <label for="nomina">Número de Nómina</label>
            <input type="text" class="form-control" id="nomina" name="nomina" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="correo">Correo Electrónico</label>
            <input type="email" class="form-control" id="correo" name="correo" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-custom btn-block">Registrar</button>
    </form>
    <p class="text-center mt-3">¿Ya tienes una cuenta? <a href="login.php">Iniciar Sesión</a></p>
</div>

<script>
    document.getElementById('registerForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Evitar el envío del formulario tradicional
        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
            .then(response => response.text()) // Cambia a text() para manejar redirección
            .then(data => {
                // Comprobar si el registro fue exitoso
                if (data.includes("Error")) {
                    alert(data.message); // Mostrar mensaje de error
                } else {
                    alert(data.message);
                    setTimeout(() => {
                        window.location.href = 'login.php'; // Redirigir a inicio de sesión
                    }, 2000); // Redirigir después de 2 segundos
                }
            })
            .catch(error => {
                console.error('Error al enviar el formulario:', error);
                alert('Hubo un error en el proceso de registro.');
            });
    });
</script>

</body>
</html>
