<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - ARCA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="css/estilosRegistro.css">

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
            <h2>Crear una Cuenta</h2>
            <p class="subtitle">Completa el formulario para obtener acceso al sistema.</p>

            <form id="registroForm" action="dao/daoRegistroU.php" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-address-card"></i>
                    <input type="text" id="nombre" name="nombre" class="input-field" placeholder="Nombre Completo" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="nombreUsuario" name="nombreUsuario" class="input-field" placeholder="Nombre de Usuario" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="input-field" placeholder="Contraseña" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password_confirm" name="password_confirm" class="input-field" placeholder="Confirmar Contraseña" required>
                </div>

                <ul id="password-requirements" class="password-requirements">
                    <li id="req-length"><i class="fa-solid fa-circle-xmark"></i> Al menos 8 caracteres</li>
                    <li id="req-lowercase"><i class="fa-solid fa-circle-xmark"></i> Una letra minúscula</li>
                    <li id="req-uppercase"><i class="fa-solid fa-circle-xmark"></i> Una letra mayúscula</li>
                    <li id="req-number"><i class="fa-solid fa-circle-xmark"></i> Al menos un número</li>
                </ul>

                <button type="submit" class="submit-btn">Crear Cuenta</button>
            </form>

            <div class="form-footer">
                <p>¿Ya tienes una cuenta? <a href="acceso.php">Inicia Sesión aquí.</a></p>
            </div>
        </div>

        <div class="version-info">
            ARCA v1.0.1
        </div>
    </div>
</div>

<script>
    // --- Lógica del Asistente de Contraseña ---
    const passwordInput = document.getElementById('password');
    const requirementsList = document.getElementById('password-requirements');
    const reqLength = document.getElementById('req-length');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqNumber = document.getElementById('req-number');
    const requirements = [
        { el: reqLength,    regex: /.{8,}/ },
        { el: reqLowercase, regex: /[a-z]/ },
        { el: reqUppercase, regex: /[A-Z]/ },
        { el: reqNumber,    regex: /[0-9]/ }
    ];
    passwordInput.addEventListener('focus', () => {
        requirementsList.classList.add('visible');
    });
    passwordInput.addEventListener('keyup', () => {
        const password = passwordInput.value;
        requirements.forEach(req => {
            const icon = req.el.querySelector('i');
            if (req.regex.test(password)) {
                req.el.classList.add('valid');
                icon.classList.remove('fa-circle-xmark');
                icon.classList.add('fa-circle-check');
            } else {
                req.el.classList.remove('valid');
                icon.classList.remove('fa-circle-check');
                icon.classList.add('fa-circle-xmark');
            }
        });
    });

    // --- Lógica de Envío del Formulario con Fetch y SweetAlert2 ---
    const form = document.getElementById('registroForm');

    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevenimos el envío tradicional

        const password = passwordInput.value;
        const passwordConfirm = document.getElementById('password_confirm').value;

        if (password !== passwordConfirm) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas no coinciden.',
            });
            return;
        }

        const formData = new FormData(form);

        Swal.fire({
            title: 'Registrando...',
            text: 'Por favor, espera.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('dao/daoRegistroU.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registro Exitoso!',
                        text: data.message,
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = 'acceso.html';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo comunicar con el servidor. Inténtalo más tarde.',
                });
            });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>