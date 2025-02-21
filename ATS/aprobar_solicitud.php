<!DOCTYPE html>
<html lang="en">
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
    <div class="solicitud">
        <img src="imagenes/solicitud-icono.png" alt="Icono de solicitud" class="solicitud-imagen">
        <p><strong>Nombre:</strong> <span id="nombre"></span></p>
        <p><strong>Área:</strong> <span id="area"></span></p>
        <p><strong>Puesto:</strong> <span id="puesto"></span></p>
        <p><strong>Tipo de Solicitud:</strong> <span id="tipo"></span></p>
        <p><strong>Descripción:</strong> <span id="descripcion"></span></p>
    </div>

    <!-- Contenedor de los botones -->
    <div class="botones-solicitud">
        <button class="boton boton-cancelar">Cancelar</button>
        <button class="boton boton-aceptar">Aceptar</button>
    </div>
</main>

<script src="js/aprobarSoli.js"></script>

</body>
</html>
