function enviarDatos() {
    let inputsvalidos = validarinput("nomina") && validarinput("nombre") && validarinput("email") && validarinput("password");

    if (inputsvalidos) {
        let nomina = document.getElementById("nomina");
        let nombre = document.getElementById("nombre");
        let email = document.getElementById("email");
        let password = document.getElementById("password");

        const data = new FormData();
        data.append('nomina', nomina.value.trim());
        data.append('nombre', nombre.value.trim());
        data.append('email', email.value.trim());
        data.append('password', password.value.trim());
        alert("nomina: "+nomina.value.trim()+ ' nombre '+ nombre.value.trim()+ ' email '+ email.value.trim()+' password ' + password.value.trim())

        fetch('dao/registroUsuario.php', {
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

                // Comprobar el estado de la respuesta
                if (data.status === 'success') {
                    alert("Registro exitoso: " + data.message); // Mensaje de éxito
                } else {
                    alert("Error en el registro: " + data.message); // Mensaje de error
                }
            })
            .catch(error => {
                console.error('Hubo un problema con el registro:', error);
                alert("Error en el envío de datos. Por favor, intenta nuevamente."); // Alerta para cuando hay un error
            });

    } else {
        alert("Por favor, llena todos los campos correctamente."); // Alerta cuando los inputs no son válidos
    }
}
