document.getElementById('loginForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Evita el envío del formulario por defecto

    const NumNomina = document.getElementById('NumNomina').value.trim();
    const Contrasena = document.getElementById('Contrasena').value.trim();
    const statusMessage = document.getElementById('statusMessage');

    // Validar que los campos no estén vacíos
    if (!NumNomina || !Contrasena) {
        alert('Por favor, complete todos los campos.');
        return;
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (NumNomina.length !== 8) {
        alert('La nómina debe tener exactamente 8 caracteres.');
        return;
    }

    // Enviar los datos al PHP usando Fetch API
    const formData = new FormData();
    formData.append('NumNomina', NumNomina);
    formData.append('Contrasena', Contrasena);

    fetch('dao/ManejoLogin.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = data.redirect; // Redirigir según la respuesta del PHP
            } else {
                alert(data.message); // Mostrar mensaje de error
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error en la comunicación con el servidor.');
        });
});
