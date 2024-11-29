// Captura el ID del reporte de la URL
const params = new URLSearchParams(window.location.search);
const reporteId = params.get('id');

// Función principal para cargar el reporte
function cargarReporte() {
    mostrarDetallesReporte(reporteId);
}

// Muestra los detalles del reporte según el ID
function mostrarDetallesReporte(id) {
    fetch(`dao/obtenerDetalleReporte.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const reporte = data.reporte;
                agregarBotonRegresar();
                mostrarDatosReporte(reporte);
                mostrarBotonFinalizar(reporte);
                manejarCambioDeEstatus(reporte);
                crearModalFinalizar(reporte);
                mostrarCarruselSiCompletado(reporte);
            }
        });
}

// Añadir el botón de regresar en forma de flecha
function agregarBotonRegresar() {
    document.body.insertAdjacentHTML('afterbegin', `
        <a href="javascript:history.back()" id="backButton">&#8592;</a>
    `);
}

// Muestra los datos del reporte en el HTML
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
}

// Mostrar el botón de finalizar si es posible
function mostrarBotonFinalizar(reporte) {
    const finalizarButtonContainer = document.querySelector('.status-button-container');
    finalizarButtonContainer.insertAdjacentHTML('beforeend', `
        <button id="finalizarButton" ${reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado' ? 'disabled' : ''}>Finalizar</button>
    `);
}

// Manejar el cambio de estatus
function manejarCambioDeEstatus(reporte) {
    if (reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado') {
        deshabilitarControles();
        mostrarAlertaAccionNoPermitida(reporte);
    }

    document.getElementById('statusSelect').addEventListener('change', function() {
        const nuevoEstatus = this.value;
        if (reporte.Estatus === 'Completado' || reporte.Estatus === 'Cancelado') {
            mostrarAlertaAccionNoPermitida(reporte);
            this.value = "";
            return;
        }
        if (nuevoEstatus === "Cancelado") {
            mostrarModalCancelacion();
        } else {
            cambiarEstatusReporte(nuevoEstatus);
        }
    });
}

// Deshabilitar controles cuando el reporte ya está completado o cancelado
function deshabilitarControles() {
    document.getElementById('finalizarButton').disabled = true;
    document.getElementById('statusSelect').disabled = true;
}

// Mostrar alerta cuando una acción no es permitida
function mostrarAlertaAccionNoPermitida(reporte) {
    Swal.fire({
        title: 'Acción no permitida',
        text: `Este reporte ya ha sido ${reporte.Estatus.toLowerCase()} y no se puede modificar.`,
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

// Función para mostrar el modal de cancelación
function mostrarModalCancelacion() {
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
    abrirCerrarModal('cancelarModal');
    manejarCancelacion();
}

function abrirCerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const isHidden = modal.classList.contains('hidden');
        if (isHidden) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    } else {
        console.error(`No se encontró el modal con el ID: ${modalId}`);
    }
}


// Maneja la cancelación del reporte
function manejarCancelacion() {
    document.getElementById('cancelarForm').addEventListener('submit', function(event) {
        event.preventDefault();
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
}

// Cambiar el estatus del reporte
function cambiarEstatusReporte(nuevoEstatus) {
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
        document.getElementById('statusSelect').value = "";
    }
}

// Crear el modal de finalización del reporte
function crearModalFinalizar(reporte) {
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
    abrirCerrarModal('finalizarModal');
    manejarFinalizacion(reporte);
}

// Maneja la finalización del reporte
function manejarFinalizacion(reporte) {
    document.getElementById('finalizarForm').addEventListener('submit', function(event) {
        event.preventDefault();
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
}


// Función para mostrar el carrusel de fotos
function  mostrarCarruselSiCompletado(idReporte, reporte) {
    // Verificar si el reporte tiene el estatus 'Completado'
    if (reporte.Estatus === 'Completado') {
        fetch(`dao/obtenerFotosReporte.php?id=${idReporte}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const fotos = data.fotos;
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

                    // Iniciar el carrusel
                    iniciarNuevoCarrusel();
                } else {
                    console.log('No se encontraron fotos para el reporte');
                }
            })
            .catch(error => {
                console.error('Error al obtener las fotos:', error);
            });
    } else {
        console.log('El reporte no está completado, no se muestran las fotos');
    }
}

// Función para manejar el carrusel de fotos con botones
function iniciarNuevoCarrusel() {
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


document.addEventListener('DOMContentLoaded', cargarReporte);

