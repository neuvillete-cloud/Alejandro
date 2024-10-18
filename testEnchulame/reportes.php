<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Reporte</title>
    <link rel="icon" href="imagenes/balance%20(1).png" type="image/x-icon">
    <link rel="stylesheet" href="css/estilos.css">
    <?php
    include_once("dao/actualizar_reporte.php");
    ?>
</head>
<body>
<div class="container">
    <fieldset class="title-fieldset">
        <legend class="title-legend">Actualizar Reporte</legend>
    </fieldset>

    <form method="post" action="guardar_actualizacion.php">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <label for="objeto">Objeto:</label>
        <input name="objeto" id="objeto" type="text" value="<?php echo $reporte['objeto']; ?>" required>

        <label for="fecha">Fecha:</label>
        <input name="fecha" id="fecha" type="date" value="<?php echo $reporte['fecha']; ?>" required>

        <label for="descripcion">Descripción:</label>
        <input name="descripcion" id="descripcion" type="text" value="<?php echo $reporte['descripcion']; ?>" required>

        <label for="area">Área:</label>
        <input name="area" id="area" type="text" value="<?php echo $reporte['area']; ?>" required>

        <button type="submit">Actualizar</button>
    </form>
</div>
</body>
</html>

