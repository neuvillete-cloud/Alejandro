<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Reporte</title>
    <link rel="stylesheet" href="css/estilosDetallesReporte.css">
</head>
<body>
<header>
    <h1>Detalles del Reporte</h1>
</header>
<section id="detalleReporte">
    <!-- Aquí se mostrarán los detalles del reporte -->
</section>

<!-- Modal para finalizar reporte con evidencia -->
<div id="finalizarModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Finalizar Reporte</h2>
        <form id="finalizarForm">
            <label for="evidenciaFoto">Subir foto de evidencia:</label>
            <input type="file" id="evidenciaFoto" name="evidenciaFoto" accept="image/*" required>

            <label for="comentarios">Comentarios:</label>
            <textarea id="comentarios" name="comentarios" rows="4" placeholder="Escriba sus comentarios aquí..." required></textarea>

            <button type="submit">Enviar y Finalizar</button>
        </form>
    </div>
</div>

<script src="js/detalles.js"></script>
</body>
</html>
