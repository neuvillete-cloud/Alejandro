function actualizarPassword() {
    const nuevaContrasena = document.getElementById('nuevaContrasena').value;
    const confirmaContrasena = document.getElementById('confirmaContrasena').value;
    const errorMessage = document.getElementById('errorMessage');
    const iniciarSesionBtn = document.getElementById('iniciarSesionBtn');

    if (nuevaContrasena !== confirmaContrasena) {
        errorMessage.style.display = 'block';
        return;
    } else {
        errorMessage.style.display = 'none';
    }

    // Aquí enviarías la solicitud para actualizar la contraseña
    const numNomina = obtenerNumNominaDesdeURL(); // Implementa esta función para obtener el número de nómina desde la URL
    const token = obtenerTokenDesdeURL(); // Implementa esta función para obtener el token desde la URL

    fetch('actualizarContrasena.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            numNomina: numNomina,
            token: token,
            nuevaContrasena: nuevaContrasena,
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Éxito',
                    text: data.message,
                    icon: 'success'
                });
                iniciarSesionBtn.style.display = 'block'; // Muestra el botón de iniciar sesión
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message,
                    icon: 'error'
                });
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
}

function irAIniciarSesion() {
    window.location.href = 'paginaDeLogin.html'; // Cambia esto por la URL de tu página de inicio de sesión
}
