<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enculame la nave</title>
    <link rel="icon" href="imagenes/balance%20(1).png" type="image/x-icon">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<div class="container">
    <!-- Título enmarcado -->
    <fieldset class="title-fieldset">
        <legend class="title-legend">Formulario de Reporte</legend>
    </fieldset>

    <!-- Formulario -->
    <form name="REPORTES" method="post" action="">
        <label for="objeto">Objeto:</label>
        <input name="objeto" id="objeto" type="text" placeholder="Objeto" data-error="ingresa un objeto valido">
        <div class="invalid-feedback"></div>

        <label for="Fecha">Fecha:</label>
        <input name="Fecha" id="Fecha" type="date" placeholder="Fecha" data-error="ingresa una fecha valida">
        <div class="invalid-feedback"></div>

        <label for="Descripcion">Descripción:</label>
        <input name="Descripcion" id="Descripcion" type="text" placeholder="Descripción del problema" data-error="ingresa un texto valido">
        <div class="invalid-feedback"></div>

        <label for="Area">Área:</label>
        <input name="Area" id="Area" type="text" placeholder="Área" data-error="ingresa un área valida">
        <div class="invalid-feedback"></div>

        <button type="button" name="Guardar" onclick="enviarDatos()">Guardar</button>
    </form>
</div>
<script src="js/envioDatos.js"></script>
<script src="js/validacionCampos.js"></script>
</body>
</html>
