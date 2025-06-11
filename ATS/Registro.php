<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siemens Energy Clone Mejorado</title>
    <link rel="stylesheet" href="css/Registro.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <img src="imagenes/logo_blanco.png" alt="Logo Grammer" class="logo-img">
            <div class="logo-texto">
                <h1>Grammer</h1>
                <span>Automotive</span>
            </div>
        </div>
        <nav>
            <a href="indexAts.php">Buscar empleos</a>
            <a href="aboutUs.php">Acerca de nosotros</a>
            <a href="practicantes.php">Escuela de Talentos</a>
            <a href="#">Inclusión y diversidad</a>
            <a href="loginATS.php">Inicio de sesión</a>
        </nav>
    </div>
</header>

<section class="section-title">
    <h1>Registro Usuario</h1>
    <img src="imagenes/portafolio.png" alt="Imagen decorativa" class="imagen-banner">
</section>

<div class="section-blanca">
    <form class="formulario-registro" method="post" action="procesar_registro.php">
        <p>Si ya está registrado, <a href="loginATS.php" class="link-inicio">inicie sesión en su cuenta</a> para presentar su solicitud.</p>

        <!-- Campos del formulario -->
        <label>Correo electrónico <span class="required">*</span></label>
        <input type="email" name="email" required>

        <label>Nombre <span class="required">*</span></label>
        <input type="text" name="nombre" required>

        <label>Apellidos <span class="required">*</span></label>
        <input type="text" name="apellidos" required>

        <label>Teléfono preferido <span class="required">*</span></label>
        <input type="tel" name="telefono" required>

        <label>Contraseña <span class="required">*</span></label>
        <input type="password" name="contrasena" required>

        <label>Confirmación de contraseña <span class="required">*</span></label>
        <input type="password" name="confirmar_contrasena" required>

        <label>Sueldo deseado (MXN mensual) <span class="required">*</span></label>
        <input type="number" name="sueldo" required>

        <label>Nivel de estudios <span class="required">*</span></label>
        <select name="nivel_estudios" required>
            <option value="">Seleccione una opción</option>
            <option value="secundaria">Secundaria</option>
            <option value="preparatoria">Preparatoria</option>
            <option value="tecnico">Técnico</option>
            <option value="licenciatura">Licenciatura</option>
            <option value="maestria">Maestría</option>
            <option value="doctorado">Doctorado</option>
        </select>

        <label>Ubicación <span class="required">*</span></label>
        <input type="text" name="ubicacion" required placeholder="Ej. Querétaro, Qro.">

        <label>Área de interés <span class="required">*</span></label>
        <select name="area" id="area" required>
            <option value="">Seleccione un área</option>
            <option value="Seguridad e Higiene">Seguridad e Higiene</option>
            <option value="GPS">GPS</option>
            <option value="IT">IT</option>
            <option value="RH">RH</option>
            <option value="Calidad">Calidad</option>
            <option value="Ingenieria">Ingenieria</option>
            <option value="Controlling">Controlling</option>
            <option value="Logistica">Logistica</option>
            <option value="Mantenimiento">Mantenimiento</option>
            <option value="Producción (APU)">Producción (APU)</option>
            <option value="Finanzas">Finanzas</option>
            <option value="Compras">Compras</option>
            <option value="Regionales">Regionales</option>
        </select>

        <label>Especialidad <span class="required">*</span></label>
        <select name="especialidad" id="especialidad" required>
            <option value="">Seleccione una especialidad</option>
        </select>
        <div id="contenedor-otra-especialidad" class="input-centro" style="display: none;">
            <input type="text" id="otra_especialidad" name="otra_especialidad"
                   class="input-otro"
                   placeholder="Especifique su carrera">
        </div>


        <label>Fecha de nacimiento <span class="required">*</span></label>
        <input type="date" name="fecha_nacimiento" required>

        <!-- Aceptación de términos -->
        <section class="terminos-condiciones">
            <div class="checkbox-condiciones">
                <label>
                    <input type="checkbox" name="acepta_terminos" required>
                    He leído y acepto los <a href="terminos.pdf" target="_blank">Términos y Condiciones</a> y el <a href="aviso_privacidad.pdf" target="_blank">Aviso de Privacidad</a>.
                </label>
            </div>

            <div class="botones-final">
                <button type="reset" class="btn-cancelar">Cancelar</button>
                <button type="submit" class="btn-confirmar">Confirmar</button>
            </div>
        </section>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const areaSelect = document.getElementById('area');
    const especialidadSelect = document.getElementById('especialidad');
    const otraEspecialidad = document.getElementById('otra_especialidad');

    const opcionesEspecialidades = {
        'Calidad': ['Ingeniería Industrial', 'Mecánica', 'Química'],
        'Controlling': ['Contaduría de Costos', 'Administración', 'Ingeniería Industrial'],
        'GPS': ['Ingeniería Industrial', 'Ingeniería de Producción', 'Ingeniero Logística'],
        'IT': ['Ingeniería en Sistemas Computacionales'],
        'RH': ['Administración', 'Licenciatura en Recursos Humanos', 'Psicología'],
        'Ingenieria': ['Ingeniería Industrial', 'Administración', 'Ingeniería Mecánica'],
        'Logistica': ['Administración', 'Negocios Internacionales', 'Ingeniería Industrial', 'Ingeniería Mecánica'],
        'Producción (APU)': ['Mecánica', 'Ingeniería Industrial', 'Administración'],
        'Mantenimiento': ['Ingeniería Mecánica', 'Ingeniería Eléctrica', 'Ingeniería Industrial'],
        'Compras': ['Ingeniería Industrial', 'Administración', 'Negocios Internacionales'],
        'Seguridad e Higiene': ['Ingeniería Industrial', 'Ingeniería Ambiental', 'Ingeniería Química', 'Ingeniería Bioquímica'],
        'Finanzas': ['Contaduría Pública', 'Ingeniería Industrial', 'Administración', 'Negocios Internacionales'],
        'Regionales': []
    };

    areaSelect.addEventListener('change', function () {
        const area = this.value;
        especialidadSelect.innerHTML = '<option value="">Seleccione una especialidad</option>';
        otraEspecialidad.style.display = 'none';

        if (opcionesEspecialidades[area]) {
            opcionesEspecialidades[area].forEach(function (especialidad) {
                const option = document.createElement('option');
                option.value = especialidad;
                option.textContent = especialidad;
                especialidadSelect.appendChild(option);
            });

            const otra = document.createElement('option');
            otra.value = 'otra';
            otra.textContent = 'Otra';
            especialidadSelect.appendChild(otra);
        }
    });

    especialidadSelect.addEventListener('change', function () {
        if (this.value === 'otra') {
            otraEspecialidad.style.display = 'block';
        } else {
            otraEspecialidad.style.display = 'none';
        }
    });
</script>
</body>
</html>
