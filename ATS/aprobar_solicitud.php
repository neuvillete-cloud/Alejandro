<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar Solicitud</title>
    <link rel="stylesheet" href="css/estilosAprobarSolicitud.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <img src="imagenes/grammer.png" alt="Icono de Solicitudes" class="header-icon">
        <h1>Aprobar Solicitudes</h1>
    </div>
</header>

<!-- Contenedor central tipo "hoja" -->
<main class="contenedor-solicitud">
    <h2>Detalles de la Solicitud</h2>

    <!-- Encabezado dentro del contenedor -->
    <div class="encabezado">
        <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Icono de solicitud" class="solicitud-imagen">
        <h3>Información General</h3>
        <span class="fecha">Fecha: <span id="fecha"></span></span>
    </div>

    <!-- Contenedor de solicitud -->
    <div class="solicitud">
        <p><strong>Nombre:</strong> <span id="nombre"></span></p>
        <p><strong>Área:</strong> <span id="area"></span></p>
        <p><strong>Puesto:</strong> <span id="puesto"></span></p>
        <p><strong>Tipo de Solicitud:</strong> <span id="tipo"></span></p>
        <p><strong>Descripción:</strong> <span id="descripcion"></span></p>
    </div>

    <!-- Contenedor de los botones (sin título) -->
    <div class="botones-solicitud">
        <button class="boton boton-cancelar">Cancelar</button>
        <button class="boton boton-aceptar">Aceptar</button>
    </div>
</main>

<script>
    // Script para agregar la fecha actual
    document.getElementById("fecha").textContent = new Date().toLocaleDateString();
</script>

<script src="js/aprobarSoli.js"></script>

</body>
</html>
