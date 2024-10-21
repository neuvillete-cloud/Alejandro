document.getElementById("registerForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Evitar el envío del formulario por defecto

    const nomina = document.getElementById('nomina').value;
    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const password = document.getElementById('password').value;
    const statusMessage = document.getElementById("statusMessage");

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
    registrarUsuario(nomina, nombre, correo, password)
        .then(data => {
            if (data.status === 'success') {
                statusMessage.style.color = "green";
                statusMessage.innerText = "Registro exitoso. Redirigiendo...";
                // Redirigir después de 2 segundos
                setTimeout(() => {
                    window.location.href = "login.php"; // Cambia esto a tu página de inicio de sesión
                }, 2000);
            } else {
                statusMessage.style.color = "red";
                statusMessage.innerText = data.message; // Mostrar el mensaje de error
            }
        })
        .catch(error => {
            statusMessage.style.color = "red";
            statusMessage.innerText = 'Error en la comunicación con el servidor.';
        });
});

// Función para validar el formato del correo
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

// Función para registrar al usuario
function registrarUsuario(nomina, nombre, correo, password) {
    return fetch('registroUsuarios.php', {
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
    }).then(response => response.json());
}
