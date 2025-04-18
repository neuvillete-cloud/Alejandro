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
    <!-- Título enmarcado -->
    <fieldset class="title-fieldset">
        <legend class="title-legend">Formulario de Reporte</legend>
    </fieldset>

    <!-- Formulario -->
    <form name="REPORTES" method="post" action="">
        <label for="objeto">Objeto:</label>
        <input name="objeto" id="objeto" type="text" placeholder="Objeto" data-error="ingresa un objeto valido">
        <div class="invalid-feedback"></div>

        <label for="Descripcion">Descripción:</label>
        <input name="Descripcion" id="Descripcion" type="text" placeholder="Descripción del problema" data-error="ingresa un texto valido">
        <div class="invalid-feedback"></div>

        <label for="Area">Área:</label>
        <input name="Area" id="Area" type="text" placeholder="Área" data-error="ingresa un área valida">
        <div class="invalid-feedback"></div>

        <button type="button" name="Guardar" onclick="enviarDatos()">Guardar</button>
        <button type="button" name="Consultar" onclick="consultarDatos()">Consultar</button>
    </form>

    <!-- Tabla para mostrar resultados (inicialmente oculta) -->
    <table id="tablaResultados" border="1" style="display:none; margin-top: 20px;">
        <thead>
        <tr>
            <th>ID</th>
            <th>Objeto</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Área</th>
        </tr>
        </thead>
        <tbody id="contenidoTabla">
        <!-- Aquí se insertarán los datos de la base de datos -->
        </tbody>
    </table>
</div>

<script src="js/respaldo.js"></script>
<script src="js/validacionCampos.js"></script>
<script src="js/consultarDatos.js"></script>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</body>
</html>

