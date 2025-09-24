<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);
$idUsuarioActual = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Error: No se proporcionó un ID de solicitud.");
}
$idSolicitud = intval($_GET['id']);

// Conexión y carga de datos para la página
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Obtenemos los datos de la solicitud y el estatus de su método de trabajo
$stmt = $conex->prepare("SELECT s.*, m.EstatusAprobacion, m.RutaArchivo AS RutaMetodo 
                         FROM Solicitudes s 
                         LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo 
                         WHERE s.IdSolicitud = ?");
$stmt->bind_param("i", $idSolicitud);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();

if (!$solicitud) { die("Error: Solicitud no encontrada."); }

// Cargamos los catálogos necesarios para los formularios
$catalogo_defectos_query = $conex->query("SELECT IdDefectoCatalogo, NombreDefecto FROM CatalogoDefectos ORDER BY NombreDefecto ASC");
$defectos_options_html = "";
while($row = $catalogo_defectos_query->fetch_assoc()) {
    $defectos_options_html .= "<option value='{$row['IdDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
}

$razones_tiempo_muerto = $conex->query("SELECT IdTiempoMuerto, Razon FROM CatalogoTiempoMuerto ORDER BY Razon ASC");

$defectos_originales = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto 
                                     FROM Defectos d 
                                     JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo 
                                     WHERE d.IdSolicitud = $idSolicitud");
$conex->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inspección - ARCA</title>
    <link rel="stylesheet" href="css/estilosT.css">
    <!-- Links a Fonts, FontAwesome, SweetAlert2 -->
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
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-hammer"></i> Reporte de Inspección - Folio S-<?php echo str_pad($solicitud['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></h1>
        <p style="font-size: 18px; font-weight: 600; color: var(--color-primario);">No. de Parte: <span style="font-weight: normal; color: var(--color-texto);"><?php echo htmlspecialchars($solicitud['NumeroParte']); ?></span></p>

        <?php
        $mostrarFormularioPrincipal = false;
        if ($solicitud['IdMetodo'] === NULL) {
            echo "<div class='notification-box warning'><i class='fa-solid fa-triangle-exclamation'></i> <strong>Acción Requerida:</strong> Para continuar, por favor, sube el método de trabajo para esta solicitud.</div>";
        } elseif ($solicitud['EstatusAprobacion'] === 'Rechazado') {
            echo "<div class='notification-box error'><i class='fa-solid fa-circle-xmark'></i> <strong>Método Rechazado:</strong> El método de trabajo anterior fue rechazado. Por favor, sube una versión corregida.</div>";
        } else {
            if ($solicitud['EstatusAprobacion'] === 'Pendiente') {
                echo "<div class='notification-box info'><i class='fa-solid fa-clock'></i> <strong>Aviso:</strong> El método de trabajo está pendiente de aprobación. Puedes continuar con el registro.</div>";
            }
            $mostrarFormularioPrincipal = true;
        }
        ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteForm" action="dao/guardar_reporte.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">

                <fieldset><legend><i class="fa-solid fa-chart-simple"></i> Resumen de Inspección</legend>
                    <div class="form-row">
                        <div class="form-group"><label>Piezas Inspeccionadas</label><input type="number" name="piezasInspeccionadas" required></div>
                        <div class="form-group"><label>Piezas Rechazadas</label><input type="number" name="piezasRechazadas" required></div>
                        <div class="form-group"><label>Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Inspector</label>
                            <input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required>
                        </div>
                        <div class="form-group"><label>Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
                    </div>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> Clasificación de Defectos Originales</legend>
                    <div class="original-defect-list">
                        <?php if ($defectos_originales->num_rows > 0): ?>
                            <?php while($defecto = $defectos_originales->fetch_assoc()): ?>
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($defecto['NombreDefecto']); ?></label>
                                    <div class="form-row">
                                        <div class="form-group w-50">
                                            <input type="number" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][cantidad]" placeholder="Cantidad de piezas con este defecto..." required>
                                        </div>
                                        <div class="form-group w-50">
                                            <input type="text" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][lote]" placeholder="Ingresa el Bach/Lote...">
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No hay defectos originales registrados en esta solicitud.</p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-magnifying-glass-plus"></i> Nuevos Defectos Encontrados (Opcional)</legend>
                    <div id="nuevos-defectos-container"></div>
                    <button type="button" id="btn-add-nuevo-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> Añadir Nuevo Defecto</button>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-stopwatch"></i> Tiempos y Comentarios</legend>
                    <div class="form-group"><label>Tiempo Total de Inspección</label><input type="text" name="tiempoInspeccion" placeholder="Ej: 2 horas 30 minutos"></div>
                    <div class="form-group">
                        <label>Razón de Tiempo Muerto (Opcional)</label>
                        <div class="select-with-button">
                            <select name="idTiempoMuerto">
                                <option value="">Ninguno</option>
                                <?php mysqli_data_seek($razones_tiempo_muerto, 0); while($razon = $razones_tiempo_muerto->fetch_assoc()): ?>
                                    <option value="<?php echo $razon['IdTiempoMuerto']; ?>"><?php echo htmlspecialchars($razon['Razon']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="tiempomuerto" title="Añadir Razón">+</button><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group"><label>Comentarios Adicionales</label><textarea name="comentarios" rows="4"></textarea></div>
                </fieldset>

                <div class="form-actions"><button type="submit" class="btn-primary">Guardar Reporte</button></div>
            </form>
        <?php endif; ?>

        <?php if (!$mostrarFormularioPrincipal): ?>
            <form id="metodoForm" action="dao/upload_metodo_trabajo.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                <fieldset>
                    <legend><i class="fa-solid fa-paperclip"></i> Subir Método de Trabajo</legend>
                    <div class="form-group"><label>Nombre del Método</label><input type="text" name="tituloMetodo" required></div>
                    <div class="form-group">
                        <label>Archivo PDF</label>
                        <label class="file-upload-label" for="metodoFile"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo...">Seleccionar archivo...</span></label>
                        <input type="file" id="metodoFile" name="metodoFile" accept=".pdf" required>
                    </div>
                </fieldset>
                <div class="form-actions"><button type="submit" class="btn-primary">Subir Método y Notificar</button></div>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
    const opcionesDefectos = `<?php echo addslashes($defectos_options_html); ?>`;

    document.addEventListener('DOMContentLoaded', function() {
        let nuevoDefectoCounter = 0;
        const nuevosDefectosContainer = document.getElementById('nuevos-defectos-container');

        document.getElementById('btn-add-nuevo-defecto')?.addEventListener('click', function() {
            nuevoDefectoCounter++;
            const defectoHTML = `
            <div class="defecto-item" id="nuevo-defecto-${nuevoDefectoCounter}">
                <div class="defecto-header">
                    <h4>Nuevo Defecto #${nuevoDefectoCounter}</h4>
                    <button type="button" class="btn-remove-defecto" data-defecto-id="${nuevoDefectoCounter}">&times;</button>
                </div>
                <div class="form-row">
                    <div class="form-group w-50">
                        <label>Tipo de Defecto</label>
                        <select name="nuevos_defectos[${nuevoDefectoCounter}][id]" required>
                            <option value="" disabled selected>Seleccione un defecto</option>
                            ${opcionesDefectos}
                        </select>
                    </div>
                    <div class="form-group w-50">
                        <label>Cantidad de Piezas</label>
                        <input type="number" name="nuevos_defectos[${nuevoDefectoCounter}][cantidad]" placeholder="Cantidad con este defecto..." required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Foto de Evidencia</label>
                    <label class="file-upload-label" for="nuevoDefectoFoto-${nuevoDefectoCounter}">
                        <i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar imagen...">Seleccionar imagen...</span>
                    </label>
                    <input type="file" id="nuevoDefectoFoto-${nuevoDefectoCounter}" name="nuevos_defectos[${nuevoDefectoCounter}][foto]" accept="image/*" required>
                </div>
            </div>`;
            nuevosDefectosContainer.insertAdjacentHTML('beforeend', defectoHTML);
        });

        nuevosDefectosContainer.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-remove-defecto')) {
                document.getElementById(`nuevo-defecto-${e.target.dataset.defectoId}`).remove();
            }
        });

        // Lógica para todos los file inputs
        document.querySelector('.form-container').addEventListener('change', function(e) {
            if (e.target.type === 'file') {
                const labelSpan = e.target.previousElementSibling.querySelector('span');
                const defaultText = labelSpan.dataset.defaultText || 'Seleccionar archivo...';
                if (e.target.files.length > 0) {
                    labelSpan.textContent = e.target.files[0].name;
                } else {
                    labelSpan.textContent = defaultText;
                }
            }
        });

        // Lógica para el envío de los formularios con fetch
        document.getElementById('reporteForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            Swal.fire({ title: 'Guardando Reporte...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch(form.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('¡Éxito!', data.message, 'success').then(() => window.location.href = 'ver_solicitudes.php');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error'));
        });

        document.getElementById('metodoForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            Swal.fire({ title: 'Subiendo Método...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch(form.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('¡Éxito!', data.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error'));
        });

        <?php if ($esSuperUsuario): ?>
        document.querySelector('.btn-add[data-tipo="tiempomuerto"]')?.addEventListener('click', function() {
            // Lógica Swal.fire para añadir nueva razón de tiempo muerto
            // Similar a como lo hicimos en nueva_solicitud.php
        });
        <?php endif; ?>

    });
</script>
</body>
</html>