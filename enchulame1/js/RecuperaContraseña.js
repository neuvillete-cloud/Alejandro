function recuperarPassword() {
    var correoRecuperacion = document.getElementById("correoRecuperacion");

    if (!correoRecuperacion || !correoRecuperacion.value.trim()) {
        Swal.fire({
            title: "Error",
            text: "Por favor, ingrese un correo electrónico válido.",
            icon: "error"
        });
        return;
    }

    const data = new FormData();
    data.append('correoRecuperacion', correoRecuperacion.value.trim());

    fetch('https://grammermx.com/Mailer/mailerRecuperarContrasena.php', {
        method: 'POST',
        body: data
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Hubo un problema al recuperar la contraseña. Por favor, intenta de nuevo más tarde.');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: "Solicitud exitosa",
                    text: "Hemos enviado un correo electrónico a " + correoRecuperacion.value + " para restablecer tu contraseña.",
                    icon: "success",
                    confirmButtonText: "OK"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "../sesion/login.php"; // Redirigir a la página de inicio de sesión
                    }
                });
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.message,
                    icon: "error",
                    confirmButtonText: "OK"
                });
            }
        })
        .catch(error => {
            console.error(error);
            Swal.fire({
                title: "Error",
                text: "Hubo un problema al procesar tu solicitud. Por favor, intenta de nuevo más tarde.",
                icon: "error",
                confirmButtonText: "OK"
            });
        });
}
