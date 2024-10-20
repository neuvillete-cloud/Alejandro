document.getElementById('registerBtn').addEventListener('click', function () {
    const nomina = document.getElementById('nomina').value;
    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const password = document.getElementById('password').value;
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

    // Enviar los datos al PHP
    fetch('registroUsuarios.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            nomina: nomina,
            nombre: nombre,
            correo: correo,
            password: password
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la comunicación con el servidor.');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'login.php'; // Redirigir a la página de inicio de sesión
            } else {
                statusMessage.textContent = data.message; // Mostrar el mensaje de error
            }
        })
        .catch(error => {
            statusMessage.textContent = 'Error en la comunicación con el servidor: ' + error.message;
        });
});

// Función para validar el formato del correo
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}
