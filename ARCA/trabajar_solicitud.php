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
$stmt = $conex->prepare("SELECT s.*, u.Nombre AS NombreCreador, m.EstatusAprobacion, m.RutaArchivo AS RutaMetodo 
                         FROM Solicitudes s 
                         LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo 
                         LEFT JOIN Usuarios u ON s.IdUsuario = u.IdUsuario
                         WHERE s.IdSolicitud = ?");
$stmt->bind_param("i", $idSolicitud);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();

if (!$solicitud) { die("Error: Solicitud no encontrada."); }

// --- Datos para la cabecera del formulario ---
$nombreResponsable = htmlspecialchars($solicitud['NombreCreador']);
$numeroParte = htmlspecialchars($solicitud['NumeroParte']);
$cantidadSolicitada = htmlspecialchars($solicitud['Cantidad']);
$nombreDefectosOriginales = [];
$defectos_originales_query = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto FROM Defectos d JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo WHERE d.IdSolicitud = $idSolicitud");
while($def = $defectos_originales_query->fetch_assoc()) {
    $nombreDefectosOriginales[] = htmlspecialchars($def['NombreDefecto']);
}
$nombresDefectosStr = implode(", ", $nombreDefectosOriginales);

// --- Catálogos para los formularios ---
$catalogo_defectos_query = $conex->query("SELECT IdDefectoCatalogo, NombreDefecto FROM CatalogoDefectos ORDER BY NombreDefecto ASC");
$defectos_options_html = "";
while($row = $catalogo_defectos_query->fetch_assoc()) {
    $defectos_options_html .= "<option value='{$row['IdDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
}
$razones_tiempo_muerto = $conex->query("SELECT IdTiempoMuerto, Razon FROM CatalogoTiempoMuerto ORDER BY Razon ASC");
$rangos_horas = $conex->query("SELECT IdRangoHora, RangoHora FROM CatalogoRangosHoras ORDER BY IdRangoHora ASC");
$defectos_originales_formulario = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto FROM Defectos d JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo WHERE d.IdSolicitud = $idSolicitud");

// --- Carga de reportes existentes para la tabla ---
$reportes_anteriores_query = $conex->prepare("
    SELECT 
        ri.IdReporte, ri.FechaInspeccion, ri.NombreInspector, ri.PiezasInspeccionadas, ri.PiezasAceptadas,
        (ri.PiezasInspeccionadas - ri.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        ri.PiezasRetrabajadas, crh.RangoHora
    FROM ReportesInspeccion ri
    LEFT JOIN CatalogoRangosHoras crh ON ri.IdRangoHora = crh.IdRangoHora
    WHERE ri.IdSolicitud = ? ORDER BY ri.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idSolicitud);
$reportes_anteriores_query->execute();
$reportes_anteriores = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);

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

        <div class="info-row">
            <p><strong>No. de Parte:</strong> <span><?php echo $numeroParte; ?></span></p>
            <p><strong>Responsable:</strong> <span><?php echo $nombreResponsable; ?></span></p>
            <p><strong>Cantidad Total:</strong> <span><?php echo $cantidadSolicitada; ?></span></p>
            <p><strong>Defectos:</strong> <span><?php echo $nombresDefectosStr; ?></span></p>
        </div>
        <hr style="margin-top: 20px; margin-bottom: 30px; border-color: var(--color-borde);">

        <?php
        $mostrarFormularioPrincipal = false;
        if ($solicitud['IdMetodo'] === NULL) {
            echo "<div class='notification-box warning'><i class='fa-solid fa-triangle-exclamation'></i> <strong>Acción Requerida:</strong> Para continuar, por favor, sube el método de trabajo para esta solicitud.</div>";
        } elseif ($solicitud['EstatusAprobacion'] === 'Rechazado') {
            echo "<div class='notification-box error'><i class='fa-solid fa-circle-xmark'></i> <strong>Método Rechazado:</strong> El método de trabajo anterior fue rechazado. Por favor, sube una versión corregida.</div>";
        } else {
            if ($solicitud['IdMetodo'] !== NULL && $solicitud['EstatusAprobacion'] === 'Pendiente') {
                echo "<div class='notification-box info'><i class='fa-solid fa-clock'></i> <strong>Aviso:</strong> El método de trabajo está pendiente de aprobación. Puedes continuar con el registro.</div>";
            }
            $mostrarFormularioPrincipal = true;
        }
        ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteForm" action="dao/guardar_reporte.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                <input type="hidden" name="idReporte" id="idReporte" value=""> <fieldset><legend><i class="fa-solid fa-chart-simple"></i> Resumen de Inspección</legend>
                    <div class="form-row">
                        <div class="form-group"><label>Piezas Inspeccionadas</label><input type="number" name="piezasInspeccionadas" id="piezasInspeccionadas" min="0" required></div>
                        <div class="form-group"><label>Piezas Aceptadas</label><input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required></div>
                        <div class="form-group"><label>Piezas Rechazadas (Cálculo)</label><input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label>Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
                    </div>

                    <div class="form-group">
                        <label>Rango de Hora de Inspección</label>
                        <select name="idRangoHora" required>
                            <option value="" disabled selected>Seleccione un rango</option>
                            <?php mysqli_data_seek($rangos_horas, 0); while($rango = $rangos_horas->fetch_assoc()): ?>
                                <option value="<?php echo $rango['IdRangoHora']; ?>"><?php echo htmlspecialchars($rango['RangoHora']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> Clasificación de Defectos Originales</legend>
                    <div class="original-defect-list">
                        <p class="piezas-rechazadas-info">Piezas rechazadas disponibles para clasificar: <span id="piezasRechazadasRestantes" style="font-weight: bold; color: var(--color-error);">0</span></p>
                        <?php if ($defectos_originales_formulario->num_rows > 0): ?>
                            <?php mysqli_data_seek($defectos_originales_formulario, 0); while($defecto = $defectos_originales_formulario->fetch_assoc()): ?>
                                <div class="form-group">
                                    <label><?php echo htmlspecialchars($defecto['NombreDefecto']); ?></label>
                                    <div class="form-row">
                                        <div class="form-group w-50">
                                            <input type="number" class="defecto-cantidad" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][cantidad]" placeholder="Cantidad con este defecto..." value="0" min="0" required>
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

                <fieldset><legend><i class="fa-solid fa-stopwatch"></i> Tiempos y Comentarios de la Sesión</legend>
                    <div class="form-group"><label>Tiempo de Inspección (Esta Sesión)</label><input type="text" name="tiempoInspeccion" placeholder="Ej: 2 horas 30 minutos"></div>

                    <div class="form-group">
                        <label>¿Hubo Tiempo Muerto?</label>
                        <button type="button" id="toggleTiempoMuertoBtn" class="btn-secondary" style="width: auto; padding: 10px 15px;">No <i class="fa-solid fa-toggle-off"></i></button>
                    </div>

                    <div id="tiempoMuertoSection" class="hidden-section">
                        <div class="form-group">
                            <label>Razón de Tiempo Muerto</label>
                            <div class="select-with-button">
                                <select name="idTiempoMuerto">
                                    <option value="">Seleccione una razón</option>
                                    <?php mysqli_data_seek($razones_tiempo_muerto, 0); while($razon = $razones_tiempo_muerto->fetch_assoc()): ?>
                                        <option value="<?php echo $razon['IdTiempoMuerto']; ?>"><?php echo htmlspecialchars($razon['Razon']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="tiempomuerto" title="Añadir Razón">+</button><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group"><label>Comentarios Adicionales de la Sesión</label><textarea name="comentarios" rows="4"></textarea></div>
                </fieldset>

                <div class="form-actions"><button type="submit" class="btn-primary" id="btnGuardarReporte">Guardar Reporte de Sesión</button></div>
            </form>

            <?php if (empty($solicitud['TiempoTotalInspeccion'])): ?>
                <form id="tiempoTotalForm" action="dao/guardar_tiempo_total.php" method="POST" style="margin-top: 40px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset><legend><i class="fa-solid fa-hourglass-end"></i> Finalizar Contención (Tiempo Total)</legend>
                        <p class="info-text">Este campo se llenará una única vez al finalizar toda la inspección de la contención.</p>
                        <div class="form-group"><label>Tiempo Total de Inspección de la Contención</label><input type="text" name="tiempoTotalInspeccion" placeholder="Ej: 20 horas 15 minutos" required></div>
                    </fieldset>
                    <div class="form-actions"><button type="submit" class="btn-primary">Guardar Tiempo Total y Finalizar</button></div>
                </form>
            <?php else: ?>
                <div class='notification-box info' style='margin-top: 40px;'><i class='fa-solid fa-circle-check'></i> <strong>Contención Finalizada:</strong> El tiempo total de inspección ya fue registrado (<?php echo htmlspecialchars($solicitud['TiempoTotalInspeccion']); ?>).</div>
            <?php endif; ?>

        <?php endif; ?>

        <?php if (!$mostrarFormularioPrincipal): ?>
            <form id="metodoForm" action="dao/upload_metodo.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
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

        <hr style="margin-top: 40px; margin-bottom: 30px; border-color: var(--color-borde);">

        <h2 style="margin-top: 40px;"><i class="fa-solid fa-list-check"></i> Historial de Registros de Inspección</h2>
        <?php if (count($reportes_anteriores) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID Reporte</th><th>Fecha Inspección</th><th>Rango Hora</th><th>Inspector</th>
                        <th>Inspeccionadas</th><th>Aceptadas</th><th>Rechazadas</th><th>Retrabajadas</th><th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes_anteriores as $reporte): ?>
                        <tr>
                            <td><?php echo "R-" . str_pad($reporte['IdReporte'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($reporte['FechaInspeccion']))); ?></td>
                            <td><?php echo htmlspecialchars($reporte['RangoHora']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['NombreInspector']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasInspeccionadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasAceptadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRechazadasCalculadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRetrabajadas']); ?></td>
                            <td>
                                <button class="btn-edit-reporte btn-primary btn-small" data-id="<?php echo $reporte['IdReporte']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aún no hay registros de inspección para esta solicitud.</p>
        <?php endif; ?>

    </div>
</main>

<script>
    const opcionesDefectos = `<?php echo addslashes($defectos_options_html); ?>`;

    document.addEventListener('DOMContentLoaded', function() {
        const piezasInspeccionadasInput = document.getElementById('piezasInspeccionadas');
        const piezasAceptadasInput = document.getElementById('piezasAceptadas');
        const piezasRechazadasCalculadasInput = document.getElementById('piezasRechazadasCalculadas');
        const piezasRechazadasRestantesSpan = document.getElementById('piezasRechazadasRestantes');
        const defectosOriginalesContainer = document.querySelector('.original-defect-list');
        const btnGuardarReporte = document.getElementById('btnGuardarReporte');

        function actualizarContadores() {
            const inspeccionadas = parseInt(piezasInspeccionadasInput.value) || 0;
            const aceptadas = parseInt(piezasAceptadasInput.value) || 0;
            const rechazadasCalculadas = inspeccionadas - aceptadas;
            piezasRechazadasCalculadasInput.value = Math.max(0, rechazadasCalculadas);

            let sumDefectosClasificados = 0;
            const defectoCantidadInputs = defectosOriginalesContainer.querySelectorAll('.defecto-cantidad');
            defectoCantidadInputs.forEach(input => {
                sumDefectosClasificados += parseInt(input.value) || 0;
            });

            const restantes = rechazadasCalculadas - sumDefectosClasificados;
            piezasRechazadasRestantesSpan.textContent = restantes;

            if (restantes < 0) {
                piezasRechazadasRestantesSpan.style.color = 'var(--color-error)';
                btnGuardarReporte.disabled = true;
                btnGuardarReporte.title = 'La suma de defectos no puede exceder las piezas rechazadas.';
            } else if (restantes > 0) {
                piezasRechazadasRestantesSpan.style.color = 'orange';
                btnGuardarReporte.disabled = true;
                btnGuardarReporte.title = 'Aún faltan piezas por clasificar.';
            } else {
                piezasRechazadasRestantesSpan.style.color = 'var(--color-exito)';
                btnGuardarReporte.disabled = false;
                btnGuardarReporte.title = '';
            }
        }

        if (piezasInspeccionadasInput) {
            piezasInspeccionadasInput.addEventListener('input', actualizarContadores);
            piezasAceptadasInput.addEventListener('input', actualizarContadores);
            defectosOriginalesContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('defecto-cantidad')) {
                    actualizarContadores();
                }
            });
            actualizarContadores();
        }

        let nuevoDefectoCounter = 0;
        const nuevosDefectosContainer = document.getElementById('nuevos-defectos-container');
        document.getElementById('btn-add-nuevo-defecto')?.addEventListener('click', function() { /* ... lógica para añadir nuevos defectos ... */ });
        nuevosDefectosContainer?.addEventListener('click', function(e) { /* ... lógica para eliminar nuevos defectos ... */ });

        document.querySelector('.form-container')?.addEventListener('change', function(e) { /* ... lógica para file inputs ... */ });

        const toggleTiempoMuertoBtn = document.getElementById('toggleTiempoMuertoBtn');
        const tiempoMuertoSection = document.getElementById('tiempoMuertoSection');
        let tiempoMuertoActivo = false;
        toggleTiempoMuertoBtn?.addEventListener('click', function() {
            tiempoMuertoActivo = !tiempoMuertoActivo;
            if (tiempoMuertoActivo) {
                tiempoMuertoSection.style.display = 'block';
                toggleTiempoMuertoBtn.innerHTML = `Sí <i class="fa-solid fa-toggle-on"></i>`;
                toggleTiempoMuertoBtn.className = 'btn-primary';
            } else {
                tiempoMuertoSection.style.display = 'none';
                toggleTiempoMuertoBtn.innerHTML = `No <i class="fa-solid fa-toggle-off"></i>`;
                toggleTiempoMuertoBtn.className = 'btn-secondary';
                tiempoMuertoSection.querySelector('select').value = '';
            }
        });

        document.getElementById('reporteForm')?.addEventListener('submit', function(e) { /* ... lógica fetch ... */ });
        document.getElementById('metodoForm')?.addEventListener('submit', function(e) { /* ... lógica fetch ... */ });
        document.getElementById('tiempoTotalForm')?.addEventListener('submit', function(e) { /* ... lógica fetch ... */ });

        document.querySelectorAll('.btn-edit-reporte').forEach(button => { /* ... lógica de edición ... */ });

        <?php if ($esSuperUsuario): ?>
        document.querySelector('.btn-add[data-tipo="tiempomuerto"]')?.addEventListener('click', function() { /* ... lógica Swal ... */ });
        <?php endif; ?>
    });
</script>
</body>
</html>