<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Solicitudes</title>
    <link rel="stylesheet" href="css/estilosSolicitante.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1>Solicitudes</h1>
        <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
</header>
<nav class="sidebar" id="sidebar">
    <ul>
        <li><a href="#">Inicio</a></li>
        <li><a href="#">Seguimiento</a></li>
        <li><a href="#">Históricos</a></li>
        <li><a href="#">Configuraciones</a></li>
    </ul>
</nav>
<main class="main-content">
    <section class="form-container">
        <h1>Registrar Solicitud</h1>
        <form id="solicitudForm">
            <!-- Campo Nombre -->
            <label for="nombre">Nombre del Solicitante</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required>

            <!-- Campo Área -->
            <label for="area">Área</label>
            <input type="text" id="area" name="area" placeholder="Ingresa el área correspondiente" required>

            <!-- Campo Tipo -->
            <label for="tipo">Tipo de Solicitud</label>
            <select id="tipo" name="tipo" required>
                <option value="" disabled selected>Selecciona una opción</option>
                <option value="nuevo">Nuevo puesto</option>
                <option value="reemplazo">Reemplazo</option>
            </select>

            <!-- Campo Reemplazo (solo visible si el tipo es "Reemplazo") -->
            <div id="reemplazoFields" style="display: none;">
                <label for="reemplazoNombre">Nombre de la Persona Reemplazada</label>
                <input type="text" id="reemplazoNombre" name="reemplazoNombre" placeholder="Ingresa el nombre del reemplazo">

                <label for="reemplazoPuesto">Puesto a Reemplazar</label>
                <input type="text" id="reemplazoPuesto" name="reemplazoPuesto" placeholder="Ingresa el puesto a reemplazar">
            </div>
            
            <img src="https://grammermx.com/Fotos/00001606.png" width="50">

            <!-- Botón para enviar el formulario -->
            <button type="submit" class="btn-submit">Registrar</button>
        </form>
    </section>
</main>

<!-- Script para mostrar campos condicionales -->
<script>
    const tipoSelect = document.getElementById('tipo');
    const reemplazoFields = document.getElementById('reemplazoFields');

    tipoSelect.addEventListener('change', () => {
        if (tipoSelect.value === 'reemplazo') {
            reemplazoFields.style.display = 'block';
        } else {
            reemplazoFields.style.display = 'none';
        }
    });

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
</script>
</body>
</html>
