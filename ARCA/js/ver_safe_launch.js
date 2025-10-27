document.addEventListener('DOMContentLoaded', function() {

    // --- CÓDIGO DE TRADUCCIÓN ---
    // ¡ELIMINADO!
    // La lógica de traducción (objeto 'translations', 'currentLang', 'translatePage')
    // ya está definida en el script inline de 'historial_safe_launch.php'.
    // Este script usará esas variables globales cuando se cargue.
    // --- FIN CÓDIGO DE TRADUCCIÓN ---


    // --- CÓDIGO DE LA PÁGINA (MODAL DE DETALLES) ---
    const modal = document.getElementById('details-modal');
    const modalCloseBtn = document.getElementById('modal-close');
    const modalBody = document.getElementById('modal-body');
    const modalFolio = document.getElementById('modal-folio');

    // Función para cerrar el modal
    function closeModal() {
        modal.classList.remove('visible');
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && modal.classList.contains('visible')) closeModal();
    });

    // Listener para todos los botones de "Ver Detalles"
    document.querySelectorAll('.btn-details').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;

            // --- CAMBIO: Formato de Folio "SL-" ---
            modalFolio.textContent = `SL-${id.padStart(4, '0')}`;

            // Usar 'translations' y 'currentLang' (definidos en el PHP)
            // Hacemos una comprobación por si el script PHP aún no ha cargado las traducciones
            const t = (typeof translations !== 'undefined') ? translations[currentLang] : {};

            modalBody.innerHTML = `<p>${t.loadingData || 'Cargando datos...'}</p>`;
            modal.classList.add('visible');

            // --- CAMBIO: Apuntar al nuevo DAO ---
            fetch(`dao/get_safe_launch_details.php?id=${id}`)
                .then(response => response.json())
                .then(result => {
                    // Volvemos a verificar las traducciones por si cargaron después
                    const t_loaded = (typeof translations !== 'undefined') ? translations[currentLang] : {};

                    if (result.status === 'success') {
                        const data = result.data;

                        // --- CAMBIO: Construir HTML para Instrucción ---
                        let instruccionHTML = '';
                        if (data.RutaInstruccion) {
                            instruccionHTML = `
                            <fieldset>
                                <legend><i class="fa-solid fa-paperclip"></i> ${t_loaded.section_instruction || 'Instrucción de Trabajo'}</legend>
                                <iframe src="${data.RutaInstruccion}" width="100%" height="500px" frameborder="0"></iframe>
                            </fieldset>`;
                        }

                        // --- CAMBIO: Construir HTML para Defectos (más simple) ---
                        let defectosHTML = `<fieldset><legend><i class="fa-solid fa-bug"></i> ${t_loaded.section_defects || 'Defectos Registrados'}</legend>`;
                        if (data.defectos && data.defectos.length > 0) {
                            defectosHTML += '<ul style="list-style-type: disc; padding-left: 25px;">';
                            data.defectos.forEach((defecto, index) => {
                                defectosHTML += `<li style="margin-bottom: 5px;"><strong>${defecto.NombreDefecto || ''}</strong></li>`;
                            });
                            defectosHTML += '</ul>';
                        } else {
                            defectosHTML += `<p>${t_loaded.noDefects || 'No se registraron defectos.'}</p>`;
                        }
                        defectosHTML += '</fieldset>';

                        // --- CAMBIO: Construir HTML para Datos Generales ---
                        // Nota: Usamos 'NombreResponsable' de la consulta SQL
                        modalBody.innerHTML = `
                            <fieldset>
                                <legend><i class="fa-solid fa-file-lines"></i> ${t_loaded.section_generalData || 'Datos Generales'}</legend>
                                <div class="form-row">
                                    <div class="form-group"><label>${t_loaded.label_personInCharge_SL || 'Responsable'}</label><input type="text" value="${data.NombreResponsable || ''}" readonly></div>
                                    <div class="form-group"><label>${t_loaded.label_projectName_SL || 'Proyecto'}</label><input type="text" value="${data.NombreProyecto || ''}" readonly></div>
                                    <div class="form-group"><label>${t_loaded.label_client_SL || 'Cliente'}</label><input type="text" value="${data.Cliente || ''}" readonly></div>
                                </div>
                            </fieldset>
                            
                            ${instruccionHTML}
                            ${defectosHTML}`;
                    } else {
                        modalBody.innerHTML = `<p style="color:var(--color-error);">${result.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const t_err = (typeof translations !== 'undefined') ? translations[currentLang] : {};
                    modalBody.innerHTML = `<p style="color:var(--color-error);">${t_err.errorLoadingData || 'Error al cargar los datos.'}</p>`;
                });
        });
    });

    // --- ELIMINADO: Listener de click en imagen (no hay imágenes en este modal) ---

    // --- ELIMINADO: Listener de botón de email (ya está en el PHP inline) ---

    // Animación para las filas de la tabla (se conserva)
    const tableRows = document.querySelectorAll('.results-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
});
