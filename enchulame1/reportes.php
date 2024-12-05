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
        <button class="new-button">Historial</button>
    </div>

    <!-- Formulario de reporte -->
    <form id="reporteForm">

        <!-- Campo de descripción -->
        <div class="form-section">
            <label for="descripcion" class="field-label">Descripción</label>
            <textarea id="descripcion" name="descripcion" placeholder="Describe el problema" required></textarea>
        </div>

        <!-- Campo de lugar (lista desplegable) -->
        <div class="form-section">
            <label for="lugar" class="field-label">Lugar</label>
            <select id="lugar" name="lugar" required>
                <option value="" disabled selected>Selecciona un lugar</option>
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
            </select>
        </div>

        <!-- Nueva lista desplegable para la planta -->
        <div class="form-section">
            <label for="planta" class="field-label">Planta</label>
            <select id="planta" name="planta" required>
                <option value="" disabled selected>Selecciona una planta</option>
                <option value="alta">Planta Alta</option>
                <option value="baja">Planta Baja</option>
            </select>
        </div>

        <!-- Descripción del lugar -->
        <div class="form-section">
            <label for="descripcionLugar" class="field-label">Descripción del Lugar</label>
            <textarea id="descripcionLugar" name="descripcionLugar" placeholder="Describe el lugar del problema" required></textarea>
        </div>

        <!-- Subida de fotos con nuevo diseño -->
        <div class="photo-container" id="photoPreview">
            <span>📷</span>
            <p>Haz clic o arrastra una imagen aquí para subirla</p>
            <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
        </div>

        <!-- Botón de envío -->
        <button type="submit" class="submit-btn">Enviar Reporte</button>
    </form>
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const photoContainer = document.getElementById('photoPreview');
    const fileInput = document.getElementById('foto');

    // Abre el selector de archivos cuando se hace clic en el cuadro
    photoContainer.addEventListener('click', () => {
        fileInput.click();
    });

    // Maneja la carga de la imagen
    fileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                // Limpia cualquier imagen previa, pero mantiene el texto y el icono
                const existingImage = photoContainer.querySelector('img');
                if (existingImage) {
                    existingImage.remove();
                }

                // Añade la nueva imagen
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = "Previsualización";
                photoContainer.appendChild(img);

                // Asegura que la imagen ocupe todo el contenedor
                img.style.width = "100%";
                img.style.height = "100%";
                img.style.objectFit = "cover";
            };
            reader.readAsDataURL(file);
        }
    });


</script>
<script src="js/manejoReportes.js"></script>
</body>
</html>


