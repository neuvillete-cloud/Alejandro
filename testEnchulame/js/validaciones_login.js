document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Evitar el envío del formulario tradicional
    const nomina = document.getElementById('nomina').value;
    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;

    if (validarCampos(nomina, nombre, correo)) {
        enviarFormulario(nomina, nombre, correo);
    }
});

// Función para validar los campos
function validarCampos(nomina, nombre, correo) {
    if (!nomina || !nombre || !correo) {
        alert('Por favor, complete todos los campos.');
        return false;
    }

    if (!validarCorreo(correo)) {
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
function validarCorreo(correo) {
    return correo.includes('@');
}

// Función para enviar el formulario
function enviarFormulario(nomina, nombre, correo) {
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
