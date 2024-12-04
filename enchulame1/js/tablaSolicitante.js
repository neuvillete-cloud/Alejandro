document.addEventListener("DOMContentLoaded", function() {
    // Cargar los reportes al inicio
    loadReportes();

    // Evento para el filtro por Nave
    document.getElementById('nave').addEventListener('change', function() {
        const nave = this.value;
        loadReportes(undefined, nave, undefined); // Pasamos 'nave' como filtro
    });

    // Evento para cambiar la cantidad de reportes por página
    document.getElementById('report-count').addEventListener('change', function() {
        const count = this.value === '*' ? 0 : parseInt(this.value); // Enviar 0 si es "Todos"
        loadReportes(undefined, undefined, count);
    });

    // Evento para actualizar la tabla cuando el usuario regresa a la página
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // Cargar los reportes nuevamente al volver a la página
            loadReportes();
        }
    });
});

// Función para cargar los reportes con los filtros aplicados
function loadReportes(nave = '', reportCount = 0) {
    const numNomina = sessionStorage.getItem('NumNomina'); // Obtener el NumNomina de la sesión

    fetch('dao/mostrarDatosTablaSolicitante.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            nave,
            reportCount,
            numNomina // Enviar el NumNomina para filtrar los reportes por usuario
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
}

// Función para llenar la tabla con los reportes
function llenarTablaReportes(reportes) {
    const tablaReportes = document.getElementById('tablaReportes');
    tablaReportes.innerHTML = ''; // Limpiar tabla antes de llenarla

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

        const celdaFechaCompromiso = document.createElement('td');
        celdaFechaCompromiso.textContent = reporte.FechaCompromiso;

        const celdaEstatus = document.createElement('td');
        const spanEstatus = document.createElement('span');

        if (reporte.Estatus === 'recibido') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'recibido');
        } else if (reporte.Estatus === 'En Proceso') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'en-proceso');
        } else if (reporte.Estatus === 'Completado') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'completado');
        } else if (reporte.Estatus === 'Cancelado') {
            spanEstatus.textContent = reporte.Estatus;
            spanEstatus.classList.add('status', 'Cancelado');
        }

        celdaEstatus.appendChild(spanEstatus);

        // Eliminar la columna de acción (botón de detalles)
        fila.appendChild(celdaIdReporte);
        fila.appendChild(celdaNombre);
        fila.appendChild(celdaArea);
        fila.appendChild(celdaUbicacion);
        fila.appendChild(celdaFecha);
        fila.appendChild(celdaDescripcion);
        fila.appendChild(celdaFechaCompromiso);
        fila.appendChild(celdaEstatus);

        tablaReportes.appendChild(fila);
    });
}
