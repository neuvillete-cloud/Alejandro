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

        // Depuración: Verifica que el valor de reporte.Estatus sea el esperado
        console.log("Estatus del reporte:", reporte.Estatus);

        // Asignar clase para cambiar color según el estatus
        celdaEstatus.classList.add('status');

        // Comprobamos si los valores de reporte.Estatus son los correctos y agregamos las clases correspondientes
        if (reporte.Estatus === 'Recibido') {
            celdaEstatus.classList.add('recibido');
        } else if (reporte.Estatus === 'En Proceso') {
            celdaEstatus.classList.add('en-proceso');
        } else if (reporte.Estatus === 'Completado') {
            celdaEstatus.classList.add('completado');
        } else {
            // Si el valor no es ninguno de los esperados, mostramos un error en la consola
            console.error("Estatus desconocido:", reporte.Estatus);
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
