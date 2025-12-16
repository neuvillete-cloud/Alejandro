<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

// =========================================================================================================
// SEGURIDAD: VERIFICACIÓN DE DOMINIO
// =========================================================================================================
include_once("dao/conexionArca.php");
$conSeguridad = new LocalConector();
$conexSeguridad = $conSeguridad->conectar();

$accesoPermitido = false;
if (isset($_SESSION['user_id'])) {
    $idUserCheck = $_SESSION['user_id'];
    $stmtCheck = $conexSeguridad->prepare("SELECT Correo FROM Usuarios WHERE IdUsuario = ?");
    $stmtCheck->bind_param("i", $idUserCheck);
    $stmtCheck->execute();

    $stmtCheck->bind_result($correoUsuario);

    if ($stmtCheck->fetch()) {
        if (strpos(strtolower($correoUsuario), '@grammer.com') !== false) {
            $accesoPermitido = true;
        }
    }
    $stmtCheck->close();
}
$conexSeguridad->close();

if (!$accesoPermitido) {
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Restringido</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">
        <style>
            body { font-family: "Montserrat", sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0; }
            .error-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
            h1 { color: #e74c3c; margin-top: 0; }
            p { color: #555; line-height: 1.6; }
            a { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #4a6984; color: white; text-decoration: none; border-radius: 5px; }
            a:hover { background-color: #3b546a; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h1><i class="fa-solid fa-lock"></i> Acceso Restringido</h1>
            <p>Lo sentimos, solo el personal interno con correo corporativo (@grammer.com) tiene autorización para crear registros.</p>
            <a href="index.php">Volver al Dashboard</a>
        </div>
    </body>
    </html>';
    exit();
}
// =========================================================================================================

// --- LÓGICA DE IDIOMA ---
$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}

// --- CONEXIÓN A BD ---
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// --- CAMBIO IMPORTANTE: Consultar la tabla INDEPENDIENTE 'CeroDefectosOEM' ---
$catalogo_oem = $conex->query("SELECT IdOEM, NombreOEM FROM CeroDefectosOEM ORDER BY NombreOEM ASC");
$oem_options_html = "";
if ($catalogo_oem) {
    while($row = $catalogo_oem->fetch_assoc()) {
        $oem_options_html .= "<option value='{$row['IdOEM']}'>" . htmlspecialchars($row['NombreOEM']) . "</option>";
    }
}
$conex->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="pageTitle">Nuevo Cero Defectos - ARCA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <h1><i class="fa-solid fa-check-circle"></i> <span data-translate-key="mainTitle">Crear Nuevo Cero Defectos</span></h1>

        <form id="ceroDefectosForm" action="dao/guardar_cero_defectos.php" method="POST" enctype="multipart/form-data">

            <fieldset><legend><i class="fa-solid fa-file-lines"></i> <span data-translate-key="section_generalData">Datos Generales</span></legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="responsable" data-translate-key="label_personInCharge">Nombre del Responsable</label>
                        <input type="text" id="responsable" name="responsable" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <div class="label-with-tooltip">
                            <label for="linea" data-translate-key="label_line">Línea</label>
                            <div class="tooltip-icon"><i class="fa-solid fa-circle-info"></i><span class="tooltip-text" data-translate-key="tooltip_line">Línea de producción asignada.</span></div>
                        </div>
                        <input type="text" id="linea" name="linea" required>
                    </div>

                    <div class="form-group">
                        <div class="label-with-tooltip">
                            <label for="oem" data-translate-key="label_oem">OEM</label>
                            <div class="tooltip-icon"><i class="fa-solid fa-circle-info"></i><span class="tooltip-text" data-translate-key="tooltip_oem">Seleccione el fabricante de equipo original.</span></div>
                        </div>
                        <select id="oem" name="oem" required>
                            <option value="" disabled selected data-translate-key="select_oem_placeholder">Seleccione un OEM...</option>
                            <?php echo $oem_options_html; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="label-with-tooltip">
                            <label for="cliente" data-translate-key="label_client">Cliente / Planta</label>
                            <div class="tooltip-icon"><i class="fa-solid fa-circle-info"></i><span class="tooltip-text" data-translate-key="tooltip_client">Cliente final o planta específica.</span></div>
                        </div>
                        <input type="text" id="cliente" name="cliente" required>
                    </div>
                </div>
            </fieldset>

            <fieldset><legend><i class="fa-solid fa-paperclip"></i> <span data-translate-key="section_documentation">Documentación</span></legend>
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
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><span data-translate-key="btn_saveZD">Guardar Cero Defectos</span></button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        let currentLang = '<?php echo $idioma_actual; ?>';

        const translations = {
            'es': {
                'pageTitle': 'Nuevo Cero Defectos - ARCA', 'welcome': 'Bienvenido', 'logout': 'Cerrar Sesión',
                'mainTitle': 'Crear Nuevo Cero Defectos',
                'section_generalData': 'Datos Generales', 'label_personInCharge': 'Nombre del Responsable',
                'label_line': 'Línea', 'tooltip_line': 'Línea de producción o celda asignada.',
                'label_oem': 'OEM', 'tooltip_oem': 'Seleccione el fabricante de equipo original (Original Equipment Manufacturer).',
                'select_oem_placeholder': 'Seleccione un OEM...',
                'label_client': 'Cliente / Planta', 'tooltip_client': 'Cliente final o planta específica para este proyecto.',
                'section_documentation': 'Documentación',
                'label_attachInstruction': 'Adjuntar Instrucción de Trabajo / Inspección (Opcional)',
                'label_docName': 'Nombre del Documento', 'label_uploadPDF': 'Subir archivo PDF',
                'btn_saveZD': 'Guardar Cero Defectos',
                'span_selectFile': 'Seleccionar archivo...',
                'swal_saving': 'Guardando Cero Defectos...', 'swal_savingText': 'Este proceso puede tardar un momento.',
                'swal_successTitle': '¡Registro Guardado!', 'swal_errorTitle': 'Error al Guardar', 'swal_connectionError': 'Error de Conexión',
                'swal_connectionErrorText': 'No se pudo comunicar con el servidor.',
                'tooltip_instructionName': 'Asigna un nombre descriptivo al documento (ej: WI-INSP-001).',
                'swal_saved': '¡Guardado!', 'swal_error': 'Error'
            },
            'en': {
                'pageTitle': 'New Zero Defects - ARCA', 'welcome': 'Welcome', 'logout': 'Log Out',
                'mainTitle': 'Create New Zero Defects',
                'section_generalData': 'General Data', 'label_personInCharge': 'Person in Charge',
                'label_line': 'Line', 'tooltip_line': 'Assigned production line or cell.',
                'label_oem': 'OEM', 'tooltip_oem': 'Select the Original Equipment Manufacturer.',
                'select_oem_placeholder': 'Select an OEM...',
                'label_client': 'Client / Plant', 'tooltip_client': 'End customer or specific plant for this project.',
                'section_documentation': 'Documentation',
                'label_attachInstruction': 'Attach Work / Inspection Instruction (Optional)',
                'label_docName': 'Document Name', 'label_uploadPDF': 'Upload PDF file',
                'btn_saveZD': 'Save Zero Defects',
                'span_selectFile': 'Select file...',
                'swal_saving': 'Saving Zero Defects...', 'swal_savingText': 'This may take a moment.',
                'swal_successTitle': 'Record Saved!', 'swal_errorTitle': 'Error Saving', 'swal_connectionError': 'Connection Error',
                'swal_connectionErrorText': 'Could not communicate with the server.',
                'tooltip_instructionName': 'Assign a descriptive name to the document (e.g., WI-INSP-001).',
                'swal_saved': 'Saved!', 'swal_error': 'Error'
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
            document.querySelectorAll('[data-translate-key-title]').forEach(el => {
                const key = el.dataset.translateKeyTitle;
                if(translations[lang] && translations[lang][key]) {
                    el.title = translations[lang][key];
                }
            });
            document.querySelectorAll('.tooltip-text[data-translate-key]').forEach(el => {
                const key = el.dataset.translateKey;
                if (translations[lang] && translations[lang][key]) {
                    el.innerText = translations[lang][key];
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

        document.getElementById('toggleInstruccion').addEventListener('change', function() {
            document.getElementById('instruccion-container').style.display = this.checked ? 'block' : 'none';
        });

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

        const ceroDefectosForm = document.getElementById('ceroDefectosForm');
        ceroDefectosForm.addEventListener('submit', function(event) {
            event.preventDefault();

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

            const formData = new FormData(ceroDefectosForm);
            Swal.fire({ title: translations[currentLang].swal_saving, text: translations[currentLang].swal_savingText, allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

            fetch(ceroDefectosForm.action, { method: 'POST', body: formData })
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

        translatePage(currentLang);
    });
</script>

</body>
</html>
