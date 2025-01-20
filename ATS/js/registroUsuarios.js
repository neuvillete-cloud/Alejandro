document.getElementById('registroformulario').addEventListener('submit', function (event) {
    event.preventDefault(); // Evitar el envío del formulario por defecto

    let NumNomina = document.getElementById('NumNomina').value.trim();
    const Nombre = document.getElementById('Nombre').value.trim();
    const Correo = document.getElementById('Correo').value.trim();
    const Contrasena = document.getElementById('Contrasena').value.trim();
    const Area = document.getElementById('Area').value.trim(); // Campo adicional de Área
    const statusMessage = document.getElementById('statusMessage'); // Mensaje de estado

    // Limpiar el mensaje de estado
    statusMessage.textContent = '';

    // Validar que los campos no estén vacíos
    if (!NumNomina || !Nombre || !Correo || !Contrasena || !Area) {
        statusMessage.textContent = 'Por favor, complete todos los campos.';
        return;
    }

    // Validar formato del correo
    if (!validateEmail(Correo)) {
        statusMessage.textContent = 'Por favor, ingrese un correo electrónico válido.';
        return;
    }

    // Completar la nómina con ceros al principio si es necesario
    if (NumNomina.length < 8) {
        NumNomina = NumNomina.padStart(8, '0'); // Completa con ceros a la izquierda
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (NumNomina.length !== 8) {
        statusMessage.textContent = 'La nómina debe tener exactamente 8 caracteres.';
        return;
    }

    // Enviar los datos al PHP usando FormData
    const formData = new FormData();
    formData.append('NumNomina', NumNomina);
    formData.append('Nombre', Nombre);
    formData.append('Correo', Correo);
    formData.append('Contrasena', Contrasena);
    formData.append('Area', Area);

    fetch('dao/registroUsuarios.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Mostrar mensaje de registro exitoso
                Swal.fire({
                    icon: 'success',
                    title: '¡Registro exitoso!',
                    text: 'Tu cuenta ha sido creada correctamente.',
                    confirmButtonText: 'Iniciar sesión'
                }).then(() => {
                    window.location.href = 'login.php'; // Redirigir a la página de inicio de sesión
                });
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
