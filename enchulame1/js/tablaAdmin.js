document.addEventListener("DOMContentLoaded", function() {
    // Cargar los reportes al inicio
    loadReportes('', '', 0);

    // Evento para la lupa dentro del input (buscar por ID)
    document.getElementById('search-id').addEventListener('input', function(event) {
        const searchId = event.target.value;
        loadReportes(searchId, undefined);
    });

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
            loadReportes('', '', 0);
        }
    });
});

// Función para cargar los reportes con los filtros aplicados
function loadReportes(searchId = '', nave = '', reportCount = 0) {
    fetch('dao/mostrarDatosTabla.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ searchId, nave, reportCount })
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

        const celdaAccion = document.createElement('td');
        const botonDetalles = document.createElement('button');
        botonDetalles.textContent = 'Ver detalles';
        botonDetalles.classList.add('action-btn');
        botonDetalles.addEventListener('click', function() {
            window.location.href = `detallesReporte.php?id=${reporte.IdReporte}`;
        });

        celdaAccion.appendChild(botonDetalles);

        fila.appendChild(celdaIdReporte);
        fila.appendChild(celdaNombre);
        fila.appendChild(celdaArea);
        fila.appendChild(celdaUbicacion);
        fila.appendChild(celdaFecha);
        fila.appendChild(celdaDescripcion);
        fila.appendChild(celdaFechaCompromiso);
        fila.appendChild(celdaEstatus);
        fila.appendChild(celdaAccion);

        tablaReportes.appendChild(fila);
    });
}


