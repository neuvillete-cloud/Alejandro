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
            color: #6A1B9A;
        }
        .btn-custom {
            background-color: #6A1B9A;
            color: white;
        }
        .btn-custom:hover {
            background-color: #5c0e87;
        }
        .btn-register {
            margin-top: 10px;
            text-align: center;
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
            <label for="correo">Correo Electrónico</label>
            <input type="email" class="form-control" id="correo" name="correo" required>
        </div>
        <button type="submit" class="btn btn-custom btn-block">Iniciar Sesión</button>
    </form>

    <div class="btn-register">
        <a href="registroUsuarios.php" class="btn btn-secondary btn-block">Registrarse</a>
    </div>
</div>

<script src="js/validacionLogin.js"></script>

</body>
</html>
