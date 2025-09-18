document.addEventListener('DOMContentLoaded', function() {

    const modal = document.getElementById('details-modal');
    const modalCloseBtn = document.getElementById('modal-close');
    const modalBody = document.getElementById('modal-body');
    const modalFolio = document.getElementById('modal-folio');

    // Función para cerrar el modal
    function closeModal() {
        // Usamos la clase 'visible' para controlar las animaciones de CSS
        modal.classList.remove('visible');
    }
    modalCloseBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        // Se cierra si se hace clic en el fondo oscuro (el overlay)
        if (e.target === modal) {
            closeModal();
        }
    });
    // También se puede cerrar con la tecla 'Escape'
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && modal.classList.contains('visible')) {
            closeModal();
        }
    });


    // --- LÓGICA PARA ABRIR Y LLENAR EL MODAL DE DETALLES ---
    document.querySelectorAll('.btn-details').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;

            // Preparamos y mostramos el modal con un mensaje de carga
            modalFolio.textContent = `S-${id.padStart(4, '0')}`;
            modalBody.innerHTML = '<p>Cargando datos...</p>';
            modal.classList.add('visible'); // Usamos la clase para mostrarlo

            // Hacemos la llamada al servidor para obtener los datos
            fetch(`dao/get_solicitud_details.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('La respuesta del servidor no fue exitosa.');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.status === 'success') {
                        const data = result.data;

                        // Plantilla del Método de Trabajo
                        let metodoHTML = '';
                        if (data.RutaArchivo) {
                            metodoHTML = `
                                <fieldset><legend>Método de Trabajo</legend>
                                    <iframe src="${data.RutaArchivo}" width="100%" height="500px" frameborder="0"></iframe>
                                </fieldset>`;
                        }

                        // Plantilla de Defectos
                        let defectosHTML = '<fieldset><legend>Defectos Registrados</legend>';
                        if (data.defectos && data.defectos.length > 0) {
                            data.defectos.forEach((defecto, index) => {
                                defectosHTML += `
                                    <div class="defecto-view-item">
                                        <h4>Defecto #${index + 1}: ${defecto.NombreDefecto || ''}</h4>
                                        <div class="defect-view-gallery">
                                            <div class="defect-image-container">
                                                <label>Foto OK</label>
                                                <img src="${defecto.RutaFotoOk}" alt="Foto OK del defecto ${defecto.NombreDefecto}">
                                            </div>
                                            <div class="defect-image-container">
                                                <label>Foto NO OK</label>
                                                <img src="${defecto.RutaFotoNoOk}" alt="Foto NO OK del defecto ${defecto.NombreDefecto}">
                                            </div>
                                        </div>
                                    </div>`;
                            });
                        } else {
                            defectosHTML += '<p>No se registraron defectos para esta solicitud.</p>';
                        }
                        defectosHTML += '</fieldset>';

                        // Construimos el HTML completo del modal con la estructura del formulario de solo lectura
                        modalBody.innerHTML = `
                            <fieldset><legend>Datos Generales</legend>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nombre del Responsable</label>
                                        <input type="text" value="${data.Responsable || ''}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Número de Parte</label>
                                        <input type="text" value="${data.NumeroParte || ''}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Cantidad</label>
                                        <input type="text" value="${data.Cantidad || ''}" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Descripción del Problema</label>
                                    <textarea rows="3" readonly>${data.Descripcion || ''}</textarea>
                                </div>
                            </fieldset>

                            <fieldset><legend>Clasificación</legend>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Proveedor</label>
                                        <input type="text" value="${data.NombreProvedor || ''}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Commodity</label>
                                        <input type="text" value="${data.NombreCommodity || ''}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Terciaria</label>
                                        <input type="text" value="${data.NombreTerciaria || ''}" readonly>
                                    </div>
                                </div>
                            </fieldset>
                            
                            ${metodoHTML}
                            ${defectosHTML}
                        `;
                    } else {
                        modalBody.innerHTML = `<p style="color:var(--color-error);">${result.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<p style="color:var(--color-error);">Error al cargar los datos. Revisa la consola para más detalles.</p>';
                });
        });
    });

    // --- LÓGICA PARA EL BOTÓN DE ENVIAR POR CORREO ---
    document.querySelectorAll('.btn-email').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Enviar Solicitud por Correo',
                input: 'email',
                inputLabel: 'Dirección de correo electrónico del destinatario',
                inputPlaceholder: 'ejemplo@dominio.com',
                showCancelButton: true,
                confirmButtonText: 'Enviar',
                cancelButtonText: 'Cancelar',
                preConfirm: (email) => {
                    if (!email) {
                        Swal.showValidationMessage('Por favor, ingresa una dirección de correo.');
                    }
                    return email;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const email = result.value;
                    Swal.fire({
                        title: 'Enviando...',
                        text: `Enviando solicitud S-${id.padStart(4, '0')} a ${email}`,
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    // Aquí harías un fetch a un script php/enviar_correo.php
                    // fetch('php/enviar_correo.php', { method: 'POST', body: ... })

                    // Simulamos una respuesta exitosa del servidor para demostración
                    setTimeout(() => {
                        Swal.fire('¡Enviado!', `La solicitud ha sido enviada a ${email}.`, 'success');
                    }, 1500);
                }
            });
        });
    });

});