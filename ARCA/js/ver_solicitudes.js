document.addEventListener('DOMContentLoaded', function() {

    const modal = document.getElementById('details-modal');
    const modalCloseBtn = document.getElementById('modal-close');
    const modalMainInfo = document.getElementById('modal-main-info');
    const modalAttachments = document.getElementById('modal-attachments');
    const modalFolio = document.getElementById('modal-folio');

    // --- LÓGICA PARA CERRAR EL MODAL ---
    function closeModal() {
        modal.classList.remove('visible');
    }
    modalCloseBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        // Se cierra si se hace clic en el fondo oscuro
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
            modalMainInfo.innerHTML = '<p>Cargando datos...</p>';
            modalAttachments.innerHTML = '';
            modal.classList.add('visible');

            // Hacemos la llamada al servidor para obtener los datos
            fetch(`php/get_solicitud_details.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('La respuesta del servidor no fue exitosa.');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.status === 'success') {
                        const data = result.data;

                        // Llenamos la Columna Izquierda (Información Principal)
                        modalMainInfo.innerHTML = `
                            <fieldset><legend>Datos Generales</legend>
                                <div class="info-item"><strong>Responsable:</strong> <span>${data.Responsable || ''}</span></div>
                                <div class="info-item"><strong>Número de Parte:</strong> <span>${data.NumeroParte || ''}</span></div>
                                <div class="info-item"><strong>Cantidad:</strong> <span>${data.Cantidad || ''}</span></div>
                                <div class="info-item"><strong>Descripción:</strong> <span>${data.Descripcion || ''}</span></div>
                            </fieldset>
                            <fieldset><legend>Clasificación</legend>
                                <div class="info-item"><strong>Proveedor:</strong> <span>${data.NombreProvedor || ''}</span></div>
                                <div class="info-item"><strong>Commodity:</strong> <span>${data.NombreCommodity || ''}</span></div>
                                <div class="info-item"><strong>Terciaria:</strong> <span>${data.NombreTerciaria || ''}</span></div>
                            </fieldset>
                        `;

                        // Llenamos la Columna Derecha (Adjuntos)
                        let attachmentsHTML = '<h3>Adjuntos</h3>';

                        if (data.RutaArchivo) {
                            attachmentsHTML += `
                                <fieldset><legend>Método de Trabajo</legend>
                                    <iframe src="${data.RutaArchivo}" width="100%" height="400px" frameborder="0"></iframe>
                                </fieldset>`;
                        }

                        attachmentsHTML += '<fieldset><legend>Defectos Registrados</legend>';
                        if (data.defectos && data.defectos.length > 0) {
                            data.defectos.forEach((defecto, index) => {
                                attachmentsHTML += `
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
                            attachmentsHTML += '<p>No se registraron defectos para esta solicitud.</p>';
                        }
                        attachmentsHTML += '</fieldset>';
                        modalAttachments.innerHTML = attachmentsHTML;

                    } else {
                        modalMainInfo.innerHTML = `<p style="color:red;">${result.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalMainInfo.innerHTML = '<p style="color:red;">Error al cargar los datos. Revisa la consola para más detalles.</p>';
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

                    // Simulamos una respuesta exitosa del servidor
                    setTimeout(() => {
                        Swal.fire('¡Enviado!', `La solicitud ha sido enviada a ${email}.`, 'success');
                    }, 1500);
                }
            });
        });
    });

});