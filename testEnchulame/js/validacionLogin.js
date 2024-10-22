document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Evitar que el formulario se envíe de manera tradicional

    // Obtener los valores de los campos
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

    // Enviar los datos al PHP usando FormData
    const formData = new FormData();
    formData.append('nomina', nomina);
    formData.append('nombre', nombre);
    formData.append('correo', correo);
    formData.append('password', password);

    fetch('dao/manejoLogin.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Redirigir al index si el inicio de sesión es exitoso
                window.location.href = 'index.php';
            } else {
                // Mostrar mensaje de error
                statusMessage.textContent = data.message;
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
