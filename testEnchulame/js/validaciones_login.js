document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Evitar el envío del formulario tradicional
    const nomina = document.getElementById('nomina').value;
    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const password = document.getElementById('password').value; // Cambiado de "contrasena" a "password"

    if (validarCampos(nomina, nombre, correo, password)) {
        enviarFormulario(nomina, nombre, correo, password); // Pasar el password al enviar el formulario
    }
});

// Función para validar los campos
function validarCampos(nomina, nombre, correo, password) {
    if (!nomina || !nombre || !correo || !password) {
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

    // Validar el password (ejemplo simple: longitud mínima)
    if (password.length < 6) {
        alert('El password debe tener al menos 6 caracteres.'); // Cambiado de "contraseña" a "password"
        return false;
    }

    return true; // Todos los campos son válidos
}

// Función para validar el formato del correo
function validarCorreo(correo) {
    return correo.includes('@');
}

// Función para enviar el formulario
function enviarFormulario(nomina, nombre, correo, password) {
    const form = document.getElementById('loginForm');

    // Agregar el password al FormData
    const formData = new FormData(form);
    formData.append('password', password); // Cambiado de "contrasena" a "password"

    fetch(form.action, {
        method: 'POST',
        body: formData
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
