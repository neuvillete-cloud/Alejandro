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
$numeroParte = htmlspecialchars($solicitud['NumeroParte']); // Número de parte de la solicitud
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
// Necesitamos un mysqli_data_seek(0) si vamos a iterar el mismo resultado varias veces
$catalogo_defectos_result = $catalogo_defectos_query->fetch_all(MYSQLI_ASSOC);
foreach($catalogo_defectos_result as $row) {
    $defectos_options_html .= "<option value='{$row['IdDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
}


$razones_tiempo_muerto = $conex->query("SELECT IdTiempoMuerto, Razon FROM CatalogoTiempoMuerto ORDER BY Razon ASC");
$rangos_horas_data = []; // Guardaremos los rangos de hora para calcular el turno
$rangos_horas_query = $conex->query("SELECT IdRangoHora, RangoHora FROM CatalogoRangosHoras ORDER BY IdRangoHora ASC");
while($rango = $rangos_horas_query->fetch_assoc()) {
    $rangos_horas_data[$rango['IdRangoHora']] = $rango['RangoHora'];
}
$defectos_originales_formulario = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto FROM Defectos d JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo WHERE d.IdSolicitud = $idSolicitud");
$defectos_originales_para_js = [];
mysqli_data_seek($defectos_originales_formulario, 0); // Resetear el puntero para JS
while ($def = $defectos_originales_formulario->fetch_assoc()) {
    $defectos_originales_para_js[$def['IdDefecto']] = htmlspecialchars($def['NombreDefecto']);
}

// --- Carga de reportes existentes para la tabla ---
$reportes_anteriores_query = $conex->prepare("
    SELECT 
        ri.IdReporte, ri.FechaInspeccion, ri.NombreInspector, ri.PiezasInspeccionadas, ri.PiezasAceptadas,
        (ri.PiezasInspeccionadas - ri.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        ri.PiezasRetrabajadas, crh.RangoHora, ri.Comentarios, ri.IdRangoHora
    FROM ReportesInspeccion ri
    LEFT JOIN CatalogoRangosHoras crh ON ri.IdRangoHora = crh.IdRangoHora
    WHERE ri.IdSolicitud = ? ORDER BY ri.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idSolicitud);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);

$reportes_procesados = [];
foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdReporte'];

    // Obtener defectos y lotes asociados a este reporte
    $defectos_reporte_query = $conex->prepare("
        SELECT 
            rdo.CantidadEncontrada, rdo.Lote, cd.NombreDefecto 
        FROM ReporteDefectosOriginales rdo
        JOIN Defectos d ON rdo.IdDefecto = d.IdDefecto
        JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo
        WHERE rdo.IdReporte = ?
    ");
    $defectos_reporte_query->bind_param("i", $reporte_id);
    $defectos_reporte_query->execute();
    $defectos_reporte_result = $defectos_reporte_query->get_result();
    $defectos_con_cantidades = [];
    $lotes_encontrados = [];
    while ($dr = $defectos_reporte_result->fetch_assoc()) {
        $defectos_con_cantidades[] = htmlspecialchars($dr['NombreDefecto']) . " (" . htmlspecialchars($dr['CantidadEncontrada']) . ")";
        if (!empty($dr['Lote'])) {
            $lotes_encontrados[] = htmlspecialchars($dr['Lote']);
        }
    }
    $reporte['DefectosConCantidades'] = implode("<br>", $defectos_con_cantidades);
    $reporte['LotesEncontrados'] = empty($lotes_encontrados) ? 'N/A' : implode(", ", array_unique($lotes_encontrados));


    // Calcular Turno del Shift Leader
    $turno_shift_leader = 'N/A';
    if (isset($rangos_horas_data[$reporte['IdRangoHora']])) {
        $rangoHoraStr = $rangos_horas_data[$reporte['IdRangoHora']]; // "HH:MM am - HH:MM pm"
        // Extraemos la primera hora del rango para la determinación del turno
        preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm))/i', $rangoHoraStr, $matches);
        if (!empty($matches[1])) {
            $hora_inicio_str = $matches[1];
            $hora_inicio_timestamp = strtotime($hora_inicio_str); // Convertir a timestamp para comparación

            // Definir rangos de turnos
            $primer_turno_inicio = strtotime('06:30 am');
            $primer_turno_fin = strtotime('02:30 pm'); // Excluyendo el fin, hasta las 2:30 pm
            $segundo_turno_inicio = strtotime('02:40 pm');
            $segundo_turno_fin = strtotime('10:30 pm'); // Excluyendo el fin, hasta las 10:30 pm

            if ($hora_inicio_timestamp >= $primer_turno_inicio && $hora_inicio_timestamp <= $primer_turno_fin) {
                $turno_shift_leader = 'Primer Turno';
            } elseif ($hora_inicio_timestamp >= $segundo_turno_inicio && $hora_inicio_timestamp <= $segundo_turno_fin) {
                $turno_shift_leader = 'Segundo Turno';
            } else {
                $turno_shift_leader = 'Tercer Turno / Otro'; // O cualquier otra designación
            }
        }
    }
    $reporte['TurnoShiftLeader'] = $turno_shift_leader;

    $reportes_procesados[] = $reporte;
}

$conex->close();

// --- INICIO DE NUEVA LÓGICA: Variable para controlar el visor de PDF ---
$mostrarVisorPDF = false;
if (isset($solicitud['EstatusAprobacion']) && $solicitud['EstatusAprobacion'] === 'Aprobado' && !empty($solicitud['RutaMetodo'])) {
    $mostrarVisorPDF = true;
}
// --- FIN DE NUEVA LÓGICA ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inspección - ARCA</title>
    <link rel="stylesheet" href="css/estilosT.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- INICIO DE NUEVOS ESTILOS -->
    <style>
        .pdf-viewer-container {
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            overflow: hidden; /* Para que el iframe respete los bordes redondeados */
            margin-top: 15px;
        }
    </style>
    <!-- FIN DE NUEVOS ESTILOS -->
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
            <p><strong>Cantidad Total:</strong> <span id="cantidadTotalSolicitada"><?php echo $cantidadSolicitada; ?></span></p>
            <p><strong>Defectos:</strong> <span><?php echo $nombresDefectosStr; ?></span></p>
        </div>

        <!-- INICIO DE NUEVA SECCIÓN: Visor de PDF para métodos aprobados -->
        <?php if ($mostrarVisorPDF): ?>
            <fieldset>
                <legend><i class="fa-solid fa-file-shield"></i> Método de Trabajo Aprobado</legend>
                <div class="pdf-viewer-container">
                    <iframe src="<?php echo htmlspecialchars($solicitud['RutaMetodo']); ?>" width="100%" height="600px" frameborder="0"></iframe>
                </div>
            </fieldset>
        <?php endif; ?>
        <!-- FIN DE NUEVA SECCIÓN -->

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
            // MODIFICACIÓN: El formulario principal solo se muestra si el método está Aprobado o Pendiente
            if ($solicitud['EstatusAprobacion'] === 'Aprobado' || $solicitud['EstatusAprobacion'] === 'Pendiente') {
                $mostrarFormularioPrincipal = true;
            }
        }
        ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteForm" action="dao/guardar_reporte.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                <input type="hidden" name="idReporte" id="idReporte" value=""> <fieldset>
                    <legend><i class="fa-solid fa-chart-simple"></i> Resumen de Inspección</legend>
                    <div class="form-row">
                        <div class="form-group"><label>Piezas Inspeccionadas</label><input type="number" name="piezasInspeccionadas" id="piezasInspeccionadas" min="0" required></div>
                        <div class="form-group"><label>Piezas Aceptadas</label><input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required></div>
                        <div class="form-group"><label>Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" id="piezasRetrabajadas" min="0" value="0" required></div>
                        <div class="form-group"><label>Piezas Rechazadas (Cálculo)</label><input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label>Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
                    </div>

                    <div class="form-group">
                        <label>Rango de Hora de Inspección</label>
                        <select name="idRangoHora" id="idRangoHora" required>
                            <option value="" disabled selected>Seleccione un rango</option>
                            <?php foreach($rangos_horas_data as $id => $rango): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($rango); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> Clasificación de Defectos Originales</legend>
                    <div class="original-defect-list">
                        <p class="piezas-rechazadas-info">Piezas rechazadas disponibles para clasificar: <span id="piezasRechazadasRestantes" style="font-weight: bold; color: var(--color-error);">0</span></p>
                        <?php if ($defectos_originales_formulario->num_rows > 0): ?>
                            <?php mysqli_data_seek($defectos_originales_formulario, 0); while($defecto = $defectos_originales_formulario->fetch_assoc()): ?>
                                <div class="form-group" data-id-defecto-original="<?php echo $defecto['IdDefecto']; ?>">
                                    <label><?php echo htmlspecialchars($defecto['NombreDefecto']); ?></label>
                                    <div class="form-row">
                                        <div class="form-group w-50">
                                            <input type="number" class="defecto-cantidad" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][cantidad]" placeholder="Cantidad con este defecto..." value="0" min="0" required>
                                        </div>
                                        <div class="form-group w-50">
                                            <input type="text" class="defecto-lote" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][lote]" placeholder="Ingresa el Bach/Lote...">
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
                    <div class="form-group"><label>Tiempo de Inspección (Esta Sesión)</label><input type="text" name="tiempoInspeccion" id="tiempoInspeccion" placeholder="Ej: 2 horas 30 minutos"></div>

                    <div class="form-group">
                        <label>¿Hubo Tiempo Muerto?</label>
                        <button type="button" id="toggleTiempoMuertoBtn" class="btn-secondary" style="width: auto; padding: 10px 15px;">No <i class="fa-solid fa-toggle-off"></i></button>
                    </div>

                    <div id="tiempoMuertoSection" class="hidden-section">
                        <div class="form-group">
                            <label>Razón de Tiempo Muerto</label>
                            <div class="select-with-button">
                                <select name="idTiempoMuerto" id="idTiempoMuerto">
                                    <option value="">Seleccione una razón</option>
                                    <?php mysqli_data_seek($razones_tiempo_muerto, 0); while($razon = $razones_tiempo_muerto->fetch_assoc()): ?>
                                        <option value="<?php echo $razon['IdTiempoMuerto']; ?>"><?php echo htmlspecialchars($razon['Razon']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="tiempomuerto" title="Añadir Razón">+</button><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group"><label>Comentarios Adicionales de la Sesión</label><textarea name="comentarios" id="comentarios" rows="4"></textarea></div>
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

        <!-- INICIO DE LA MODIFICACIÓN: Lógica condicional para el formulario de subida -->
        <?php if (!$mostrarFormularioPrincipal): ?>
            <?php if ($solicitud['IdMetodo'] === NULL): // Caso: Primera vez que se sube ?>
                <form id="metodoForm" action="https://grammermx.com/Mailer/upload_metodo.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset>
                        <legend><i class="fa-solid fa-paperclip"></i> Subir Método de Trabajo</legend>
                        <div class="form-group">
                            <label>Nombre del Método</label>
                            <input type="text" name="tituloMetodo" required>
                        </div>
                        <div class="form-group">
                            <label>Archivo PDF</label>
                            <label class="file-upload-label" for="metodoFile"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo...">Seleccionar archivo...</span></label>
                            <input type="file" id="metodoFile" name="metodoFile" accept=".pdf" required>
                        </div>
                    </fieldset>
                    <div class="form-actions"><button type="submit" class="btn-primary">Subir y Notificar</button></div>
                </form>
            <?php else: // Caso: El método fue rechazado y se debe resubir ?>
                <form id="metodoForm" action="dao/resubir_metodo.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset>
                        <legend><i class="fa-solid fa-paperclip"></i> Corregir Método de Trabajo</legend>
                        <div class="form-group">
                            <label>Archivo PDF</label>
                            <label class="file-upload-label" for="metodoFile"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo...">Seleccionar archivo...</span></label>
                            <input type="file" id="metodoFile" name="metodoFile" accept=".pdf" required>
                        </div>
                    </fieldset>
                    <div class="form-actions"><button type="submit" class="btn-primary">Subir y Notificar</button></div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        <!-- FIN DE LA MODIFICACIÓN -->

        <hr style="margin-top: 40px; margin-bottom: 30px; border-color: var(--color-borde);">

        <h2 style="margin-top: 40px;"><i class="fa-solid fa-list-check"></i> Historial de Registros de Inspección</h2>
        <?php if (count($reportes_procesados) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID Reporte</th>
                        <th>No. de Parte</th>
                        <th>Fecha Inspección</th>
                        <th>Rango Hora</th>
                        <th>Turno Shift Leader</th>
                        <th>Inspector</th>
                        <th>Inspeccionadas</th>
                        <th>Aceptadas</th>
                        <th>Rechazadas</th>
                        <th>Retrabajadas</th>
                        <th>Defectos (Cant.)</th>
                        <th>No. de Lote</th>
                        <th>Comentarios</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes_procesados as $reporte): ?>
                        <tr>
                            <td><?php echo "R-" . str_pad($reporte['IdReporte'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo $numeroParte; ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($reporte['FechaInspeccion']))); ?></td>
                            <td><?php echo htmlspecialchars($reporte['RangoHora']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['TurnoShiftLeader']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['NombreInspector']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasInspeccionadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasAceptadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRechazadasCalculadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRetrabajadas']); ?></td>
                            <td><?php echo $reporte['DefectosConCantidades']; ?></td>
                            <td><?php echo $reporte['LotesEncontrados']; ?></td>
                            <td><?php echo htmlspecialchars($reporte['Comentarios']); ?></td>
                            <td>
                                <button class="btn-edit-reporte btn-primary btn-small" data-id="<?php echo $reporte['IdReporte']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn-delete-reporte btn-danger btn-small" data-id="<?php echo $reporte['IdReporte']; ?>"><i class="fa-solid fa-trash-can"></i></button>
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
    const defectosOriginalesMapa = <?php echo json_encode($defectos_originales_para_js); ?>; // Para JS
    // --- NUEVO: Obtenemos la cantidad total del PHP para usarla en JavaScript ---
    const cantidadTotalSolicitada = <?php echo intval($cantidadSolicitada); ?>;
    let nuevoDefectoCounter = 0; // Se usará para identificar nuevos defectos, incluso los cargados por edición
    let editandoReporte = false; // Bandera para saber si estamos en modo edición

    document.addEventListener('DOMContentLoaded', function() {
        const reporteForm = document.getElementById('reporteForm');
        const idReporteInput = document.getElementById('idReporte');
        const piezasInspeccionadasInput = document.getElementById('piezasInspeccionadas');
        const piezasAceptadasInput = document.getElementById('piezasAceptadas');
        const piezasRetrabajadasInput = document.getElementById('piezasRetrabajadas');
        const piezasRechazadasCalculadasInput = document.getElementById('piezasRechazadasCalculadas');
        const piezasRechazadasRestantesSpan = document.getElementById('piezasRechazadasRestantes');
        const defectosOriginalesContainer = document.querySelector('.original-defect-list');
        const btnGuardarReporte = document.getElementById('btnGuardarReporte');
        const nuevosDefectosContainer = document.getElementById('nuevos-defectos-container');
        const btnAddNuevoDefecto = document.getElementById('btn-add-nuevo-defecto');

        const fechaInspeccionInput = document.querySelector('input[name="fechaInspeccion"]');
        const idRangoHoraSelect = document.getElementById('idRangoHora');
        const tiempoInspeccionInput = document.getElementById('tiempoInspeccion');
        const toggleTiempoMuertoBtn = document.getElementById('toggleTiempoMuertoBtn');
        const tiempoMuertoSection = document.getElementById('tiempoMuertoSection');
        const idTiempoMuertoSelect = document.getElementById('idTiempoMuerto');
        const comentariosTextarea = document.getElementById('comentarios');

        // --- MODIFICADO: Funcionalidad del Contador de Piezas Rechazadas y Validación ---
        function actualizarContadores() {
            const inspeccionadas = parseInt(piezasInspeccionadasInput.value) || 0;
            const aceptadas = parseInt(piezasAceptadasInput.value) || 0;
            const retrabajadas = parseInt(piezasRetrabajadasInput.value) || 0;

            if (inspeccionadas > cantidadTotalSolicitada) {
                piezasInspeccionadasInput.setCustomValidity(`La cantidad inspeccionada (${inspeccionadas}) no puede ser mayor que la cantidad total solicitada (${cantidadTotalSolicitada}).`);
                piezasInspeccionadasInput.reportValidity();
            } else {
                piezasInspeccionadasInput.setCustomValidity('');
            }

            const rechazadasBrutas = inspeccionadas - aceptadas;
            piezasRechazadasCalculadasInput.value = Math.max(0, rechazadasBrutas);

            const rechazadasDisponibles = rechazadasBrutas - retrabajadas;

            if (retrabajadas > rechazadasBrutas) {
                piezasRetrabajadasInput.setCustomValidity('Las piezas retrabajadas no pueden exceder las piezas rechazadas.');
                piezasRetrabajadasInput.reportValidity();
                btnGuardarReporte.disabled = true;
                btnGuardarReporte.title = 'Las piezas retrabajadas no pueden exceder las piezas rechazadas.';
                piezasRechazadasRestantesSpan.style.color = 'var(--color-error)';
                piezasRechazadasRestantesSpan.textContent = Math.max(0, rechazadasDisponibles);
                return;
            } else {
                piezasRetrabajadasInput.setCustomValidity('');
            }

            let sumDefectosClasificados = 0;
            const defectoCantidadInputs = defectosOriginalesContainer.querySelectorAll('.defecto-cantidad');
            defectoCantidadInputs.forEach(input => {
                sumDefectosClasificados += parseInt(input.value) || 0;
            });
            nuevosDefectosContainer.querySelectorAll('.nuevo-defecto-cantidad').forEach(input => {
                sumDefectosClasificados += parseInt(input.value) || 0;
            });

            const restantes = rechazadasDisponibles - sumDefectosClasificados;
            piezasRechazadasRestantesSpan.textContent = Math.max(0, restantes);

            if (inspeccionadas > cantidadTotalSolicitada) {
                btnGuardarReporte.disabled = true;
                btnGuardarReporte.title = 'La cantidad inspeccionada excede el total solicitado.';
            } else if (restantes < 0) {
                piezasRechazadasRestantesSpan.style.color = 'var(--color-error)';
                btnGuardarReporte.disabled = true;
                btnGuardarReporte.title = 'La suma de defectos no puede exceder las piezas rechazadas disponibles.';
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
            piezasRetrabajadasInput.addEventListener('input', actualizarContadores);
            defectosOriginalesContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('defecto-cantidad')) {
                    actualizarContadores();
                }
            });
            nuevosDefectosContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('nuevo-defecto-cantidad')) {
                    actualizarContadores();
                }
            });
            actualizarContadores();
        }

        // --- Función para añadir un nuevo bloque de defecto (usado para agregar y para edición) ---
        function addNuevoDefectoBlock(id = null, idDefectoCatalogo = '', cantidad = '', rutaFoto = '') {
            nuevoDefectoCounter++;
            const currentCounter = nuevoDefectoCounter;
            const defectoHTML = `
            <div class="defecto-item" id="nuevo-defecto-${currentCounter}">
                <div class="defecto-header">
                    <h4>Nuevo Defecto #${currentCounter}</h4>
                    <button type="button" class="btn-remove-defecto" data-defecto-id="${currentCounter}">&times;</button>
                </div>
                <div class="form-row">
                    <div class="form-group w-50">
                        <label>Tipo de Defecto</label>
                        <select name="nuevos_defectos[${currentCounter}][id]" required>
                            <option value="" disabled selected>Seleccione un defecto</option>
                            ${opcionesDefectos}
                        </select>
                    </div>
                    <div class="form-group w-50">
                        <label>Cantidad de Piezas</label>
                        <input type="number" class="nuevo-defecto-cantidad" name="nuevos_defectos[${currentCounter}][cantidad]" placeholder="Cantidad con este defecto..." min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Foto de Evidencia</label>
                    <label class="file-upload-label" for="nuevoDefectoFoto-${currentCounter}">
                        <i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar imagen...">Seleccionar imagen...</span>
                    </label>
                    <input type="file" id="nuevoDefectoFoto-${currentCounter}" name="nuevos_defectos[${currentCounter}][foto]" accept="image/*" ${rutaFoto ? '' : 'required'}>
                    ${rutaFoto ? `<p class="current-file-info">Archivo actual: <a href="${rutaFoto}" target="_blank">Ver foto</a> (Se reemplazará si subes uno nuevo)</p>
                                   <input type="hidden" name="nuevos_defectos[${currentCounter}][foto_existente]" value="${rutaFoto}">
                                   <input type="hidden" name="nuevos_defectos[${currentCounter}][idDefectoEncontrado]" value="${id}">` : ''}
                </div>
            </div>`;
            nuevosDefectosContainer.insertAdjacentHTML('beforeend', defectoHTML);

            const newBlock = document.getElementById(`nuevo-defecto-${currentCounter}`);
            if (idDefectoCatalogo) {
                newBlock.querySelector(`select[name="nuevos_defectos[${currentCounter}][id]"]`).value = idDefectoCatalogo;
            }
            if (cantidad) {
                newBlock.querySelector(`input[name="nuevos_defectos[${currentCounter}][cantidad]"]`).value = cantidad;
            }
            document.getElementById(`nuevoDefectoFoto-${currentCounter}`).addEventListener('change', updateFileNameLabel);
            return newBlock;
        }

        btnAddNuevoDefecto?.addEventListener('click', function() {
            addNuevoDefectoBlock();
            actualizarContadores();
        });

        // --- Lógica para eliminar nuevos defectos ---
        nuevosDefectosContainer?.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-remove-defecto')) {
                const defectoItem = document.getElementById(`nuevo-defecto-${e.target.dataset.defectoId}`);
                if (defectoItem) {
                    const idDefectoEncontradoInput = defectoItem.querySelector('input[name*="[idDefectoEncontrado]"]');
                    if (editandoReporte && idDefectoEncontradoInput && idDefectoEncontradoInput.value) {
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Este defecto se eliminará permanentemente del reporte.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const inputEliminar = document.createElement('input');
                                inputEliminar.type = 'hidden';
                                inputEliminar.name = `defectos_encontrados_a_eliminar[]`;
                                inputEliminar.value = idDefectoEncontradoInput.value;
                                reporteForm.appendChild(inputEliminar);
                                defectoItem.remove();
                                actualizarContadores();
                                Swal.fire('Eliminado!', 'El defecto será eliminado al guardar el reporte.', 'success');
                            }
                        });
                    } else {
                        defectoItem.remove();
                        actualizarContadores();
                    }
                }
            }
        });

        // --- Lógica para actualizar el nombre de archivo en la etiqueta del input file ---
        function updateFileNameLabel(e) {
            const labelSpan = e.target.previousElementSibling.querySelector('span');
            const defaultText = labelSpan.dataset.defaultText || 'Seleccionar archivo...';
            if (e.target.files.length > 0) {
                labelSpan.textContent = e.target.files[0].name;
            } else {
                labelSpan.textContent = defaultText;
            }
        }
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', updateFileNameLabel);
        });

        // --- Lógica para mostrar/ocultar Tiempo Muerto ---
        let tiempoMuertoActivo = false;
        function toggleTiempoMuertoSection(activate) {
            tiempoMuertoActivo = activate;
            if (tiempoMuertoActivo) {
                tiempoMuertoSection.style.display = 'block';
                toggleTiempoMuertoBtn.innerHTML = `Sí <i class="fa-solid fa-toggle-on"></i>`;
                toggleTiempoMuertoBtn.className = 'btn-primary';
            } else {
                tiempoMuertoSection.style.display = 'none';
                toggleTiempoMuertoBtn.innerHTML = `No <i class="fa-solid fa-toggle-off"></i>`;
                toggleTiempoMuertoBtn.className = 'btn-secondary';
                idTiempoMuertoSelect.value = '';
            }
        }
        toggleTiempoMuertoBtn?.addEventListener('click', function() {
            toggleTiempoMuertoSection(!tiempoMuertoActivo);
        });
        toggleTiempoMuertoSection(false);

        // --- Lógica para el envío de los formularios con fetch ---
        reporteForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            const inspeccionadas = parseInt(piezasInspeccionadasInput.value) || 0;
            const aceptadas = parseInt(piezasAceptadasInput.value) || 0;
            const retrabajadas = parseInt(piezasRetrabajadasInput.value) || 0;
            const rechazadasBrutas = inspeccionadas - aceptadas;

            if (retrabajadas > rechazadasBrutas) {
                Swal.fire('Error de Validación', 'Las piezas retrabajadas no pueden exceder las piezas rechazadas.', 'error');
                return;
            }

            if (inspeccionadas > cantidadTotalSolicitada) {
                Swal.fire('Error de Validación', `La cantidad inspeccionada (${inspeccionadas}) no puede ser mayor que la cantidad total solicitada (${cantidadTotalSolicitada}).`, 'error');
                return;
            }

            Swal.fire({ title: 'Guardando Reporte...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

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

        document.getElementById('tiempoTotalForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            Swal.fire({ title: 'Guardando Tiempo Total...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

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

        // --- Función para cargar el reporte en el formulario de edición ---
        async function cargarReporteParaEdicion(idReporte) {
            Swal.fire({ title: 'Cargando Reporte...', text: 'Obteniendo datos para edición.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const response = await fetch(`dao/obtener_reporte_para_edicion.php?idReporte=${idReporte}`);
                const data = await response.json();

                if (data.status === 'success') {
                    const reporte = data.reporte;
                    const defectosOriginales = data.defectosOriginales;
                    const nuevosDefectos = data.nuevosDefectos;

                    idReporteInput.value = reporte.IdReporte;
                    piezasInspeccionadasInput.value = reporte.PiezasInspeccionadas;
                    piezasAceptadasInput.value = reporte.PiezasAceptadas;
                    piezasRetrabajadasInput.value = reporte.PiezasRetrabajadas;
                    fechaInspeccionInput.value = reporte.FechaInspeccion;
                    idRangoHoraSelect.value = reporte.IdRangoHora;
                    tiempoInspeccionInput.value = reporte.TiempoInspeccion || '';
                    comentariosTextarea.value = reporte.Comentarios || '';

                    if (reporte.IdTiempoMuerto) {
                        toggleTiempoMuertoSection(true);
                        idTiempoMuertoSelect.value = reporte.IdTiempoMuerto;
                    } else {
                        toggleTiempoMuertoSection(false);
                    }

                    defectosOriginalesContainer.querySelectorAll('.defecto-cantidad').forEach(input => input.value = 0);
                    defectosOriginalesContainer.querySelectorAll('.defecto-lote').forEach(input => input.value = '');

                    defectosOriginales.forEach(defecto => {
                        const inputCantidad = defectosOriginalesContainer.querySelector(`input[name="defectos_originales[${defecto.IdDefecto}][cantidad]"]`);
                        const inputLote = defectosOriginalesContainer.querySelector(`input[name="defectos_originales[${defecto.IdDefecto}][lote]"]`);
                        if (inputCantidad) inputCantidad.value = defecto.CantidadEncontrada;
                        if (inputLote) inputLote.value = defecto.Lote;
                    });

                    nuevosDefectosContainer.innerHTML = '';
                    nuevoDefectoCounter = 0;
                    nuevosDefectos.forEach(defecto => {
                        const newBlock = addNuevoDefectoBlock(
                            defecto.IdDefectoEncontrado,
                            defecto.IdDefectoCatalogo,
                            defecto.Cantidad,
                            defecto.RutaFotoEvidencia
                        );
                        const fileInput = newBlock.querySelector(`input[type="file"]`);
                        if (defecto.RutaFotoEvidencia) {
                            fileInput.removeAttribute('required');
                            const labelSpan = fileInput.previousElementSibling.querySelector('span');
                            labelSpan.textContent = "Archivo existente";
                        }
                    });

                    actualizarContadores();
                    editandoReporte = true;
                    btnGuardarReporte.textContent = 'Actualizar Reporte de Sesión';
                    reporteForm.action = 'dao/actualizar_reporte.php';
                    Swal.close();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error de Conexión', 'No se pudo cargar el reporte para edición.', 'error');
            }
        }

        document.querySelectorAll('.btn-edit-reporte').forEach(button => {
            button.addEventListener('click', function() {
                cargarReporteParaEdicion(this.dataset.id);
            });
        });

        document.querySelectorAll('.btn-delete-reporte').forEach(button => {
            button.addEventListener('click', function() {
                const idReporteAEliminar = this.dataset.id;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto! Se eliminará el reporte y todos los defectos y fotos asociados.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Eliminando Reporte...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        fetch('dao/eliminar_reporte.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `idReporte=${idReporteAEliminar}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('¡Eliminado!', data.message, 'success').then(() => window.location.reload());
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            })
                            .catch(error => Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor para eliminar el reporte.', 'error'));
                    }
                });
            });
        });


        <?php if ($esSuperUsuario): ?>
        document.querySelector('.btn-add[data-tipo="tiempomuerto"]')?.addEventListener('click', function() {
            Swal.fire({
                title: 'Añadir Nueva Razón de Tiempo Muerto',
                input: 'text',
                inputLabel: 'Nombre de la razón',
                inputPlaceholder: 'Ingrese la nueva razón',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return '¡Necesitas escribir algo!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const nuevaRazon = result.value;
                    fetch('dao/guardar_razon_tiempomuerto.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `razon=${encodeURIComponent(nuevaRazon)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire('¡Guardado!', data.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error de Conexión', 'No se pudo guardar la razón.', 'error'));
                }
            });
        });
        <?php endif; ?>
    });
</script>
</body>
</html>
