<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Reporte</title>
    <link rel="icon" href="imagenes/balance%20(1).png" type="image/x-icon">
    <link rel="stylesheet" href="css/estilos.css">

</head>
<body>
<div class="container">
    <fieldset class="title-fieldset">
        <legend class="title-legend">Actualizar Reporte</legend>
    </fieldset>

    <form method="post" action="guardar_actualizacion.php">
        <label for="id">Id:</label>
        <input type="text" name="id" id="id">

        <label for="objeto">Objeto:</label>
        <input name="objeto" id="objeto" type="text"  required>

        <label for="fecha">Fecha:</label>
        <input name="fecha" id="fecha" type="date"  required>

        <label for="descripcion">Descripción:</label>
        <input name="descripcion" id="descripcion" type="text"  required>

        <label for="area">Área:</label>
        <input name="area" id="area" type="text"  required>

        <button type="submit">Actualizar</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="js/envioDatos.js"></script>
<script src="js/validacionCampos.js"></script>
<script src="js/consultarDatos.js"></script>
<script>
    $(document).ready(function() { cargarDatosReporte() });
    </script>

</body>
</html>

