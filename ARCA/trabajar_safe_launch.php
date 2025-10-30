<?php
// Incluye el script que verifica si hay una sesión activa
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }

$esSuperUsuario = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1);
$idUsuarioActual = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Error: No se proporcionó un ID de Safe Launch.");
}
$idSafeLaunch = intval($_GET['id']);

// Conexión y carga de datos para la página
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Obtenemos los datos de la solicitud de Safe Launch
$stmt = $conex->prepare("SELECT sl.*, u.Nombre AS NombreCreador
                         FROM SafeLaunchSolicitudes sl
                         LEFT JOIN Usuarios u ON sl.IdUsuario = u.IdUsuario
                         WHERE sl.IdSafeLaunch = ?");
$stmt->bind_param("i", $idSafeLaunch);
$stmt->execute();
$safeLaunchData = $stmt->get_result()->fetch_assoc();
$stmt->close(); // Cerrar el statement aquí

if (!$safeLaunchData) { die("Error: Safe Launch no encontrado."); }

// --- Datos para la cabecera del formulario ---
$nombreResponsable = htmlspecialchars($safeLaunchData['NombreCreador']);
$nombreProyecto = htmlspecialchars($safeLaunchData['NombreProyecto']);
$cliente = htmlspecialchars($safeLaunchData['Cliente']);


// --- INICIO CORRECCIÓN ---

// --- 1. Catálogo de Defectos ASOCIADOS (para la CUADRÍCULA y la TABLA) ---
// Esta consulta SÍ debe ser filtrada por el IdSafeLaunch
$stmt_defectos_asociados = $conex->prepare("
    SELECT slcd.IdSLDefectoCatalogo, slcd.NombreDefecto
    FROM SafeLaunchDefectos sld
    JOIN SafeLaunchCatalogoDefectos slcd ON sld.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
    WHERE sld.IdSafeLaunch = ?
    ORDER BY slcd.NombreDefecto ASC
");
$stmt_defectos_asociados->bind_param("i", $idSafeLaunch);
$stmt_defectos_asociados->execute();
$defectos_asociados_result = $stmt_defectos_asociados->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_defectos_asociados->close();

$defectos_originales_para_js = []; // Mapa para nombres de defectos originales
$defectos_para_formulario_y_tabla = []; // Array para iterar en HTML
foreach($defectos_asociados_result as $row) {
    $defectos_originales_para_js[$row['IdSLDefectoCatalogo']] = htmlspecialchars($row['NombreDefecto']);
    $defectos_para_formulario_y_tabla[] = [
        'id' => $row['IdSLDefectoCatalogo'],
        'nombre' => htmlspecialchars($row['NombreDefecto'])
    ];
}

// --- 2. Catálogo COMPLETO de Defectos (para el dropdown de "Nuevos Defectos") ---
// Esta consulta debe traer TODOS los defectos del catálogo
$catalogo_completo_query = $conex->query("SELECT IdSLDefectoCatalogo, NombreDefecto FROM SafeLaunchCatalogoDefectos ORDER BY NombreDefecto ASC");
$catalogo_completo_result = $catalogo_completo_query->fetch_all(MYSQLI_ASSOC);

$defectos_options_html_sl = "";
foreach($catalogo_completo_result as $row) {
    $defectos_options_html_sl .= "<option value='{$row['IdSLDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
}
// --- FIN CORRECCIÓN ---


// --- Historial de Reportes Anteriores ---
function parsearTiempoAMinutos($tiempoStr) {
    if (empty($tiempoStr)) return 0;
    $totalMinutos = 0;
    if (preg_match('/(\d+)\s*hora(s)?/', $tiempoStr, $matches)) {
        $totalMinutos += intval($matches[1]) * 60;
    }
    if (preg_match('/(\d+)\s*minuto(s)?/', $tiempoStr, $matches)) {
        $totalMinutos += intval($matches[1]);
    }
    if ($totalMinutos === 0 && preg_match('/(\d+)/', $tiempoStr, $matches)) { // Fallback simple si solo hay números
        if (str_contains(strtolower($tiempoStr), 'hora')) {
            $totalMinutos = intval($matches[1]) * 60;
        } else {
            $totalMinutos = intval($matches[1]);
        }
    }
    return $totalMinutos;
}

function formatarMinutosATiempo($totalMinutos) {
    if ($totalMinutos <= 0) return "0 minutos";
    $horas = floor($totalMinutos / 60);
    $minutos = $totalMinutos % 60;
    $partes = [];
    if ($horas > 0) {
        $partes[] = $horas . " hora(s)";
    }
    if ($minutos > 0) {
        $partes[] = $minutos . " minuto(s)";
    }
    return empty($partes) ? "0 minutos" : implode(" ", $partes);
}

$reportes_anteriores_query = $conex->prepare("
    SELECT
        r.IdSLReporte, r.FechaInspeccion, r.NombreInspector, r.PiezasInspeccionadas, r.PiezasAceptadas,
        (r.PiezasInspeccionadas - r.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        r.PiezasRetrabajadas,
        r.RangoHora,
        r.Comentarios,
        r.TiempoInspeccion
    FROM SafeLaunchReportesInspeccion r
    WHERE r.IdSafeLaunch = ? ORDER BY r.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idSafeLaunch);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);
$reportes_anteriores_query->close();

$reportes_procesados = [];
$totalMinutosRegistrados = 0;
$totalPiezasInspeccionadasYa = 0;
$totalDefectosEncontradosGlobal = 0;
$totalDefectosPorTipoGlobal = array_fill_keys(array_column($defectos_para_formulario_y_tabla, 'id'), 0);

foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdSLReporte'];
    $totalMinutosRegistrados += parsearTiempoAMinutos($reporte['TiempoInspeccion']);
    $totalPiezasInspeccionadasYa += (int)$reporte['PiezasInspeccionadas'];
    $totalDefectosReporteActual = 0;
    $reporte['DefectosPorTipo'] = array_fill_keys(array_column($defectos_para_formulario_y_tabla, 'id'), 0);

    // --- LÓGICA DE PROCESAMIENTO DE DEFECTOS MEJORADA ---

    // 1. Obtener defectos de la cuadrícula (Asociados)
    $defectos_reporte_query = $conex->prepare("
        SELECT rd.IdSLDefectoCatalogo, rd.CantidadEncontrada
        FROM SafeLaunchReporteDefectos rd
        WHERE rd.IdSLReporte = ?
    ");
    $defectos_reporte_query->bind_param("i", $reporte_id);
    $defectos_reporte_query->execute();
    $defectos_reporte_result = $defectos_reporte_query->get_result();
    while ($dr = $defectos_reporte_result->fetch_assoc()) {
        $idDefecto = $dr['IdSLDefectoCatalogo'];
        $cantidad = (int)$dr['CantidadEncontrada'];

        if (array_key_exists($idDefecto, $reporte['DefectosPorTipo'])) {
            $reporte['DefectosPorTipo'][$idDefecto] = $cantidad;
            $totalDefectosReporteActual += $cantidad;
            if (array_key_exists($idDefecto, $totalDefectosPorTipoGlobal)) {
                $totalDefectosPorTipoGlobal[$idDefecto] += $cantidad;
            }
        }
    }
    $defectos_reporte_query->close();

    // 2. Obtener defectos opcionales (Nuevos) CON NOMBRE
    $nuevos_defectos_query = $conex->prepare("
        SELECT slcd.NombreDefecto, snd.Cantidad 
        FROM SafeLaunchNuevosDefectos snd
        JOIN SafeLaunchCatalogoDefectos slcd ON snd.IdSLDefectoCatalogo = slcd.IdSLDefectoCatalogo
        WHERE snd.IdSLReporte = ?
    ");
    $nuevos_defectos_query->bind_param("i", $reporte_id);
    $nuevos_defectos_query->execute();
    $nuevos_defectos_result = $nuevos_defectos_query->get_result();

    $nuevos_defectos_para_mostrar = [];
    $totalNuevosDefectos = 0;
    while ($nd = $nuevos_defectos_result->fetch_assoc()) {
        $cantidad_nd = (int)$nd['Cantidad'];
        $nuevos_defectos_para_mostrar[] = htmlspecialchars($nd['NombreDefecto']) . " (" . $cantidad_nd . ")";
        $totalNuevosDefectos += $cantidad_nd;
    }
    $nuevos_defectos_query->close();

    // 3. Almacenar totales y strings para mostrar
    $reporte['NuevosDefectosParaMostrar'] = empty($nuevos_defectos_para_mostrar) ? 'N/A' : implode("<br>", $nuevos_defectos_para_mostrar);
    $reporte['TotalDefectosReporte'] = $totalDefectosReporteActual + $totalNuevosDefectos; // Total combinado
    $totalDefectosEncontradosGlobal += $reporte['TotalDefectosReporte']; // Sumar al gran total global

    // --- FIN LÓGICA MEJORADA ---


    // Calcular Turno/Shift Leader
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
$tiempoTotalFormateado = formatarMinutosATiempo($totalMinutosRegistrados);

// Calcular PPM y % Defectuoso Global (usando el $totalDefectosEncontradosGlobal actualizado)
$ppm_global = ($totalPiezasInspeccionadasYa > 0) ? round(($totalDefectosEncontradosGlobal / $totalPiezasInspeccionadasYa) * 1000000) : 0;
$porcentaje_defectuoso_global = ($totalPiezasInspeccionadasYa > 0) ? round(($totalDefectosEncontradosGlobal / $totalPiezasInspeccionadasYa) * 100, 2) : 0;


$conex->close(); // Cerrar conexión principal

$mostrarVisorPDF = !empty($safeLaunchData['RutaInstruccion']);

// Determinar si el formulario principal debe mostrarse (siempre en Safe Launch, a menos que se implemente un cierre futuro)
$mostrarFormularioPrincipal = true; // Por defecto, siempre se puede reportar en Safe Launch
// Puedes añadir lógica aquí si implementas un estatus 'Cerrado' para SafeLaunchSolicitudes
// if ($safeLaunchData['Estatus'] === 'Cerrado') { $mostrarFormularioPrincipal = false; }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Safe Launch - ARCA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Usamos el mismo CSS que trabajar_solicitud -->
    <style>
        /* =================================================================
           HOJA DE ESTILOS PRINCIPAL PARA EL PROYECTO ARCA
           ================================================================= */

        /* --- 1. Variables Globales y Estilos Base --- */
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
            --sombra-suave: 0 4px 12px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- 2. Estilos del Layout Principal (Header) --- */
        .header {
            background-color: var(--color-blanco);
            box-shadow: var(--sombra-suave);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primario);
        }

        .logo i { margin-right: 10px; }

        .user-info {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 15px;
        }

        .user-info span {
            margin-right: 0;
            font-weight: 700;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--color-secundario);
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover { color: var(--color-primario); }
        .logout-btn i { margin-left: 8px; }

        /* --- 3. Estilos para el Formulario --- */
        .form-container { background-color: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: var(--sombra-suave); }
        .form-container h1 { font-family: 'Montserrat', sans-serif; margin-top: 0; margin-bottom: 30px; font-size: 24px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        fieldset { border: none; padding: 0; margin-bottom: 25px; border-bottom: 1px solid #e0e0e0; padding-bottom: 25px; }
        fieldset:last-of-type { border-bottom: none; }
        legend { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 18px; color: var(--color-primario, #4a6984); margin-bottom: 20px; }
        legend i { margin-right: 10px; color: var(--color-acento); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; margin-bottom: 15px; min-width: 200px; }
        .form-group label { margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; font-family: 'Lato', sans-serif; box-sizing: border-box; }
        .hidden-section { display: none; margin-top: 15px; }
        .select-with-button { display: flex; align-items: flex-end; gap: 10px; }
        .select-with-button select { flex-grow: 1; }
        .form-actions { text-align: right; margin-top: 20px; }
        .form-row .form-group.w-50 { flex-basis: calc(50% - 10px); }
        .form-row .form-group.w-25 { flex-basis: calc(25% - 15px); }

        /* --- 4. Componentes y Secciones Específicas --- */
        .info-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; padding: 15px 0; border-bottom: 1px solid var(--color-borde); font-size: 15px; }
        .info-row p { margin: 0; flex-basis: calc(50% - 10px); color: var(--color-texto); }
        .info-row p strong { color: var(--color-primario); margin-right: 5px; }
        .notification-box { padding: 15px 20px; margin-bottom: 25px; border-radius: 8px; display: flex; align-items: center; gap: 15px; font-weight: 600; font-size: 15px; border: 1px solid; }
        .notification-box i { font-size: 20px; }
        .notification-box.warning { background-color: #fff3e0; border-color: #ffe0b2; color: #b75c09; }
        .notification-box.error { background-color: #fdecea; border-color: #f5c2c7; color: var(--color-error); }
        .notification-box.info { background-color: #e3f2fd; border-color: #bbdefb; color: #0d47a1; }
        .info-text { font-size: 14px; color: #666; margin-top: -10px; margin-bottom: 20px; font-style: italic; }

        /* --- Ajustes para Clasificación de Defectos Safe Launch --- */
        .defect-classification-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        .defect-classification-item {
            border: 1px solid var(--color-borde);
            border-radius: 6px;
            padding: 15px;
            background-color: #fdfdfd;
        }
        .defect-classification-item label {
            font-weight: 700;
            color: var(--color-primario);
            display: block; /* Asegura que la etiqueta ocupe su propia línea */
            margin-bottom: 10px;
        }
        .defect-classification-item .form-row {
            gap: 10px; /* Menos espacio entre cantidad y lote */
            align-items: flex-end; /* Alinea los inputs en la parte inferior */
        }
        .defect-classification-item .form-group {
            margin-bottom: 0; /* Quita margen inferior extra */
        }


        .piezas-rechazadas-info { font-size: 15px; margin-bottom: 20px; padding: 10px 15px; background-color: #eaf2f8; border-left: 5px solid var(--color-secundario); border-radius: 4px; }


        .pdf-viewer-container {
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            overflow: hidden; /* Oculta el desbordamiento en PC */
            margin-top: 15px;
            height: 75vh;
        }
        #pdfViewerWrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .form-row.defect-entry-row, .form-row.parte-inspeccionada-row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 10px; }
        .form-row.defect-entry-row .form-group, .form-row.parte-inspeccionada-row .form-group { flex: 1 1 0; min-width: 0; margin-bottom: 0; }
        #partes-inspeccionadas-container { margin-top: 15px; margin-bottom: 15px; }

        /* --- 5. Selector de Idioma --- */
        .language-selector { display: flex; align-items: center; gap: 5px; background-color: var(--color-fondo); padding: 4px; border-radius: 20px; margin-right: 0; border: 1px solid var(--color-borde); }
        .lang-btn { border: none; background-color: transparent; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 13px; padding: 4px 12px; border-radius: 15px; cursor: pointer; color: #888; transition: all 0.3s ease; }
        .lang-btn:not(.active):hover { background-color: #e9eef2; color: var(--color-primario); }
        .lang-btn.active { background-color: var(--color-secundario); color: var(--color-blanco); cursor: default; }

        /* --- 6. Botones y Utilerías --- */
        .btn-primary, .btn-secondary { padding: 12px 25px; border-radius: 6px; border: none; font-family: 'Montserrat', sans-serif; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .btn-primary { background-color: var(--color-secundario); color: white; }
        .btn-primary:hover { background-color: var(--color-primario); }
        .btn-secondary { background-color: #e0e0e0; color: #333; }
        .btn-secondary:hover { background-color: #bdbdbd; }
        .btn-add { width: 45px; height: 45px; border-radius: 50%; border: none; background-color: var(--color-primario); color: white; font-size: 24px; font-weight: bold; cursor: pointer; flex-shrink: 0; }
        .btn-small { padding: 6px 12px; font-size: 14px; border-radius: 4px; }
        .btn-danger { background-color: var(--color-error); color: var(--color-blanco); }
        button:disabled { opacity: 0.6; cursor: not-allowed; }

        /* --- 7. Subida de Archivos (CSS ELIMINADO PORQUE NO SE USA) --- */
        /* --- ESTILOS DE .file-upload-label ELIMINADOS --- */
        input[type="file"] { display: none; }

        /* --- 8. Tabla de Historial --- */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 20px; margin-bottom: 40px; border: 1px solid var(--color-borde); border-radius: 8px; box-shadow: var(--sombra-suave); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; white-space: nowrap; }
        .data-table th { background-color: var(--color-primario); color: var(--color-blanco); font-weight: 600; text-transform: uppercase; position: sticky; top: 0; z-index: 1; }
        .data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tbody tr:hover { background-color: #f0f4f8; }
        .data-table td .btn-small { margin: 0 2px; }
        /* --- CSS CORREGIDO: +4 para incluir la nueva columna --- */
        .data-table th:nth-child(n+6):nth-child(-n+<?php echo 5 + count($defectos_para_formulario_y_tabla) + 4; ?>),
        .data-table td:nth-child(n+6):nth-child(-n+<?php echo 5 + count($defectos_para_formulario_y_tabla) + 4; ?>) {
            text-align: center;
        }

        .data-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }
        .data-table .total-row td {
            font-weight: bold;
            background-color: #e9ecef;
            border-top: 2px solid var(--color-primario);
        }
        .data-table .total-row td:first-child {
            text-align: right;
            padding-right: 10px;
        }

        /* --- NUEVO: Estilos para Nuevos Defectos (de contenciones) --- */
        .defecto-item {
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fcfcfc;
        }
        .defecto-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--color-borde);
        }
        .defecto-header h4 {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            color: var(--color-primario);
        }
        .btn-remove-defecto {
            background: none;
            border: none;
            color: var(--color-error);
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 5px;
            line-height: 1;
        }
        .btn-remove-defecto:hover {
            color: #7a2121;
        }
        /* --- FIN NUEVO --- */


        /* --- 9. Estilos Responsivos --- */
        @media (max-width: 992px) {
            .info-row p {
                flex-basis: 100%;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: center;
            }
            .user-info {
                flex-direction: column;
                justify-content: center;
                width: 100%;
            }
            .container { padding: 15px; }
            .form-container { padding: 20px 25px; }
            .form-container h1 { font-size: 20px; line-height: 1.4; }
            .form-row { flex-direction: column; gap: 0; }
            .form-row .form-group.w-50,
            .form-row .form-group.w-25 {
                flex-basis: 100%;
            }
            .data-table { font-size: 12px; }
            .data-table th,
            .data-table td { padding: 8px 10px; }

            .pdf-viewer-container {
                overflow-x: auto; /* Permite scroll horizontal en móvil */
                -webkit-overflow-scrolling: touch; /* Scroll suave en iOS */
                height: 600px; /* Altura fija para móvil */
            }
            #pdfViewerWrapper iframe {
                min-width: 600px; /* Ancho mínimo para legibilidad */
                height: 100%;
            }

            .defect-classification-grid {
                grid-template-columns: 1fr; /* Una columna en móvil */
            }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
    <div class="user-info">
        <div class="language-selector">
            <button type="button" class="lang-btn" data-lang="es">ES</button>
            <button type="button" class="lang-btn" data-lang="en">EN</button>
        </div>
        <span><span data-translate-key="welcome">Bienvenido</span>, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'"><span data-translate-key="logout">Cerrar Sesión</span> <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="form-container">
        <h1><i class="fa-solid fa-hammer"></i> <span data-translate-key="main_title">Reporte de Inspección Safe Launch</span> - <span data-translate-key="folio">Folio</span> SL-<?php echo str_pad($safeLaunchData['IdSafeLaunch'], 4, '0', STR_PAD_LEFT); ?></h1>

        <div class="info-row">
            <p><strong data-translate-key="project_name">Proyecto:</strong> <span><?php echo $nombreProyecto; ?></span></p>
            <p><strong data-translate-key="responsible">Responsable:</strong> <span><?php echo $nombreResponsable; ?></span></p>
            <p><strong data-translate-key="client">Cliente:</strong> <span><?php echo $cliente; ?></span></p>
        </div>

        <?php if ($mostrarVisorPDF): ?>
            <fieldset>
                <legend><i class="fa-solid fa-file-shield"></i> <span data-translate-key="instruction_title">Instrucción de Trabajo</span></legend>
                <div class="form-actions" style="margin-bottom: 15px;">
                    <button type="button" id="togglePdfViewerBtn" class="btn-secondary"><i class="fa-solid fa-eye"></i> <span data-translate-key="view_instruction_btn">Ver Instrucción</span></button>
                </div>
                <div id="pdfViewerWrapper" style="display: none;">
                    <div class="pdf-viewer-container">
                        <iframe src="<?php echo htmlspecialchars($safeLaunchData['RutaInstruccion']); ?>#view=FitH" frameborder="0"></iframe>
                    </div>
                </div>
            </fieldset>
        <?php else: ?>
            <div class='notification-box warning'><i class='fa-solid fa-triangle-exclamation'></i> <span data-translate-key='no_instruction_attached'>No hay instrucción de trabajo adjunta para este Safe Launch.</span></div>
        <?php endif; ?>


        <?php if ($mostrarFormularioPrincipal): ?>
            <form id="reporteFormSL" action="dao/guardar_reporte_safe_launch.php" method="POST">
                <input type="hidden" name="idSafeLaunch" value="<?php echo $idSafeLaunch; ?>">
                <input type="hidden" name="idSLReporte" id="idSLReporte" value="">

                <fieldset>
                    <legend><i class="fa-solid fa-chart-simple"></i> <span data-translate-key="summary_title">Resumen de Inspección</span></legend>

                    <div class="form-row">
                        <div class="form-group">
                            <label data-translate-key="total_inspected">Piezas Inspeccionadas</label>
                            <input type="number" name="piezasInspeccionadas" id="piezasInspeccionadas" min="0" required>
                        </div>
                        <div class="form-group"><label data-translate-key="accepted_pieces">Piezas Aceptadas</label><input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required></div>
                        <div class="form-group"><label data-translate-key="reworked_pieces">Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" id="piezasRetrabajadas" min="0" value="0" required></div>
                        <div class="form-group"><label data-translate-key="rejected_pieces_calc">Piezas Rechazadas (Cálculo)</label><input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label data-translate-key="inspector_name">Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label data-translate-key="inspection_date">Fecha de Inspección</label><input type="date" name="fechaInspeccion" required value="<?php echo date('Y-m-d'); ?>"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label data-translate-key="start_time">Hora de Inicio</label>
                            <input type="time" name="horaInicio" id="horaInicio" required>
                        </div>
                        <div class="form-group">
                            <label data-translate-key="end_time">Hora de Fin</label>
                            <input type="time" name="horaFin" id="horaFin" required>
                        </div>
                    </div>
                    <input type="hidden" name="rangoHoraCompleto" id="rangoHoraCompleto">

                </fieldset>

                <fieldset>
                    <legend><i class="fa-solid fa-clipboard-check"></i> <span data-translate-key="defect_classification_title">Clasificación de Defectos</span></legend>
                    <p class="piezas-rechazadas-info"><span data-translate-key="available_to_classify">Piezas rechazadas disponibles para clasificar:</span> <span id="piezasRechazadasRestantes" style="font-weight: bold; color: var(--color-error);">0</span></p>
                    <div class="defect-classification-grid">
                        <?php foreach ($defectos_para_formulario_y_tabla as $defecto): ?>
                            <div class="defect-classification-item" data-id-defecto-catalogo="<?php echo $defecto['id']; ?>">
                                <label><?php echo $defecto['nombre']; ?></label>
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="number" class="defecto-cantidad" name="defectos[<?php echo $defecto['id']; ?>][cantidad]" placeholder="Cantidad..." value="0" min="0" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" class="defecto-lote" name="defectos[<?php echo $defecto['id']; ?>][lote]" placeholder="Bach/Lote...">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($defectos_para_formulario_y_tabla)): ?>
                            <p data-translate-key="no_defects_associated">No hay defectos específicamente asociados a este Safe Launch en la solicitud inicial.</p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend><i class="fa-solid fa-magnifying-glass-plus"></i> <span data-translate-key="new_defects_title">Nuevos Defectos Encontrados (Opcional)</span></legend>
                    <div id="nuevos-defectos-container-sl"></div>
                    <button type="button" id="btn-add-nuevo-defecto-sl" class="btn-secondary"><i class="fa-solid fa-plus"></i> <span data-translate-key="add_new_defect_btn">Añadir Nuevo Defecto</span></button>
                </fieldset>

                <fieldset>
                    <legend><i class="fa-solid fa-stopwatch"></i> <span data-translate-key="session_time_comments_title">Tiempos y Comentarios de la Sesión</span></legend>
                    <div class="form-group">
                        <label data-translate-key="inspection_time_session">Tiempo de Inspección (Esta Sesión)</label>
                        <input type="text" name="tiempoInspeccion" id="tiempoInspeccion" value="" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="form-group"><label data-translate-key="additional_comments">Comentarios Adicionales de la Sesión</label><textarea name="comentarios" id="comentarios" rows="4"></textarea></div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="btnGuardarReporteSL"><span data-translate-key="save_session_report_btn">Guardar Reporte de Sesión</span></button>
                </div>
            </form>

        <?php else: ?>
            <div class='notification-box info' style='margin-top: 20px;'><i class='fa-solid fa-circle-check'></i> <strong data-translate-key="sl_closed_title">Safe Launch Cerrado:</strong> <span data-translate-key="sl_closed_desc">Este Safe Launch ya ha sido marcado como cerrado. No se pueden añadir nuevos reportes.</span></div>
        <?php endif; ?>

        <hr style="margin-top: 40px; margin-bottom: 30px; border-color: var(--color-borde);">

        <h2 style="margin-top: 40px;"><i class="fa-solid fa-list-check"></i> <span data-translate-key="history_title">Historial de Registros de Inspección</span></h2>
        <?php if (count($reportes_procesados) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-translate-key="th_report_id">ID Reporte</th>
                        <th data-translate-key="th_inspection_date">Fecha Insp.</th>
                        <th data-translate-key="th_time_range">Rango Hora</th>
                        <th data-translate-key="th_shift_leader">Turno</th>
                        <th data-translate-key="th_inspector">Inspector</th>
                        <th data-translate-key="th_inspected">Insp.</th>
                        <th data-translate-key="th_accepted">Acep.</th>
                        <th data-translate-key="th_rejected">Rech.</th>
                        <th data-translate-key="th_reworked">Retrab.</th>
                        <?php foreach ($defectos_para_formulario_y_tabla as $defecto): ?>
                            <th><?php echo $defecto['nombre']; ?></th>
                        <?php endforeach; ?>
                        <!-- --- NUEVA COLUMNA --- -->
                        <th data-translate-key="th_new_defects_qty">Nuevos Def. (Cant.)</th>
                        <th data-translate-key="th_total_defects">Total Def.</th>
                        <th data-translate-key="th_ppm">PPM</th>
                        <th data-translate-key="th_defect_percent">% Def.</th>
                        <th data-translate-key="th_comments">Comentarios</th>
                        <th data-translate-key="th_actions">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes_procesados as $reporte): ?>
                        <tr>
                            <td><?php echo "SLR-" . str_pad($reporte['IdSLReporte'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($reporte['FechaInspeccion']))); ?></td>
                            <td><?php echo htmlspecialchars($reporte['RangoHora'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($reporte['TurnoShiftLeader']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['NombreInspector']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasInspeccionadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasAceptadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRechazadasCalculadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRetrabajadas']); ?></td>
                            <?php foreach ($defectos_para_formulario_y_tabla as $defecto): ?>
                                <td><?php echo $reporte['DefectosPorTipo'][$defecto['id']] ?? 0; ?></td>
                            <?php endforeach; ?>
                            <!-- --- NUEVA CELDA --- -->
                            <td><?php echo $reporte['NuevosDefectosParaMostrar']; ?></td>
                            <td><?php echo $reporte['TotalDefectosReporte']; ?></td>
                            <td><?php echo ($reporte['PiezasInspeccionadas'] > 0) ? round(($reporte['TotalDefectosReporte'] / $reporte['PiezasInspeccionadas']) * 1000000) : 0; ?></td>
                            <td><?php echo ($reporte['PiezasInspeccionadas'] > 0) ? round(($reporte['TotalDefectosReporte'] / $reporte['PiezasInspeccionadas']) * 100, 2) . '%' : '0%'; ?></td>
                            <td><?php echo htmlspecialchars($reporte['Comentarios'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn-edit-reporte-sl btn-primary btn-small" data-id="<?php echo $reporte['IdSLReporte']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn-delete-reporte-sl btn-danger btn-small" data-id="<?php echo $reporte['IdSLReporte']; ?>"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="total-row">
                        <td colspan="5" style="text-align: right;"><span data-translate-key="totals">TOTALES:</span></td>
                        <td><?php echo $totalPiezasInspeccionadasYa; ?></td>
                        <td><?php echo array_sum(array_column($reportes_procesados, 'PiezasAceptadas')); ?></td>
                        <td><?php echo array_sum(array_column($reportes_procesados, 'PiezasRechazadasCalculadas')); ?></td>
                        <td><?php echo array_sum(array_column($reportes_procesados, 'PiezasRetrabajadas')); ?></td>
                        <?php foreach ($defectos_para_formulario_y_tabla as $defecto): ?>
                            <td><?php echo $totalDefectosPorTipoGlobal[$defecto['id']] ?? 0; ?></td>
                        <?php endforeach; ?>
                        <!-- --- NUEVA CELDA (VACÍA) --- -->
                        <td></td>
                        <td><?php echo $totalDefectosEncontradosGlobal; ?></td>
                        <td><?php echo $ppm_global; ?></td>
                        <td><?php echo $porcentaje_defectuoso_global . '%'; ?></td>
                        <td colspan="2"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <p data-translate-key="no_history_records_sl">Aún no hay registros de inspección para este Safe Launch.</p>
        <?php endif; ?>

    </div>
</main>

<script>
    const catalogoDefectosSL = <?php echo json_encode($defectos_para_formulario_y_tabla); ?>;
    const opcionesDefectosSL = '<?php echo addslashes($defectos_options_html_sl); ?>';
    let nuevoDefectoCounterSL = 0;
    let editandoReporteSL = false;
    let valorOriginalInspeccionadoAlEditarSL = 0;


    document.addEventListener('DOMContentLoaded', function() {
        // --- INICIO: LÓGICA DE TRADUCCIÓN ---
        const translations = {
            es: {
                welcome: "Bienvenido", logout: "Cerrar Sesión", main_title: "Reporte de Inspección Safe Launch",
                folio: "Folio", project_name: "Proyecto", responsible: "Responsable", client: "Cliente",
                instruction_title: "Instrucción de Trabajo", view_instruction_btn: "Ver Instrucción",
                hide_instruction_btn: "Ocultar Instrucción", download_instruction_btn: "Descargar Instrucción",
                no_instruction_attached: "No hay instrucción de trabajo adjunta para este Safe Launch.",
                summary_title: "Resumen de Inspección", total_inspected: "Piezas Inspeccionadas",
                accepted_pieces: "Piezas Aceptadas", reworked_pieces: "Piezas Retrabajadas",
                rejected_pieces_calc: "Piezas Rechazadas (Cálculo)", inspector_name: "Nombre del Inspector",
                inspection_date: "Fecha de Inspección", start_time: "Hora de Inicio", end_time: "Hora de Fin",
                defect_classification_title: "Clasificación de Defectos", available_to_classify: "Piezas rechazadas disponibles para clasificar:",
                no_defects_associated: "No hay defectos específicamente asociados a este Safe Launch en la solicitud inicial.",
                session_time_comments_title: "Tiempos y Comentarios de la Sesión",
                inspection_time_session: "Tiempo de Inspección (Esta Sesión)",
                additional_comments: "Comentarios Adicionales de la Sesión",
                save_session_report_btn: "Guardar Reporte de Sesión",
                update_session_report_btn: "Actualizar Reporte de Sesión", cancel_edit_btn: "Cancelar Edición",
                sl_closed_title: "Safe Launch Cerrado:", sl_closed_desc: "Este Safe Launch ya ha sido marcado como cerrado. No se pueden añadir nuevos reportes.",
                history_title: "Historial de Registros de Inspección", th_report_id: "ID Reporte", th_inspection_date: "Fecha Insp.",
                th_time_range: "Rango Hora", th_shift_leader: "Turno", th_inspector: "Inspector",
                th_inspected: "Insp.", th_accepted: "Acep.", th_rejected: "Rech.", th_reworked: "Retrab.",
                th_new_defects_qty: "Nuevos Def. (Cant.)", // --- NUEVA TRADUCCIÓN ---
                th_total_defects: "Total Def.", th_ppm: "PPM", th_defect_percent: "% Def.",
                th_comments: "Comentarios", th_actions: "Acciones", totals: "TOTALES:",
                no_history_records_sl: "Aún no hay registros de inspección para este Safe Launch.",
                swal_saving_title: "Guardando Reporte...", swal_saving_text: "Por favor, espera.",
                swal_success_title: "¡Éxito!", swal_error_title: "Error", swal_connection_error: "Error de Conexión",
                swal_connection_error_text: "No se pudo comunicar con el servidor.",
                swal_loading_edit_title: "Cargando Reporte...", swal_loading_edit_text: "Obteniendo datos para edición.",
                swal_loading_edit_error: "No se pudo cargar el reporte para edición.",
                swal_delete_title: "¿Estás seguro?", swal_delete_text: "¡No podrás revertir esto!",
                swal_delete_confirm: "Sí, eliminar", swal_delete_cancel: "Cancelar",
                swal_deleting_title: "Eliminando Reporte...", swal_deleting_text: "Por favor, espera.",
                swal_deleted_title: "¡Eliminado!", swal_deleted_text: "El reporte ha sido eliminado.",
                swal_delete_error: "No se pudo eliminar el reporte.",
                new_defects_title: "Nuevos Defectos Encontrados (Opcional)",
                add_new_defect_btn: "Añadir Nuevo Defecto",
                new_defect_header: "Nuevo Defecto",
                defect_type_label: "Tipo de Defecto",
                select_defect_option: "Seleccione un defecto",
                qty_label: "Cantidad de Piezas",
                qty_placeholder: "Cantidad con este defecto...",
                swal_remove_defect_title: "¿Estás seguro?",
                swal_remove_defect_text: "Este defecto se eliminará del formulario.",
                swal_remove_defect_confirm: "Sí, eliminar",
                swal_remove_defect_cancel: "Cancelar",
                swal_remove_defect_removed: "Eliminado",
                swal_remove_defect_removed_text: "El defecto fue removido."
            },
            en: {
                welcome: "Welcome", logout: "Logout", main_title: "Safe Launch Inspection Report",
                folio: "Folio", project_name: "Project", responsible: "Responsible", client: "Client",
                instruction_title: "Work Instruction", view_instruction_btn: "View Instruction",
                hide_instruction_btn: "Hide Instruction", download_instruction_btn: "Download Instruction",
                no_instruction_attached: "No work instruction attached for this Safe Launch.",
                summary_title: "Inspection Summary", total_inspected: "Inspected Pieces",
                accepted_pieces: "Accepted Pieces", reworked_pieces: "Reworked Pieces",
                rejected_pieces_calc: "Rejected Pieces (Calculated)", inspector_name: "Inspector's Name",
                inspection_date: "Inspection Date", start_time: "Start Time", end_time: "End Time",
                defect_classification_title: "Defect Classification", available_to_classify: "Rejected pieces available for classification:",
                no_defects_associated: "There are no defects specifically associated with this Safe Launch in the initial request.",
                session_time_comments_title: "Session Times and Comments",
                inspection_time_session: "Inspection Time (This Session)",
                additional_comments: "Additional Session Comments",
                save_session_report_btn: "Save Session Report",
                update_session_report_btn: "Update Session Report", cancel_edit_btn: "Cancel Edit",
                sl_closed_title: "Safe Launch Closed:", sl_closed_desc: "This Safe Launch has already been marked as closed. New reports cannot be added.",
                history_title: "Inspection Records History", th_report_id: "Report ID", th_inspection_date: "Insp. Date",
                th_time_range: "Time Range", th_shift_leader: "Shift", th_inspector: "Inspector",
                th_inspected: "Insp.", th_accepted: "Acc.", th_rejected: "Rej.", th_reworked: "Rew.",
                th_new_defects_qty: "New Def. (Qty)", // --- NEW TRANSLATION ---
                th_total_defects: "Total Def.", th_ppm: "PPM", th_defect_percent: "% Def.",
                th_comments: "Comments", th_actions: "Actions", totals: "TOTALS:",
                no_history_records_sl: "There are no inspection records for this Safe Launch yet.",
                swal_saving_title: "Saving Report...", swal_saving_text: "Please wait.",
                swal_success_title: "Success!", swal_error_title: "Error", swal_connection_error: "Connection Error",
                swal_connection_error_text: "Could not communicate with the server.",
                swal_loading_edit_title: "Loading Report...", swal_loading_edit_text: "Fetching data for editing.",
                swal_loading_edit_error: "Could not load the report for editing.",
                swal_delete_title: "Are you sure?", swal_delete_text: "You won't be able to revert this!",
                swal_delete_confirm: "Yes, delete it", swal_delete_cancel: "Cancel",
                swal_deleting_title: "Deleting Report...", swal_deleting_text: "Please wait.",
                swal_deleted_title: "Deleted!", swal_deleted_text: "The report has been deleted.",
                swal_delete_error: "Could not delete the report.",
                new_defects_title: "New Defects Found (Optional)",
                add_new_defect_btn: "Add New Defect",
                new_defect_header: "New Defect",
                defect_type_label: "Defect Type",
                select_defect_option: "Select a defect",
                qty_label: "Quantity of Pieces",
                qty_placeholder: "Quantity with this defect...",
                swal_remove_defect_title: "Are you sure?",
                swal_remove_defect_text: "This defect will be removed from the form.",
                swal_remove_defect_confirm: "Yes, remove",
                swal_remove_defect_cancel: "Cancel",
                swal_remove_defect_removed: "Removed",
                swal_remove_defect_removed_text: "The defect was removed."
            }
        };

        function setLanguage(lang) {
            document.documentElement.lang = lang;
            localStorage.setItem('language', lang);
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.getAttribute('data-translate-key');
                if (translations[lang] && translations[lang][key]) {

                    const span = el.querySelector('span');
                    if(span && (el.tagName === 'BUTTON' || el.tagName === 'LEGEND' || el.tagName === 'H1' || el.tagName === 'H2')) {
                        span.innerText = translations[lang][key];
                    } else if (el.tagName === 'INPUT' && el.type === 'submit') {
                        el.value = translations[lang][key];
                    } else if (!el.children.length || el.classList.contains('file-upload-label')) {
                        el.innerText = translations[lang][key];
                    } else if (el.tagName === 'LABEL' && el.querySelector('span')) {
                        el.querySelector('span').innerText = translations[lang][key];
                    }

                    const icon = el.querySelector('i');
                    if (icon && (el.tagName === 'LEGEND' || el.tagName === 'H1' || el.tagName === 'H2' || el.tagName === 'BUTTON' || el.tagName === 'P' || el.tagName === 'STRONG')) {
                        const spanInterno = el.querySelector('span');
                        if (!spanInterno) {
                            el.innerHTML = icon.outerHTML + ' ' + translations[lang][key];
                        }
                    } else if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) {
                        el.value = translations[lang][key];
                    } else if (el.tagName === 'BUTTON' && !icon) {
                        el.innerText = translations[lang][key];
                    } else if (!icon && !span && el.tagName !== 'LABEL') {
                        el.innerText = translations[lang][key];
                    } else if (el.tagName === 'LABEL' && !icon && !span) {
                        el.innerText = translations[lang][key];
                    }

                    if(el.classList.contains('file-upload-label') && el.querySelector('span')) {
                        el.querySelector('span').innerText = translations[lang][key];
                    }
                    if(el.tagName === 'OPTION' && el.value === "") {
                        el.innerText = translations[lang][key];
                    }
                }
            });
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.lang === lang));
            adjustPdfButtonText();
        }


        function getCurrentLanguage() { return localStorage.getItem('language') || 'es'; }
        function translate(key) { const lang = getCurrentLanguage(); return (translations[lang] && translations[lang][key]) ? translations[lang][key] : key; }

        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                setLanguage(btn.dataset.lang);
            });
        });
        // --- FIN: LÓGICA DE TRADUCCIÓN ---

        const reporteForm = document.getElementById('reporteFormSL');
        if (reporteForm) {
            const idReporteInput = document.getElementById('idSLReporte');
            const piezasInspeccionadasInput = document.getElementById('piezasInspeccionadas');
            const piezasAceptadasInput = document.getElementById('piezasAceptadas');
            const piezasRetrabajadasInput = document.getElementById('piezasRetrabajadas');
            const piezasRechazadasCalculadasInput = document.getElementById('piezasRechazadasCalculadas');
            const piezasRechazadasRestantesSpan = document.getElementById('piezasRechazadasRestantes');
            const defectosContainer = document.querySelector('.defect-classification-grid');
            const nuevosDefectosContainerSL = document.getElementById('nuevos-defectos-container-sl');
            const btnAddNuevoDefectoSL = document.getElementById('btn-add-nuevo-defecto-sl');
            const btnGuardarReporte = document.getElementById('btnGuardarReporteSL');
            const fechaInspeccionInput = document.querySelector('input[name="fechaInspeccion"]');
            const horaInicioInput = document.getElementById('horaInicio');
            const horaFinInput = document.getElementById('horaFin');
            const rangoHoraCompletoInput = document.getElementById('rangoHoraCompleto');
            const tiempoInspeccionInput = document.getElementById('tiempoInspeccion');
            const comentariosTextarea = document.getElementById('comentarios');

            function calcularYActualizarTiempo() {
                const horaInicio = horaInicioInput.value;
                const horaFin = horaFinInput.value;

                if (horaInicio && horaFin) {
                    const fechaInicio = new Date(`1970-01-01T${horaInicio}`);
                    const fechaFin = new Date(`1970-01-01T${horaFin}`);

                    if (fechaFin < fechaInicio) {
                        tiempoInspeccionInput.value = 'Error: Hora de fin inválida';
                        horaFinInput.setCustomValidity("La hora de fin no puede ser anterior a la hora de inicio.");
                        horaFinInput.reportValidity();
                        return;
                    } else {
                        horaFinInput.setCustomValidity("");
                    }

                    const diffMs = fechaFin - fechaInicio;
                    const totalMinutos = Math.round(diffMs / 60000);
                    const horas = Math.floor(totalMinutos / 60);
                    const minutos = totalMinutos % 60;

                    let tiempoStr = '';
                    if (horas > 0) tiempoStr += `${horas} hora(s) `;
                    if (minutos > 0 || horas === 0) tiempoStr += `${minutos} minuto(s)`;

                    tiempoInspeccionInput.value = tiempoStr.trim() || '0 minuto(s)';

                } else {
                    tiempoInspeccionInput.value = '';
                }
            }

            horaInicioInput.addEventListener('change', calcularYActualizarTiempo);
            horaFinInput.addEventListener('change', calcularYActualizarTiempo);

            function actualizarContadores() {
                if (!piezasInspeccionadasInput) return;

                const inspeccionadas = parseInt(piezasInspeccionadasInput.value) || 0;
                const aceptadas = parseInt(piezasAceptadasInput.value) || 0;
                const retrabajadas = parseInt(piezasRetrabajadasInput.value) || 0;

                if (aceptadas > inspeccionadas) {
                    piezasAceptadasInput.setCustomValidity("Las piezas aceptadas no pueden ser mayores que las inspeccionadas.");
                    piezasAceptadasInput.reportValidity();
                } else {
                    piezasAceptadasInput.setCustomValidity("");
                }

                const rechazadasBrutas = inspeccionadas - aceptadas;
                piezasRechazadasCalculadasInput.value = Math.max(0, rechazadasBrutas);

                if (retrabajadas > rechazadasBrutas) {
                    piezasRetrabajadasInput.setCustomValidity('Las piezas retrabajadas no pueden exceder las piezas rechazadas.');
                    piezasRetrabajadasInput.reportValidity();
                } else {
                    piezasRetrabajadasInput.setCustomValidity('');
                }

                const rechazadasDisponibles = rechazadasBrutas - retrabajadas;
                let sumDefectosClasificados = 0;
                defectosContainer.querySelectorAll('.defecto-cantidad').forEach(input => { sumDefectosClasificados += parseInt(input.value) || 0; });
                if (nuevosDefectosContainerSL) {
                    nuevosDefectosContainerSL.querySelectorAll('.nuevo-defecto-cantidad-sl').forEach(input => { sumDefectosClasificados += parseInt(input.value) || 0; });
                }

                const restantes = rechazadasDisponibles - sumDefectosClasificados;
                piezasRechazadasRestantesSpan.textContent = Math.max(0, restantes);

                const sonDefectosInvalidos = restantes !== 0;

                if (restantes < 0) {
                    piezasRechazadasRestantesSpan.style.color = 'var(--color-error)';
                } else if (restantes > 0) {
                    piezasRechazadasRestantesSpan.style.color = 'orange';
                } else {
                    piezasRechazadasRestantesSpan.style.color = 'var(--color-exito)';
                }

                let deshabilitar = false;
                let titulo = '';

                if (aceptadas > inspeccionadas) {
                    deshabilitar = true;
                    titulo = 'Las piezas aceptadas exceden las inspeccionadas.';
                } else if (retrabajadas > rechazadasBrutas) {
                    deshabilitar = true;
                    titulo = 'Las piezas retrabajadas exceden las rechazadas.';
                } else if (sonDefectosInvalidos) {
                    deshabilitar = true;
                    titulo = restantes > 0 ? 'Aún faltan piezas por clasificar.' : 'La suma de defectos excede las piezas rechazadas disponibles.';
                }

                btnGuardarReporte.disabled = deshabilitar;
                btnGuardarReporte.title = titulo;
            }

            piezasInspeccionadasInput.addEventListener('input', actualizarContadores);
            piezasAceptadasInput.addEventListener('input', actualizarContadores);
            piezasRetrabajadasInput.addEventListener('input', actualizarContadores);
            defectosContainer.addEventListener('input', function(e) {
                if (e.target.classList.contains('defecto-cantidad')) {
                    actualizarContadores();
                }
            });
            if (nuevosDefectosContainerSL) {
                nuevosDefectosContainerSL.addEventListener('input', function(e) {
                    if (e.target.classList.contains('nuevo-defecto-cantidad-sl')) {
                        actualizarContadores();
                    }
                });
            }
            actualizarContadores(); // Calcular al cargar

            reporteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (horaInicioInput.value && horaFinInput.value) {
                    const formatTo12Hour = (timeStr) => {
                        if (!timeStr) return '';
                        const [hours, minutes] = timeStr.split(':');
                        let h = parseInt(hours);
                        const ampm = h >= 12 ? 'pm' : 'am';
                        h = h % 12;
                        h = h ? h : 12;
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

                Swal.fire({ title: translate('swal_saving_title'), text: translate('swal_saving_text'), allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                const actionUrl = editandoReporteSL ? 'dao/actualizar_reporte_sl.php' : 'dao/guardar_reporte_safe_launch.php';

                fetch(actionUrl, { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire(translate('swal_success_title'), data.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire(translate('swal_error_title'), data.message, 'error');
                        }
                    })
                    .catch(error => Swal.fire(translate('swal_connection_error'), translate('swal_connection_error_text'), 'error'));
            });
        } // Fin if (reporteForm)

        async function cargarReporteParaEdicionSL(idReporte) {
            const reporteForm = document.getElementById('reporteFormSL');
            if (!reporteForm) return;

            Swal.fire({ title: translate('swal_loading_edit_title'), text: translate('swal_loading_edit_text'), allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const response = await fetch(`dao/obtener_reporte_sl_para_edicion.php?idSLReporte=${idReporte}`);
                const data = await response.json();

                if (data.status === 'success') {
                    const reporte = data.reporte;
                    const defectos = data.defectos;
                    const nuevosDefectos = data.nuevosDefectos || [];

                    valorOriginalInspeccionadoAlEditarSL = parseInt(reporte.PiezasInspeccionadas, 10) || 0;

                    document.getElementById('idSLReporte').value = reporte.IdSLReporte;
                    document.getElementById('piezasInspeccionadas').value = reporte.PiezasInspeccionadas;
                    document.getElementById('piezasAceptadas').value = reporte.PiezasAceptadas;
                    document.getElementById('piezasRetrabajadas').value = reporte.PiezasRetrabajadas;
                    document.querySelector('input[name="fechaInspeccion"]').value = reporte.FechaInspeccion;
                    document.getElementById('comentarios').value = reporte.Comentarios || '';

                    if (reporte.RangoHora) {
                        const convertTo24Hour = (timeStr) => {
                            if (!timeStr) return '';
                            let [time, modifier] = timeStr.trim().split(' ');
                            if (!modifier) return timeStr;
                            let [hours, minutes] = time.split(':');
                            if (hours === '12') hours = '00';
                            if (modifier.toUpperCase() === 'PM') hours = parseInt(hours, 10) + 12;
                            return `${String(hours).padStart(2, '0')}:${minutes}`;
                        };
                        const [startStr, endStr] = reporte.RangoHora.split(' - ');
                        if (startStr) document.getElementById('horaInicio').value = convertTo24Hour(startStr);
                        if (endStr) document.getElementById('horaFin').value = convertTo24Hour(endStr);
                    } else {
                        document.getElementById('horaInicio').value = '';
                        document.getElementById('horaFin').value = '';
                    }
                    calcularYActualizarTiempo();

                    document.querySelectorAll('.defect-classification-item').forEach(item => {
                        item.querySelector('.defecto-cantidad').value = 0;
                        const loteInput = item.querySelector('.defecto-lote');
                        if(loteInput) loteInput.value = '';
                    });

                    if (defectos && defectos.length > 0) {
                        defectos.forEach(def => {
                            const itemDiv = document.querySelector(`.defect-classification-item[data-id-defecto-catalogo="${def.IdSLDefectoCatalogo}"]`);
                            if (itemDiv) {
                                itemDiv.querySelector('.defecto-cantidad').value = def.CantidadEncontrada;
                                const loteInput = itemDiv.querySelector('.defecto-lote');
                                if(loteInput) loteInput.value = def.BachLote || '';
                            }
                        });
                    }

                    const nuevosDefectosContainerSL = document.getElementById('nuevos-defectos-container-sl');
                    if (nuevosDefectosContainerSL) {
                        nuevosDefectosContainerSL.innerHTML = '';
                        nuevoDefectoCounterSL = 0;
                        if (nuevosDefectos && nuevosDefectos.length > 0) {
                            nuevosDefectos.forEach(defecto => {
                                addNuevoDefectoBlockSL(
                                    defecto.IdSLNuevoDefecto, // Corregido: Usar el ID correcto de la tabla
                                    defecto.IdSLDefectoCatalogo,
                                    defecto.Cantidad
                                );
                            });
                        }
                    }

                    editandoReporteSL = true;
                    actualizarContadores();

                    const btnGuardarReporte = document.getElementById('btnGuardarReporteSL');
                    btnGuardarReporte.querySelector('span').innerText = translate('update_session_report_btn');

                    const formActions = btnGuardarReporte.parentElement;
                    let cancelButton = formActions.querySelector('.btn-cancel-edit');
                    if (!cancelButton) {
                        cancelButton = document.createElement('button');
                        cancelButton.type = 'button';
                        cancelButton.className = 'btn-secondary btn-cancel-edit';
                        cancelButton.innerHTML = `<span data-translate-key="cancel_edit_btn">${translate('cancel_edit_btn')}</span>`;
                        cancelButton.style.marginLeft = '10px';
                        cancelButton.onclick = () => window.location.reload();
                        formActions.appendChild(cancelButton);
                    }

                    Swal.close();
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                } else {
                    Swal.fire(translate('swal_error_title'), data.message, 'error');
                }
            } catch (error) {
                console.error("Error al cargar reporte SL para edición:", error);
                Swal.fire(translate('swal_connection_error'), translate('swal_loading_edit_error'), 'error');
            }
        }

        document.querySelectorAll('.btn-edit-reporte-sl').forEach(button => {
            button.addEventListener('click', function() {
                cargarReporteParaEdicionSL(this.dataset.id);
            });
        });

        document.querySelectorAll('.btn-delete-reporte-sl').forEach(button => {
            button.addEventListener('click', function() {
                const idReporteAEliminar = this.dataset.id;
                Swal.fire({
                    title: translate('swal_delete_title'),
                    text: translate('swal_delete_text'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: translate('swal_delete_confirm'),
                    cancelButtonText: translate('swal_delete_cancel')
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: translate('swal_deleting_title'), text: translate('swal_deleting_text'), allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        fetch('dao/eliminar_reporte_sl.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `idSLReporte=${idReporteAEliminar}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire(translate('swal_deleted_title'), translate('swal_deleted_text'), 'success').then(() => window.location.reload());
                                } else {
                                    Swal.fire(translate('swal_error_title'), data.message || translate('swal_delete_error'), 'error');
                                }
                            })
                            .catch(error => Swal.fire(translate('swal_connection_error'), translate('swal_delete_error'), 'error'));
                    }
                });
            });
        });

        const togglePdfBtn = document.getElementById('togglePdfViewerBtn');
        const pdfWrapper = document.getElementById('pdfViewerWrapper');
        const pdfUrl = '<?php echo $mostrarVisorPDF ? htmlspecialchars($safeLaunchData['RutaInstruccion']) : ''; ?>';

        function adjustPdfButtonText() {
            if (!togglePdfBtn) return;
            const isMobile = window.innerWidth <= 768;
            const span = togglePdfBtn.querySelector('span');
            const icon = togglePdfBtn.querySelector('i');

            if (isMobile) {
                span.innerText = translate('download_instruction_btn');
                icon.className = 'fa-solid fa-download';
                if (pdfWrapper && pdfWrapper.style.display !== 'none') {
                    pdfWrapper.style.display = 'none';
                    togglePdfBtn.classList.remove('btn-primary');
                    togglePdfBtn.classList.add('btn-secondary');
                }
            } else {
                const isHidden = pdfWrapper ? pdfWrapper.style.display === 'none' : true;
                span.innerText = isHidden ? translate('view_instruction_btn') : translate('hide_instruction_btn');
                icon.className = isHidden ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
                if (isHidden) {
                    togglePdfBtn.classList.remove('btn-primary');
                    togglePdfBtn.classList.add('btn-secondary');
                } else {
                    togglePdfBtn.classList.remove('btn-secondary');
                    togglePdfBtn.classList.add('btn-primary');
                }
            }
        }

        if (togglePdfBtn && pdfWrapper) {
            togglePdfBtn.addEventListener('click', function() {
                const isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    const link = document.createElement('a');
                    link.href = pdfUrl;
                    link.download = pdfUrl.substring(pdfUrl.lastIndexOf('/') + 1);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    const isHidden = pdfWrapper.style.display === 'none';
                    pdfWrapper.style.display = isHidden ? 'block' : 'none';
                    adjustPdfButtonText();
                }
            });
            adjustPdfButtonText();
            window.addEventListener('resize', adjustPdfButtonText);
        }

        // --- INICIO: LÓGICA DE NUEVOS DEFECTOS ---

        function addNuevoDefectoBlockSL(idDefectoEncontrado = null, idDefectoCatalogo = '', cantidad = '') {
            nuevoDefectoCounterSL++;
            const currentCounter = nuevoDefectoCounterSL;
            const nuevosDefectosContainerSL = document.getElementById('nuevos-defectos-container-sl');

            const defectoHTML = `
            <div class="defecto-item" id="nuevo-defecto-sl-${currentCounter}">
                <div class="defecto-header">
                    <h4><span data-translate-key="new_defect_header">${translate('new_defect_header')}</span> #${currentCounter}</h4>
                    <button type="button" class="btn-remove-defecto" data-defecto-id="${currentCounter}">&times;</button>
                </div>

                <div class="form-row">
                    <div class="form-group w-50">
                        <label data-translate-key="defect_type_label">${translate('defect_type_label')}</label>
                        <select name="nuevos_defectos_sl[${currentCounter}][id]" required>
                            <option value="" disabled selected>${translate('select_defect_option')}</option>
                            ${opcionesDefectosSL}
                        </select>
                    </div>
                    <div class="form-group w-50">
                        <label data-translate-key="qty_label">${translate('qty_label')}</label>
                        <input type="number" class="nuevo-defecto-cantidad-sl" name="nuevos_defectos_sl[${currentCounter}][cantidad]" placeholder="${translate('qty_placeholder')}" min="0" value="0" required>
                    </div>
                </div>
                ${idDefectoEncontrado ?
                `<input type="hidden" name="nuevos_defectos_sl[${currentCounter}][idDefectoEncontrado]" value="${idDefectoEncontrado}">` :
                ''}
            </div>`;
            nuevosDefectosContainerSL.insertAdjacentHTML('beforeend', defectoHTML);

            const newBlock = document.getElementById(`nuevo-defecto-sl-${currentCounter}`);

            if (idDefectoCatalogo) {
                newBlock.querySelector(`select[name="nuevos_defectos_sl[${currentCounter}][id]"]`).value = idDefectoCatalogo;
            }
            if (cantidad) {
                newBlock.querySelector(`input[name="nuevos_defectos_sl[${currentCounter}][cantidad]"]`).value = cantidad;
            }

            return newBlock;
        }

        const btnAddNuevoDefectoSL = document.getElementById('btn-add-nuevo-defecto-sl');
        if (btnAddNuevoDefectoSL) {
            btnAddNuevoDefectoSL.addEventListener('click', function() {
                addNuevoDefectoBlockSL();
                actualizarContadores();
            });
        }

        const nuevosDefectosContainerSL = document.getElementById('nuevos-defectos-container-sl');
        if (nuevosDefectosContainerSL) {
            nuevosDefectosContainerSL.addEventListener('click', function(e) {
                const removeButton = e.target.closest('.btn-remove-defecto');
                if (removeButton) {
                    const defectoItem = removeButton.closest('.defecto-item');
                    const idDefectoEncontradoInput = defectoItem.querySelector('input[name*="[idDefectoEncontrado]"]');

                    if (editandoReporteSL && idDefectoEncontradoInput && idDefectoEncontradoInput.value) {
                        Swal.fire({
                            title: translate('swal_remove_defect_title'),
                            text: "Este defecto se marcará para eliminación al guardar.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: translate('swal_remove_defect_confirm'),
                            cancelButtonText: translate('swal_remove_defect_cancel')
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const inputEliminar = document.createElement('input');
                                inputEliminar.type = 'hidden';
                                inputEliminar.name = 'defectos_sl_a_eliminar[]';
                                inputEliminar.value = idDefectoEncontradoInput.value;
                                reporteForm.appendChild(inputEliminar);

                                defectoItem.remove();
                                actualizarContadores();
                                Swal.fire(translate('swal_remove_defect_removed'), "El defecto se eliminará al guardar el reporte.", 'success');
                            }
                        });
                    } else {
                        defectoItem.remove();
                        actualizarContadores();
                    }
                }
            });
        }

        // --- FIN: LÓGICA DE NUEVOS DEFECTOS ---

        // Carga inicial del idioma
        setLanguage(getCurrentLanguage());
    });
</script>
</body>
</html>

