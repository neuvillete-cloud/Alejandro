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
            'label_tertiary': 'Terciaria', 'section_workMethod': 'Método de Trabajo', 'section_defects': 'Defectos Registrados',
            'noDefects': 'No se registraron defectos para esta solicitud.', 'defect': 'Defecto', 'photo_ok': 'Foto OK', 'photo_nok': 'Foto NO OK',
            'swal_sendTitle': 'Enviar Solicitud',
            'swal_sendLabel': 'Dirección de correo electrónico del destinatario',
            'swal_sendPlaceholder': 'ejemplo@dominio.com',
            'swal_sendConfirm': 'Enviar Todo',
            'swal_sendCancel': 'Cancelar',
            'swal_sendValidation': 'Por favor, ingresa una dirección de correo válida.',
            'swal_sending': 'Enviando...',
            'swal_sent': '¡Enviado!',
            'swal_sentText': 'La solicitud ha sido enviada.',
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
            'label_tertiary': 'Tertiary', 'section_workMethod': 'Work Method', 'section_defects': 'Registered Defects',
            'noDefects': 'No defects were registered for this request.', 'defect': 'Defect', 'photo_ok': 'OK Photo', 'photo_nok': 'NOK Photo',
            'swal_sendTitle': 'Send Request',
            'swal_sendLabel': 'Recipient\'s email address',
            'swal_sendPlaceholder': 'example@domain.com',
            'swal_sendConfirm': 'Send All',
            'swal_sendCancel': 'Cancel',
            'swal_sendValidation': 'Please enter a valid email address.',
            'swal_sending': 'Sending...',
            'swal_sent': 'Sent!',
            'swal_sentText': 'The request has been sent.',
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
    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape" && modal.classList.contains('visible')) closeModal();
        });
    }

    // Lógica para ver detalles
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
                        let defectosHTML = `<fieldset><legend><i class="fa-solid fa-bug"></i> ${translations[currentLang].section_defects}</legend>`;
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
                            ${defectosHTML}`;
                    } else {
                        modalBody.innerHTML = `<p style="color:var(--color-error);">${result.message}</p>`;
                    }
                })
                .catch(error => { console.error('Error:', error); modalBody.innerHTML = `<p style="color:var(--color-error);">${translations[currentLang].errorLoadingData}</p>`; });
        });
    });

    if (modalBody) {
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
    }

    // --- LÓGICA DE ENVÍO MÚLTIPLE DE CORREOS ---
    document.querySelectorAll('.btn-email').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const clickedButton = this;
            let correosLista = []; // Array para almacenar los correos temporalmente

            // Función auxiliar para renderizar la lista de correos dentro del SweetAlert
            const renderEmailList = () => {
                const container = document.getElementById('email-list-container');
                if (!container) return;

                if (correosLista.length === 0) {
                    container.innerHTML = '<div style="color: #999; font-style: italic; padding: 10px;">Ningún correo agregado aún</div>';
                    return;
                }

                let html = '<ul style="list-style: none; padding: 0; margin: 10px 0; text-align: left;">';
                correosLista.forEach((email, index) => {
                    html += `
                        <li style="background: #f1f3f5; margin-bottom: 5px; padding: 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fa-solid fa-user-envelope"></i> ${email}</span>
                            <button type="button" class="remove-email-btn" data-index="${index}" style="background: none; border: none; color: #dc3545; cursor: pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </li>`;
                });
                html += '</ul>';
                container.innerHTML = html;

                // Agregar eventos a los botones de eliminar
                document.querySelectorAll('.remove-email-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const idx = parseInt(e.currentTarget.dataset.index);
                        correosLista.splice(idx, 1);
                        renderEmailList();
                    });
                });
            };

            Swal.fire({
                title: translations[currentLang].swal_sendTitle || 'Enviar Solicitud',
                html: `
                    <p style="margin-bottom: 15px;">Ingresa los correos electrónicos de los destinatarios:</p>
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <input type="email" id="swal-input-email" class="swal2-input" placeholder="ejemplo@dominio.com" style="margin: 0; flex: 1;">
                        <button type="button" id="btn-add-email" class="swal2-confirm swal2-styled" style="background-color: #6c757d; margin: 0; min-width: 60px;">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div id="email-list-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px;"></div>
                `,
                showCancelButton: true,
                confirmButtonText: translations[currentLang].swal_sendConfirm || 'Enviar Todo',
                cancelButtonText: translations[currentLang].swal_sendCancel || 'Cancelar',
                didOpen: () => {
                    renderEmailList();
                    const input = document.getElementById('swal-input-email');
                    const addBtn = document.getElementById('btn-add-email');

                    // Función para agregar correo
                    const addEmail = () => {
                        const email = input.value.trim();
                        // Validación simple de email
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        if (!emailRegex.test(email)) {
                            Swal.showValidationMessage('Por favor ingresa un correo válido');
                            return;
                        }
                        if (correosLista.includes(email)) {
                            Swal.showValidationMessage('Este correo ya fue agregado');
                            return;
                        }

                        correosLista.push(email);
                        input.value = ''; // Limpiar input
                        Swal.resetValidationMessage();
                        renderEmailList();
                        input.focus();
                    };

                    addBtn.addEventListener('click', addEmail);
                    input.addEventListener('keypress', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault(); // Evitar que Swal se cierre
                            addEmail();
                        }
                    });
                },
                preConfirm: () => {
                    if (correosLista.length === 0) {
                        Swal.showValidationMessage('Debes agregar al menos un destinatario');
                        return false;
                    }
                    return correosLista;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const emailsToSend = result.value; // Esto ya es el array de correos

                    Swal.fire({
                        title: translations[currentLang].swal_sending || 'Enviando...',
                        text: `Procesando ${emailsToSend.length} destinatario(s)...`,
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData();
                    formData.append('id', id);
                    // IMPORTANTE: Enviamos el array como JSON string
                    formData.append('emails_json', JSON.stringify(emailsToSend));

                    fetch('https://grammermx.com/Mailer/enviar_solicitud.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('¡Enviado!', data.message, 'success');

                                // Actualiza el estatus visualmente en la tabla
                                const fila = clickedButton.closest('tr');
                                if (fila) {
                                    const celdaEstatus = fila.querySelector('.status');
                                    if (celdaEstatus) {
                                        celdaEstatus.textContent = translations[currentLang].status_asignado || 'Asignado';
                                        celdaEstatus.className = "status status-asignado";
                                    }
                                }

                                // Deshabilitar botón
                                clickedButton.disabled = true;
                                clickedButton.classList.add('sent');
                                clickedButton.innerHTML = '<i class="fa-solid fa-check"></i>';
                                clickedButton.title = translations[currentLang].title_emailSent || 'Correo Enviado';
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