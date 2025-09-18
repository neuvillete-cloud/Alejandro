document.addEventListener('DOMContentLoaded', function() {

    const modal = document.getElementById('details-modal');
    const modalCloseBtn = document.getElementById('modal-close');
    const modalBody = document.getElementById('modal-body');
    const modalFolio = document.getElementById('modal-folio');

    // Cerrar el modal
    modalCloseBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target == modal) {
            modal.style.display = 'none';
        }
    });

    // Abrir y llenar el modal de detalles
    document.querySelectorAll('.btn-details').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            modalFolio.textContent = `#${id.padStart(4, '0')}`;
            modalBody.innerHTML = '<p>Cargando datos...</p>';
            modal.style.display = 'flex';

            fetch(`dao/get_solicitud_details.php?id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        const data = result.data;
                        let metodoHTML = '';
                        if (data.RutaArchivo) { // Usamos la nueva variable
                            metodoHTML = `
        <fieldset><legend>Método de Trabajo</legend>
            <iframe src="${data.RutaArchivo}" width="100%" height="500px"></iframe>
        </fieldset>`;
                        }

                        let defectosHTML = '<fieldset><legend>Defectos Registrados</legend>';
                        if (data.defectos.length > 0) {
                            data.defectos.forEach((defecto, index) => {
                                defectosHTML += `
                                    <div class="defecto-item-view">
                                        <h4>Defecto #${index + 1}: ${defecto.NombreDefecto}</h4>
                                        <div class="defect-gallery">
                                            <div class="defect-image">
                                                <label>Foto OK</label>
                                                <img src="${defecto.RutaFotoOk}" alt="Foto OK">
                                            </div>
                                            <div class="defect-image">
                                                <label>Foto NO OK</label>
                                                <img src="${defecto.RutaFotoNoOk}" alt="Foto NO OK">
                                            </div>
                                        </div>
                                    </div>`;
                            });
                        } else {
                            defectosHTML += '<p>No se registraron defectos para esta solicitud.</p>';
                        }
                        defectosHTML += '</fieldset>';

                        modalBody.innerHTML = `
                            <fieldset><legend>Datos Generales</legend>
                                <p><strong>Responsable:</strong> ${data.Responsable}</p>
                                <p><strong>Número de Parte:</strong> ${data.NumeroParte}</p>
                                <p><strong>Cantidad:</strong> ${data.Cantidad}</p>
                                <p><strong>Descripción:</strong> ${data.Descripcion}</p>
                            </fieldset>
                            <fieldset><legend>Clasificación</legend>
                                <p><strong>Proveedor:</strong> ${data.NombreProvedor}</p>
                                <p><strong>Commodity:</strong> ${data.NombreCommodity}</p>
                                <p><strong>Terciaria:</strong> ${data.NombreTerciaria}</p>
                            </fieldset>
                            ${metodoHTML}
                            ${defectosHTML}
                        `;
                    } else {
                        modalBody.innerHTML = `<p style="color:red;">${result.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<p style="color:red;">Error al cargar los datos.</p>';
                });
        });
    });

    // Lógica para el botón de enviar por correo
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
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Aquí harías un fetch a un script php/enviar_correo.php
                    console.log(`Enviar solicitud #${id} al correo: ${result.value}`);
                    Swal.fire('Enviado', `La solicitud #${id} ha sido enviada a ${result.value}.`, 'success');
                }
            });
        });
    });

});