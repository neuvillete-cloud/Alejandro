document.addEventListener("DOMContentLoaded", function() {
    // Llamada a la API para obtener los reportes
    fetch('dao/mostrarDatosTabla.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            // Aquí puedes enviar datos adicionales si los necesitas
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                llenarTablaReportes(data.data);
            } else {
                console.error('Error al obtener los reportes:', data.message);
            }
        })
        .catch(error => {
            console.error('Error al hacer la solicitud:', error);
        });
});
function llenarTablaReportes(reportes) {
    const tablaReportes = document.getElementById('tablaReportes');

    // Limpiar la tabla antes de llenarla
    tablaReportes.innerHTML = '';

    reportes.forEach(reporte => {
        const fila = document.createElement('tr');

        // Crear las celdas con la información de los reportes
        const celdaIdReporte = document.createElement('td');
        celdaIdReporte.textContent = reporte.IdReporte;

        const celdaNombre = document.createElement('td');
        celdaNombre.textContent = reporte.NombreUsuario;

        const celdaArea = document.createElement('td');
        celdaArea.textContent = reporte.Area;

        const celdaUbicacion = document.createElement('td');
        celdaUbicacion.textContent = reporte.Ubicacion;

        const celdaFecha = document.createElement('td');
        celdaFecha.textContent = reporte.FechaRegistro;

        const celdaDescripcion = document.createElement('td');
        celdaDescripcion.textContent = reporte.DescripcionProblema;

        // Celda de estado
        const celdaEstatus = document.createElement('td');
        const estatusElemento = document.createElement('span');
        estatusElemento.classList.add('status');

        // Verifica el valor de reporte.Estatus
        console.log("Estado del reporte:", reporte.Estatus);

        switch (reporte.Estatus) {
            case 'Recibido':
                estatusElemento.classList.add('recibido');
                estatusElemento.textContent = 'Recibido';
                break;
            case 'En Proceso':
                estatusElemento.classList.add('en-proceso');
                estatusElemento.textContent = 'En Proceso';
                break;
            case 'Completado':
                estatusElemento.classList.add('completado');
                estatusElemento.textContent = 'Completado';
                break;
            default:
                estatusElemento.textContent = 'Desconocido';
                estatusElemento.classList.add('desconocido');
        }

        celdaEstatus.appendChild(estatusElemento);

        // Celda de acción con el botón "Ver detalles"
        const celdaAccion = document.createElement('td');
        const botonDetalles = document.createElement('button');
        botonDetalles.textContent = 'Ver detalles';
        botonDetalles.classList.add('action-btn');
        celdaAccion.appendChild(botonDetalles);

        // Agregar las celdas a la fila
        fila.appendChild(celdaIdReporte);
        fila.appendChild(celdaNombre);
        fila.appendChild(celdaArea);
        fila.appendChild(celdaUbicacion);
        fila.appendChild(celdaFecha);
        fila.appendChild(celdaDescripcion);
        fila.appendChild(celdaEstatus);
        fila.appendChild(celdaAccion);

        // Agregar la fila a la tabla
        tablaReportes.appendChild(fila);
    });
}
