<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Reporte</title>
    <link rel="icon" href="imagenes/balance%20(1).png" type="image/x-icon">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<div class="container">
    <fieldset class="title-fieldset">
        <legend class="title-legend">Formulario de Reporte</legend>
    </fieldset>

    <form name="REPORTES" method="post" action="">
        <label for="objeto">Objeto:</label>
        <input name="objeto" id="objeto" type="text" placeholder="Objeto" data-error="ingresa un objeto válido">
        <div class="invalid-feedback"></div>

        <label for="Fecha">Fecha:</label>
        <input name="Fecha" id="Fecha" type="date" placeholder="Fecha" data-error="ingresa una fecha válida">
        <div class="invalid-feedback"></div>

        <label for="Descripcion">Descripción:</label>
        <input name="Descripcion" id="Descripcion" type="text" placeholder="Descripción del problema" data-error="ingresa un texto válido">
        <div class="invalid-feedback"></div>

        <label for="Area">Área:</label>
        <input name="Area" id="Area" type="text" placeholder="Área" data-error="ingresa un área válida">
        <div class="invalid-feedback"></div>

        <button type="button" name="Guardar" onclick="enviarDatos()">Guardar</button>
        <button type="button" name="Consultar" onclick="consultarDatos()">Consultar</button>
    </form>

    <table id="tablaResultados" border="1" style="display:none; margin-top: 20px;">
        <thead>
        <tr>
            <th>ID</th>
            <th>Objeto</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Área</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody id="contenidoTabla"></tbody>
    </table>
</div>

<!-- Scripts -->
<script src="js/envioDatos.js"></script>
<script src="js/consultarDatos.js"></script>
</body>
</html>
