<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="UTF-8">
    <title>iniciar seccion</title>
    <link rel="icon" href="imagenes/263100.png" type="image/x-icon">
    <!--Boostrap-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">

</head>
<body>
<div class="container">
    <form name= "Login" method="post" action="" >
        <div>
            <label for="nomina" > nomina:</label>
            <input class="input-group mb-3" name="nomina" id="nomina" type = "text" placeholder=" numero de nomina" data-error="ingresa un numero de nomina valido">
            <div class="invalid-feedback"></div>
        </div>
        <div>
            <label for="nombre"> nombre: </label>
            <input name="nombre" id="nombre" type = "text" placeholder="Nombre completo" data-error="ingresa un nombre correcto">
            <div class="invalid-feedback"></div>
        </div>
        <div>
            <label for="email"> email:</label>
            <input name="email" id="email" type = "email" placeholder="ingresa tu correo" data-error="ingresa un correo valido">
            <div class="invalid-feedback"></div>
        </div>
        <div>
            <label for="password">password: </label>
            <input name="password" id="password" type = "password" placeholder="ingresa tu contraseña" data-error="ingrese una contraseña correcta">
            <div class="invalid-feedback"></div>
        </div>

    <button class="btn btn-primary" type="button" name="guardar" onclick="enviarDatos()">Guardar</button>
</form>
</div>
<script src="js/datos.js"></script>
<script src="js/validacionUsuarios.js"></script>
<!-- -Archivos de jQuery-->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</body>
</html>