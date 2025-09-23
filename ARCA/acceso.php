<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema ARCA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        <div class="login-form-container">
            <h2>Bienvenido de Vuelta</h2>
            <p class="subtitle">Por favor, introduce tus credenciales para acceder.</p>

            <form id="loginForm" action="dao/daoLogin.php" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="nombreUsuario" name="nombreUsuario" class="input-field" placeholder="Nombre de Usuario" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="input-field" placeholder="Contraseña" required>
                </div>
                <div class="extra-options">
                    <div>
                        <input type="checkbox" id="remember" name="remember" style="margin-right: 5px;">
                        <label for="remember">Recordar sesión</label>
                    </div>
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="submit-btn">Acceder</button>
            </form>
            <div class="form-footer">
                <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí.</a></p>
            </div>
        </div>
        <div class="version-info">
            ARCA v1.0.1
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('loginForm');

    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevenimos el envío tradicional

        const formData = new FormData(form);

        Swal.fire({
            title: 'Iniciando Sesión...',
            text: 'Por favor, espera.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('dao/daoLogin.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Bienvenido!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500 // Se cierra automáticamente
                    }).then(() => {
                        // ¡ESTE ES EL CAMBIO!
                        // Redirige a la URL que nos proporciona el PHP.
                        window.location.href = data.redirect_url;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Acceso',
                        text: data.message,
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