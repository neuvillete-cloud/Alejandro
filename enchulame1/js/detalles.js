// Captura el ID del reporte de la URL
const params = new URLSearchParams(window.location.search);
const reporteId = params.get('id');

// Muestra los detalles del reporte según el ID
function mostrarDetallesReporte(id) {
    // Llama al servidor para obtener los detalles del reporte según el ID
    fetch(`dao/obtenerDetalleReporte.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const reporte = data.reporte;

                // Añadir el botón de regresar en forma de luna al cuerpo del documento
                document.body.insertAdjacentHTML('afterbegin', `
                    <!-- Botón de regresar -->
                    <a href="javascript:history.back()" id="backButton">
                        &#8592;
                    </a>
                `);

                // Muestra los datos del reporte en la página
                const detalleDiv = document.getElementById('detalleReporte');
                detalleDiv.innerHTML = `
                    <div class="report-section">
                        <h2>Reporte #${reporte.IdReporte}</h2>
                        <p><strong>Nombre:</strong> ${reporte.NombreUsuario}</p>
                        <p><strong>Área:</strong> ${reporte.Area}</p>
                        <p><strong>Ubicación:</strong> ${reporte.Ubicacion}</p>
                        <p><strong>Fecha:</strong> ${reporte.FechaRegistro}</p>
                        <p><strong>Descripción del Problema:</strong> ${reporte.DescripcionProblema}</p>
                        <p><strong>Estado:</strong> <span id="estatus">${reporte.Estatus}</span></p>
                        <p><strong>Detalles Adicionales:</strong> ${reporte.DescripcionLugar || 'N/A'}</p>
                    </div>
                    <div class="image-container">
                        <img src="${reporte.FotoProblemaURL}" alt="Foto del Problema">
                    </div>
                    <!-- Contenedor separado para el botón de cambiar estatus -->
                    <div class="status-button-container">
                        <button id="statusButton">Cambiar Estatus</button>
                    </div>
                `;

                // Evento para cambiar el estatus del reporte
                document.getElementById('statusButton').addEventListener('click', function() {
                    if (confirm('¿Está seguro de que desea cambiar el estatus a "En Proceso"?')) {
                        fetch('Mailer/actualizarEstatusReporte.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${reporteId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    alert(data.message);
                                    // Actualizar el estatus en la página sin recargarla
                                    document.getElementById('estatus').textContent = 'En Proceso';
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => console.error('Error al actualizar el estatus:', error));
                    }
                });

            } else {
                document.getElementById('detalleReporte').innerHTML = '<p>Reporte no encontrado.</p>';
            }
        })
        .catch(error => {
            console.error('Error al obtener el reporte:', error);
            document.getElementById('detalleReporte').innerHTML = '<p>Error al cargar el reporte.</p>';
        });
}

// Llama a la función con el ID obtenido
mostrarDetallesReporte(reporteId);
