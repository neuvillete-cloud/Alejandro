function enviarDatos() {
    let inputsvalidos = validarinput("nomina")&& validarinput("nombre")&& validarinput("email")&& validarinput("contrasena")
    if (inputsvalidos){
        let nomina = document.getElementById("nomina");
        let nombre = document.getElementById("nombre");
        let email = document.getElementById("email");
        let contrasena = document.getElementById("contrasena");


        const data = new FormData()

        data.append('nomina', nomina.value.trim())
        data.append('nombre',nombre.value.trim())
        data.append('email',email.value.trim())
        data.append('contraseña',contrasena.value.trim())

        fetch('../dao/registroUsuario.php', {
            method: 'POST',
            body: data
        })
            .then(response => {
                if (response.ok) {
                    return response.json(); // Suponiendo que el servidor regresa JSON
                } else {
                    throw new Error('Error en el envío de datos');
                }
            })
            .then(data => {
                // Maneja la respuesta del servidor
                console.log("Registro exitoso:", data);
                // Podrías mostrar un mensaje de éxito, redirigir, etc.
            })
            .catch(error => {
                console.error('Hubo un problema con el registro:', error);
                // Manejo de errores, como mostrar un mensaje al usuario
            });

    }
}