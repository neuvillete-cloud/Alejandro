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

<script src="js/envioDatos.js"></script>
<script src="js/validacionCampos.js"></script>
<script src="js/consultarDatos.js"></script>
</body>
</html>

