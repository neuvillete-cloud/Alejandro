<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Levantar Reporte</title>
    <link rel="stylesheet" href="css/estilosReporte.css">
</head>
<body>
<div class="container-report">
    <!-- Imagen superior -->
    <div class="logo-container">
        <img src="imagenes/Grammer_Logo_Original_Blue_sRGB_screen_transparent.png" alt="Logo" class="logo">
    </div>

    <div class="header-report">
        <h2>Levantar Reporte</h2>
    </div>

    <!-- Formulario de reporte -->
    <form id="reporteForm">
        <!-- Nombre de usuario (solo visual) -->
        <div class="form-section">
            <label class="field-label">Nombre:</label>
            <p id="nombreUsuario">[Nombre del Usuario]</p>
        </div>

        <!-- Campo de descripción -->
        <div class="form-section">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" placeholder="Describe el problema" required></textarea>
        </div>

        <!-- Campo de lugar (lista desplegable) -->
        <div class="form-section">
            <label for="lugar">Lugar</label>
            <select id="lugar" name="lugar" required>
                <option value="" disabled selected>Selecciona una nave</option>
                <option value="nave 1">nave 1</option>
                <option value="nave 2">nave 2</option>
                <option value="nave 3">nave 3</option>
                <option value="nave 4">nave 4</option>
                <option value="nave 5">nave 5</option>
                <option value="nave 6">nave 6</option>
                <option value="nave 7">nave 7</option>
                <option value="nave 8">nave 8</option>
                <option value="nave 9">nave 9</option>
                <option value="nave 10">nave 10</option>
                <option value="nave 11">nave 11</option>
                <option value="nave 12">nave 12</option>
                <!-- Agrega más opciones según sea necesario -->
            </select>
        </div>

        <!-- Descripción del lugar -->
        <div class="form-section">
            <label for="descripcionLugar">Descripción del Lugar</label>
            <textarea id="descripcionLugar" name="descripcionLugar" placeholder="Describe el lugar del problema" required></textarea>
        </div>

        <!-- Subida de fotos -->
        <div class="form-section">
            <label for="foto">Foto</label>
            <input type="file" id="foto" name="foto" accept="image/*">
        </div>

        <!-- Botón de envío -->
        <button type="submit" class="submit-btn">Enviar Reporte</button>
    </form>
</div>


</body>
</html>

