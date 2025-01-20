document.getElementById('login-form').addEventListener('submit', function (event) {
    event.preventDefault(); // Evita el envío del formulario por defecto

    let NumNomina = document.getElementById('NumNomina').value.trim();
    const Contrasena = document.getElementById('Contrasena').value.trim();

    // Validar que los campos no estén vacíos
    if (!NumNomina || !Contrasena) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos vacíos',
            text: 'Por favor, complete todos los campos.'
        });
        return;
    }

    // Completar la nómina con ceros a la izquierda si tiene menos de 8 caracteres
    if (NumNomina.length < 8) {
        NumNomina = NumNomina.padStart(8, '0');
    }

    // Enviar los datos al PHP usando Fetch API
    const formData = new FormData();
    formData.append('NumNomina', NumNomina);
    formData.append('Contrasena', Contrasena);

    fetch('dao/daoLogin.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Inicio de sesión exitoso',
                    text: 'Redirigiendo...',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = data.redirect; // Redirigir según la respuesta del PHP
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la comunicación con el servidor.'
            });
        });
});
