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
$isVariosPartes = (strtolower($numeroParte) === 'varios'); // NUEVA LÓGICA
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

$defectos_originales_formulario = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto FROM Defectos d JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo WHERE d.IdSolicitud = $idSolicitud");
$defectos_originales_para_js = [];
mysqli_data_seek($defectos_originales_formulario, 0); // Resetear el puntero para JS
while ($def = $defectos_originales_formulario->fetch_assoc()) {
    $defectos_originales_para_js[$def['IdDefecto']] = htmlspecialchars($def['NombreDefecto']);
}

// --- Carga de reportes existentes para la tabla (CORRECCIÓN APLICADA AQUÍ) ---
$reportes_anteriores_query = $conex->prepare("
    SELECT 
        ri.IdReporte, ri.FechaInspeccion, ri.NombreInspector, ri.PiezasInspeccionadas, ri.PiezasAceptadas,
        (ri.PiezasInspeccionadas - ri.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        ri.PiezasRetrabajadas, 
        COALESCE(ri.RangoHora, crh.RangoHora) AS RangoHora, 
        ri.Comentarios
    FROM ReportesInspeccion ri
    LEFT JOIN CatalogoRangosHoras crh ON ri.IdRangoHora = crh.IdRangoHora
    WHERE ri.IdSolicitud = ? ORDER BY ri.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idSolicitud);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);

$reportes_procesados = [];
// --- NUEVA LÓGICA: Calcular el tiempo total ---
$totalHorasRegistradas = count($reportes_raw);

foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdReporte'];

    // Obtener defectos y lotes asociados a este reporte (VERSIÓN CORREGIDA)
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
    if (isset($reporte['RangoHora'])) {
        $rangoHoraStr = $reporte['RangoHora'];
        preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm))/i', $rangoHoraStr, $matches);
        if (!empty($matches[1])) {
            $hora_inicio_str = $matches[1];
            $hora_inicio_timestamp = strtotime($hora_inicio_str);

            $primer_turno_inicio = strtotime('06:30 am');
            $primer_turno_fin = strtotime('02:30 pm');
            $segundo_turno_inicio = strtotime('02:40 pm');
            $segundo_turno_fin = strtotime('10:30 pm');

            if ($hora_inicio_timestamp >= $primer_turno_inicio && $hora_inicio_timestamp <= $primer_turno_fin) {
                $turno_shift_leader = 'Primer Turno';
            } elseif ($hora_inicio_timestamp >= $segundo_turno_inicio && $hora_inicio_timestamp <= $segundo_turno_fin) {
                $turno_shift_leader = 'Segundo Turno';
            } else {
                $turno_shift_leader = 'Tercer Turno / Otro';
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

    <style>
        .pdf-viewer-container {
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
        }
        .form-row.defect-entry-row, .form-row.parte-inspeccionada-row {
            display: flex;
            gap: 10px;
            align-items: flex-end; /* Alinea los elementos en la parte inferior */
            margin-bottom: 10px;
        }
        .form-row.defect-entry-row .form-group, .form-row.parte-inspeccionada-row .form-group {
            flex: 1 1 0; /* Permite que los inputs crezcan y se encojan */
            min-width: 0; /* Permite que los inputs se encojan más allá de su tamaño de contenido */
            margin-bottom: 0;
        }
        .btn-remove-batch, .btn-remove-parte {
            flex-shrink: 0; /* Evita que el botón de eliminar se encoja */
        }
        #partes-inspeccionadas-container {
            margin-top: 15px;
            margin-bottom: 15px;
        }
    </style>
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

        <?php if ($mostrarVisorPDF): ?>
            <fieldset>
                <legend><i class="fa-solid fa-file-shield"></i> Método de Trabajo Aprobado</legend>
                <div class="form-actions" style="margin-bottom: 15px;">
                    <button type="button" id="togglePdfViewerBtn" class="btn-secondary"><i class="fa-solid fa-eye"></i> Ver Método de Trabajo</button>
                </div>
                <div id="pdfViewerWrapper" style="display: none;">
                    <div class="pdf-viewer-container">
                        <iframe src="<?php echo htmlspecialchars($solicitud['RutaMetodo']); ?>" width="100%" height="600px" frameborder="0"></iframe>
                    </div>
                </div>
            </fieldset>
        <?php endif; ?>

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
            if ($solicitud['EstatusAprobacion'] === 'Aprobado' || $solicitud['EstatusAprobacion'] === 'Pendiente') {
                $mostrarFormularioPrincipal = true;
            }
        }
        ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteForm" action="dao/guardar_reporte.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                <input type="hidden" name="idReporte" id="idReporte" value="">
                <fieldset>
                    <legend><i class="fa-solid fa-chart-simple"></i> Resumen de Inspección</legend>

                    <?php if ($isVariosPartes): ?>
                        <div id="desglose-partes-container">
                            <label>Desglose de Piezas por No. de Parte</label>
                            <div id="partes-inspeccionadas-container">
                                <!-- Las filas se añadirán aquí dinámicamente -->
                            </div>
                            <button type="button" id="btn-add-parte-inspeccionada" class="btn-secondary btn-small"><i class="fa-solid fa-plus"></i> Añadir No. de Parte</button>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Total de Piezas Inspeccionadas</label>
                            <input type="number" name="piezasInspeccionadas" id="piezasInspeccionadas" min="0" required <?php if($isVariosPartes) echo 'readonly style="background-color: #e9ecef;"'; ?>>
                        </div>
                        <div class="form-group"><label>Piezas Aceptadas</label><input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required></div>
                        <div class="form-group"><label>Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" id="piezasRetrabajadas" min="0" value="0" required></div>
                        <div class="form-group"><label>Piezas Rechazadas (Cálculo)</label><input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label>Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Hora de Inicio</label>
                            <input type="time" name="horaInicio" id="horaInicio" required>
                        </div>
                        <div class="form-group">
                            <label>Hora de Fin (1 hora)</label>
                            <input type="time" name="horaFin" id="horaFin" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        </div>
                    </div>
                    <input type="hidden" name="rangoHoraCompleto" id="rangoHoraCompleto">

                </fieldset>

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> Clasificación de Defectos Originales</legend>
                    <div class="original-defect-list">
                        <p class="piezas-rechazadas-info">Piezas rechazadas disponibles para clasificar: <span id="piezasRechazadasRestantes" style="font-weight: bold; color: var(--color-error);">0</span></p>
                        <?php if ($defectos_originales_formulario->num_rows > 0): ?>
                            <?php mysqli_data_seek($defectos_originales_formulario, 0); while($defecto = $defectos_originales_formulario->fetch_assoc()): ?>
                                <div class="form-group" data-id-defecto-original="<?php echo $defecto['IdDefecto']; ?>">
                                    <label><?php echo htmlspecialchars($defecto['NombreDefecto']); ?></label>
                                    <div class="defect-entries-container">
                                        <div class="form-row defect-entry-row">
                                            <div class="form-group">
                                                <input type="number" class="defecto-cantidad" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][entries][0][cantidad]" placeholder="Cantidad..." value="0" min="0" required>
                                            </div>
                                            <?php if ($isVariosPartes): ?>
                                                <div class="form-group">
                                                    <input type="text" class="defecto-parte" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][entries][0][parte]" placeholder="No. de Parte..." required>
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-group">
                                                <input type="text" class="defecto-lote" name="defectos_originales[<?php echo $defecto['IdDefecto']; ?>][entries][0][lote]" placeholder="Bach/Lote...">
                                            </div>
                                            <!-- Placeholder for alignment -->
                                            <div style="width: 42px; flex-shrink: 0;"></div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-add-batch btn-secondary btn-small" data-defecto-id="<?php echo $defecto['IdDefecto']; ?>"><i class="fa-solid fa-plus"></i> Añadir Lote</button>
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
                    <!-- CAMPO AUTOMÁTICO -->
                    <div class="form-group"><label>Tiempo de Inspección (Esta Sesión)</label><input type="text" name="tiempoInspeccion" id="tiempoInspeccion" value="1 hora" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>

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
                <!-- FORMULARIO DE FINALIZACIÓN AUTOMÁTICO -->
                <form id="tiempoTotalForm" action="dao/guardar_tiempo_total.php" method="POST" style="margin-top: 40px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset><legend><i class="fa-solid fa-hourglass-end"></i> Finalizar Contención (Tiempo Total)</legend>
                        <p class="info-text">El tiempo total de las sesiones ya registradas es de <strong><?php echo $totalHorasRegistradas; ?> hora(s)</strong>. Al finalizar, este será el valor guardado.</p>
                        <input type="hidden" name="tiempoTotalInspeccion" value="<?php echo $totalHorasRegistradas . ' hora(s)'; ?>">
                    </fieldset>
                    <div class="form-actions"><button type="submit" class="btn-primary">Finalizar Contención y Guardar Tiempo Total</button></div>
                </form>
            <?php else: ?>
                <div class='notification-box info' style='margin-top: 40px;'><i class='fa-solid fa-circle-check'></i> <strong>Contención Finalizada:</strong> El tiempo total de inspección ya fue registrado (<?php echo htmlspecialchars($solicitud['TiempoTotalInspeccion']); ?>).</div>
            <?php endif; ?>

        <?php endif; ?>

        <?php if (!$mostrarFormularioPrincipal): ?>
            <?php if ($solicitud['IdMetodo'] === NULL): ?>
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
                    <div class="form-actions"><button type="button" id="btnSubirMetodo" class="btn-primary">Subir y Notificar</button></div>
                </form>
            <?php else: ?>
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
                    <div class="form-actions"><button type="button" id="btnSubirMetodo" class="btn-primary">Subir y Notificar</button></div>
                </form>
            <?php endif; ?>
        <?php endif; ?>

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
    const cantidadTotalSolicitada = <?php echo intval($cantidadSolicitada); ?>;
    const isVariosPartes = <?php echo json_encode($isVariosPartes); ?>;
    let nuevoDefectoCounter = 0;
    let editandoReporte = false;

    document.addEventListener('DOMContentLoaded', function() {
        const reporteForm = document.getElementById('reporteForm');
        if (reporteForm) {
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

            const horaInicioInput = document.getElementById('horaInicio');
            const horaFinInput = document.getElementById('horaFin');
            const rangoHoraCompletoInput = document.getElementById('rangoHoraCompleto');

            const tiempoInspeccionInput = document.getElementById('tiempoInspeccion');
            const toggleTiempoMuertoBtn = document.getElementById('toggleTiempoMuertoBtn');
            const tiempoMuertoSection = document.getElementById('tiempoMuertoSection');
            const idTiempoMuertoSelect = document.getElementById('idTiempoMuerto');
            const comentariosTextarea = document.getElementById('comentarios');

            // --- LÓGICA PARA EL SELECTOR DE HORA NATIVO ---
            horaInicioInput.addEventListener('change', function() {
                if (this.value) {
                    const [hours, minutes] = this.value.split(':');
                    const startTime = new Date();
                    startTime.setHours(parseInt(hours), parseInt(minutes), 0);
                    startTime.setHours(startTime.getHours() + 1); // Añadir una hora

                    const endHours = String(startTime.getHours()).padStart(2, '0');
                    const endMinutes = String(startTime.getMinutes()).padStart(2, '0');

                    horaFinInput.value = `${endHours}:${endMinutes}`;
                } else {
                    horaFinInput.value = '';
                }
            });

            function actualizarContadores() {
                if (!piezasInspeccionadasInput) return;
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

            function addNuevoDefectoBlock(id = null, idDefectoCatalogo = '', cantidad = '', rutaFoto = '') {
                nuevoDefectoCounter++;
                const currentCounter = nuevoDefectoCounter;

                const parteInputHtml = isVariosPartes ? `
                    <div class="form-group">
                        <label>Número de Parte</label>
                        <input type="text" name="nuevos_defectos[${currentCounter}][parte]" placeholder="No. de Parte..." required>
                    </div>` : '';

                const defectoHTML = `
                <div class="defecto-item" id="nuevo-defecto-${currentCounter}">
                    <div class="defecto-header">
                        <h4>Nuevo Defecto #${currentCounter}</h4>
                        <button type="button" class="btn-remove-defecto" data-defecto-id="${currentCounter}">&times;</button>
                    </div>
                    ${parteInputHtml}
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

            reporteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (horaInicioInput.value && horaFinInput.value) {
                    const formatTo12Hour = (timeStr) => {
                        if (!timeStr) return '';
                        const [hours, minutes] = timeStr.split(':');
                        let h = parseInt(hours);
                        const ampm = h >= 12 ? 'pm' : 'am';
                        h = h % 12;
                        h = h ? h : 12; // la hora '0' debe ser '12'
                        const finalHours = String(h).padStart(2, '0');
                        return `${finalHours}:${minutes} ${ampm}`;
                    };
                    const formattedRange = `${formatTo12Hour(horaInicioInput.value)} - ${formatTo12Hour(horaFinInput.value)}`;
                    rangoHoraCompletoInput.value = formattedRange;
                } else {
                    rangoHoraCompletoInput.value = '';
                }

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

                        if (reporte.RangoHora) {
                            const convertTo24Hour = (timeStr) => {
                                if (!timeStr) return '';
                                let [time, modifier] = timeStr.trim().split(' ');
                                let [hours, minutes] = time.split(':');
                                if (hours === '12') {
                                    hours = '00';
                                }
                                if (modifier && modifier.toUpperCase() === 'PM') {
                                    hours = parseInt(hours, 10) + 12;
                                }
                                return `${String(hours).padStart(2, '0')}:${minutes}`;
                            };
                            const [startStr, endStr] = reporte.RangoHora.split(' - ');
                            if (startStr && endStr) {
                                horaInicioInput.value = convertTo24Hour(startStr);
                                horaFinInput.value = convertTo24Hour(endStr);
                            }
                        } else {
                            horaInicioInput.value = '';
                            horaFinInput.value = '';
                        }

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

            // --- LÓGICA PARA "VARIOS" NÚMEROS DE PARTE ---
            const desgloseContainer = document.getElementById('partes-inspeccionadas-container');
            const addParteBtn = document.getElementById('btn-add-parte-inspeccionada');

            if(isVariosPartes && desgloseContainer && addParteBtn) {
                let parteCounter = 0;

                function addParteRow() {
                    const newIndex = parteCounter++;
                    const newRowHtml = `
                        <div class="form-row parte-inspeccionada-row">
                            <div class="form-group">
                                <input type="text" name="partes_inspeccionadas[${newIndex}][parte]" placeholder="No. de Parte..." required>
                            </div>
                            <div class="form-group">
                                <input type="number" name="partes_inspeccionadas[${newIndex}][cantidad]" class="cantidad-parte-inspeccionada" placeholder="Cantidad..." min="0" required>
                            </div>
                            <button type="button" class="btn-remove-parte btn-danger btn-small"><i class="fa-solid fa-trash-can"></i></button>
                        </div>`;
                    desgloseContainer.insertAdjacentHTML('beforeend', newRowHtml);
                }

                function updateTotalInspeccionadas() {
                    let total = 0;
                    const cantidades = desgloseContainer.querySelectorAll('.cantidad-parte-inspeccionada');
                    cantidades.forEach(input => {
                        total += parseInt(input.value) || 0;
                    });

                    // *** INICIO DE LA NUEVA VALIDACIÓN ***
                    // Validamos que el total no exceda la cantidad solicitada en el reporte
                    if (total > cantidadTotalSolicitada) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'warning',
                            title: 'Cantidad Excedida',
                            text: `El total de piezas (${total}) supera la cantidad solicitada (${cantidadTotalSolicitada}).`,
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true
                        });
                    }
                    // *** FIN DE LA NUEVA VALIDACIÓN ***

                    piezasInspeccionadasInput.value = total;
                    // Disparamos el evento para que se ejecuten las otras validaciones (actualizarContadores)
                    piezasInspeccionadasInput.dispatchEvent(new Event('input'));
                }

                addParteBtn.addEventListener('click', addParteRow);

                desgloseContainer.addEventListener('input', function(e) {
                    if (e.target.classList.contains('cantidad-parte-inspeccionada')) {
                        updateTotalInspeccionadas();
                    }
                });

                desgloseContainer.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.btn-remove-parte');
                    if(removeBtn) {
                        removeBtn.parentElement.remove();
                        updateTotalInspeccionadas();
                    }
                });

                addParteRow();
            }
        }

        const metodoForm = document.getElementById('metodoForm');
        const btnSubirMetodo = document.getElementById('btnSubirMetodo');

        if (metodoForm && btnSubirMetodo) {
            btnSubirMetodo.addEventListener('click', function() {
                const formData = new FormData(metodoForm);
                const tituloMetodoInput = metodoForm.querySelector('[name="tituloMetodo"]');
                const fileInput = document.getElementById('metodoFile');

                if ((tituloMetodoInput && !tituloMetodoInput.value) || !fileInput || fileInput.files.length === 0) {
                    Swal.fire('Campos Incompletos', 'Por favor, completa todos los campos requeridos antes de subir.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Subiendo Método...',
                    text: 'Por favor, espera.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch(metodoForm.action, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            return response.text().then(text => {
                                throw new Error("El servidor no respondió en formato JSON. Respuesta: " + text);
                            });
                        }
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error de Conexión', 'Hubo un problema al procesar la respuesta del servidor. Revisa la consola del navegador para más detalles.', 'error');
                    });
            });
        }

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

        // --- LÓGICA PARA MOSTRAR/OCULTAR VISOR DE PDF ---
        const togglePdfBtn = document.getElementById('togglePdfViewerBtn');
        const pdfWrapper = document.getElementById('pdfViewerWrapper');

        if (togglePdfBtn && pdfWrapper) {
            togglePdfBtn.addEventListener('click', function() {
                const isHidden = pdfWrapper.style.display === 'none';
                if (isHidden) {
                    pdfWrapper.style.display = 'block';
                    this.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Ocultar Método de Trabajo';
                    this.classList.remove('btn-secondary');
                    this.classList.add('btn-primary');
                } else {
                    pdfWrapper.style.display = 'none';
                    this.innerHTML = '<i class="fa-solid fa-eye"></i> Ver Método de Trabajo';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-secondary');
                }
            });
        }

        // --- LÓGICA PARA AÑADIR/QUITAR LOTES DE DEFECTOS ---
        const originalDefectList = document.querySelector('.original-defect-list');

        if (originalDefectList) {
            let batchCounters = {};

            originalDefectList.addEventListener('click', function(e) {
                // Handle adding a batch
                const addBtn = e.target.closest('.btn-add-batch');
                if (addBtn) {
                    const defectoId = addBtn.dataset.defectoId;
                    const container = addBtn.previousElementSibling; // .defect-entries-container

                    if (batchCounters[defectoId] === undefined) {
                        batchCounters[defectoId] = container.querySelectorAll('.form-row').length;
                    }

                    const newIndex = batchCounters[defectoId];
                    batchCounters[defectoId]++;

                    const parteInputHtml = isVariosPartes ? `
                        <div class="form-group">
                            <input type="text" class="defecto-parte" name="defectos_originales[${defectoId}][entries][${newIndex}][parte]" placeholder="No. de Parte..." required>
                        </div>` : '';

                    const newRowHtml = `
                        <div class="form-row defect-entry-row">
                            <div class="form-group">
                                <input type="number" class="defecto-cantidad" name="defectos_originales[${defectoId}][entries][${newIndex}][cantidad]" placeholder="Cantidad..." value="0" min="0" required>
                            </div>
                            ${parteInputHtml}
                            <div class="form-group">
                                <input type="text" class="defecto-lote" name="defectos_originales[${defectoId}][entries][${newIndex}][lote]" placeholder="Bach/Lote...">
                            </div>
                            <button type="button" class="btn-remove-batch btn-danger btn-small"><i class="fa-solid fa-trash-can"></i></button>
                        </div>`;

                    container.insertAdjacentHTML('beforeend', newRowHtml);
                }

                // Handle removing a batch
                const removeBtn = e.target.closest('.btn-remove-batch');
                if (removeBtn) {
                    removeBtn.parentElement.remove(); // Remove the entire .form-row
                    actualizarContadores();
                }
            });
        }
    });
</script>
</body>
</html>

