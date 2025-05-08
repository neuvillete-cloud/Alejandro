document.querySelector('.formulario-registro').addEventListener('submit', function (event) {
    event.preventDefault();

    const email = document.querySelector('input[name="email"]').value.trim();
    const nombre = document.querySelector('input[name="nombre"]').value.trim();
    const apellidos = document.querySelector('input[name="apellidos"]').value.trim();
    const telefono = document.querySelector('input[name="telefono"]').value.trim();
    const contrasena = document.querySelector('input[name="contrasena"]').value.trim();
    const confirmarContrasena = document.querySelector('input[name="confirmar_contrasena"]').value.trim();

    if (!email || !nombre || !apellidos || !telefono || !contrasena || !confirmarContrasena) {
        Swal.fire({
            icon: 'error',
            title: 'Campos incompletos',
            text: 'Por favor, completa todos los campos obligatorios.',
        });
        return;
    }

    if (!validateEmail(email)) {
        Swal.fire({
            icon: 'error',
            title: 'Correo inválido',
            text: 'Ingresa un correo electrónico válido.',
        });
        return;
    }

    if (contrasena !== confirmarContrasena) {
        Swal.fire({
            icon: 'error',
            title: 'Contraseñas diferentes',
            text: 'Las contraseñas no coinciden.',
        });
        return;
    }

    const formData = new FormData();
    formData.append('email', email);
    formData.append('nombre', nombre);
    formData.append('apellidos', apellidos);
    formData.append('telefono', telefono);
    formData.append('contrasena', contrasena);

    fetch('procesar_registro.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro exitoso!',
                    text: 'Tu cuenta ha sido creada.',
                    confirmButtonText: 'Iniciar sesión'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Ocurrió un error al registrar.',
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Error del servidor',
                text: 'No se pudo conectar con el servidor.',
            });
        });
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email.toLowerCase());
}
