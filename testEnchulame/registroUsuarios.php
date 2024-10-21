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
            color: #6A1B9A;
        }
        .btn-custom {
            background-color: #6A1B9A;
            color: white;
        }
        .btn-custom:hover {
            background-color: #5c0e87;
        }
        .status-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <h2 class="register-title">Registro</h2>
    <form id="registerForm">
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
        <button type="button" class="btn btn-custom btn-block" id="registerBtn">Registrarse</button>
        <div class="status-message" id="statusMessage"></div>
    </form>
</div>

<script src="js/validaciones_registro.js"></script>

</body>
</html>
