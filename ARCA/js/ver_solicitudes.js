document.addEventListener('DOMContentLoaded', function() {

    // --- INICIO CÓDIGO DE TRADUCCIÓN ---
    let currentLang = 'es';

    const translations = {
        'es': {
            'pageTitle': 'Ver Solicitudes - ARCA', 'nav_dashboard': 'Dashboard', 'nav_myRequests': 'Mis Solicitudes',
            'welcome': 'Bienvenido', 'logout': 'Cerrar Sesión', 'mainTitle': 'Mis Solicitudes de Contención',
            'btn_createNewRequest': 'Crear Nueva Solicitud', 'label_searchByFolio': 'Buscar por Folio',
            'label_searchByDate': 'Buscar por Fecha', 'btn_filter': 'Filtrar', 'btn_clear': 'Limpiar',
            'table_folio': 'Folio', 'table_partNumber': 'No. Parte', 'table_supplier': 'Proveedor',
            'table_date': 'Fecha de Registro', 'table_status': 'Estatus', 'table_actions': 'Acciones',
            'noResults': 'No se encontraron solicitudes', 'modal_title': 'Detalles de la Solicitud',
            'title_viewDetails': 'Ver Detalles', 'title_sendByEmail': 'Enviar por Correo', 'title_emailSent': 'Correo Enviado',
            'loadingData': 'Cargando datos...', 'errorLoadingData': 'Error al cargar los datos.',
            'section_generalData': 'Datos Generales', 'label_personInCharge': 'Responsable', 'label_partNumberModal': 'Número de Parte',
            'label_quantity': 'Cantidad', 'label_partDescription': 'Descripción de Parte', 'label_problemDescription': 'Descripción del Problema',
            'section_classification': 'Clasificación', 'label_supplierModal': 'Proveedor', 'label_location': 'Lugar de Contención',
            'label_tertiary': 'Terciaria', 'section_workMethod': 'Método de Trabajo', 'section_defects': 'Defectos Originales',
            'section_new_defects': 'Nuevos Defectos Encontrados',
            'label_inspector': 'Inspector', 'label_date': 'Fecha', 'label_evidence': 'Evidencia', 'label_qty_found': 'Cant. Encontrada',
            'noDefects': 'No se registraron defectos para esta solicitud.', 'defect': 'Defecto', 'photo_ok': 'Foto OK', 'photo_nok': 'Foto NO OK',
            'swal_sendTitle': 'Enviar Solicitud por Correo', 'swal_sendLabel': 'Dirección de correo electrónico del destinatario',
            'swal_sendPlaceholder': 'ejemplo@dominio.com', 'swal_sendConfirm': 'Enviar', 'swal_sendCancel': 'Cancelar',
            'swal_sendValidation': 'Por favor, ingresa una dirección de correo.', 'swal_sending': 'Enviando...',
            'swal_sent': '¡Enviado!', 'swal_sentText': 'La solicitud ha sido enviada a',
            'status_asignado': 'Asignado'
        },
        'en': {
            'pageTitle': 'View Requests - ARCA', 'nav_dashboard': 'Dashboard', 'nav_myRequests': 'My Requests',
            'welcome': 'Welcome', 'logout': 'Log Out', 'mainTitle': 'My Containment Requests',
            'btn_createNewRequest': 'Create New Request', 'label_searchByFolio': 'Search by Folio',
            'label_searchByDate': 'Search by Date', 'btn_filter': 'Filter', 'btn_clear': 'Clear',
            'table_folio': 'Folio', 'table_partNumber': 'Part No.', 'table_supplier': 'Supplier',
            'table_date': 'Registration Date', 'table_status': 'Status', 'table_actions': 'Actions',
            'noResults': 'No requests found', 'modal_title': 'Request Details',
            'title_viewDetails': 'View Details', 'title_sendByEmail': 'Send by Email', 'title_emailSent': 'Email Sent',
            'loadingData': 'Loading data...', 'errorLoadingData': 'Error loading data.',
            'section_generalData': 'General Data', 'label_personInCharge': 'Person in Charge', 'label_partNumberModal': 'Part Number',
            'label_quantity': 'Quantity', 'label_partDescription': 'Part Description', 'label_problemDescription': 'Problem Description',
            'section_classification': 'Classification', 'label_supplierModal': 'Supplier', 'label_location': 'Containment Location',
            'label_tertiary': 'Tertiary', 'section_workMethod': 'Work Method', 'section_defects': 'Original Defects',
            'section_new_defects': 'New Defects Found',
            'label_inspector': 'Inspector', 'label_date': 'Date', 'label_evidence': 'Evidence', 'label_qty_found': 'Qty Found',
            'noDefects': 'No defects were registered for this request.', 'defect': 'Defect', 'photo_ok': 'OK Photo', 'photo_nok': 'NOK Photo',
            'swal_sendTitle': 'Send Request by Email', 'swal_sendLabel': 'Recipient\'s email address',
            'swal_sendPlaceholder': 'example@domain.com', 'swal_sendConfirm': 'Send', 'swal_sendCancel': 'Cancel',
            'swal_sendValidation': 'Please enter an email address.', 'swal_sending': 'Sending...',
            'swal_sent': 'Sent!', 'swal_sentText': 'The request has been sent to',
            'status_asignado': 'Assigned'
        }
    };

    function translatePage(lang) {
        currentLang = lang;
        document.documentElement.lang = lang;
        document.querySelectorAll('[data-translate-key]').forEach(el => {
            const key = el.dataset.translateKey;
            const target = translations[lang];
            if (target && target[key]) {
                const icon = el.querySelector('i');
                if (icon) {
                    el.innerHTML = icon.outerHTML + ' ' + target[key];
                } else {
                    el.innerText = target[key];
                }
            }
        });
        document.querySelectorAll('[data-translate-key-title]').forEach(el => {
            const key = el.dataset.translateKeyTitle;
            if (translations[lang] && translations[lang][key]) {
                el.title = translations[lang][key];
            }
        });
        document.title = translations[lang]['pageTitle'];
    }

    const langButtons = document.querySelectorAll('.lang-btn');
    langButtons.forEach(button => {
        button.addEventListener('click', function() {
            langButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const selectedLang = this.dataset.lang;
            translatePage(selectedLang);
            localStorage.setItem('userLanguage', selectedLang);

            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('lang', selectedLang);

            document.querySelectorAll('.main-nav a, a.btn-primary, a.btn-tertiary').forEach(a => {
                if (a.href && (a.href.includes('.php') || a.href.includes('.html'))) {
                    try {
                        const linkUrl = new URL(a.href);
                        linkUrl.searchParams.set('lang', selectedLang);
                        a.href = linkUrl.toString();
                    } catch (e) {}
                }
            });
            const filterForm = document.querySelector('.filter-form');
            if (filterForm) {
                const formUrl = new URL(filterForm.action);
                formUrl.searchParams.set('lang', selectedLang);
                filterForm.action = formUrl.toString();
            }
        });
    });

    function initializeLanguage() {
        const urlParams = new URLSearchParams(window.location.search);
        const langFromUrl = urlParams.get('lang');
        const savedLang = localStorage.getItem('userLanguage');
        const initialLang = langFromUrl || savedLang || 'es';

        const langBtnToActivate = document.querySelector(`.lang-btn[data-lang="${initialLang}"]`);
        if (langBtnToActivate) {
            langBtnToActivate.click();
        } else {
            document.querySelector('.lang-btn[data-lang="es"]').click();
        }
    }
    initializeLanguage();
    // --- FIN CÓDIGO DE TRADUCCIÓN ---


    // --- CÓDIGO DE LA PÁGINA ---
    const modal = document.getElementById('details-modal');
    const modalCloseBtn = document.getElementById('modal-close');
    const modalBody = document.getElementById('modal-body');
    const modalFolio = document.getElementById('modal-folio');

    function closeModal() {
        modal.classList.remove('visible');
    }
    modalCloseBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && modal.classList.contains('visible')) closeModal();
    });

    document.querySelectorAll('.btn-details').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            modalFolio.textContent = `S-${id.padStart(4, '0')}`;
            modalBody.innerHTML = `<p>${translations[currentLang].loadingData}</p>`;
            modal.classList.add('visible');

            fetch(`dao/get_solicitud_details.php?id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        const data = result.data;
                        let metodoHTML = '';
                        if (data.RutaArchivo) {
                            metodoHTML = `<fieldset><legend><i class="fa-solid fa-paperclip"></i> ${translations[currentLang].section_workMethod}</legend><iframe src="${data.RutaArchivo}" width="100%" height="500px" frameborder="0"></iframe></fieldset>`;
                        }

                        // --- 1. DEFECTOS ORIGINALES ---
                        let defectosHTML = `<fieldset><legend><i class="fa-solid fa-clipboard-check"></i> ${translations[currentLang].section_defects}</legend>`;
                        if (data.defectos && data.defectos.length > 0) {
                            data.defectos.forEach((defecto, index) => {
                                defectosHTML += `
                                <div class="defecto-view-item">
                                    <h4>${translations[currentLang].defect} #${index + 1}: ${defecto.NombreDefecto || ''}</h4>
                                    <div class="defect-view-gallery">
                                        <div class="defect-photo-box ok-box"><div class="box-label"><i class="fa-solid fa-thumbs-up"></i><span>${translations[currentLang].photo_ok}</span></div><img src="${defecto.RutaFotoOk}" alt="Foto OK: ${defecto.NombreDefecto}"></div>
                                        <div class="defect-photo-box nok-box"><div class="box-label"><i class="fa-solid fa-triangle-exclamation"></i><span>${translations[currentLang].photo_nok}</span></div><img src="${defecto.RutaFotoNoOk}" alt="Foto NO OK: ${defecto.NombreDefecto}"></div>
                                    </div>
                                </div>`;
                            });
                        } else {
                            defectosHTML += `<p>${translations[currentLang].noDefects}</p>`;
                        }
                        defectosHTML += '</fieldset>';

                        // --- 2. NUEVOS DEFECTOS ENCONTRADOS (SECCIÓN NUEVA) ---
                        let nuevosDefectosHTML = '';
                        if (data.nuevosDefectos && data.nuevosDefectos.length > 0) {
                            nuevosDefectosHTML = `<fieldset style="border-top: 2px dashed #dbe1e8; margin-top: 20px;"><legend><i class="fa-solid fa-magnifying-glass-plus"></i> ${translations[currentLang].section_new_defects}</legend>`;

                            data.nuevosDefectos.forEach((nuevo, index) => {
                                let fotoEvidencia = '';
                                if (nuevo.RutaFotoEvidencia) {
                                    fotoEvidencia = `
                                    <div class="defect-photo-box nok-box" style="margin-top: 10px; max-width: 250px;">
                                        <div class="box-label"><i class="fa-solid fa-camera"></i> <span>${translations[currentLang].label_evidence}</span></div>
                                        <img src="${nuevo.RutaFotoEvidencia}" alt="Evidencia: ${nuevo.NombreDefecto}">
                                    </div>`;
                                }

                                const parteInfo = nuevo.NumeroParte ? ` - <strong>${translations[currentLang].label_partNumberModal}:</strong> ${nuevo.NumeroParte}` : '';

                                nuevosDefectosHTML += `
                                <div class="defecto-view-item" style="background-color: #fff8f0; border-left: 4px solid #f0ad4e;">
                                    <h4 style="color: #b75c09;">${nuevo.NombreDefecto} ${parteInfo}</h4>
                                    <div style="font-size: 0.9em; margin-bottom: 5px; color: #555;">
                                        <strong><i class="fa-solid fa-hashtag"></i> ${translations[currentLang].label_qty_found}:</strong> ${nuevo.Cantidad} | 
                                        <strong><i class="fa-solid fa-user-tag"></i> ${translations[currentLang].label_inspector}:</strong> ${nuevo.NombreInspector} | 
                                        <strong><i class="fa-solid fa-calendar"></i> ${translations[currentLang].label_date}:</strong> ${new Date(nuevo.FechaInspeccion).toLocaleDateString()}
                                    </div>
                                    ${fotoEvidencia}
                                </div>`;
                            });
                            nuevosDefectosHTML += '</fieldset>';
                        }

                        // --- 3. CONSTRUCCIÓN FINAL DEL MODAL ---
                        modalBody.innerHTML = `
                            <fieldset><legend><i class="fa-solid fa-file-lines"></i> ${translations[currentLang].section_generalData}</legend>
                                <div class="form-row">
                                    <div class="form-group"><label>${translations[currentLang].label_personInCharge}</label><input type="text" value="${data.Responsable || ''}" readonly></div>
                                    <div class="form-group"><label>${translations[currentLang].label_partNumberModal}</label><input type="text" value="${data.NumeroParte || ''}" readonly></div>
                                    <div class="form-group"><label>${translations[currentLang].label_quantity}</label><input type="text" value="${data.Cantidad || ''}" readonly></div>
                                </div>
                                <div class="form-group"><label>${translations[currentLang].label_partDescription}</label><input type="text" value="${data.DescripcionParte || ''}" readonly></div>
                                <div class="form-group"><label>${translations[currentLang].label_problemDescription}</label><textarea rows="3" readonly>${data.Descripcion || ''}</textarea></div>
                            </fieldset>
                            <fieldset><legend><i class="fa-solid fa-tags"></i> ${translations[currentLang].section_classification}</legend>
                                <div class="form-row">
                                    <div class="form-group"><label>${translations[currentLang].label_supplierModal}</label><input type="text" value="${data.NombreProvedor || ''}" readonly></div>
                                    <div class="form-group"><label>${translations[currentLang].label_location}</label><input type="text" value="${data.NombreLugar || ''}" readonly></div>
                                    <div class="form-group"><label>${translations[currentLang].label_tertiary}</label><input type="text" value="${data.NombreTerciaria || ''}" readonly></div>
                                </div>
                            </fieldset>
                            ${metodoHTML}
                            ${defectosHTML}
                            ${nuevosDefectosHTML}`; // AQUI SE INYECTA LA NUEVA SECCIÓN
                    } else {
                        modalBody.innerHTML = `<p style="color:var(--color-error);">${result.message}</p>`;
                    }
                })
                .catch(error => { console.error('Error:', error); modalBody.innerHTML = `<p style="color:var(--color-error);">${translations[currentLang].errorLoadingData}</p>`; });
        });
    });

    modalBody.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG' && e.target.closest('.defect-photo-box')) {
            const imageSrc = e.target.src;
            const imageAlt = e.target.alt;
            Swal.fire({
                imageUrl: imageSrc, imageAlt: imageAlt, width: 'auto', padding: '0',
                background: 'none', showConfirmButton: false, showCloseButton: true
            });
        }
    });

    document.querySelectorAll('.btn-email').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const clickedButton = this;

            Swal.fire({
                title: translations[currentLang].swal_sendTitle, input: 'email',
                inputLabel: translations[currentLang].swal_sendLabel,
                inputPlaceholder: translations[currentLang].swal_sendPlaceholder,
                showCancelButton: true, confirmButtonText: translations[currentLang].swal_sendConfirm,
                cancelButtonText: translations[currentLang].swal_sendCancel,
                preConfirm: (email) => { if (!email) { Swal.showValidationMessage(translations[currentLang].swal_sendValidation); } return email; }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const email = result.value;
                    Swal.fire({ title: translations[currentLang].swal_sending, text: `${translations[currentLang].swal_sentText} ${email}`, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('email', email);

                    fetch('https://grammermx.com/Mailer/enviar_solicitud.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('¡Enviado!', data.message, 'success');

                                // Actualiza el estatus en la misma fila
                                const fila = clickedButton.closest('tr');
                                if (fila) {
                                    const celdaEstatus = fila.querySelector('.status');
                                    if (celdaEstatus) {
                                        celdaEstatus.textContent = translations[currentLang].status_asignado;
                                        celdaEstatus.className = "status status-asignado";
                                    }
                                }

                                // --- INICIO DE LA MODIFICACIÓN DEL BOTÓN ---
                                clickedButton.disabled = true; // Desactiva el botón
                                clickedButton.classList.add('sent'); // Añade una clase para estilizarlo (ej. color verde)
                                clickedButton.innerHTML = '<i class="fa-solid fa-check"></i>'; // Cambia el ícono a una palomita
                                clickedButton.title = translations[currentLang].title_emailSent; // Cambia el texto flotante (tooltip)
                                // --- FIN DE LA MODIFICACIÓN DEL BOTÓN ---

                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error de Conexión', 'No se pudo completar la solicitud.', 'error');
                        });
                }
            });
        });
    });

    const tableRows = document.querySelectorAll('.results-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
});