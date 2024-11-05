// RestableceContrasena.js

document.addEventListener('DOMContentLoaded', () => {
    // Obtén el token y el número de nómina de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const numNomina = urlParams.get('numNomina');
    const token = urlParams.get('token');

    // Establece los valores en los campos ocultos
    document.getElementById('numNomina').value = numNomina;
    document.getElementById('token').value = token;
});

// Función para actualizar la contraseña
function actualizarPassword() {
    const nuevaContrasena = document.getElementById('nuevaContrasena').value;
    const confirmaContrasena = document.getElementById('confirmaContrasena').value;

    if (nuevaContrasena !== confirmaContrasena) {
        document.getElementById('errorMessage').style.display = 'block';
        return;
    }

    const numNomina = document.getElementById('numNomina').value;
    const token = document.getElementById('token').value;

    // Envío de datos al servidor
    fetch('dao/daoRestablecerContrasena.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            numNomina: numNomina,
            token: token,
            nuevaContrasena: nuevaContrasena
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Éxito', data.message, 'success');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Error al actualizar la contraseña.', 'error');
        });
}
