<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ARCA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Enlazamos la misma hoja de estilos que el login -->
    <link rel="stylesheet" href="css/estilosAcceso.css">

</head>
<body>

<div class="login-wrapper">
    <div class="branding-panel">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <h1>Sistema de Gestión de Contenciones y Calidad</h1>
        <p>Una herramienta interna para asegurar la integridad de los procesos y materiales de la compañía.</p>
    </div>
    <div class="login-panel">

        <!-- Encabezado para móviles (igual que en login) -->
        <div class="mobile-branding">
            <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
            <p>Sistema de Gestión de Contenciones y Calidad</p>
        </div>

        <div class="login-form-container">
            <h2>Recuperar Contraseña</h2>
            <p class="subtitle">Introduce tu número de nómina para iniciar el proceso.</p>

            <!-- Formulario de recuperación -->
            <form id="recuperarForm" action="dao/daoRecuperar.php" method="POST">
                <div class="input-group">
                    <!-- Usamos un ícono relevante, y pedimos el NumNomina basado en tu BD -->
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="numNomina" name="numNomina" class="input-field" placeholder="Número de Nómina" required>
                </div>

                <!-- Eliminamos "Recordar sesión" y "Olvidaste contraseña" -->

                <button type="submit" class="submit-btn">Enviar Instrucciones</button>
            </form>
            <div class="form-footer">
                <!-- Enlace para volver al login -->
                <p>¿Recordaste tu contraseña? <a href="acceso.php">Inicia sesión aquí.</a></p>
            </div>
        </div>
        <div class="version-info">
            ARCA v1.0.1
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('recuperarForm');

    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevenimos el envío tradicional

        const formData = new FormData(form);

        Swal.fire({
            title: 'Procesando Solicitud...',
            text: 'Por favor, espera.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Apuntamos a un nuevo archivo DAO para manejar esta lógica
        fetch('https://grammermx.com/Mailer/RecuperarContra.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Revisa tu Correo!',
                        // El mensaje (data.message) vendría del servidor
                        text: data.message,
                        showConfirmButton: true
                        // No hay redirección, el usuario debe revisar su correo
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message, // Ej: "Número de nómina no encontrado"
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo comunicar con el servidor.',
                });
            });
    });
</script>

</body>
</html>
