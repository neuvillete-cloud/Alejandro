<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);

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
    <title>Nueva Solicitud - ARCA</title>

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
        <div id="google_translate_element"></div>
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">
            Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-file-circle-plus"></i> Crear Nueva Solicitud de Contención</h1>

        <div class="stepper">
            <div class="step active">
                <div class="step-icon">1</div>
                <div class="step-label">Datos y Defectos</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-icon">2</div>
                <div class="step-label">Clasificación</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-icon">3</div>
                <div class="step-label">Documentación</div>
            </div>
        </div>

        <form id="solicitudForm" action="dao/guardar_solicitud.php" method="POST" enctype="multipart/form-data">

            <fieldset><legend><i class="fa-solid fa-file-lines"></i> Datos Generales</legend>
                <div class="form-row">
                    <div class="form-group w-50">
                        <label for="responsable">Nombre del Responsable</label>
                        <input type="text" id="responsable" name="responsable" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" >
                    </div>
                    <div class="form-group w-25">
                        <label for="numeroParte">Número de Parte</label>
                        <input type="text" id="numeroParte" name="numeroParte" required>
                    </div>
                    <div class="form-group w-25">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcionParte">Descripción de Parte</label>
                    <input type="text" id="descripcionParte" name="descripcionParte" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción del Problema</label>
                    <textarea id="descripcion" name="descripcion" rows="3" required></textarea>
                </div>

                <fieldset><legend><i class="fa-solid fa-bug"></i> Registro de Defectos</legend>
                    <div id="defectos-container"></div>
                    <button type="button" id="btn-add-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> Añadir Defecto</button>
                </fieldset>
            </fieldset>

            <fieldset><legend><i class="fa-solid fa-tags"></i> Clasificación</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="proveedor">Proveedor</label>
                        <div class="select-with-button">
                            <select id="proveedor" name="IdProvedor" required>
                                <option value="" disabled selected>Seleccione un proveedor</option>
                                <?php while($row = $proveedores->fetch_assoc()): ?>
                                    <option value="<?php echo $row['IdProvedor']; ?>"><?php echo htmlspecialchars($row['NombreProvedor']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?>
                                <button type="button" class="btn-add" data-tipo="proveedor" title="Añadir Nuevo Proveedor">+</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lugar">Lugar de Contención</label>
                        <div class="select-with-button">
                            <select id="lugar" name="IdLugar" required>
                                <option value="" disabled selected>Seleccione un lugar</option>
                                <?php while($row = $lugares->fetch_assoc()): ?>
                                    <option value="<?php echo $row['IdLugar']; ?>"><?php echo htmlspecialchars($row['NombreLugar']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?>
                                <button type="button" class="btn-add" data-tipo="lugar" title="Añadir Nuevo Lugar">+</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="terciaria">Terciaria</label>
                        <div class="select-with-button">
                            <select id="terciaria" name="IdTerciaria" required>
                                <option value="" disabled selected>Seleccione una terciaria</option>
                                <?php while($row = $terciarias->fetch_assoc()): ?>
                                    <option value="<?php echo $row['IdTerciaria']; ?>"><?php echo htmlspecialchars($row['NombreTerciaria']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?>
                                <button type="button" class="btn-add" data-tipo="terciaria" title="Añadir Nueva Terciaria">+</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset><legend><i class="fa-solid fa-paperclip"></i> Documentación Adicional</legend>
                <div class="form-group-checkbox">
                    <input type="checkbox" id="toggleMetodo">
                    <label for="toggleMetodo">Adjuntar Método de Trabajo (Opcional)</label>
                </div>
                <div id="metodo-trabajo-container" class="hidden-section">
                    <div class="form-group">
                        <label for="tituloMetodo">Nombre del Método</label>
                        <input type="text" id="tituloMetodo" name="tituloMetodo">
                    </div>
                    <div class="form-group">
                        <label for="metodoFile">Subir archivo PDF</label>
                        <label class="file-upload-label" for="metodoFile">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span data-default-text="Seleccionar archivo...">Seleccionar archivo...</span>
                        </label>
                        <input type="file" id="metodoFile" name="metodoFile" accept=".pdf">
                    </div>
                </div>
            </fieldset>

            <div class="form-actions"><button type="submit" class="btn-primary">Guardar Solicitud</button></div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Lógica para mostrar/ocultar el método de trabajo
        document.getElementById('toggleMetodo').addEventListener('change', function() {
            document.getElementById('metodo-trabajo-container').style.display = this.checked ? 'block' : 'none';
        });

        // Lógica para añadir defectos dinámicamente
        const btnAddDefecto = document.getElementById('btn-add-defecto');
        const defectosContainer = document.getElementById('defectos-container');
        let defectoCounter = 0;

        btnAddDefecto.addEventListener('click', function() {
            if (defectosContainer.children.length >= 5) {
                Swal.fire('Límite alcanzado', 'Puedes registrar un máximo de 5 defectos por solicitud.', 'warning');
                return;
            }
            defectoCounter++;
            const defectoHTML = `
            <div class="defecto-item" id="defecto-${defectoCounter}">
                <div class="defecto-header">
                    <h4>Defecto #${defectosContainer.children.length + 1}</h4>
                    <button type="button" class="btn-remove-defecto" data-defecto-id="${defectoCounter}">&times;</button>
                </div>
                <div class="form-group">
                    <label for="defectoNombre-${defectoCounter}">Nombre del Defecto</label>
                    <input type="text" name="defectos[${defectoCounter}][nombre]" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="defectoFotoOk-${defectoCounter}">Foto OK (Ejemplo correcto)</label>
                        <label class="file-upload-label" for="defectoFotoOk-${defectoCounter}">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span data-default-text="Seleccionar imagen...">Seleccionar imagen...</span>
                        </label>
                        <input type="file" id="defectoFotoOk-${defectoCounter}" name="defectos[${defectoCounter}][foto_ok]" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="defectoFotoNok-${defectoCounter}">Foto NO OK (Evidencia del defecto)</label>
                         <label class="file-upload-label" for="defectoFotoNok-${defectoCounter}">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span data-default-text="Seleccionar imagen...">Seleccionar imagen...</span>
                        </label>
                        <input type="file" id="defectoFotoNok-${defectoCounter}" name="defectos[${defectoCounter}][foto_nok]" accept="image/*" required>
                    </div>
                </div>
            </div>`;
            defectosContainer.insertAdjacentHTML('beforeend', defectoHTML);
        });

        // Lógica para eliminar un defecto
        defectosContainer.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-remove-defecto')) {
                document.getElementById(`defecto-${e.target.dataset.defectoId}`).remove();
            }
        });

        // Lógica para actualizar el texto de los botones de subida de archivo
        document.querySelector('.form-container').addEventListener('change', function(e) {
            if (e.target.type === 'file') {
                const labelSpan = e.target.previousElementSibling.querySelector('span');
                const defaultText = labelSpan.dataset.defaultText;
                if (e.target.files.length > 0) {
                    labelSpan.textContent = e.target.files[0].name;
                } else {
                    labelSpan.textContent = defaultText;
                }
            }
        });

        <?php if ($esSuperUsuario): ?>
        // Lógica para los botones de añadir catálogos (Super Usuario)
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function() {
                const tipo = this.dataset.tipo;
                const titulos = {
                    proveedor: 'Añadir Nuevo Proveedor',
                    lugar: 'Añadir Nuevo Lugar',
                    terciaria: 'Añadir Nueva Terciaria'
                };

                Swal.fire({
                    title: titulos[tipo],
                    input: 'text',
                    inputLabel: `Nombre del nuevo ${tipo}`,
                    inputPlaceholder: 'Ingrese el nombre...',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: (nombre) => {
                        if (!nombre) {
                            Swal.showValidationMessage('El nombre no puede estar vacío');
                            return false;
                        }
                        const formData = new FormData();
                        formData.append('nombre', nombre);

                        return fetch(`dao/add_${tipo}.php`, {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => {
                                if (!response.ok) { throw new Error(response.statusText); }
                                return response.json();
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`La solicitud falló: ${error}`);
                            });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value.status === 'success') {
                        Swal.fire('¡Guardado!', result.value.message, 'success');
                        const select = document.getElementById(tipo);
                        const newOption = new Option(result.value.data.nombre, result.value.data.id, true, true);
                        select.add(newOption);
                    } else if (result.value) {
                        Swal.fire('Error', result.value.message, 'error');
                    }
                });
            });
        });
        <?php endif; ?>

        // Lógica para Enviar el Formulario Completo
        const solicitudForm = document.getElementById('solicitudForm');
        solicitudForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (defectosContainer.children.length === 0) {
                Swal.fire({ icon: 'error', title: 'Faltan Defectos', text: 'Debes registrar al menos un defecto para poder guardar la solicitud.' });
                return;
            }
            const formData = new FormData(solicitudForm);
            Swal.fire({ title: 'Guardando Solicitud...', text: 'Este proceso puede tardar un momento.', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            fetch('dao/guardar_solicitud.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ icon: 'success', title: '¡Solicitud Guardada!', text: data.message })
                            .then(() => { window.location.href = 'index.php'; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error al Guardar', text: data.message });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo comunicar con el servidor.' });
                });
        });
    });
</script>
<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({pageLanguage: 'es', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
    }
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>