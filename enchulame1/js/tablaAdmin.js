document.addEventListener("DOMContentLoaded", function() {
    // Llamada a la API para obtener los reportes
    fetch('dao/mostrarDatosTabla.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
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
// Función para llenar la tabla con los reportes
function llenarTablaReportes(reportes) {
    const tablaReportes = document.getElementById('tablaReportes');

    // Limpiar la tabla antes de llenarla
    tablaReportes.innerHTML = '';

    // Iterar sobre los reportes y crear filas
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

        const celdaEstatus = document.createElement('td');
        celdaEstatus.textContent = reporte.Estatus;

        // Asignar clase para cambiar color según el estatus
        celdaEstatus.classList.add('status');
        if (reporte.Estatus === 'Recibido') {
            celdaEstatus.classList.add('recibido');
        } else if (reporte.Estatus === 'En Proceso') {
            celdaEstatus.classList.add('en-proceso');
        } else if (reporte.Estatus === 'Completado') {
            celdaEstatus.classList.add('completado');
        }

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
