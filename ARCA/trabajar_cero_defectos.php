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
// Usamos LEFT JOIN con CeroDefectosOEM para traer el nombre del fabricante
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
$linea = htmlspecialchars($ceroDefectosData['Linea']); // Antes Proyecto
$oem = htmlspecialchars($ceroDefectosData['NombreOEM']); // Nuevo campo
$cliente = htmlspecialchars($ceroDefectosData['Cliente']);
$estatusActual = htmlspecialchars($ceroDefectosData['NombreEstatus']);
$idEstatusActual = intval($ceroDefectosData['IdEstatus']);

// 2. Cargar Catálogo General de Defectos para el dropdown
// Usamos la tabla general `CatalogoDefectos`
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
foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdCDReporte'];

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
    <!-- Reutilizamos estilos, agregando específicos para Cero Defectos -->
    <link rel="stylesheet" href="css/estilosSolicitud.css">
    <style>
        /* Estilos específicos para la tabla de defectos dinámica */
        .tabla-defectos-dinamica { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-defectos-dinamica th, .tabla-defectos-dinamica td { border: 1px solid #e0e0e0; padding: 10px; text-align: left; }
        .tabla-defectos-dinamica th { background-color: #f8f9fa; color: #4a6984; font-weight: 600; }
        .btn-remove-row { color: #dc3545; cursor: pointer; border: none; background: none; font-size: 1.2em; }

        /* Ajuste layout general */
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .form-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .info-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .info-row p { margin: 0; min-width: 200px; }
        .info-row strong { color: #4a6984; }

        .status-badge { padding: 5px 10px; border-radius: 12px; font-size: 0.85em; font-weight: bold; }
        .status-open { background-color: #e3f2fd; color: #0d47a1; }
        .status-closed { background-color: #fdecea; color: #a83232; }

        .pdf-viewer-container iframe { width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 4px; }

        /* Inputs compactos para la tabla */
        .input-compact { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
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

                <!-- SECCIÓN 1: Resumen de Producción -->
                <fieldset>
                    <legend><i class="fa-solid fa-industry"></i> Resumen de Producción</legend>
                    <div class="form-row">
                        <!-- CAMBIO: Piezas Producidas -->
                        <div class="form-group">
                            <label>Piezas Producidas</label>
                            <input type="number" name="piezasProducidas" id="piezasProducidas" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Piezas Aceptadas</label>
                            <input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required>
                        </div>
                        <!-- CAMBIO: Retrabajadas ELIMINADO -->
                        <div class="form-group">
                            <label>Piezas Rechazadas (Cálculo)</label>
                            <input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; color: #a83232; font-weight: bold;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Inspector</label>
                            <input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Inspección</label>
                            <input type="date" name="fechaInspeccion" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <!-- CAMBIO: Turno en lugar de Horas -->
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
                        <textarea name="comentarios" rows="3" placeholder="Observaciones generales del turno..."></textarea>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Guardar Reporte</button>
                </div>
            </form>
        <?php else: ?>
            <div class="notification-box warning">
                <i class="fa-solid fa-lock"></i> Este registro está cerrado. No se pueden agregar nuevos reportes.
            </div>
        <?php endif; ?>

        <hr style="margin: 40px 0;">

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
                            <td style="color: #a83232; font-weight: bold;"><?php echo $rep['PiezasRechazadasCalculadas']; ?></td>
                            <td><?php echo $rep['DetalleDefectos']; ?></td>
                            <td><?php echo htmlspecialchars($rep['Comentarios']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">No hay reportes registrados aún.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>

<script>
    // Variable global con las opciones de defectos para usarlas en JS
    const opcionesDefectosHTML = `<?php echo addslashes($defectos_options); ?>`;

    document.addEventListener('DOMContentLoaded', function() {

        // --- Lógica: Cálculos de Piezas ---
        const inputProducidas = document.getElementById('piezasProducidas');
        const inputAceptadas = document.getElementById('piezasAceptadas');
        const inputRechazadas = document.getElementById('piezasRechazadasCalculadas');

        function calcularRechazadas() {
            if(!inputProducidas || !inputAceptadas) return;
            const prod = parseInt(inputProducidas.value) || 0;
            const acep = parseInt(inputAceptadas.value) || 0;

            // Validación básica
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
        let contadorDefectos = 0;

        if (btnAddDefecto) {
            btnAddDefecto.addEventListener('click', function() {
                contadorDefectos++;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="defectos[${contadorDefectos}][id]" class="input-compact" required>
                            <option value="" disabled selected>Seleccionar...</option>
                            ${opcionesDefectosHTML}
                        </select>
                    </td>
                    <td>
                        <select name="defectos[${contadorDefectos}][prioridad]" class="input-compact" required>
                            <option value="Alta">Alta</option>
                            <option value="Media">Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </td>
                    <td>
                        <select name="defectos[${contadorDefectos}][encontrado]" class="input-compact" required>
                            <option value="Inicio de Línea">Inicio de Línea</option>
                            <option value="Estación Media">Estación Media</option>
                            <option value="Final de Línea">Final de Línea</option>
                            <option value="Quality Wall">Quality Wall</option>
                            <option value="Auditoría">Auditoría</option>
                        </select>
                    </td>
                    <td>
                        <select name="defectos[${contadorDefectos}][severidad]" class="input-compact" required>
                            <option value="Crítica">Crítica</option>
                            <option value="Mayor">Mayor</option>
                            <option value="Menor">Menor</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="defectos[${contadorDefectos}][cantidad]" class="input-compact" min="1" value="1" required>
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button>
                    </td>
                `;
                tablaBody.appendChild(tr);
            });
        }

        // --- Envío del Formulario ---
        const form = document.getElementById('reporteFormZD');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Validar que la suma de defectos coincida con las rechazadas (Opcional, pero recomendado)
                // Aquí solo validamos que no exceda, o dejamos libre según tu regla anterior.
                // En Cero Defectos, a veces se documentan defectos que fueron retrabajados y pasaron a aceptados,
                // pero por la lógica "Producidas - Aceptadas = Rechazadas", asumiremos que los defectos aquí suman al rechazo.

                const formData = new FormData(this);

                Swal.fire({
                    title: 'Guardando...',
                    text: 'Registrando reporte de Cero Defectos',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(this.action, {
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
    });
</script>

</body>
</html>
