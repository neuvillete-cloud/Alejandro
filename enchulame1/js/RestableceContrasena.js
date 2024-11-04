// Función para validar si las contraseñas coinciden
function validarPasswords(passwordId, confirmPasswordId, errorId) {
    var password = document.getElementById(passwordId).value.trim();
    var confirmPassword = document.getElementById(confirmPasswordId).value.trim();

    if (password === confirmPassword && password.length > 0) {
        document.getElementById(errorId).style.display = 'none'; // Ocultar mensaje de error
        return true; // Las contraseñas son válidas
    } else {
        document.getElementById(errorId).style.display = 'block'; // Mostrar mensaje de error
        return false; // Las contraseñas no coinciden
    }
}

// Función para actualizar la contraseña
function actualizarPassword() {
    // Validar que las contraseñas sean iguales
    var ContrasenaValida = validarPasswords('nuevaContrasena', 'confirmaContrasena', 'errorMessage');

    if (ContrasenaValida) {
        var queryString = window.location.search; // Obtener la cadena de consulta de la URL actual
        var searchParams = new URLSearchParams(queryString); // Crear un nuevo objeto URLSearchParams con la cadena de consulta
        var NumNomina = searchParams.get('NumNomina');
        var Token = searchParams.get('Token');

        // Verificar que se hayan obtenido el token y el NumNomina
        if (Token && NumNomina) {
            var nuevaContrasena = document.getElementById("nuevaContrasena");
            const data = new FormData();
            data.append('nuevaContrasena', nuevaContrasena.value.trim());
            data.append('Token', Token);
            data.append('NumNomina', NumNomina);

            console.log('Token:', Token, ' Usuario:', NumNomina);

            // Hacer la petición al servidor para actualizar la contraseña
            fetch(rutaBase + 'dao/daoRestablecerContrasena.php', {
                method: 'POST',
                body: data
            }).then(res => {
                if (!res.ok) {
                    throw new Error('Hubo un problema al actualizar la contraseña. Por favor, intenta de nuevo más tarde.');
                }
                return res.json();
            }).then(data => {
                if (data.status === 'success') {
                    console.log(data.message);
                    Swal.fire({
                        title: "Contraseña actualizada",
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "login.php";
                        }
                    });
                } else if (data.status === 'error') {
                    console.log(data.message);
                    Swal.fire({
                        title: "Error",
                        text: data.message,
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }
            }).catch(error => {
                console.log(error);
                Swal.fire({
                    title: "Error",
                    text: error,
                    icon: "error"
                });
            });
        } else {
            Swal.fire({
                title: "Enlace no válido",
                icon: "error"
            });
        }
    } else {
        Swal.fire({
            title: "Datos incorrectos",
            text: "Revise su información",
            icon: "error"
        });
    }
}

// Evento para manejar el envío del formulario
document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevenir el envío del formulario
    actualizarPassword(); // Llamar a la función para actualizar la contraseña
});
