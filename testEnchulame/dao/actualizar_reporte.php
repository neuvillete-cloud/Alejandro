<?php
include_once("conexion.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Aquí puedes obtener los datos del reporte usando el ID
    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("SELECT objeto, fecha, descripcion, area FROM Reporte WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $reporte = $resultado->fetch_assoc();
    } else {
        echo "Reporte no encontrado.";
        exit;
    }

    $conex->close();
} else {
    echo "ID no especificado.";
    exit;
}
?>

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

