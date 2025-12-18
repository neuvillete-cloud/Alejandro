<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);
$idUsuarioActual = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Error: No se proporcionó un ID de Cero Defectos.");
}
$idCeroDefectos = intval($_GET['id']);

// Conexión y carga de datos
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// 1. Obtener datos de la solicitud Cero Defectos
$stmt = $conex->prepare("SELECT cd.*, u.Nombre AS NombreCreador, e.NombreEstatus, oem.NombreOEM
                         FROM CeroDefectosSolicitudes cd
                         LEFT JOIN Usuarios u ON cd.IdUsuario = u.IdUsuario
                         LEFT JOIN Estatus e ON cd.IdEstatus = e.IdEstatus
                         LEFT JOIN CeroDefectosOEM oem ON cd.IdOEM = oem.IdOEM
                         WHERE cd.IdCeroDefectos = ?");
$stmt->bind_param("i", $idCeroDefectos);
$stmt->execute();
$ceroDefectosData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ceroDefectosData) { die("Error: Registro de Cero Defectos no encontrado."); }

// Datos cabecera
$nombreResponsable = htmlspecialchars($ceroDefectosData['NombreCreador']);
$linea = htmlspecialchars($ceroDefectosData['Linea']);
$oem = htmlspecialchars($ceroDefectosData['NombreOEM']);
$cliente = htmlspecialchars($ceroDefectosData['Cliente']);
$estatusActual = htmlspecialchars($ceroDefectosData['NombreEstatus']);
$idEstatusActual = intval($ceroDefectosData['IdEstatus']);

// 2. Cargar Catálogo General de Defectos para el dropdown
$catalogo_query = $conex->query("SELECT IdDefectoCatalogo, NombreDefecto FROM CatalogoDefectos ORDER BY NombreDefecto ASC");
$defectos_options = "";
if ($catalogo_query) {
    while($row = $catalogo_query->fetch_assoc()) {
        $defectos_options .= "<option value='{$row['IdDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
    }
}

// 3. Historial de Reportes Anteriores
$reportes_anteriores_query = $conex->prepare("
    SELECT
        r.IdCDReporte, r.FechaInspeccion, r.NombreInspector, 
        r.PiezasProducidas, r.PiezasAceptadas,
        (r.PiezasProducidas - r.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        r.Turno,
        r.Comentarios
    FROM CeroDefectosReportesInspeccion r
    WHERE r.IdCeroDefectos = ? ORDER BY r.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idCeroDefectos);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);
$reportes_anteriores_query->close();

// Procesar reportes para mostrar defectos detallados en el historial
$reportes_procesados = [];
$totalProducidas = 0;
$totalAceptadas = 0;
$totalRechazadas = 0;

foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdCDReporte'];

    // Acumular totales para el footer
    $totalProducidas += (int)$reporte['PiezasProducidas'];
    $totalAceptadas += (int)$reporte['PiezasAceptadas'];
    $totalRechazadas += (int)$reporte['PiezasRechazadasCalculadas'];

    // Obtener defectos de este reporte
    $defectos_query = $conex->prepare("
        SELECT cd.NombreDefecto, rd.Cantidad, rd.Prioridad, rd.EncontradoEn, rd.Severidad
        FROM CeroDefectosReporteDefectos rd
        JOIN CatalogoDefectos cd ON rd.IdDefectoCatalogo = cd.IdDefectoCatalogo
        WHERE rd.IdCDReporte = ?
    ");
    $defectos_query->bind_param("i", $reporte_id);
    $defectos_query->execute();
    $defectos_result = $defectos_query->get_result();

    $lista_defectos_str = "";
    while($d = $defectos_result->fetch_assoc()) {
        $lista_defectos_str .= "<li><strong>" . htmlspecialchars($d['NombreDefecto']) . ":</strong> " . $d['Cantidad'] .
            " <small class='text-muted'>(" . $d['Severidad'] . " - " . $d['EncontradoEn'] . ")</small></li>";
    }
    $defectos_query->close();

    $reporte['DetalleDefectos'] = empty($lista_defectos_str) ? "Sin defectos" : "<ul style='padding-left:15px; margin:0; font-size:0.9em;'>" . $lista_defectos_str . "</ul>";
    $reportes_procesados[] = $reporte;
}

$conex->close();

$mostrarVisorPDF = !empty($ceroDefectosData['RutaInstruccion']);
$mostrarFormularioPrincipal = ($idEstatusActual != 4); // 4 = Cerrado
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Cero Defectos - ARCA</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* =================================================================
           ESTILOS UNIFICADOS (Basados en Safe Launch)
           ================================================================= */
        :root {
            --color-primario: #4a6984;
            --color-secundario: #5c85ad;
            --color-acento: #8ab4d7;
            --color-fondo: #f4f6f9;
            --color-blanco: #ffffff;
            --color-texto: #333333;
            --color-borde: #dbe1e8;
            --color-exito: #28a745;
            --color-error: #a83232;
            --color-peligro-fondo: #fdecea;
            --color-peligro-borde: #f5c2c7;
            --sombra-suave: 0 4px 12px rgba(0,0,0,0.08);
        }

        body { font-family: 'Lato', sans-serif; margin: 0; background-color: var(--color-fondo); color: var(--color-texto); line-height: 1.6; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* HEADER */
        .header { background-color: var(--color-blanco); box-shadow: var(--sombra-suave); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 700; color: var(--color-primario); }
        .user-info { display: flex; align-items: center; gap: 15px; font-weight: 700; }
        .logout-btn { background: none; border: none; color: var(--color-secundario); cursor: pointer; font-size: 16px; }
        .logout-btn:hover { color: var(--color-primario); }

        /* FORMULARIO */
        .form-container { background: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: var(--sombra-suave); }
        .form-container h1 { font-family: 'Montserrat', sans-serif; margin-top: 0; margin-bottom: 30px; font-size: 24px; color: var(--color-texto); }
        fieldset { border: none; padding: 0; margin-bottom: 25px; border-bottom: 1px solid #e0e0e0; padding-bottom: 25px; }
        fieldset:last-of-type { border-bottom: none; }
        legend { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 18px; color: var(--color-primario); margin-bottom: 20px; }
        legend i { margin-right: 10px; color: var(--color-acento); }

        .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; margin-bottom: 15px; min-width: 200px; }
        .form-group label { margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; font-family: 'Lato', sans-serif; box-sizing: border-box; }

        .form-actions { text-align: right; margin-top: 20px; }

        /* INFO ROW */
        .info-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; padding: 15px 0; border-bottom: 1px solid var(--color-borde); font-size: 15px; }
        .info-row p { margin: 0; flex-basis: calc(33% - 10px); color: var(--color-texto); }
        .info-row strong { color: var(--color-primario); margin-right: 5px; }

        /* NOTIFICATIONS & BADGES */
        .notification-box { padding: 15px 20px; margin-bottom: 25px; border-radius: 8px; display: flex; align-items: center; gap: 15px; font-weight: 600; font-size: 15px; border: 1px solid; }
        .notification-box.warning { background-color: #fff3e0; border-color: #ffe0b2; color: #b75c09; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; }
        .status-open { background-color: #e3f2fd; color: #0d47a1; }
        .status-closed { background-color: var(--color-peligro-fondo); color: var(--color-error); }

        /* TABLA DINÁMICA DE DEFECTOS (Formulario) */
        .tabla-defectos-dinamica { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-defectos-dinamica th, .tabla-defectos-dinamica td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; }
        .tabla-defectos-dinamica th { background-color: #f8f9fa; color: var(--color-primario); font-weight: 600; }
        .btn-remove-row { color: var(--color-error); cursor: pointer; border: none; background: none; font-size: 1.2em; transition: color 0.3s; }
        .btn-remove-row:hover { color: #8c2a2a; }
        .input-compact { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }

        /* TABLA DE HISTORIAL (Estilo idéntico a Safe Launch) */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 20px; margin-bottom: 40px; border: 1px solid var(--color-borde); border-radius: 8px; box-shadow: var(--sombra-suave); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; white-space: nowrap; }
        .data-table th { background-color: var(--color-primario); color: var(--color-blanco); font-weight: 600; text-transform: uppercase; position: sticky; top: 0; z-index: 1; }
        .data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tbody tr:hover { background-color: #f0f4f8; }

        .data-table .total-row td {
            font-weight: bold;
            background-color: #e9ecef;
            border-top: 2px solid var(--color-primario);
        }

        /* BOTONES */
        .btn-primary { background-color: var(--color-secundario); color: white; padding: 12px 25px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .btn-primary:hover { background-color: var(--color-primario); }
        .btn-secondary { background-color: #e0e0e0; color: #333; padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; transition: background 0.3s; }
        .btn-secondary:hover { background-color: #bdbdbd; }
        .btn-danger { background-color: var(--color-error); color: white; padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; }
        .btn-danger:hover { background-color: #8c2a2a; }
        .btn-small { padding: 6px 12px; font-size: 14px; border-radius: 4px; margin: 0 2px; }

        .pdf-viewer-container iframe { width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 4px; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: center; }
            .form-container { padding: 20px 25px; }
            .form-row { flex-direction: column; gap: 0; }
            .info-row p { flex-basis: 100%; }
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
        <h1><i class="fa-solid fa-check-double"></i> Reporte Cero Defectos - Folio ZD-<?php echo str_pad($idCeroDefectos, 4, '0', STR_PAD_LEFT); ?></h1>

        <div class="info-row">
            <p><strong>Línea:</strong> <?php echo $linea; ?></p>
            <p><strong>OEM:</strong> <?php echo $oem; ?></p>
            <p><strong>Cliente:</strong> <?php echo $cliente; ?></p>
            <p><strong>Responsable:</strong> <?php echo $nombreResponsable; ?></p>
            <p><strong>Estatus:</strong> <span class="status-badge <?php echo ($idEstatusActual == 4) ? 'status-closed' : 'status-open'; ?>"><?php echo $estatusActual; ?></span></p>
        </div>

        <?php if ($mostrarVisorPDF): ?>
            <fieldset>
                <legend><i class="fa-solid fa-file-pdf"></i> Instrucción de Trabajo</legend>
                <button type="button" class="btn-secondary" onclick="document.getElementById('pdfViewer').style.display = document.getElementById('pdfViewer').style.display === 'none' ? 'block' : 'none';">
                    <i class="fa-solid fa-eye"></i> Mostrar/Ocultar PDF
                </button>
                <div id="pdfViewer" style="display:none; margin-top:15px;" class="pdf-viewer-container">
                    <iframe src="<?php echo htmlspecialchars($ceroDefectosData['RutaInstruccion']); ?>"></iframe>
                </div>
            </fieldset>
        <?php endif; ?>

        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteFormZD" action="dao/guardar_reporte_cero_defectos.php" method="POST">
                <input type="hidden" name="idCeroDefectos" value="<?php echo $idCeroDefectos; ?>">
                <!-- Campo oculto para ID de reporte en caso de edición -->
                <input type="hidden" name="idCDReporte" id="idCDReporte" value="">

                <!-- SECCIÓN 1: Resumen de Producción -->
                <fieldset>
                    <legend><i class="fa-solid fa-industry"></i> Resumen de Producción</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Piezas Producidas</label>
                            <input type="number" name="piezasProducidas" id="piezasProducidas" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Piezas Aceptadas</label>
                            <input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Piezas Rechazadas (Cálculo)</label>
                            <input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; color: var(--color-error); font-weight: bold;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Inspector</label>
                            <input type="text" name="nombreInspector" id="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Inspección</label>
                            <input type="date" name="fechaInspeccion" id="fechaInspeccion" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Turno</label>
                            <select name="turno" id="turno" required>
                                <option value="" disabled selected>Seleccione Turno...</option>
                                <option value="1">Turno 1</option>
                                <option value="2">Turno 2</option>
                                <option value="3">Turno 3</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- SECCIÓN 2: Registro de Defectos (NUEVO DISEÑO) -->
                <fieldset>
                    <legend><i class="fa-solid fa-bug"></i> Registro de Defectos</legend>

                    <table class="tabla-defectos-dinamica">
                        <thead>
                        <tr>
                            <th style="width: 25%;">Defecto</th>
                            <th style="width: 15%;">Prioridad</th>
                            <th style="width: 20%;">Encontrado En</th>
                            <th style="width: 15%;">Severidad</th>
                            <th style="width: 10%;">Cantidad</th>
                            <th style="width: 5%;"></th>
                        </tr>
                        </thead>
                        <tbody id="tablaDefectosBody">
                        <!-- Las filas se añadirán aquí dinámicamente -->
                        </tbody>
                    </table>

                    <div style="margin-top: 15px;">
                        <button type="button" id="btnAddDefecto" class="btn-secondary">
                            <i class="fa-solid fa-plus"></i> Agregar Defecto
                        </button>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><i class="fa-solid fa-comment"></i> Comentarios</legend>
                    <div class="form-group">
                        <textarea name="comentarios" id="comentarios" rows="3" placeholder="Observaciones generales del turno..."></textarea>
                    </div>
                </fieldset>

                <div class="form-actions" id="formActionsContainer">
                    <button type="submit" class="btn-primary" id="btnGuardarReporte">Guardar Reporte</button>
                    <!-- El botón de cancelar edición se insertará aquí dinámicamente -->
                </div>
            </form>
        <?php else: ?>
            <div class="notification-box warning">
                <i class="fa-solid fa-lock"></i> Este registro está cerrado. No se pueden agregar nuevos reportes.
            </div>
        <?php endif; ?>

        <hr style="margin: 40px 0; border-color: var(--color-borde);">

        <!-- Historial -->
        <h2><i class="fa-solid fa-clock-rotate-left"></i> Historial de Reportes</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Turno</th>
                    <th>Inspector</th>
                    <th>Producidas</th>
                    <th>Aceptadas</th>
                    <th>Rechazadas</th>
                    <th>Detalle de Defectos</th>
                    <th>Comentarios</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($reportes_procesados) > 0): ?>
                    <?php foreach ($reportes_procesados as $rep): ?>
                        <tr>
                            <td><?php echo date("d/m/Y", strtotime($rep['FechaInspeccion'])); ?></td>
                            <td><?php echo htmlspecialchars($rep['Turno']); ?></td>
                            <td><?php echo htmlspecialchars($rep['NombreInspector']); ?></td>
                            <td><?php echo $rep['PiezasProducidas']; ?></td>
                            <td><?php echo $rep['PiezasAceptadas']; ?></td>
                            <td style="color: var(--color-error); font-weight: bold;"><?php echo $rep['PiezasRechazadasCalculadas']; ?></td>
                            <td style="white-space: normal;"><?php echo $rep['DetalleDefectos']; ?></td>
                            <td style="white-space: normal;"><?php echo htmlspecialchars($rep['Comentarios']); ?></td>
                            <td style="text-align: center;">
                                <?php if ($mostrarFormularioPrincipal): ?>
                                    <button class="btn-edit-reporte-cd btn-primary btn-small" data-id="<?php echo $rep['IdCDReporte']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="btn-delete-reporte-cd btn-danger btn-small" data-id="<?php echo $rep['IdCDReporte']; ?>"><i class="fa-solid fa-trash-can"></i></button>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;">No hay reportes registrados aún.</td></tr>
                <?php endif; ?>
                </tbody>
                <!-- NUEVO: Footer con totales -->
                <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">TOTALES:</td>
                    <td><?php echo $totalProducidas; ?></td>
                    <td><?php echo $totalAceptadas; ?></td>
                    <td><?php echo $totalRechazadas; ?></td>
                    <td colspan="3"></td>
                </tr>
                </tfoot>
            </table>
        </div>

    </div>
</main>

<script>
    // Variable global con las opciones de defectos para usarlas en JS
    const opcionesDefectosHTML = `<?php echo addslashes($defectos_options); ?>`;
    let contadorDefectos = 0;
    let editandoReporte = false;

    document.addEventListener('DOMContentLoaded', function() {

        // --- Lógica: Cálculos de Piezas ---
        const inputProducidas = document.getElementById('piezasProducidas');
        const inputAceptadas = document.getElementById('piezasAceptadas');
        const inputRechazadas = document.getElementById('piezasRechazadasCalculadas');

        function calcularRechazadas() {
            if(!inputProducidas || !inputAceptadas) return;
            const prod = parseInt(inputProducidas.value) || 0;
            const acep = parseInt(inputAceptadas.value) || 0;

            if (acep > prod) {
                inputAceptadas.setCustomValidity("No pueden haber más aceptadas que producidas");
            } else {
                inputAceptadas.setCustomValidity("");
            }

            const rech = Math.max(0, prod - acep);
            inputRechazadas.value = rech;
        }

        if(inputProducidas) {
            inputProducidas.addEventListener('input', calcularRechazadas);
            inputAceptadas.addEventListener('input', calcularRechazadas);
        }

        // --- Lógica: Tabla Dinámica de Defectos ---
        const btnAddDefecto = document.getElementById('btnAddDefecto');
        const tablaBody = document.getElementById('tablaDefectosBody');

        // Función auxiliar para agregar una fila
        window.agregarFilaDefecto = function(datos = null) {
            contadorDefectos++;
            const tr = document.createElement('tr');

            // Valores por defecto o cargados
            const idDefecto = datos ? datos.idDefecto : "";
            const prioridad = datos ? datos.prioridad : "Media";
            const encontrado = datos ? datos.encontrado : "Estación Media";
            const severidad = datos ? datos.severidad : "Mayor";
            const cantidad = datos ? datos.cantidad : 1;

            tr.innerHTML = `
                <td>
                    <select name="defectos[${contadorDefectos}][id]" class="input-compact" required>
                        <option value="" disabled ${!idDefecto ? 'selected' : ''}>Seleccionar...</option>
                        ${opcionesDefectosHTML}
                    </select>
                </td>
                <td>
                    <select name="defectos[${contadorDefectos}][prioridad]" class="input-compact" required>
                        <option value="Alta" ${prioridad === 'Alta' ? 'selected' : ''}>Alta</option>
                        <option value="Media" ${prioridad === 'Media' ? 'selected' : ''}>Media</option>
                        <option value="Baja" ${prioridad === 'Baja' ? 'selected' : ''}>Baja</option>
                    </select>
                </td>
                <td>
                    <select name="defectos[${contadorDefectos}][encontrado]" class="input-compact" required>
                        <option value="Inicio de Línea" ${encontrado === 'Inicio de Línea' ? 'selected' : ''}>Inicio de Línea</option>
                        <option value="Estación Media" ${encontrado === 'Estación Media' ? 'selected' : ''}>Estación Media</option>
                        <option value="Final de Línea" ${encontrado === 'Final de Línea' ? 'selected' : ''}>Final de Línea</option>
                        <option value="Quality Wall" ${encontrado === 'Quality Wall' ? 'selected' : ''}>Quality Wall</option>
                        <option value="Auditoría" ${encontrado === 'Auditoría' ? 'selected' : ''}>Auditoría</option>
                    </select>
                </td>
                <td>
                    <select name="defectos[${contadorDefectos}][severidad]" class="input-compact" required>
                        <option value="Crítica" ${severidad === 'Crítica' ? 'selected' : ''}>Crítica</option>
                        <option value="Mayor" ${severidad === 'Mayor' ? 'selected' : ''}>Mayor</option>
                        <option value="Menor" ${severidad === 'Menor' ? 'selected' : ''}>Menor</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="defectos[${contadorDefectos}][cantidad]" class="input-compact" min="1" value="${cantidad}" required>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;

            // Establecer el valor del select de defectos manualmente si viene data
            if(idDefecto) {
                const selectDefecto = tr.querySelector(`select[name="defectos[${contadorDefectos}][id]"]`);
                if(selectDefecto) selectDefecto.value = idDefecto;
            }

            tablaBody.appendChild(tr);
        };

        if (btnAddDefecto) {
            btnAddDefecto.addEventListener('click', () => agregarFilaDefecto());
        }

        // --- Envío del Formulario ---
        const form = document.getElementById('reporteFormZD');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const actionUrl = editandoReporte ? 'dao/actualizar_reporte_cd.php' : 'dao/guardar_reporte_cero_defectos.php';

                Swal.fire({
                    title: 'Guardando...',
                    text: 'Procesando reporte...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire('¡Guardado!', data.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                    });
            });
        }

        // --- LÓGICA DE EDICIÓN (ADAPTADA) ---
        document.querySelectorAll('.btn-edit-reporte-cd').forEach(btn => {
            btn.addEventListener('click', function() {
                const idReporte = this.dataset.id;
                cargarReporteParaEdicionCD(idReporte);
            });
        });

        async function cargarReporteParaEdicionCD(idReporte) {
            Swal.fire({ title: 'Cargando...', text: 'Obteniendo datos...', didOpen: () => Swal.showLoading() });

            try {
                // Asumimos que existe este endpoint que devuelve { status:'success', reporte: {...}, defectos: [...] }
                const response = await fetch(`dao/obtener_reporte_cd_para_edicion.php?idCDReporte=${idReporte}`);
                const data = await response.json();

                if (data.status === 'success') {
                    const r = data.reporte;
                    const defectos = data.defectos || [];

                    // 1. Llenar campos simples
                    document.getElementById('idCDReporte').value = r.IdCDReporte;
                    document.getElementById('piezasProducidas').value = r.PiezasProducidas;
                    document.getElementById('piezasAceptadas').value = r.PiezasAceptadas;
                    document.getElementById('nombreInspector').value = r.NombreInspector;
                    document.getElementById('fechaInspeccion').value = r.FechaInspeccion;
                    document.getElementById('turno').value = r.Turno;
                    document.getElementById('comentarios').value = r.Comentarios;

                    calcularRechazadas(); // Actualizar cálculo visual

                    // 2. Llenar tabla dinámica de defectos
                    tablaBody.innerHTML = ''; // Limpiar tabla
                    contadorDefectos = 0; // Reiniciar contador

                    if (defectos.length > 0) {
                        defectos.forEach(d => {
                            agregarFilaDefecto({
                                idDefecto: d.IdDefectoCatalogo,
                                prioridad: d.Prioridad,
                                encontrado: d.EncontradoEn,
                                severidad: d.Severidad,
                                cantidad: d.Cantidad
                            });
                        });
                    }

                    // 3. Cambiar estado de la UI a "Edición"
                    editandoReporte = true;
                    const btnGuardar = document.getElementById('btnGuardarReporte');
                    btnGuardar.innerText = 'Actualizar Reporte';

                    // Agregar botón Cancelar si no existe
                    let btnCancel = document.getElementById('btnCancelarEdicion');
                    if (!btnCancel) {
                        btnCancel = document.createElement('button');
                        btnCancel.type = 'button';
                        btnCancel.id = 'btnCancelarEdicion';
                        btnCancel.className = 'btn-secondary';
                        btnCancel.innerText = 'Cancelar Edición';
                        btnCancel.style.marginLeft = '10px';
                        btnCancel.onclick = () => window.location.reload();
                        document.getElementById('formActionsContainer').appendChild(btnCancel);
                    }

                    Swal.close();
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'No se pudieron cargar los datos para edición.', 'error');
            }
        }

        // --- LÓGICA DE ELIMINACIÓN ---
        document.querySelectorAll('.btn-delete-reporte-cd').forEach(btn => {
            btn.addEventListener('click', function() {
                const idReporte = this.dataset.id;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "No podrás revertir esta acción",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('dao/eliminar_reporte_cd.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `idCDReporte=${idReporte}`
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('Eliminado', 'El reporte ha sido eliminado.', 'success')
                                        .then(() => window.location.reload());
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            })
                            .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
                    }
                });
            });
        });

    });
</script>

</body>
</html>