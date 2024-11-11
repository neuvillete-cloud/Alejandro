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
    tablaReportes.innerHTML = '';  // Limpiar tabla antes de llenarla

    reportes.forEach(reporte => {
        const fila = document.createElement('tr');

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

        // Celda para el estatus con clases CSS correspondientes
        const celdaEstatus = document.createElement('td');
        const spanEstatus = document.createElement('span');  // Usamos un span para el estatus

        // Verificamos si el estatus tiene el valor esperado
        if (reporte.Estatus === 'recibido') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'recibido');
        } else if (reporte.Estatus === 'En Proceso') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'en-proceso');
        } else if (reporte.Estatus === 'Completado') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'completado');
        } else {
            spanEstatus.textContent = 'Estatus Desconocido';
            spanEstatus.classList.add('status', 'desconocido');
        }

        // Agregar el span con el estatus a la celda
        celdaEstatus.appendChild(spanEstatus);

        // Celda de acción con botón "Ver detalles"
        const celdaAccion = document.createElement('td');
        const botonDetalles = document.createElement('button');
        botonDetalles.textContent = 'Ver detalles';
        botonDetalles.classList.add('action-btn')
        botonDetalles.addEventListener('click', function() {
            window.location.href = `detallesReporte.html?id=${reporte.IdReporte}`;
        });

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
