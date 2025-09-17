<?php
// Incluye el script que verifica si hay una sesión activa o una cookie "remember_me"
// Asegúrate de que la ruta a tu archivo sea la correcta.
include_once("dao/verificar_sesion.php");

// Si después de la verificación, el usuario sigue sin estar logueado, se redirige a la página de acceso
if (!isset($_SESSION['loggedin'])) {
    header('Location: acceso.php');
    exit();
}

// Se verifica si el usuario tiene el rol de Super Usuario (IdRol = 1)
$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);

// --- Cargar datos para los menús desplegables ---
// Asegúrate de que la ruta a tu archivo de conexión sea la correcta.
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Cargar Proveedores
$proveedores = $conex->query("SELECT IdProvedor, NombreProvedor FROM Provedores ORDER BY NombreProvedor ASC");

// Cargar Commodities
$commodities = $conex->query("SELECT IdCommodity, NombreCommodity FROM Commodity ORDER BY NombreCommodity ASC");

// Cargar Terciarias
$terciarias = $conex->query("SELECT IdTerciaria, NombreTerciaria FROM Terciarias ORDER BY NombreTerciaria ASC");

// Cerrar la conexión después de obtener los datos
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
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">
            Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-file-circle-plus"></i> Crear Nueva Solicitud de Contención</h1>

        <form id="solicitudForm" action="php/guardar_solicitud.php" method="POST" enctype="multipart/form-data">

            <fieldset><legend>Datos Generales</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="responsable">Nombre del Responsable</label>
                        <input type="text" id="responsable" name="responsable" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" >
                    </div>
                    <div class="form-group">
                        <label for="numeroParte">Número de Parte</label>
                        <input type="text" id="numeroParte" name="numeroParte" required>
                    </div>
                    <div class="form-group">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción del Problema</label>
                    <textarea id="descripcion" name="descripcion" rows="3" required></textarea>
                </div>
            </fieldset>

            <fieldset><legend>Clasificación</legend>
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
                        <label for="commodity">Commodity</label>
                        <div class="select-with-button">
                            <select id="commodity" name="IdCommodity" required>
                                <option value="" disabled selected>Seleccione un commodity</option>
                                <?php while($row = $commodities->fetch_assoc()): ?>
                                    <option value="<?php echo $row['IdCommodity']; ?>"><?php echo htmlspecialchars($row['NombreCommodity']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?>
                                <button type="button" class="btn-add" data-tipo="commodity" title="Añadir Nuevo Commodity">+</button>
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

            <fieldset><legend>Documentación Adicional</legend>
                <div class="form-group-checkbox"><input type="checkbox" id="toggleMetodo"><label for="toggleMetodo">Adjuntar Método de Trabajo (Opcional)</label></div>
                <div id="metodo-trabajo-container" class="hidden-section"><div class="form-group"><label for="metodoFile">Subir archivo PDF</label><input type="file" id="metodoFile" name="metodoFile" accept=".pdf"></div></div>
            </fieldset>

            <fieldset><legend>Registro de Defectos</legend>
                <div id="defectos-container"></div>
                <button type="button" id="btn-add-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> Añadir Defecto</button>
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
                        <input type="file" name="defectos[${defectoCounter}][foto_ok]" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="defectoFotoNok-${defectoCounter}">Foto NO OK (Evidencia del defecto)</label>
                        <input type="file" name="defectos[${defectoCounter}][foto_nok]" accept="image/*" required>
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

        <?php if ($esSuperUsuario): ?>
        // Lógica para los botones de añadir catálogos (Super Usuario)
        document.querySelectorAll('.btn-add').forEach(button => {
            button.addEventListener('click', function() {
                const tipo = this.dataset.tipo;
                const titulos = {
                    proveedor: 'Añadir Nuevo Proveedor',
                    commodity: 'Añadir Nuevo Commodity',
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

                        return fetch(`dao/add_${tipo}.php`, { // Asegúrate de que la ruta sea correcta
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

        // --- INICIA NUEVO BLOQUE: Lógica para Enviar el Formulario Completo ---
        const solicitudForm = document.getElementById('solicitudForm');

        solicitudForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Siempre prevenimos el envío por defecto

            // Validación: Asegurarse de que al menos un defecto ha sido añadido
            if (defectosContainer.children.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Faltan Defectos',
                    text: 'Debes registrar al menos un defecto para poder guardar la solicitud.',
                });
                return;
            }

            const formData = new FormData(solicitudForm);

            Swal.fire({
                title: 'Guardando Solicitud...',
                text: 'Este proceso puede tardar un momento.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('php/guardar_solicitud.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Solicitud Guardada!',
                            text: data.message,
                        }).then(() => {
                            // Redirigir o limpiar el formulario
                            window.location.href = 'index.php'; // Por ejemplo, al dashboard
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al Guardar',
                            text: data.message,
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo comunicar con el servidor.',
                    });
                });
        });
    });
</script>

</body>
</html>