function enviarDatos() {
    // Verificar que los inputs sean válidos
    let inputsvalidos = validarinput("objeto") && validarinput("Fecha") && validarinput("Descripcion") && validarinput("Area");

    if (inputsvalidos) {
        // Obtener los valores de los campos del formulario
        let objeto = document.getElementById("objeto");
        let fecha = document.getElementById("Fecha");
        let descripcion = document.getElementById("Descripcion");
        let area = document.getElementById("Area");

        // Crear un objeto FormData para enviar los datos
        const data = new FormData();
        data.append('objeto', objeto.value.trim());
        data.append('Fecha', fecha.value.trim());
        data.append('Descripcion', descripcion.value.trim());
        data.append('Area', area.value.trim());

        // Enviar los datos usando fetch
        fetch('dao/registroReporte.php', {
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

// Función para consultar los datos de la base de datos
function consultarDatos() {
    fetch('dao/conexion.php', { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            // Mostrar la tabla
            document.getElementById('tablaResultados').style.display = 'table';

            // Limpiar contenido previo
            const contenidoTabla = document.getElementById('contenidoTabla');
            contenidoTabla.innerHTML = '';

            // Insertar los datos recibidos en la tabla
            data.forEach(reporte => {
                let fila = `
                    <tr>
                        <td>${reporte.id}</td>
                        <td>${reporte.objeto}</td>
                        <td>${reporte.fecha}</td>
                        <td>${reporte.descripcion}</td>
                        <td>${reporte.area}</td>
                    </tr>`;
                contenidoTabla.insertAdjacentHTML('beforeend', fila);
            });
        })
        .catch(error => {
            console.error('Error al consultar los datos:', error);
        });
}
