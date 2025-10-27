<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);

// --- LÓGICA DE IDIOMA ---
$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}

// --- CONEXIÓN A BD AÑADIDA ---
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// --- CAMBIO AQUÍ: Consultar la nueva tabla ---
$catalogo_defectos = $conex->query("SELECT IdSLDefectoCatalogo, NombreDefecto FROM SafeLaunchCatalogoDefectos ORDER BY NombreDefecto ASC");
// Preparamos las opciones en una variable para inyectarla en JavaScript
$defectos_options_html = "";
if ($catalogo_defectos) {
    while($row = $catalogo_defectos->fetch_assoc()) {
        // --- CAMBIO AQUÍ: Usar el nuevo ID (IdSLDefectoCatalogo) ---
        $defectos_options_html .= "<option value='{$row['IdSLDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
    }
}
$conex->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="pageTitle">Nuevo Safe Launch - ARCA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Se utiliza la misma hoja de estilos que nueva_solicitud.php -->
    <link rel="stylesheet" href="css/estilosSolicitud.css">
</head>
<body>

<header class="header">
    <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
    <div class="user-info">
        <div class="language-selector">
            <button type="button" class="lang-btn active" data-lang="es">ES</button>
            <button type="button" class="lang-btn" data-lang="en">EN</button>
        </div>
        <span><span data-translate-key="welcome">Bienvenido</span>, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">
            <span data-translate-key="logout">Cerrar Sesión</span> <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-rocket"></i> <span data-translate-key="mainTitle">Crear Nuevo Safe Launch</span></h1>

        <!-- Stepper eliminado según la solicitud -->

        <form id="safeLaunchForm" action="dao/guardar_safe_launch.php" method="POST" enctype="multipart/form-data">

            <fieldset><legend><i class="fa-solid fa-file-lines"></i> <span data-translate-key="section_generalData">Datos Generales</span></legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="responsable" data-translate-key="label_personInCharge">Nombre del Responsable</label>
                        <input type="text" id="responsable" name="responsable" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <div class="label-with-tooltip">
                            <label for="nombreProyecto" data-translate-key="label_projectName">Nombre del Proyecto</label>
                            <div class="tooltip-icon"><i class="fa-solid fa-circle-info"></i><span class="tooltip-text" data-translate-key="tooltip_projectName">Nombre clave o identificador del proyecto.</span></div>
                        </div>
                        <input type="text" id="nombreProyecto" name="nombreProyecto" required>
                    </div>
                    <div class="form-group">
                        <div class="label-with-tooltip">
                            <label for="cliente" data-translate-key="label_client">Cliente</label>
                            <div class="tooltip-icon"><i class="fa-solid fa-circle-info"></i><span class="tooltip-text" data-translate-key="tooltip_client">Cliente final para el cual es este proyecto.</span></div>
                        </div>
                        <input type="text" id="cliente" name="cliente" required>
                    </div>
                </div>
            </fieldset>

            <fieldset><legend><i class="fa-solid fa-bug"></i> <span data-translate-key="section_defects">Registro de Defectos</span></legend>
                <div id="defectos-sl-container">
                    <!-- Los defectos se añadirán aquí dinámicamente -->
                </div>
                <div class="form-row">
                    <button type="button" id="btn-add-sl-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> <span data-translate-key="btn_addSLDefect">Añadir Defecto</span></button>

                    <!-- Botón opcional para añadir al catálogo (si es SuperUsuario) -->
                    <?php if ($esSuperUsuario): ?>
                        <!-- --- CAMBIO AQUÍ: El data-tipo ahora apunta a una nueva lógica --- -->
                        <button type="button" class="btn-add" data-tipo="sldefectocatalogo" data-translate-key-title="title_addDefect" title="Añadir Defecto al Catálogo">+</button>
                    <?php endif; ?>
                </div>
            </fieldset>

            <!-- Fieldset de Clasificación eliminado -->

            <fieldset><legend><i class="fa-solid fa-paperclip"></i> <span data-translate-key="section_documentation">Documentación</span></legend>

                <!-- INICIO: Campo unificado para Instrucción -->
                <div class="form-group-checkbox">
                    <input type="checkbox" id="toggleInstruccion">
                    <label for="toggleInstruccion" data-translate-key="label_attachInstruction">Adjuntar Instrucción de Trabajo / Inspección (Opcional)</label>
                </div>
                <div id="instruccion-container" class="hidden-section" style="margin-left: 20px; border-left: 3px solid var(--color-borde); padding-left: 20px;">
                    <div class="form-group">
                        <div class="label-with-tooltip">
                            <label for="tituloInstruccion" data-translate-key="label_docName">Nombre del Documento</label>
                            <div class="tooltip-icon"><i class="fa-solid fa-circle-info"></i><span class="tooltip-text" data-translate-key="tooltip_instructionName">Asigna un nombre descriptivo al documento (ej: WI-INSP-001).</span></div>
                        </div>
                        <input type="text" id="tituloInstruccion" name="tituloInstruccion">
                    </div>
                    <div class="form-group">
                        <label for="fileInstruccion" data-translate-key="label_uploadPDF">Subir archivo PDF</label>
                        <label class="file-upload-label" for="fileInstruccion"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo..." data-translate-key="span_selectFile">Seleccionar archivo...</span></label>
                        <input type="file" id="fileInstruccion" name="fileInstruccion" accept=".pdf">
                    </div>
                </div>
                <!-- FIN: Campo unificado para Instrucción -->

            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><span data-translate-key="btn_saveSL">Guardar Safe Launch</span></button>
            </div>
        </form>
    </div>
</main>

<script>
    // ADICIÓN: Inyectamos las opciones de defectos desde PHP a una variable de JavaScript
    let opcionesDefectos = `<?php echo addslashes($defectos_options_html); ?>`;

    document.addEventListener('DOMContentLoaded', function() {

        let currentLang = '<?php echo $idioma_actual; ?>';

        const translations = {
            'es': {
                'pageTitle': 'Nuevo Safe Launch - ARCA', 'welcome': 'Bienvenido', 'logout': 'Cerrar Sesión',
                'mainTitle': 'Crear Nuevo Safe Launch',
                'section_generalData': 'Datos Generales', 'label_personInCharge': 'Nombre del Responsable',
                'label_projectName': 'Nombre del Proyecto', 'label_client': 'Cliente',
                'section_defects': 'Registro de Defectos', 'btn_addSLDefect': 'Añadir Defecto',
                'defecto': 'Defecto',
                'select_defect': 'Seleccione un defecto del catálogo',
                'title_addDefect': 'Añadir Defecto al Catálogo',
                'label_defectDescription': 'Descripción del defecto...',
                'section_documentation': 'Documentación',
                // --- CAMBIO AQUÍ ---
                'label_attachInstruction': 'Adjuntar Instrucción de Trabajo / Inspección (Opcional)',
                'label_docName': 'Nombre del Documento', 'label_uploadPDF': 'Subir archivo PDF',
                'btn_saveSL': 'Guardar Safe Launch',
                'span_selectFile': 'Seleccionar archivo...',
                'swal_saving': 'Guardando Safe Launch...', 'swal_savingText': 'Este proceso puede tardar un momento.',
                'swal_missingDefectsTitle': 'Faltan Defectos', 'swal_missingDefectsText': 'Debes registrar al menos un defecto para poder guardar.',
                'swal_successTitle': '¡Safe Launch Guardado!', 'swal_errorTitle': 'Error al Guardar', 'swal_connectionError': 'Error de Conexión',
                'swal_connectionErrorText': 'No se pudo comunicar con el servidor.',
                'tooltip_projectName': 'Nombre clave o identificador del proyecto.',
                'tooltip_client': 'Cliente final para el cual es este proyecto.',
                // --- CAMBIO AQUÍ ---
                'tooltip_instructionName': 'Asigna un nombre descriptivo al documento (ej: WI-INSP-001).',
                // Traducciones para el pop-up de añadir catálogo (copiadas del original)
                'swal_inputLabel': 'Nombre del nuevo', 'swal_placeholder': 'Ingrese el nombre...',
                'swal_btnSave': 'Guardar', 'swal_btnCancel': 'Cancelar', 'swal_validationEmpty': 'El nombre no puede estar vacío',
                'swal_requestFail': 'La solicitud falló:', 'swal_saved': '¡Guardado!', 'swal_error': 'Error'
            },
            'en': {
                'pageTitle': 'New Safe Launch - ARCA', 'welcome': 'Welcome', 'logout': 'Log Out',
                'mainTitle': 'Create New Safe Launch',
                'section_generalData': 'General Data', 'label_personInCharge': 'Person in Charge',
                'label_projectName': 'Project Name', 'label_client': 'Client',
                'section_defects': 'Defects Log', 'btn_addSLDefect': 'Add Defect',
                'defecto': 'Defect',
                'select_defect': 'Select a defect from the catalog',
                'title_addDefect': 'Add Defect to Catalog',
                'label_defectDescription': 'Defect description...',
                'section_documentation': 'Documentation',
                // --- CAMBIO AQUÍ ---
                'label_attachInstruction': 'Attach Work / Inspection Instruction (Optional)',
                'label_docName': 'Document Name', 'label_uploadPDF': 'Upload PDF file',
                'btn_saveSL': 'Save Safe Launch',
                'span_selectFile': 'Select file...',
                'swal_saving': 'Saving Safe Launch...', 'swal_savingText': 'This may take a moment.',
                'swal_missingDefectsTitle': 'Missing Defects', 'swal_missingDefectsText': 'You must register at least one defect to save.',
                'swal_successTitle': 'Safe Launch Saved!', 'swal_errorTitle': 'Error Saving', 'swal_connectionError': 'Connection Error',
                'swal_connectionErrorText': 'Could not communicate with the server.',
                'tooltip_projectName': 'Key name or identifier for the project.',
                'tooltip_client': 'End customer for this project.',
                // --- CAMBIO AQUÍ ---
                'tooltip_instructionName': 'Assign a descriptive name to the document (e.g., WI-INSP-001).',
                // Translations for the add catalog popup (copied from original)
                'swal_inputLabel': 'Name of the new', 'swal_placeholder': 'Enter the name...',
                'swal_btnSave': 'Save', 'swal_btnCancel': 'Cancel', 'swal_validationEmpty': 'The name cannot be empty',
                'swal_requestFail': 'The request failed:', 'swal_saved': 'Saved!', 'swal_error': 'Error'
            }
        };

        function translatePage(lang) {
            currentLang = lang;
            document.documentElement.lang = lang;
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.dataset.translateKey;
                if (translations[lang] && translations[lang][key]) {
                    const icon = el.querySelector('i');
                    if (icon && (el.tagName === 'LEGEND' || el.tagName === 'H1')) {
                        el.innerHTML = icon.outerHTML + ' ' + translations[lang][key];
                    } else {
                        el.innerText = translations[lang][key];
                    }
                }
            });
            // TRADUCIR TÍTULOS (para el botón "+")
            document.querySelectorAll('[data-translate-key-title]').forEach(el => {
                const key = el.dataset.translateKeyTitle;
                if(translations[lang] && translations[lang][key]) {
                    el.title = translations[lang][key];
                }
            });
            // TRADUCIR TOOLTIPS
            document.querySelectorAll('.tooltip-text[data-translate-key]').forEach(el => {
                const key = el.dataset.translateKey;
                if (translations[lang] && translations[lang][key]) {
                    el.innerText = translations[lang][key];
                }
            });
            // Actualizar placeholders
            document.querySelectorAll('[placeholder]').forEach(el => {
                const key = el.dataset.translateKeyPlaceholder;
                if (key && translations[lang] && translations[lang][key]) {
                    el.placeholder = translations[lang][key];
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
            });
        });

        const savedLang = localStorage.getItem('userLanguage') || '<?php echo $idioma_actual; ?>';
        if (savedLang) {
            const langBtnToActivate = document.querySelector(`.lang-btn[data-lang="${savedLang}"]`);
            if (langBtnToActivate) langBtnToActivate.click();
        }

        // --- CAMBIO AQUÍ: Lógica para mostrar/ocultar la Instrucción Unificada ---
        document.getElementById('toggleInstruccion').addEventListener('change', function() {
            document.getElementById('instruccion-container').style.display = this.checked ? 'block' : 'none';
        });
        // --- FIN DEL CAMBIO ---


        // Lógica para añadir defectos (con <select>)
        const btnAddDefecto = document.getElementById('btn-add-sl-defecto');
        const defectosContainer = document.getElementById('defectos-sl-container');
        let defectoCounter = 0; // Este contador SÓLO se incrementa, para IDs únicos

        btnAddDefecto.addEventListener('click', function() {
            // --- ESTA ES LA LÓGICA CORREGIDA ---
            // 1. Contamos los elementos Hijos ANTES de añadir el nuevo.
            const numeroDeDefecto = defectosContainer.children.length + 1;

            // 2. Seguimos usando el contador global para IDs únicos.
            defectoCounter++;

            const defectoHTML = `
            <div class="defecto-item-sl" id="defecto-sl-${defectoCounter}">
                <div class="form-row">
                    <div class="form-group" style="flex-grow: 1;">
                        <!-- 3. Usamos numeroDeDefecto para la ETIQUETA visual -->
                        <label for="defectoSelect-${defectoCounter}">${translations[currentLang].defecto} #${numeroDeDefecto}</label>
                        <!-- 4. Usamos defectoCounter para el ID/NAME del select -->
                        <select id="defectoSelect-${defectoCounter}" name="defectos[${defectoCounter}][id]" required>
                            <option value="" disabled selected>${translations[currentLang].select_defect}</option>
                            ${opcionesDefectos}
                        </select>
                    </div>
                    <!-- 5. Usamos defectoCounter para el data-defecto-id -->
                    <button type="button" class="btn-remove-defecto" data-defecto-id="${defectoCounter}" style="align-self: flex-end; margin-bottom: 15px; background: none; border: none; color: var(--color-error); font-size: 24px; cursor: pointer; padding: 0 10px;">&times;</button>
                </div>
            </div>`;
            defectosContainer.insertAdjacentHTML('beforeend', defectoHTML);
        });

        // Lógica para eliminar un defecto
        defectosContainer.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-remove-defecto')) {
                // Eliminamos el elemento padre
                document.getElementById(`defecto-sl-${e.target.dataset.defectoId}`).remove();

                // --- ESTA ES LA OTRA PARTE DE LA CORRECCIÓN ---
                // 1. Después de eliminar, volvemos a buscar todos los 'labels' restantes
                defectosContainer.querySelectorAll('.defecto-item-sl label').forEach((label, index) => {
                    // 2. Re-numeramos la etiqueta basado en su nuevo índice (index + 1)
                    label.innerText = `${translations[currentLang].defecto} #${index + 1}`;
                });
            }
        });

        // Lógica para actualizar el texto de los botones de subida de archivo
        document.querySelector('.form-container').addEventListener('change', function(e) {
            if (e.target.type === 'file') {
                const labelSpan = e.target.previousElementSibling.querySelector('span');
                const defaultTextKey = labelSpan.dataset.defaultText === 'Seleccionar archivo...' ? 'span_selectFile' : 'span_selectImage';
                if (e.target.files.length > 0) {
                    labelSpan.textContent = e.target.files[0].name;
                } else {
                    labelSpan.textContent = translations[currentLang][defaultTextKey];
                }
            }
        });

        // LÓGICA PARA EL BOTÓN "+" (Añadir al Catálogo)
        <?php if ($esSuperUsuario): ?>
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function() {
                const tipo = this.dataset.tipo; // "sldefectocatalogo"
                const titulos = {
                    // --- CAMBIO AQUÍ: El tipo ahora es 'sldefectocatalogo' ---
                    'sldefectocatalogo': translations[currentLang].title_addDefect,
                    // (Se eliminaron los otros tipos que no están en esta página)
                };

                Swal.fire({
                    title: titulos[tipo],
                    input: 'text',
                    inputLabel: `${translations[currentLang].swal_inputLabel} defecto`,
                    inputPlaceholder: translations[currentLang].swal_placeholder,
                    showCancelButton: true,
                    confirmButtonText: translations[currentLang].swal_btnSave,
                    cancelButtonText: translations[currentLang].swal_btnCancel,
                    preConfirm: (nombre) => {
                        if (!nombre) {
                            Swal.showValidationMessage(translations[currentLang].swal_validationEmpty);
                            return false;
                        }
                        const formData = new FormData();
                        formData.append('nombre', nombre);

                        // --- CAMBIO AQUÍ: 'tipo' ahora es 'sldefectocatalogo' y llamará a 'dao/add_sldefectocatalogo.php' ---
                        return fetch(`dao/add_${tipo}.php`, { // Llama a dao/add_sldefectocatalogo.php
                            method: 'POST',
                            body: formData
                        })
                            .then(response => {
                                if (!response.ok) { throw new Error(response.statusText); }
                                return response.json();
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`${translations[currentLang].swal_requestFail} ${error}`);
                            });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value.status === 'success') {
                        Swal.fire(translations[currentLang].swal_saved, result.value.message, 'success');

                        // Actualizar la variable JS y todos los <select> existentes
                        // 'result.value.data.id' debe ser el nuevo 'IdSLDefectoCatalogo' devuelto por el DAO
                        const newOptionHTML = `<option value="${result.value.data.id}">${result.value.data.nombre}</option>`;
                        opcionesDefectos += newOptionHTML;

                        document.querySelectorAll('select[name^="defectos"]').forEach(select => {
                            select.insertAdjacentHTML('beforeend', newOptionHTML);
                        });

                    } else if (result.value) {
                        Swal.fire(translations[currentLang].swal_error, result.value.message, 'error');
                    }
                });
            });
        });
        <?php endif; ?>


        // Lógica para Enviar el Formulario Completo
        const safeLaunchForm = document.getElementById('safeLaunchForm');
        safeLaunchForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Validar que haya al menos un defecto
            if (defectosContainer.children.length === 0) {
                Swal.fire({ icon: 'error', title: translations[currentLang].swal_missingDefectsTitle, text: translations[currentLang].swal_missingDefectsText });
                return;
            }

            // --- CAMBIO AQUÍ: Validación unificada ---
            const instruccionChecked = document.getElementById('toggleInstruccion').checked;
            const tituloInstruccion = document.getElementById('tituloInstruccion').value.trim();
            const fileInstruccion = document.getElementById('fileInstruccion').files.length;

            if (instruccionChecked && (tituloInstruccion === "" || fileInstruccion === 0)) {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Si adjuntas la Instrucción, debes proporcionar un nombre y un archivo PDF.' });
                return;
            }
            if (instruccionChecked && !tituloInstruccion && fileInstruccion > 0) {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Si adjuntas un archivo PDF, debes darle un nombre al documento.' });
                return;
            }
            if (instruccionChecked && tituloInstruccion && fileInstruccion === 0) {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Si pones un nombre al documento, debes adjuntar el archivo PDF.' });
                return;
            }
            // --- FIN DEL CAMBIO ---

            const formData = new FormData(safeLaunchForm);
            Swal.fire({ title: translations[currentLang].swal_saving, text: translations[currentLang].swal_savingText, allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

            // El action del form es 'dao/guardar_safe_launch.php'
            fetch(safeLaunchForm.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ icon: 'success', title: translations[currentLang].swal_successTitle, text: data.message })
                            .then(() => { window.location.href = 'index.php'; }); // Redirige al index al éxito
                    } else {
                        Swal.fire({ icon: 'error', title: translations[currentLang].swal_errorTitle, text: data.message });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: translations[currentLang].swal_connectionError, text: translations[currentLang].swal_connectionErrorText });
                });
        });

        // Disparar la traducción inicial
        translatePage(currentLang);

    });
</script>

</body>
</html>