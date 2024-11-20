// Captura el ID del reporte de la URL
const params = new URLSearchParams(window.location.search);
const reporteId = params.get('id');

// Muestra los detalles del reporte según el ID
function mostrarDetallesReporte(id) {
    fetch(`dao/obtenerDetalleReporte.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const reporte = data.reporte;

                // Añadir el botón de regresar en forma de flecha
                document.body.insertAdjacentHTML('afterbegin', `
                    <a href="javascript:history.back()" id="backButton">&#8592;</a>
                `);

                // Muestra los datos del reporte
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
                        <select id="statusSelect">
                            <option value="" disabled selected>Cambiar Estatus</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Cancelado">Cancelar</option>
                            <option value="No Aplica">No Aplica</option>
                        </select>
                    </div>
                `;

                // Añadir el botón de "Finalizar" dinámicamente al contenedor
                const finalizarButtonContainer = document.querySelector('.status-button-container');
                finalizarButtonContainer.insertAdjacentHTML('beforeend', `
                    <button id="finalizarButton">Finalizar</button>
                `);

                // Evento para manejar el cambio de estatus desde el menú desplegable
                document.getElementById('statusSelect').addEventListener('change', function() {
                    const nuevoEstatus = this.value;

                    if (confirm(`¿Está seguro de que desea cambiar el estatus a "${nuevoEstatus}"?`)) {
                        fetch('https://grammermx.com/Mailer/actualizarEstatusReporte.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${reporteId}&nuevoEstatus=${encodeURIComponent(nuevoEstatus)}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire({
                                        title: 'Estatus Actualizado',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    });
                                    document.getElementById('estatus').textContent = nuevoEstatus;
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error al actualizar el estatus:', error);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Hubo un error al actualizar el estatus.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            });
                    } else {
                        // Restablecer el menú al estado inicial si se cancela la acción
                        this.value = "";
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

                                <button type="submit">Finalizar Reporte</button>
                            </form>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Evento para abrir el modal al hacer clic en "Finalizar"
                document.getElementById('finalizarButton').addEventListener('click', function() {
                    document.getElementById('finalizarModal').style.display = 'flex';
                });

                // Cerrar el modal al hacer clic en la "x" o fuera del modal
                document.querySelector('.close').addEventListener('click', function() {
                    document.getElementById('finalizarModal').style.display = 'none';
                });
                window.onclick = function(event) {
                    if (event.target === document.getElementById('finalizarModal')) {
                        document.getElementById('finalizarModal').style.display = 'none';
                    }
                };

                // Enviar los datos del formulario de finalización
                document.getElementById('finalizarForm').addEventListener('submit', function(event) {
                    event.preventDefault();

                    const comentario = document.getElementById('comentarioFinal').value;
                    const foto = document.getElementById('fotoEvidencia').files[0];

                    const formData = new FormData();
                    formData.append('id', reporteId); // Agregar el ID del reporte
                    formData.append('comentarioFinal', comentario);
                    formData.append('fotoEvidencia', foto);

                    // Enviar la solicitud al servidor
                    fetch('https://grammermx.com/Mailer/finalizarReporte.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                // Cerrar el modal y actualizar el estatus en la página
                                document.getElementById('finalizarModal').style.display = 'none';
                                document.getElementById('estatus').textContent = 'Finalizado';
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error al finalizar el reporte:', error);
                            alert('Hubo un error al finalizar el reporte.');
                        });
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
