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

    <!-- Contenedor de solicitud (ahora incluye el encabezado) -->
    <div class="solicitud">
        <!-- Encabezado ahora dentro de .solicitud -->
        <div class="encabezado">
            <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Icono de solicitud" class="solicitud-imagen">
            <h3>Información General</h3>
            <span class="fecha">Fecha: <span id="fecha"></span></span>
        </div>

        <!-- Datos con líneas -->
        <div class="datos">
            <div class="dato">
                <label>Nombre del Solicitante:</label>
                <span class="linea" id="nombre"></span>
            </div>
            <div class="dato">
                <label>Área:</label>
                <span class="linea" id="area"></span>
            </div>
        </div>

        <div class="datos">
            <div class="dato">
                <label>Puesto:</label>
                <span class="linea" id="puesto"></span>
            </div>
            <div class="dato">
                <label>Tipo de Contratacion:</label>
                <span class="linea" id="tipo"></span>
            </div>
        </div>

        <div class="datos">
            <div class="dato">
                <label>Nombre de la persona a reemplazar:</label>
                <span class="linea" id="NombreReemplazo"></span>
            </div>
        </div>

        <div class="datos">
            <div class="dato">
                <label>Fecha de Solicitud:</label>
                <span class="linea" id="FechaSolicitud"></span>
            </div>
        </div>

        <div class="datos">
            <div class="dato">
                <label>Folio de la Solicitud:</label>
                <span class="linea" id="FolioSolicitud"></span>
            </div>
        </div>



    </div>

    <!-- Contenedor de los botones -->
    <div class="botones-solicitud">
        <button class="boton boton-cancelar">Cancelar</button>
        <button class="boton boton-aceptar">Aceptar</button>
    </div>
</main>

<!-- Modal para aprobación/rechazo -->
<div id="modalAprobacion" class="modal">
    <div class="modal-contenido">
        <h2>Aprobar o Rechazar Solicitud</h2>
        <label for="nombreAprobador">Nombre del Aprobador:</label>
        <input type="text" id="nombreAprobador" placeholder="Ingrese su nombre">

        <label for="accion">Acción:</label>
        <select id="accion">
            <option value="">Seleccione una opción</option>
            <option value="aprobar">Aprobar</option>
            <option value="rechazar">Rechazar</option>
        </select>

        <label for="comentario">Comentario:</label>
        <textarea id="comentario" placeholder="Opcional si aprueba, obligatorio si rechaza"></textarea>

        <div class="modal-botones">
            <button id="cerrarModal" class="boton-modal-cerrar">Cancelar</button>
            <button id="confirmarAccion" class="boton-modal-confirmar">Confirmar</button>
        </div>
    </div>
</div>

<script>
    // Script para agregar la fecha actual
    document.getElementById("fecha").textContent = new Date().toLocaleDateString();
</script>

<script src="js/aprobarSoli.js"></script>

</body>
</html>
