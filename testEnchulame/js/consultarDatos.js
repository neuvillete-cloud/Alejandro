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

    console.log(idReporte); // Imprime: 123
    $.getJSON('https://grammermx.com/AleTest/testEnchulame/dao/consultar_reporte_por_id.php?id='+idReporte, function (response) {
        let data = response.data[0];
        alert(response.data[0]);
        alert(response.data[0].id);
        $('#id').val(data.id);

    });

}
