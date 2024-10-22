document.getElementById('loginForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Evita el envío del formulario por defecto

    const nomina = document.getElementById('nomina').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const statusMessage = document.getElementById('statusMessage');

    // Validar que los campos no estén vacíos
    if (!nomina || !correo) {
        alert('Por favor, complete todos los campos.');
        return;
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (nomina.length !== 8) {
        alert('La nómina debe tener exactamente 8 caracteres.');
        return;
    }

    // Validar formato del correo
    if (!validateEmail(correo)) {
        alert('Por favor, ingrese un correo electrónico válido.');
        return;
    }

    // Enviar los datos al PHP usando Fetch API
    const formData = new FormData();
    formData.append('nomina', nomina);
    formData.append('correo', correo);

    fetch('dao/manejoLogin.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = 'index.php'; // Redirigir al index si el login es exitoso
            } else {
                alert(data.message); // Mostrar mensaje de error
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error en la comunicación con el servidor.');
        });
});

// Función para validar el formato del correo
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}
