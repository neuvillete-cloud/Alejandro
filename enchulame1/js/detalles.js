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
                        <p><strong>Fecha Finalizado:</strong> ${reporte.FechaFinalizado}</p>
                        <p><strong>Descripción del Problema:</strong> ${reporte.DescripcionProblema}</p>
                        <p><strong>Estado:</strong> <span id="estatus">${reporte.Estatus}</span></p>
                        <p><strong>Detalles Adicionales:</strong> ${reporte.DescripcionLugar || 'N/A'}</p>
                    </div>
                    <div class="image-container" id="carruselContainer">
                        <img src="${reporte.FotoProblemaURL}" alt="Foto del Problema">
                    </div>
                    <div class="status-button-container">
                        <select id="statusSelect" ${reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado' ? 'disabled' : ''}>
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
                    <button id="finalizarButton" ${reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado' ? 'disabled' : ''}>Finalizar</button>
                `);

                // Deshabilitar botones y mostrar alertas si el reporte ya está finalizado o cancelado
                if (reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado') {
                    document.getElementById('finalizarButton').disabled = true;
                    document.getElementById('statusSelect').disabled = true;

                    Swal.fire({
                        title: 'Acción no permitida',
                        text: `Este reporte ya ha sido ${reporte.Estatus.toLowerCase()} y no se puede modificar.`,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }

                // Evento para manejar el cambio de estatus desde el menú desplegable
                document.getElementById('statusSelect').addEventListener('change', function() {
                    const nuevoEstatus = this.value;

                    if (reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado') {
                        Swal.fire({
                            title: 'Acción no permitida',
                            text: `No se puede cambiar el estado porque el reporte ya está ${reporte.Estatus.toLowerCase()}.`,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        this.value = "";
                        return;
                    }

                    // Lógica existente para manejar cambios de estatus
                    if (nuevoEstatus === "Cancelado") {
                        // Mostrar modal de cancelación
                        const modalHTML = `
                            <div id="cancelarModal" class="modal">
                                <div class="modal-content">
                                    <span class="close">&times;</span>
                                    <h2>Comentario para Cancelación</h2>
                                    <form id="cancelarForm">
                                        <label for="comentarioCancelacion">Comentario:</label>
                                        <textarea id="comentarioCancelacion" name="comentarioCancelacion" required></textarea>
                                        <button type="submit">Cancelar Reporte</button>
                                    </form>
                                </div>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('beforeend', modalHTML);

                        // Abrir el modal
                        document.getElementById('cancelarModal').style.display = 'flex';

                        // Cerrar el modal al hacer clic en la "x" o fuera del modal
                        document.querySelector('.close').addEventListener('click', function() {
                            document.getElementById('cancelarModal').style.display = 'none';
                        });
                        window.onclick = function(event) {
                            if (event.target === document.getElementById('cancelarModal')) {
                                document.getElementById('cancelarModal').style.display = 'none';
                            }
                        };

                        // Manejar el envío del formulario de cancelación
                        document.getElementById('cancelarForm').addEventListener('submit', function(event) {
                            event.preventDefault();

                            const comentarioCancelacion = document.getElementById('comentarioCancelacion').value;

                            // Enviar la solicitud al servidor con el comentario
                            fetch('https://grammermx.com/Mailer/cancelarReporte.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `id=${reporteId}&comentarioCancelacion=${encodeURIComponent(comentarioCancelacion)}`
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire({
                                            title: 'Reporte Cancelado',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        });
                                        document.getElementById('cancelarModal').style.display = 'none';
                                        document.getElementById('estatus').textContent = 'Cancelado';
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
                                    console.error('Error al cancelar el reporte:', error);
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Hubo un error al cancelar el reporte.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                });
                        });
                    } else if (confirm(`¿Está seguro de que desea cambiar el estatus a "${nuevoEstatus}"?`)) {
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

                // Manejar el envío del formulario de finalización
                document.getElementById('finalizarForm').addEventListener('submit', function(event) {
                    event.preventDefault();

                    const formData = new FormData();
                    formData.append('id', reporteId);
                    formData.append('comentarioFinal', document.getElementById('comentarioFinal').value);
                    formData.append('fotoEvidencia', document.getElementById('fotoEvidencia').files[0]);

                    fetch('https://grammermx.com/Mailer/finalizarReporte.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Reporte Finalizado',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                });
                                document.getElementById('finalizarModal').style.display = 'none';
                                document.getElementById('estatus').textContent = 'Completado';

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
                            console.error('Error al finalizar el reporte:', error);
                            Swal.fire({
                                title: 'Error',
                                text: 'Hubo un error al finalizar el reporte.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                });


                // Mostrar el carrusel de fotos si el reporte está completado
                if (reporte.Estatus === 'Completado') {
                    mostrarCarruselFotos(reporte.IdReporte);
                }

                // Función para mostrar el carrusel de fotos
                function mostrarCarruselFotos(idReporte) {
                    fetch(`dao/obtenerFotosReporte.php?idReporte=${idReporte}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const fotos = data.fotos;
                                const carruselContainer = document.getElementById('carruselContainer');
                                carruselContainer.innerHTML = `
                                    <div id="carrusel">
                                        <button id="prevButton">&#8592; Anterior</button>
                                        <div id="carruselItems"></div>
                                        <button id="nextButton">Siguiente &#8594;</button>
                                    </div>
                                `;

                                const carruselItems = document.getElementById('carruselItems');
                                fotos.forEach(fotos => {
                                    const item = document.createElement('div');
                                    item.classList.add('carrusel-item');
                                    item.innerHTML = `<img src="${fotos.url}" alt="Foto del Reporte">`;
                                    carruselItems.appendChild(item);
                                });

                                iniciarCarrusel();
                            } else {
                                console.log('No se encontraron fotos para el reporte');
                            }
                        })
                        .catch(error => {
                            console.error('Error al obtener las fotos:', error);
                        });
                }

                // Función para manejar el carrusel de fotos con botones
                function iniciarCarrusel() {
                    const items = document.querySelectorAll('.carrusel-item');
                    const prevButton = document.getElementById('prevButton');
                    const nextButton = document.getElementById('nextButton');
                    let currentIndex = 0;

                    // Mostrar solo la primera imagen inicialmente
                    items.forEach((item, index) => {
                        item.style.display = index === currentIndex ? 'block' : 'none';
                    });

                    // Evento para botón "Anterior"
                    prevButton.addEventListener('click', () => {
                        items[currentIndex].style.display = 'none';
                        currentIndex = (currentIndex === 0) ? items.length - 1 : currentIndex - 1;
                        items[currentIndex].style.display = 'block';
                    });

                    // Evento para botón "Siguiente"
                    nextButton.addEventListener('click', () => {
                        items[currentIndex].style.display = 'none';
                        currentIndex = (currentIndex === items.length - 1) ? 0 : currentIndex + 1;
                        items[currentIndex].style.display = 'block';
                    });
                }

            }
        })
        .catch(error => {
            console.error('Error al obtener los detalles del reporte:', error);
        });
}

// Llamada a la función para mostrar los detalles del reporte
mostrarDetallesReporte(reporteId);
