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

                // Añadir el botón de regresar al cuerpo del documento
                document.body.insertAdjacentHTML('afterbegin', `
                    <!-- Botón de regresar -->
                    <a href="javascript:history.back()" id="backButton">&#8592;</a>
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
                    <div class="status-button-container">
                        <button id="statusButton">Cambiar a En Proceso</button>
                        <button id="finalizarButton">Finalizar Reporte</button>
                    </div>
                `;

                // Evento para cambiar el estatus del reporte
                document.getElementById('statusButton').addEventListener('click', function() {
                    if (confirm('¿Está seguro de cambiar el estatus a "En Proceso"?')) {
                        fetch('https://grammermx.com/Mailer/actualizarEstatusReporte.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${reporteId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('Éxito', data.message, 'success');
                                    document.getElementById('estatus').textContent = 'En Proceso';
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                });

                // Crear el modal de finalización
                const modalHTML = `
                    <div id="finalizarModal" class="modal">
                        <div class="modal-content">
                            <span class="close">&times;</span>
                            <h2>Finalizar Reporte</h2>
                            <form id="finalizarForm" enctype="multipart/form-data">
                                <label for="comentarioFinal">Comentario Final:</label>
                                <textarea id="comentarioFinal" name="comentarioFinal" required></textarea>
                                <label for="fotoEvidencia">Subir Foto de Evidencia:</label>
                                <input type="file" id="fotoEvidencia" name="fotoEvidencia" required>
                                <button type="submit">Finalizar</button>
                            </form>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Abrir el modal
                document.getElementById('finalizarButton').addEventListener('click', function() {
                    document.getElementById('finalizarModal').style.display = 'flex';
                });

                // Cerrar el modal
                document.querySelector('.close').addEventListener('click', function() {
                    document.getElementById('finalizarModal').style.display = 'none';
                });

                // Enviar los datos del formulario de finalización
                document.getElementById('finalizarForm').addEventListener('submit', function(event) {
                    event.preventDefault();
                    const formData = new FormData(finalizarForm);
                    formData.append('id', reporteId);

                    fetch('https://grammermx.com/Mailer/finalizarReporte.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('Éxito', data.message, 'success');
                                document.getElementById('estatus').textContent = 'Finalizado';
                                document.getElementById('finalizarModal').style.display = 'none';
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });

            } else {
                document.getElementById('detalleReporte').innerHTML = '<p>Reporte no encontrado.</p>';
            }
        })
        .catch(error => console.error('Error:', error));
}

mostrarDetallesReporte(reporteId);
