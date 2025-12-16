document.addEventListener('DOMContentLoaded', function() {

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

            // --- CAMBIO: Formato de Folio "ZD-" (Zero Defects) ---
            modalFolio.textContent = `ZD-${id.padStart(4, '0')}`;

            // Usar 'translations' y 'currentLang' (definidos en el PHP inline)
            const t = (typeof translations !== 'undefined') ? translations[currentLang] : {};

            modalBody.innerHTML = `<p>${t.loadingData || 'Cargando datos...'}</p>`;
            modal.classList.add('visible');

            // --- CAMBIO: Apuntar al DAO de Cero Defectos ---
            fetch(`dao/get_cero_defectos_details.php?id=${id}`)
                .then(response => response.json())
                .then(result => {
                    const t_loaded = (typeof translations !== 'undefined') ? translations[currentLang] : {};

                    if (result.status === 'success') {
                        const data = result.data;

                        // --- Construir HTML para Instrucción ---
                        let instruccionHTML = '';
                        if (data.RutaInstruccion) {
                            instruccionHTML = `
                            <fieldset>
                                <legend><i class="fa-solid fa-paperclip"></i> ${t_loaded.section_instruction || 'Instrucción de Trabajo'}</legend>
                                <iframe src="${data.RutaInstruccion}" width="100%" height="500px" frameborder="0"></iframe>
                            </fieldset>`;
                        }

                        // --- CAMBIO: Sin sección de Defectos (se eliminó en este módulo) ---

                        // --- CAMBIO: Construir HTML para Datos Generales (Línea y OEM) ---
                        modalBody.innerHTML = `
                            <fieldset>
                                <legend><i class="fa-solid fa-file-lines"></i> ${t_loaded.section_generalData || 'Datos Generales'}</legend>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>${t_loaded.label_personInCharge || 'Responsable'}</label>
                                        <input type="text" value="${data.NombreResponsable || ''}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>${t_loaded.table_line || 'Línea'}</label>
                                        <input type="text" value="${data.Linea || ''}" readonly>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>${t_loaded.table_oem || 'OEM'}</label>
                                        <input type="text" value="${data.NombreOEM || ''}" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>${t_loaded.table_client || 'Cliente'}</label>
                                        <input type="text" value="${data.Cliente || ''}" readonly>
                                    </div>
                                </div>
                            </fieldset>
                            
                            ${instruccionHTML}`;
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

    // Animación para las filas de la tabla
    const tableRows = document.querySelectorAll('.results-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
});