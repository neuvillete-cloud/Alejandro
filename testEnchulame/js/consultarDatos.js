function consultarDatos() {
    // Realizamos la solicitud GET al archivo PHP para obtener los datos
    fetch('dao/consultar_reporte.php', {
        method: 'GET'
    })
        .then(response => response.json()) // Convertimos la respuesta a JSON
        .then(data => {
            // Mostramos la tabla si hay datos
            if (data.length > 0) {
                document.getElementById('tablaResultados').style.display = 'table';
            }

            // Limpiamos la tabla antes de insertar nuevos datos
            const contenidoTabla = document.getElementById('contenidoTabla');
            contenidoTabla.innerHTML = '';

            // Recorremos los datos recibidos y los insertamos en la tabla
            data.forEach(reporte => {
                let fila = `
                <tr>
                    <td>${reporte.id}</td>
                    <td>${reporte.objeto}</td>
                    <td>${reporte.fecha}</td>
                    <td>${reporte.descripcion}</td>
                    <td>${reporte.area}</td>
                    <td><button onclick="cargarReporte(${reporte.id})">Actualizar</button></td>
                </tr>`;
                contenidoTabla.insertAdjacentHTML('beforeend', fila);

            });
        })
        .catch(error => {
            console.error('Error al consultar los datos:', error);
        });
}

function cargarReporte(idReporte){
    window.location.href= "https://grammermx.com/AleTest/testEnchulame/reportes.php?id="+idReporte;
   }

function cargarDatosReporte(){
    // Obtener los parámetros de la URL
    const params = new URLSearchParams(window.location.search);

    // Obtener el valor del parámetro "id"
    const idReporte = params.get('id');
    console.log(idReporte); // Verificar el ID recibido desde la URL

    // Realizar la solicitud GET para obtener el reporte
    $.getJSON('https://grammermx.com/AleTest/testEnchulame/dao/consultar_reporte_por_id.php?id=' + idReporte, function (response) {
            console.log(response); // Verifica toda la respuesta JSON
            // La respuesta es directamente un array
            if (Array.isArray(response) && response.length > 0) {
                let data = response[0]; // Toma el primer elemento del array
                console.log("Datos recibidos:", data);
                // Asigna los valores a los inputs
                $('#id').val(data.id);
                $('#objeto').val(data.objeto);
                $('#fecha').val(data.fecha);
                $('#descripcion').val(data.descripcion);
                $('#area').val(data.area);
            } else {
                alert('No se encontraron datos para este ID.');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud: ", textStatus, errorThrown);
    });
}

function actualizarReporte() {
    // Crear un objeto FormData
    let formData = new FormData();

    // Recuperar los datos de los inputs
    formData.append('id', $('#id').val()); // ID no se modificará pero es necesario enviarlo
    formData.append('objeto', $('#objeto').val());
    formData.append('fecha', $('#fecha').val()); // Fecha no se modificará
    formData.append('descripcion', $('#descripcion').val());
    formData.append('area', $('#area').val());

    // Realizar la solicitud POST para actualizar el reporte
    fetch('https://grammermx.com/AleTest/testEnchulame/dao/actualizar_reporte.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reporte actualizado exitosamente');
            } else {
                alert('Error al actualizar el reporte: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Ocurrió un error al actualizar el reporte.');
        });
}
