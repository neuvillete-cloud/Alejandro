// Función para obtener y mostrar los reportes
async function obtenerReportes() {
    try {
        const response = await fetch('dao/mostrarDatosTabla.php');
        const reportes = await response.json();

        const tablaReportes = document.getElementById('tablaReportes');
        tablaReportes.innerHTML = ''; // Limpiar la tabla

        reportes.forEach(reporte => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${reporte.IdReporte}</td>
                <td>${reporte.nombre}</td>
                <td>${reporte.IdArea}</td>
                <td>${reporte.Ubicacion}</td>
                <td>${reporte.FechaRegistro}</td>
                <td>${reporte.DescripcionProblema}</td>
                <td>${reporte.IdEstatus}</td>
            `;
            tablaReportes.appendChild(fila);
        });
    } catch (error) {
        console.error('Error al obtener reportes:', error);
    }
}

// Llamar a la función para cargar los reportes al cargar la página
document.addEventListener('DOMContentLoaded', obtenerReportes);
