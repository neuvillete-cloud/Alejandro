function enviarDatos() {
    if (validarinput("objeto") && validarinput("Descripcion") && validarinput("Area")) {
        let objeto = document.getElementById("objeto").value.trim();

        let descripcion = document.getElementById("Descripcion").value.trim();
        let area = document.getElementById("Area").value.trim();

        const data = new FormData();
        data.append('objeto', objeto);

        data.append('Descripcion', descripcion);
        data.append('Area', area);

        fetch('dao/registroReporte.php', {
            method: 'POST',
            body: data
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Registro exitoso: " + data.message);
                } else {
                    alert("Error en el registro: " + data.message);
                }
            })
            .catch(error => {
                console.error('Hubo un problema con el registro:', error);
                alert("Error en el envío de datos. Por favor, intenta nuevamente.");
            });
    } else {
        alert("Por favor, llena todos los campos correctamente.");
    }
}
