document.getElementById('registerBtn').addEventListener('click', function () {
    const nomina = document.getElementById('nomina').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const password = document.getElementById('password').value.trim();
    const statusMessage = document.getElementById('statusMessage');

    // Validar que los campos no estén vacíos
    if (!nomina || !nombre || !correo || !password) {
        statusMessage.textContent = 'Por favor, complete todos los campos.';
        return;
    }

    // Validar formato del correo
    if (!validateEmail(correo)) {
        statusMessage.textContent = 'Por favor, ingrese un correo electrónico válido.';
        return;
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (nomina.length !== 8) {
        statusMessage.textContent = 'La nómina debe tener exactamente 8 caracteres.';
        return;
    }

    // Crear un objeto FormData para enviar los datos
    const formData = new FormData();
    formData.append('nomina', nomina);
    formData.append('nombre', nombre);
    formData.append('correo', correo);
    formData.append('password', password);

    // Enviar los datos al PHP
    fetch('dao/registroUsuario.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'login.php'; // Redirigir a la página de inicio de sesión
            } else {
                statusMessage.textContent = data.message; // Mostrar el mensaje de error
            }
        })
        .catch(error => {
            statusMessage.textContent = 'Error en la comunicación con el servidor.';
        });
});

// Función para validar el formato del correo
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}
