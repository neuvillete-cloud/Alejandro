<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);

// Lógica de Idioma
$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}
// Fin Lógica de Idioma

// Conexión y carga de datos para los menús desplegables
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

$proveedores = $conex->query("SELECT IdProvedor, NombreProvedor FROM Provedores ORDER BY NombreProvedor ASC");
$terciarias = $conex->query("SELECT IdTerciaria, NombreTerciaria FROM Terciarias ORDER BY NombreTerciaria ASC");
$lugares = $conex->query("SELECT IdLugar, NombreLugar FROM Lugares ORDER BY NombreLugar ASC");

$conex->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="pageTitle">Nueva Solicitud - ARCA</title>

    <link rel="stylesheet" href="css/estilosSolicitud.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <h1><i class="fa-solid fa-file-circle-plus"></i> <span data-translate-key="mainTitle">Crear Nueva Solicitud de Contención</span></h1>

        <div class="stepper">
            <div class="step active"><div class="step-icon">1</div><div class="step-label" data-translate-key="step1">Datos y Defectos</div></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-icon">2</div><div class="step-label" data-translate-key="step2">Clasificación</div></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-icon">3</div><div class="step-label" data-translate-key="step3">Documentación</div></div>
        </div>

        <form id="solicitudForm" action="dao/guardar_solicitud.php" method="POST" enctype="multipart/form-data">

            <fieldset><legend data-translate-key="section_generalData"><i class="fa-solid fa-file-lines"></i> Datos Generales</legend>
                <div class="form-row">
                    <div class="form-group w-50"><label for="responsable" data-translate-key="label_personInCharge">Nombre del Responsable</label><input type="text" id="responsable" name="responsable" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" ></div>
                    <div class="form-group w-25"><label for="numeroParte" data-translate-key="label_partNumber">Número de Parte</label><input type="text" id="numeroParte" name="numeroParte" required></div>
                    <div class="form-group w-25"><label for="cantidad" data-translate-key="label_quantity">Cantidad</label><input type="number" id="cantidad" name="cantidad" required></div>
                </div>
                <div class="form-group"><label for="descripcionParte" data-translate-key="label_partDescription">Descripción de Parte</label><input type="text" id="descripcionParte" name="descripcionParte" required></div>
                <div class="form-group"><label for="descripcion" data-translate-key="label_problemDescription">Descripción del Problema</label><textarea id="descripcion" name="descripcion" rows="3" required></textarea></div>

                <fieldset><legend data-translate-key="section_defects"><i class="fa-solid fa-bug"></i> Registro de Defectos</legend>
                    <div id="defectos-container"></div>
                    <button type="button" id="btn-add-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> <span data-translate-key="btn_addDefect">Añadir Defecto</span></button>
                </fieldset>
            </fieldset>

            <fieldset><legend data-translate-key="section_classification"><i class="fa-solid fa-tags"></i> Clasificación</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="proveedor" data-translate-key="label_supplier">Proveedor</label>
                        <div class="select-with-button">
                            <select id="proveedor" name="IdProvedor" required>
                                <option value="" disabled selected data-translate-key="select_supplier">Seleccione un proveedor</option>
                                <?php mysqli_data_seek($proveedores, 0); while($row = $proveedores->fetch_assoc()): ?><option value="<?php echo $row['IdProvedor']; ?>"><?php echo htmlspecialchars($row['NombreProvedor']); ?></option><?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="proveedor" data-translate-key-title="title_addSupplier" title="Añadir Nuevo Proveedor">+</button><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lugar" data-translate-key="label_location">Lugar de Contención</label>
                        <div class="select-with-button">
                            <select id="lugar" name="IdLugar" required>
                                <option value="" disabled selected data-translate-key="select_location">Seleccione un lugar</option>
                                <?php mysqli_data_seek($lugares, 0); while($row = $lugares->fetch_assoc()): ?><option value="<?php echo $row['IdLugar']; ?>"><?php echo htmlspecialchars($row['NombreLugar']); ?></option><?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="lugar" data-translate-key-title="title_addLocation" title="Añadir Nuevo Lugar">+</button><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terciaria" data-translate-key="label_tertiary">Terciaria</label>
                        <div class="select-with-button">
                            <select id="terciaria" name="IdTerciaria" required>
                                <option value="" disabled selected data-translate-key="select_tertiary">Seleccione una terciaria</option>
                                <?php mysqli_data_seek($terciarias, 0); while($row = $terciarias->fetch_assoc()): ?><option value="<?php echo $row['IdTerciaria']; ?>"><?php echo htmlspecialchars($row['NombreTerciaria']); ?></option><?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="terciaria" data-translate-key-title="title_addTertiary" title="Añadir Nueva Terciaria">+</button><?php endif; ?>
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset><legend data-translate-key="section_documentation"><i class="fa-solid fa-paperclip"></i> Documentación Adicional</legend>
                <div class="form-group-checkbox"><input type="checkbox" id="toggleMetodo"><label for="toggleMetodo" data-translate-key="label_attachMethod">Adjuntar Método de Trabajo (Opcional)</label></div>
                <div id="metodo-trabajo-container" class="hidden-section">
                    <div class="form-group"><label for="tituloMetodo" data-translate-key="label_methodName">Nombre del Método</label><input type="text" id="tituloMetodo" name="tituloMetodo"></div>
                    <div class="form-group">
                        <label for="metodoFile" data-translate-key="label_uploadPDF">Subir archivo PDF</label>
                        <label class="file-upload-label" for="metodoFile"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo..." data-translate-key="span_selectFile">Seleccionar archivo...</span></label>
                        <input type="file" id="metodoFile" name="metodoFile" accept=".pdf">
                    </div>
                </div>
            </fieldset>

            <div class="form-actions"><button type="submit" class="btn-primary" data-translate-key="btn_saveRequest">Guardar Solicitud</button></div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        let currentLang = 'es'; // Variable global para el idioma actual

        // 1. El Diccionario de Traducciones Completo
        const translations = {
            'es': {
                'pageTitle': 'Nueva Solicitud - ARCA', 'welcome': 'Bienvenido', 'logout': 'Cerrar Sesión',
                'mainTitle': 'Crear Nueva Solicitud de Contención', 'step1': 'Datos y Defectos', 'step2': 'Clasificación', 'step3': 'Documentación',
                'section_generalData': 'Datos Generales', 'label_personInCharge': 'Nombre del Responsable', 'label_partNumber': 'Número de Parte',
                'label_partDescription': 'Descripción de Parte', 'label_quantity': 'Cantidad', 'label_problemDescription': 'Descripción del Problema',
                'section_defects': 'Registro de Defectos', 'btn_addDefect': 'Añadir Defecto',
                'section_classification': 'Clasificación', 'label_supplier': 'Proveedor', 'select_supplier': 'Seleccione un proveedor',
                'label_location': 'Lugar de Contención', 'select_location': 'Seleccione un lugar', 'label_tertiary': 'Terciaria', 'select_tertiary': 'Seleccione una terciaria',
                'section_documentation': 'Documentación Adicional', 'label_attachMethod': 'Adjuntar Método de Trabajo (Opcional)',
                'label_methodName': 'Nombre del Método', 'label_uploadPDF': 'Subir archivo PDF',
                'btn_saveRequest': 'Guardar Solicitud',
                'span_selectFile': 'Seleccionar archivo...', 'span_selectImage': 'Seleccionar imagen...',
                'defecto': 'Defecto', 'label_defectName': 'Nombre del Defecto', 'label_photoOk': 'Foto OK (Ejemplo correcto)', 'label_photoNok': 'Foto NO OK (Evidencia del defecto)',
                'swal_limitTitle': 'Límite alcanzado', 'swal_limitText': 'Puedes registrar un máximo de 5 defectos por solicitud.',
                'title_addSupplier': 'Añadir Nuevo Proveedor', 'title_addLocation': 'Añadir Nuevo Lugar', 'title_addTertiary': 'Añadir Nueva Terciaria',
                'swal_inputLabel': 'Nombre del nuevo', 'swal_placeholder': 'Ingrese el nombre...',
                'swal_btnSave': 'Guardar', 'swal_btnCancel': 'Cancelar', 'swal_validationEmpty': 'El nombre no puede estar vacío',
                'swal_requestFail': 'La solicitud falló:', 'swal_saved': '¡Guardado!', 'swal_error': 'Error',
                'swal_saving': 'Guardando Solicitud...', 'swal_savingText': 'Este proceso puede tardar un momento.',
                'swal_missingDefectsTitle': 'Faltan Defectos', 'swal_missingDefectsText': 'Debes registrar al menos un defecto para poder guardar la solicitud.',
                'swal_successTitle': '¡Solicitud Guardada!', 'swal_errorTitle': 'Error al Guardar', 'swal_connectionError': 'Error de Conexión',
                'swal_connectionErrorText': 'No se pudo comunicar con el servidor.'
            },
            'en': {
                'pageTitle': 'New Request - ARCA', 'welcome': 'Welcome', 'logout': 'Log Out',
                'mainTitle': 'Create New Containment Request', 'step1': 'Data & Defects', 'step2': 'Classification', 'step3': 'Documentation',
                'section_generalData': 'General Data', 'label_personInCharge': 'Person in Charge', 'label_partNumber': 'Part Number',
                'label_partDescription': 'Part Description', 'label_quantity': 'Quantity', 'label_problemDescription': 'Problem Description',
                'section_defects': 'Defects Log', 'btn_addDefect': 'Add Defect',
                'section_classification': 'Classification', 'label_supplier': 'Supplier', 'select_supplier': 'Select a supplier',
                'label_location': 'Containment Location', 'select_location': 'Select a location', 'label_tertiary': 'Tertiary', 'select_tertiary': 'Select a tertiary',
                'section_documentation': 'Additional Documentation', 'label_attachMethod': 'Attach Work Method (Optional)',
                'label_methodName': 'Method Name', 'label_uploadPDF': 'Upload PDF file',
                'btn_saveRequest': 'Save Request',
                'span_selectFile': 'Select file...', 'span_selectImage': 'Select image...',
                'defecto': 'Defect', 'label_defectName': 'Defect Name', 'label_photoOk': 'OK Photo (Correct example)', 'label_photoNok': 'NOK Photo (Defect evidence)',
                'swal_limitTitle': 'Limit Reached', 'swal_limitText': 'You can register a maximum of 5 defects per request.',
                'title_addSupplier': 'Add New Supplier', 'title_addLocation': 'Add New Location', 'title_addTertiary': 'Add New Tertiary',
                'swal_inputLabel': 'Name of the new', 'swal_placeholder': 'Enter the name...',
                'swal_btnSave': 'Save', 'swal_btnCancel': 'Cancel', 'swal_validationEmpty': 'The name cannot be empty',
                'swal_requestFail': 'The request failed:', 'swal_saved': 'Saved!', 'swal_error': 'Error',
                'swal_saving': 'Saving Request...', 'swal_savingText': 'This may take a moment.',
                'swal_missingDefectsTitle': 'Missing Defects', 'swal_missingDefectsText': 'You must register at least one defect to save the request.',
                'swal_successTitle': 'Request Saved!', 'swal_errorTitle': 'Error Saving', 'swal_connectionError': 'Connection Error',
                'swal_connectionErrorText': 'Could not communicate with the server.'
            }
        };

        // 2. La Función que traduce la página
        function translatePage(lang) {
            currentLang = lang;
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.dataset.translateKey;
                const target = translations[lang];
                if (target && target[key]) {
                    // Si es un placeholder, lo asignamos al placeholder
                    if (el.tagName === 'OPTION' || el.tagName === 'INPUT' && el.type === 'text') {
                        // Podríamos añadir una lógica para placeholders si fuera necesario
                    }
                    el.innerText = target[key];
                }
            });
            document.querySelectorAll('[data-translate-key-title]').forEach(el => {
                const key = el.dataset.translateKeyTitle;
                const target = translations[lang];
                if(target && target[key]) {
                    el.title = target[key];
                }
            });
            document.title = translations[lang]['pageTitle'];
        }

        // 3. Lógica para los botones de idioma
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

        // 4. Revisa si hay un idioma guardado y lo aplica
        const savedLang = localStorage.getItem('userLanguage');
        if (savedLang) {
            const langBtnToActivate = document.querySelector(`.lang-btn[data-lang="${savedLang}"]`);
            if (langBtnToActivate) langBtnToActivate.click();
        }

        // --- Lógica del formulario (existente) ---

        document.getElementById('toggleMetodo').addEventListener('change', function() { /*...*/ });
        const btnAddDefecto = document.getElementById('btn-add-defecto');
        const defectosContainer = document.getElementById('defectos-container');
        let defectoCounter = 0;

        btnAddDefecto.addEventListener('click', function() {
            if (defectosContainer.children.length >= 5) {
                Swal.fire(translations[currentLang].swal_limitTitle, translations[currentLang].swal_limitText, 'warning');
                return;
            }
            defectoCounter++;
            const defectoHTML = `
        <div class="defecto-item" id="defecto-${defectoCounter}">
            <div class="defecto-header">
                <h4>${translations[currentLang].defecto} #${defectosContainer.children.length + 1}</h4>
                <button type="button" class="btn-remove-defecto" data-defecto-id="${defectoCounter}">&times;</button>
            </div>
            <div class="form-group">
                <label>${translations[currentLang].label_defectName}</label>
                <input type="text" name="defectos[${defectoCounter}][nombre]" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>${translations[currentLang].label_photoOk}</label>
                    <label class="file-upload-label" for="defectoFotoOk-${defectoCounter}">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span data-default-text="${translations[currentLang].span_selectImage}">${translations[currentLang].span_selectImage}</span>
                    </label>
                    <input type="file" id="defectoFotoOk-${defectoCounter}" name="defectos[${defectoCounter}][foto_ok]" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label>${translations[currentLang].label_photoNok}</label>
                     <label class="file-upload-label" for="defectoFotoNok-${defectoCounter}">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span data-default-text="${translations[currentLang].span_selectImage}">${translations[currentLang].span_selectImage}</span>
                    </label>
                    <input type="file" id="defectoFotoNok-${defectoCounter}" name="defectos[${defectoCounter}][foto_nok]" accept="image/*" required>
                </div>
            </div>
        </div>`;
            defectosContainer.insertAdjacentHTML('beforeend', defectoHTML);
        });

        defectosContainer.addEventListener('click', function(e) { if (e.target && e.target.classList.contains('btn-remove-defecto')) { /*...*/ } });
        document.querySelector('.form-container').addEventListener('change', function(e) { if (e.target.type === 'file') { /*...*/ } });

        <?php if ($esSuperUsuario): ?>
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function() {
                const tipo = this.dataset.tipo;
                const titulos = {
                    proveedor: translations[currentLang].title_addSupplier,
                    lugar: translations[currentLang].title_addLocation,
                    terciaria: translations[currentLang].title_addTertiary
                };

                Swal.fire({
                    title: titulos[tipo], input: 'text',
                    inputLabel: `${translations[currentLang].swal_inputLabel} ${tipo}`,
                    inputPlaceholder: translations[currentLang].swal_placeholder,
                    showCancelButton: true, confirmButtonText: translations[currentLang].swal_btnSave,
                    cancelButtonText: translations[currentLang].swal_btnCancel,
                    preConfirm: (nombre) => {
                        if (!nombre) { Swal.showValidationMessage(translations[currentLang].swal_validationEmpty); return false; }
                        const formData = new FormData();
                        formData.append('nombre', nombre);
                        return fetch(`dao/add_${tipo}.php`, { method: 'POST', body: formData })
                            .then(response => { if (!response.ok) { throw new Error(response.statusText); } return response.json(); })
                            .catch(error => { Swal.showValidationMessage(`${translations[currentLang].swal_requestFail} ${error}`); });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value.status === 'success') {
                        Swal.fire(translations[currentLang].swal_saved, result.value.message, 'success');
                        const select = document.getElementById(tipo);
                        const newOption = new Option(result.value.data.nombre, result.value.data.id, true, true);
                        select.add(newOption);
                    } else if (result.value) {
                        Swal.fire(translations[currentLang].swal_error, result.value.message, 'error');
                    }
                });
            });
        });
        <?php endif; ?>

        const solicitudForm = document.getElementById('solicitudForm');
        solicitudForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (defectosContainer.children.length === 0) {
                Swal.fire({ icon: 'error', title: translations[currentLang].swal_missingDefectsTitle, text: translations[currentLang].swal_missingDefectsText });
                return;
            }
            const formData = new FormData(solicitudForm);
            Swal.fire({ title: translations[currentLang].swal_saving, text: translations[currentLang].swal_savingText, allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            fetch('dao/guardar_solicitud.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ icon: 'success', title: translations[currentLang].swal_successTitle, text: data.message })
                            .then(() => { window.location.href = 'index.php'; });
                    } else {
                        Swal.fire({ icon: 'error', title: translations[currentLang].swal_errorTitle, text: data.message });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: translations[currentLang].swal_connectionError, text: translations[currentLang].swal_connectionErrorText });
                });
        });
    });
</script>

</body>
</html>