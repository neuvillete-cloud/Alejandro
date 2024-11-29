// Captura el ID del reporte de la URL
const params = new URLSearchParams(window.location.search);
const reporteId = params.get('id');

// Llamada inicial para mostrar los detalles del reporte
mostrarDetallesReporte(reporteId);

/** Función principal para mostrar los detalles del reporte */
function mostrarDetallesReporte(id) {
    fetch(`dao/obtenerDetalleReporte.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const reporte = data.reporte;

                agregarBotonRegresar();
                mostrarDatosReporte(reporte);
                configurarCambioEstatus(reporte);
                configurarFinalizacionReporte(reporte);

                if (reporte.Estatus === 'Completado') {
                    mostrarCarruselFotos(reporte.IdReporte);
                }
            }
        })
        .catch(error => {
            console.error('Error al obtener los detalles del reporte:', error);
        });
}

/** Agrega el botón de regresar en la parte superior de la página */
function agregarBotonRegresar() {
    document.body.insertAdjacentHTML('afterbegin', `
        <a href="javascript:history.back()" id="backButton">&#8592;</a>
    `);
}

/** Muestra los datos del reporte en el DOM */
function mostrarDatosReporte(reporte) {
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
    agregarBotonFinalizar(reporte);
}

/** Agrega el botón de finalizar al contenedor */
function agregarBotonFinalizar(reporte) {
    const finalizarButtonContainer = document.querySelector('.status-button-container');
    finalizarButtonContainer.insertAdjacentHTML('beforeend', `
        <button id="finalizarButton" ${reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado' ? 'disabled' : ''}>Finalizar</button>
    `);

    if (reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado') {
        mostrarAlertaReporteNoEditable(reporte);
    }
}

/** Muestra alerta si el reporte ya no es editable */
function mostrarAlertaReporteNoEditable(reporte) {
    Swal.fire({
        title: 'Acción no permitida',
        text: `Este reporte ya ha sido ${reporte.Estatus.toLowerCase()} y no se puede modificar.`,
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

/** Configura la lógica de cambio de estatus */
function configurarCambioEstatus(reporte) {
    document.getElementById('statusSelect').addEventListener('change', function() {
        manejarCambioEstatus(this, reporte);
    });
}

/** Maneja el cambio de estatus del reporte */
function manejarCambioEstatus(selectElement, reporte) {
    const nuevoEstatus = selectElement.value;

    if (reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado') {
        Swal.fire({
            title: 'Acción no permitida',
            text: `No se puede cambiar el estado porque el reporte ya está ${reporte.Estatus.toLowerCase()}.`,
            icon: 'error',
            confirmButtonText: 'OK'
        });
        selectElement.value = "";
        return;
    }

    if (nuevoEstatus === "Cancelado") {
        mostrarModalCancelacion(reporte.IdReporte);
    } else if (confirm(`¿Está seguro de que desea cambiar el estatus a "${nuevoEstatus}"?`)) {
        actualizarEstatusReporte(reporte.IdReporte, nuevoEstatus);
    } else {
        selectElement.value = "";
    }
}

/** Muestra el modal de cancelación */
function mostrarModalCancelacion(reporteId) {
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
    configurarModal('cancelarModal', cancelarReporte);
}

/** Configura la lógica del modal */
function configurarModal(modalId, submitCallback) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';

    document.querySelector(`#${modalId} .close`).addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };

    document.querySelector(`#${modalId} form`).addEventListener('submit', function(event) {
        event.preventDefault();
        submitCallback(modal);
    });
}

/** Cancela el reporte en el servidor */
function cancelarReporte(modal) {
    const comentarioCancelacion = document.getElementById('comentarioCancelacion').value;
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
                modal.style.display = 'none';
                document.getElementById('estatus').textContent = 'Cancelado';
            } else {
                mostrarError(data.message);
            }
        })
        .catch(error => mostrarError('Hubo un error al cancelar el reporte.'));
}

/** Actualiza el estatus del reporte */
function actualizarEstatusReporte(reporteId, nuevoEstatus) {
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
                mostrarError(data.message);
            }
        })
        .catch(error => mostrarError('Hubo un error al actualizar el estatus.'));
}

/** Configura la lógica para finalizar el reporte */
/** Configura la lógica para el botón de finalizar */
function configurarFinalizacionReporte(reporte) {
    // Escucha el evento de clic en el botón "Finalizar"
    const finalizarButton = document.getElementById('finalizarButton');
    finalizarButton.addEventListener('click', () => {
        mostrarModalFinalizacion(reporte.IdReporte);
    });
}

/** Muestra el modal de finalización al hacer clic en el botón "Finalizar" */
function mostrarModalFinalizacion(reporteId) {
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
    configurarModal('finalizarModal', finalizarReporte);
}


/** Lógica para finalizar el reporte */
function finalizarReporte(modal) {
    const comentarioFinal = document.getElementById('comentarioFinal').value;
    const fotoEvidencia = document.getElementById('fotoEvidencia').files[0];
    const formData = new FormData();

    formData.append('id', reporteId);
    formData.append('comentarioFinal', comentarioFinal);
    formData.append('fotoEvidencia', fotoEvidencia);

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
                modal.style.display = 'none';
                document.getElementById('estatus').textContent = 'Completado';
                mostrarCarruselFotos(reporteId);
            } else {
                mostrarError(data.message);
            }
        })
        .catch(error => mostrarError('Hubo un error al finalizar el reporte.'));
}

/** Muestra un error genérico */
function mostrarError(message) {
    Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
    });
}

/** Muestra el carrusel de fotos */
function mostrarCarruselFotos(reporteId) {
    fetch(`dao/obtenerFotosReporte.php?id=${reporteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                inicializarCarrusel(data.fotos);
            } else {
                console.log('No se encontraron fotos para el reporte');
            }
        })
        .catch(error => {
            console.error('Error al obtener las fotos:', error);
        });
}

/** Inicializa el carrusel con las fotos obtenidas */
function inicializarCarrusel(fotos) {
    const carruselContainer = document.getElementById('carruselContainer');
    carruselContainer.innerHTML = `
        <div class="slideshow-container">
            ${fotos.map((foto, index) => `
                <div class="mySlides fade">
                    <div class="numbertext">${index + 1} / ${fotos.length}</div>
                    <img src="${foto.url}" style="width:100%">
                    <div class="text">Foto ${index + 1}</div>
                </div>
            `).join('')}
            <a class="prev">&#10094;</a>
            <a class="next">&#10095;</a>
        </div>
        <div style="text-align:center">
            ${fotos.map((_, index) => `<span class="dot" data-index="${index}"></span>`).join('')}
        </div>
    `;
    configurarCarrusel();
}

/** Configura la lógica del carrusel */
function configurarCarrusel() {
    let slideIndex = 0;
    const slides = document.querySelectorAll('.mySlides');
    const dots = document.querySelectorAll('.dot');
    const prevButton = document.querySelector('.prev');
    const nextButton = document.querySelector('.next');

    function showSlides(index) {
        if (index >= slides.length) slideIndex = 0;
        if (index < 0) slideIndex = slides.length - 1;

        slides.forEach((slide, i) => {
            slide.style.display = i === slideIndex ? 'block' : 'none';
        });

        dots.forEach((dot, i) => {
            dot.className = i === slideIndex ? 'dot active' : 'dot';
        });
    }

    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

    function currentSlide(index) {
        showSlides(slideIndex = index);
    }

    showSlides(slideIndex);

    prevButton.addEventListener('click', () => plusSlides(-1));
    nextButton.addEventListener('click', () => plusSlides(1));
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => currentSlide(index));
    });
}
