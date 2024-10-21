document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Evitar el envío del formulario tradicional
    const nomina = document.getElementById('nomina').value;
    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;

    if (validarCampos(nomina, nombre, email)) {
        enviarFormulario(nomina, nombre, email);
    }
});

// Función para validar los campos
function validarCampos(nomina, nombre, email) {
    if (!nomina || !nombre || !email) {
        alert('Por favor, complete todos los campos.');
        return false;
    }

    if (!validarCorreo(email)) {
        alert('Por favor, ingrese un correo electrónico válido.');
        return false;
    }

    if (nomina.length < 8) {
        alert('El número de nómina debe tener 8 caracteres. Se completará automáticamente con ceros.');
        document.getElementById('nomina').value = nomina.padStart(8, '0'); // Completar con ceros
    } else if (nomina.length > 8) {
        alert('La nómina debe tener exactamente 8 caracteres.');
        return false;
    }

    return true; // Todos los campos son válidos
}

// Función para validar el formato del correo
function validarCorreo(email) {
    return email.includes('@');
}

// Función para enviar el formulario
function enviarFormulario(nomina, nombre, email) {
    const form = document.getElementById('loginForm');

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form)
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Abrir la página principal en una nueva ventana
                window.open('index.php', '_blank');
            } else {
                alert(data.message); // Mostrar mensaje de error
            }
        })
        .catch(error => {
            console.error('Error al enviar el formulario:', error);
            alert('Hubo un error en el proceso de inicio de sesión.');
        });
}
