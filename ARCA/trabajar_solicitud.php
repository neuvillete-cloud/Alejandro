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
$catalogo_defectos_result = $catalogo_defectos_query->fetch_all(MYSQLI_ASSOC);
foreach($catalogo_defectos_result as $row) {
    $defectos_options_html .= "<option value='{$row['IdDefectoCatalogo']}'>" . htmlspecialchars($row['NombreDefecto']) . "</option>";
}


$razones_tiempo_muerto = $conex->query("SELECT IdTiempoMuerto, Razon FROM CatalogoTiempoMuerto ORDER BY Razon ASC");

$defectos_originales_formulario = $conex->query("SELECT d.IdDefecto, cd.NombreDefecto FROM Defectos d JOIN CatalogoDefectos cd ON d.IdDefectoCatalogo = cd.IdDefectoCatalogo WHERE d.IdSolicitud = $idSolicitud");
$defectos_originales_para_js = [];
mysqli_data_seek($defectos_originales_formulario, 0);
while ($def = $defectos_originales_formulario->fetch_assoc()) {
    $defectos_originales_para_js[$def['IdDefecto']] = htmlspecialchars($def['NombreDefecto']);
}

// --- INICIO DE CAMBIO: Modificación de la consulta y cálculo de tiempo total ---
function parsearTiempoAMinutos($tiempoStr) {
    if (empty($tiempoStr)) return 0;
    $totalMinutos = 0;
    if (preg_match('/(\d+)\s*hora(s)?/', $tiempoStr, $matches)) {
        $totalMinutos += intval($matches[1]) * 60;
    }
    if (preg_match('/(\d+)\s*minuto(s)?/', $tiempoStr, $matches)) {
        $totalMinutos += intval($matches[1]);
    }
    if ($totalMinutos === 0 && str_contains(strtolower($tiempoStr), 'hora')) {
        $totalMinutos = intval(filter_var($tiempoStr, FILTER_SANITIZE_NUMBER_INT)) * 60;
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
        ri.IdReporte, ri.FechaInspeccion, ri.NombreInspector, ri.PiezasInspeccionadas, ri.PiezasAceptadas,
        (ri.PiezasInspeccionadas - ri.PiezasAceptadas) AS PiezasRechazadasCalculadas,
        ri.PiezasRetrabajadas, 
        COALESCE(ri.RangoHora, crh.RangoHora) AS RangoHora, 
        ri.Comentarios,
        ri.TiempoInspeccion,
        ctm.Razon AS RazonTiempoMuerto
    FROM ReportesInspeccion ri
    LEFT JOIN CatalogoRangosHoras crh ON ri.IdRangoHora = crh.IdRangoHora
    LEFT JOIN CatalogoTiempoMuerto ctm ON ri.IdTiempoMuerto = ctm.IdTiempoMuerto
    WHERE ri.IdSolicitud = ? ORDER BY ri.FechaRegistro DESC
");
$reportes_anteriores_query->bind_param("i", $idSolicitud);
$reportes_anteriores_query->execute();
$reportes_raw = $reportes_anteriores_query->get_result()->fetch_all(MYSQLI_ASSOC);

$reportes_procesados = [];
$totalMinutosRegistrados = 0;

foreach ($reportes_raw as $reporte) {
    $reporte_id = $reporte['IdReporte'];
    $totalMinutosRegistrados += parsearTiempoAMinutos($reporte['TiempoInspeccion']);

    if ($isVariosPartes) {
        $desglose_query = $conex->prepare("SELECT NumeroParte FROM ReporteDesglosePartes WHERE IdReporte = ?");
        $desglose_query->bind_param("i", $reporte_id);
        $desglose_query->execute();
        $desglose_result = $desglose_query->get_result();
        $partes_desglosadas = [];
        while ($fila = $desglose_result->fetch_assoc()) {
            $partes_desglosadas[] = htmlspecialchars($fila['NumeroParte']);
        }
        $reporte['NumeroParteParaMostrar'] = empty($partes_desglosadas) ? 'Varios (Sin Desglose)' : implode("<br>", array_unique($partes_desglosadas));
        $desglose_query->close();
    } else {
        $reporte['NumeroParteParaMostrar'] = $numeroParte;
    }

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
    $reporte['DefectosConCantidades'] = empty($defectos_con_cantidades) ? 'N/A' : implode("<br>", $defectos_con_cantidades);
    $reporte['LotesEncontrados'] = empty($lotes_encontrados) ? 'N/A' : implode(", ", array_unique($lotes_encontrados));
    $defectos_reporte_query->close();

    // --- NUEVO: OBTENER NUEVOS DEFECTOS ---
    $nuevos_defectos_query = $conex->prepare("
        SELECT de.Cantidad, cd.NombreDefecto
        FROM DefectosEncontrados de
        JOIN CatalogoDefectos cd ON de.IdDefectoCatalogo = cd.IdDefectoCatalogo
        WHERE de.IdReporte = ?
    ");
    $nuevos_defectos_query->bind_param("i", $reporte_id);
    $nuevos_defectos_query->execute();
    $nuevos_defectos_result = $nuevos_defectos_query->get_result();
    $nuevos_defectos_encontrados = [];
    while ($nd = $nuevos_defectos_result->fetch_assoc()) {
        $nuevos_defectos_encontrados[] = htmlspecialchars($nd['NombreDefecto']) . " (" . htmlspecialchars($nd['Cantidad']) . ")";
    }
    $reporte['NuevosDefectosEncontrados'] = empty($nuevos_defectos_encontrados) ? 'N/A' : implode("<br>", $nuevos_defectos_encontrados);
    $nuevos_defectos_query->close();

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
// --- FIN DE CAMBIO ---

$conex->close();

$totalPiezasInspeccionadasYa = 0;
foreach ($reportes_raw as $reporte) {
    $totalPiezasInspeccionadasYa += (int)$reporte['PiezasInspeccionadas'];
}

$mostrarVisorPDF = false;
if (isset($solicitud['EstatusAprobacion']) && $solicitud['EstatusAprobacion'] === 'Aprobado' && !empty($solicitud['RutaMetodo'])) {
    $mostrarVisorPDF = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inspección - ARCA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            flex-wrap: wrap; /* CORRECCIÓN: Permite que los elementos pasen a la siguiente línea */
            gap: 15px;       /* CORRECCIÓN: Añade espacio entre el logo y la info de usuario si se envuelven */
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
            flex-wrap: wrap; /* CORRECCIÓN: Permite que los elementos internos se envuelvan */
            justify-content: flex-end; /* CORRECCIÓN: Alinea a la derecha */
            gap: 15px; /* CORRECCIÓN: Espacio consistente entre elementos */
        }

        .user-info span {
            margin-right: 0; /* CORRECCIÓN: Eliminado para usar 'gap' */
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
        .form-container h1 { font-family: 'Montserrat', sans-serif; margin-top: 0; margin-bottom: 30px; font-size: 24px; display: flex; align-items: center; gap: 15px; }
        fieldset { border: none; padding: 0; margin-bottom: 25px; border-bottom: 1px solid #e0e0e0; padding-bottom: 25px; }
        fieldset:last-of-type { border-bottom: none; }
        legend { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 18px; color: var(--color-primario, #4a6984); margin-bottom: 20px; }
        legend i { margin-right: 10px; color: var(--color-acento); }
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; margin-bottom: 15px; min-width: 200px; }
        .form-group label { margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 16px; font-family: 'Lato', sans-serif; box-sizing: border-box; }
        .form-group-checkbox { display: flex; align-items: center; gap: 10px; }
        .hidden-section { display: none; }
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
        .defecto-item { border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 15px; background-color: #fafafa; }
        .defecto-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .defecto-header h4 { margin: 0; font-family: 'Montserrat', sans-serif; }
        .original-defect-list .form-group { border-bottom: 1px solid var(--color-borde); padding-bottom: 15px; margin-bottom: 15px; }
        .original-defect-list .form-group:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .original-defect-list .form-group label { font-weight: 700; color: var(--color-primario); }
        .piezas-rechazadas-info { font-size: 15px; margin-bottom: 20px; padding: 10px 15px; background-color: #eaf2f8; border-left: 5px solid var(--color-secundario); border-radius: 4px; }
        #tiempoMuertoSection { margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--color-borde); }
        .pdf-viewer-container { border: 1px solid var(--color-borde); border-radius: 8px; overflow: hidden; margin-top: 15px; }
        .form-row.defect-entry-row, .form-row.parte-inspeccionada-row { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 10px; }
        .form-row.defect-entry-row .form-group, .form-row.parte-inspeccionada-row .form-group { flex: 1 1 0; min-width: 0; margin-bottom: 0; }
        .btn-remove-batch, .btn-remove-parte { flex-shrink: 0; }
        #partes-inspeccionadas-container { margin-top: 15px; margin-bottom: 15px; }

        /* --- 5. Selector de Idioma --- */
        .language-selector { display: flex; align-items: center; gap: 5px; background-color: var(--color-fondo); padding: 4px; border-radius: 20px; margin-right: 0; /* CORRECCIÓN: Eliminado para usar 'gap' */ border: 1px solid var(--color-borde); }
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
        .btn-remove-defecto { background: none; border: none; color: var(--color-error); font-size: 24px; font-weight: bold; cursor: pointer; }
        .btn-small { padding: 6px 12px; font-size: 14px; border-radius: 4px; cursor: pointer; border: none; transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-danger { background-color: var(--color-error); color: var(--color-blanco); }
        .btn-danger:hover { background-color: #8c2a2a; transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); }
        .btn-danger:active { background-color: #7a2525; transform: translateY(0); box-shadow: none; }
        .btn-primary.disabled, .btn-secondary.disabled, button:disabled { opacity: 0.6; cursor: not-allowed; }
        button:focus { outline: 2px solid var(--color-acento); outline-offset: 2px; }

        /* --- 7. Subida de Archivos --- */
        .file-upload-label { border: 2px dashed var(--color-borde); border-radius: 6px; padding: 20px; display: flex; align-items: center; justify-content: center; flex-direction: column; cursor: pointer; transition: all 0.3s ease; text-align: center; color: #777; background-color: #fdfdfd; }
        .file-upload-label:hover { border-color: var(--color-secundario); background-color: #f7f9fc; color: var(--color-secundario); }
        .file-upload-label i { font-size: 28px; margin-bottom: 10px; }
        .file-upload-label span { font-weight: 600; font-size: 14px; }
        input[type="file"] { display: none; }

        /* --- 8. Tabla de Historial --- */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 20px; margin-bottom: 40px; border: 1px solid var(--color-borde); border-radius: 8px; box-shadow: var(--sombra-suave); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 800px; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        .data-table th { background-color: var(--color-primario); color: var(--color-blanco); font-weight: 600; text-transform: uppercase; }
        .data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tbody tr:hover { background-color: #f0f4f8; }
        .data-table td .btn-small { margin: 0 2px; }

        /* --- 9. Estilos Responsivos --- */
        @media (max-width: 992px) {
            .info-row p {
                flex-basis: calc(100% - 10px); /* Una columna en pantallas más pequeñas */
            }
        }

        @media (max-width: 768px) {
            /* CORRECCIÓN: Estilos responsivos para el Header */
            .header {
                flex-direction: column;
                align-items: center;
            }
            .user-info {
                flex-direction: column;
                justify-content: center;
                width: 100%;
            }
            /* --- Fin de la corrección --- */

            .container {
                padding: 15px;
            }
            .form-container {
                padding: 20px 25px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-row .form-group.w-50,
            .form-row .form-group.w-25 {
                flex-basis: 100%;
            }
            .data-table {
                font-size: 12px;
            }
            .data-table th,
            .data-table td {
                padding: 8px 10px;
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
        <h1><i class="fa-solid fa-hammer"></i> <span data-translate-key="main_title">Reporte de Inspección</span> - <span data-translate-key="folio">Folio</span> S-<?php echo str_pad($solicitud['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></h1>

        <div class="info-row">
            <p><strong data-translate-key="part_number">No. de Parte:</strong> <span><?php echo $numeroParte; ?></span></p>
            <p><strong data-translate-key="responsible">Responsable:</strong> <span><?php echo $nombreResponsable; ?></span></p>
            <p><strong data-translate-key="total_qty">Cantidad Total:</strong> <span id="cantidadTotalSolicitada"><?php echo $cantidadSolicitada; ?></span></p>
            <p><strong data-translate-key="defects">Defectos:</strong> <span><?php echo $nombresDefectosStr; ?></span></p>
        </div>

        <?php if ($mostrarVisorPDF): ?>
            <fieldset>
                <legend><i class="fa-solid fa-file-shield"></i> <span data-translate-key="approved_method_title">Método de Trabajo Aprobado</span></legend>
                <div class="form-actions" style="margin-bottom: 15px;">
                    <button type="button" id="togglePdfViewerBtn" class="btn-secondary"><i class="fa-solid fa-eye"></i> <span data-translate-key="view_method_btn">Ver Método de Trabajo</span></button>
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
            echo "<div class='notification-box warning'><i class='fa-solid fa-triangle-exclamation'></i> <strong data-translate-key='action_required'>Acción Requerida:</strong> <span data-translate-key='upload_method_prompt'>Para continuar, por favor, sube el método de trabajo para esta solicitud.</span></div>";
        } elseif ($solicitud['EstatusAprobacion'] === 'Rechazado') {
            echo "<div class='notification-box error'><i class='fa-solid fa-circle-xmark'></i> <strong data-translate-key='method_rejected'>Método Rechazado:</strong> <span data-translate-key='upload_corrected_version'>El método de trabajo anterior fue rechazado. Por favor, sube una versión corregida.</span></div>";
        } else {
            if ($solicitud['IdMetodo'] !== NULL && $solicitud['EstatusAprobacion'] === 'Pendiente') {
                echo "<div class='notification-box info'><i class='fa-solid fa-clock'></i> <strong data-translate-key='notice'>Aviso:</strong> <span data-translate-key='method_pending_approval'>El método de trabajo está pendiente de aprobación. Puedes continuar con el registro.</span></div>";
            }
            if ($solicitud['EstatusAprobacion'] === 'Aprobado' || $solicitud['EstatusAprobacion'] === 'Pendiente') {
                $mostrarFormularioPrincipal = true;
            }
        }
        ?>

        <?php if ($mostrarFormularioPrincipal): ?>

            <?php
            $formStyle = '';
            if ($totalPiezasInspeccionadasYa >= $cantidadSolicitada && $cantidadSolicitada > 0) {
                $formStyle = 'style="display: none;"';
                echo "<div id='mensajeInspeccionCompletada' class='notification-box info' style='margin-top: 20px; display: flex; align-items: flex-start; gap: 12px;'>
                        <i class='fa-solid fa-circle-check' style='font-size: 1.2em; margin-top: 2px;'></i>
                        <div>
                            <strong data-translate-key='inspection_complete_title'>Inspección Completada:</strong> <span data-translate-key='inspection_complete_desc'>Ya se ha registrado la cantidad total de piezas a inspeccionar ({$totalPiezasInspeccionadasYa} / {$cantidadSolicitada}).
                            <br>No se pueden crear nuevos reportes, pero puede <strong>editar o eliminar</strong> registros existentes si es necesario.</span>
                        </div>
                      </div>";
            }
            ?>
            <form id="reporteForm" action="dao/guardar_reporte.php" method="POST" enctype="multipart/form-data" <?php echo $formStyle; ?>>
                <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                <input type="hidden" name="idReporte" id="idReporte" value="">
                <fieldset>
                    <legend><i class="fa-solid fa-chart-simple"></i> <span data-translate-key="summary_title">Resumen de Inspección</span></legend>

                    <?php if ($isVariosPartes): ?>
                        <div id="desglose-partes-container">
                            <label data-translate-key="part_breakdown_label">Desglose de Piezas por No. de Parte</label>
                            <div id="partes-inspeccionadas-container"></div>
                            <button type="button" id="btn-add-parte-inspeccionada" class="btn-secondary btn-small"><i class="fa-solid fa-plus"></i> <span data-translate-key="add_part_number_btn">Añadir No. de Parte</span></button>
                        </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label data-translate-key="total_inspected">Total de Piezas Inspeccionadas</label>
                            <input type="number" name="piezasInspeccionadas" id="piezasInspeccionadas" min="0" required <?php if($isVariosPartes) echo 'readonly style="background-color: #e9ecef;"'; ?>>
                        </div>
                        <div class="form-group"><label data-translate-key="accepted_pieces">Piezas Aceptadas</label><input type="number" name="piezasAceptadas" id="piezasAceptadas" min="0" required></div>
                        <div class="form-group"><label data-translate-key="reworked_pieces">Piezas Retrabajadas</label><input type="number" name="piezasRetrabajadas" id="piezasRetrabajadas" min="0" value="0" required></div>
                        <div class="form-group"><label data-translate-key="rejected_pieces_calc">Piezas Rechazadas (Cálculo)</label><input type="text" id="piezasRechazadasCalculadas" value="0" readonly style="background-color: #e9ecef; cursor: not-allowed;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label data-translate-key="inspector_name">Nombre del Inspector</label><input type="text" name="nombreInspector" value="<?php echo htmlspecialchars($_SESSION['user_nombre']); ?>" required></div>
                        <div class="form-group"><label data-translate-key="inspection_date">Fecha de Inspección</label><input type="date" name="fechaInspeccion" required></div>
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

                <fieldset><legend><i class="fa-solid fa-clipboard-check"></i> <span data-translate-key="original_defects_title">Clasificación de Defectos Originales</span></legend>
                    <div class="original-defect-list">
                        <p class="piezas-rechazadas-info"><span data-translate-key="available_to_classify">Piezas rechazadas disponibles para clasificar:</span> <span id="piezasRechazadasRestantes" style="font-weight: bold; color: var(--color-error);">0</span></p>
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
                                            <div style="width: 42px; flex-shrink: 0;"></div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-add-batch btn-secondary btn-small" data-defecto-id="<?php echo $defecto['IdDefecto']; ?>"><i class="fa-solid fa-plus"></i> <span data-translate-key="add_lot_btn">Añadir Lote</span></button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p data-translate-key="no_original_defects">No hay defectos originales registrados en esta solicitud.</p>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-magnifying-glass-plus"></i> <span data-translate-key="new_defects_title">Nuevos Defectos Encontrados (Opcional)</span></legend>
                    <div id="nuevos-defectos-container"></div>
                    <button type="button" id="btn-add-nuevo-defecto" class="btn-secondary"><i class="fa-solid fa-plus"></i> <span data-translate-key="add_new_defect_btn">Añadir Nuevo Defecto</span></button>
                </fieldset>

                <fieldset><legend><i class="fa-solid fa-stopwatch"></i> <span data-translate-key="session_time_comments_title">Tiempos y Comentarios de la Sesión</span></legend>
                    <div class="form-group">
                        <label data-translate-key="inspection_time_session">Tiempo de Inspección (Esta Sesión)</label>
                        <input type="text" name="tiempoInspeccion" id="tiempoInspeccion" value="" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label data-translate-key="downtime_question">¿Hubo Tiempo Muerto?</label>
                        <button type="button" id="toggleTiempoMuertoBtn" class="btn-secondary" style="width: auto; padding: 10px 15px;"><span data-translate-key="no">No</span> <i class="fa-solid fa-toggle-off"></i></button>
                    </div>

                    <div id="tiempoMuertoSection" class="hidden-section">
                        <div class="form-group">
                            <label data-translate-key="downtime_reason">Razón de Tiempo Muerto</label>
                            <div class="select-with-button">
                                <select name="idTiempoMuerto" id="idTiempoMuerto">
                                    <option value="" data-translate-key="select_reason">Seleccione una razón</option>
                                    <?php mysqli_data_seek($razones_tiempo_muerto, 0); while($razon = $razones_tiempo_muerto->fetch_assoc()): ?>
                                        <option value="<?php echo $razon['IdTiempoMuerto']; ?>"><?php echo htmlspecialchars($razon['Razon']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($esSuperUsuario): ?><button type="button" class="btn-add" data-tipo="tiempomuerto" title="Añadir Razón">+</button><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group"><label data-translate-key="additional_comments">Comentarios Adicionales de la Sesión</label><textarea name="comentarios" id="comentarios" rows="4"></textarea></div>
                </fieldset>

                <div class="form-actions"><button type="submit" class="btn-primary" id="btnGuardarReporte"><span data-translate-key="save_session_report_btn">Guardar Reporte de Sesión</span></button></div>
            </form>


            <?php if (empty($solicitud['TiempoTotalInspeccion'])): ?>
                <form id="tiempoTotalForm" action="dao/finalizar_reporte.php" method="POST" style="margin-top: 40px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset><legend><i class="fa-solid fa-hourglass-end"></i> <span data-translate-key="finalize_containment_title">Finalizar Contención (Tiempo Total)</span></legend>
                        <p class="info-text"><span data-translate-key="total_time_registered_desc">El tiempo total de las sesiones ya registradas es de</span> <strong><?php echo $tiempoTotalFormateado; ?></strong>. <span data-translate-key="total_time_finalize_desc">Al finalizar, este será el valor guardado.</span></p>
                        <input type="hidden" name="tiempoTotalInspeccion" value="<?php echo $tiempoTotalFormateado; ?>">
                    </fieldset>
                    <div class="form-actions"><button type="submit" class="btn-primary"><span data-translate-key="finalize_containment_btn">Finalizar Contención y Guardar Tiempo Total</span></button></div>
                </form>
            <?php else: ?>
                <div class='notification-box info' style='margin-top: 40px;'><i class='fa-solid fa-circle-check'></i> <strong data-translate-key="containment_finalized_title">Contención Finalizada:</strong> <span data-translate-key="containment_finalized_desc">El tiempo total de inspección ya fue registrado (<?php echo htmlspecialchars($solicitud['TiempoTotalInspeccion']); ?>).</span></div>
            <?php endif; ?>

        <?php endif; ?>

        <?php if (!$mostrarFormularioPrincipal): ?>
            <?php if ($solicitud['IdMetodo'] === NULL): ?>
                <form id="metodoForm" action="https://grammermx.com/Mailer/upload_metodo.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset>
                        <legend><i class="fa-solid fa-paperclip"></i> <span data-translate-key="upload_method_title">Subir Método de Trabajo</span></legend>
                        <div class="form-group">
                            <label data-translate-key="method_name">Nombre del Método</label>
                            <input type="text" name="tituloMetodo" required>
                        </div>
                        <div class="form-group">
                            <label data-translate-key="pdf_file">Archivo PDF</label>
                            <label class="file-upload-label" for="metodoFile"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo..." data-translate-key="select_file">Seleccionar archivo...</span></label>
                            <input type="file" id="metodoFile" name="metodoFile" accept=".pdf" required>
                        </div>
                    </fieldset>
                    <div class="form-actions"><button type="button" id="btnSubirMetodo" class="btn-primary"><span data-translate-key="upload_and_notify_btn">Subir y Notificar</span></button></div>
                </form>
            <?php else: ?>
                <form id="metodoForm" action="https://grammermx.com/Mailer/resubir_metodo.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="hidden" name="idSolicitud" value="<?php echo $idSolicitud; ?>">
                    <fieldset>
                        <legend><i class="fa-solid fa-paperclip"></i> <span data-translate-key="correct_method_title">Corregir Método de Trabajo</span></legend>
                        <div class="form-group">
                            <label data-translate-key="pdf_file">Archivo PDF</label>
                            <label class="file-upload-label" for="metodoFile"><i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="Seleccionar archivo..." data-translate-key="select_file">Seleccionar archivo...</span></label>
                            <input type="file" id="metodoFile" name="metodoFile" accept=".pdf" required>
                        </div>
                    </fieldset>
                    <div class="form-actions"><button type="button" id="btnSubirMetodo" class="btn-primary"><span data-translate-key="upload_and_notify_btn">Subir y Notificar</span></button></div>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <hr style="margin-top: 40px; margin-bottom: 30px; border-color: var(--color-borde);">

        <h2 style="margin-top: 40px;"><i class="fa-solid fa-list-check"></i> <span data-translate-key="history_title">Historial de Registros de Inspección</span></h2>
        <?php if (count($reportes_procesados) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th data-translate-key="th_report_id">ID Reporte</th>
                        <th data-translate-key="th_part_number">No. de Parte</th>
                        <th data-translate-key="th_inspection_date">Fecha Inspección</th>
                        <th data-translate-key="th_time_range">Rango Hora</th>
                        <th data-translate-key="th_shift_leader">Turno Shift Leader</th>
                        <th data-translate-key="th_inspector">Inspector</th>
                        <th data-translate-key="th_inspected">Inspeccionadas</th>
                        <th data-translate-key="th_accepted">Aceptadas</th>
                        <th data-translate-key="th_rejected">Rechazadas</th>
                        <th data-translate-key="th_reworked">Retrabajadas</th>
                        <th data-translate-key="th_defects_qty">Defectos (Cant.)</th>
                        <th data-translate-key="th_new_defects_qty">Nuevos Defectos (Cant.)</th>
                        <th data-translate-key="th_lot_number">No. de Lote</th>
                        <th data-translate-key="th_downtime">Tiempo Muerto</th>
                        <th data-translate-key="th_comments">Comentarios</th>
                        <th data-translate-key="th_actions">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportes_procesados as $reporte): ?>
                        <tr>
                            <td><?php echo "R-" . str_pad($reporte['IdReporte'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo $reporte['NumeroParteParaMostrar']; ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($reporte['FechaInspeccion']))); ?></td>
                            <td><?php echo htmlspecialchars($reporte['RangoHora']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['TurnoShiftLeader']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['NombreInspector']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasInspeccionadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasAceptadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRechazadasCalculadas']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['PiezasRetrabajadas']); ?></td>
                            <td><?php echo $reporte['DefectosConCantidades']; ?></td>
                            <td><?php echo $reporte['NuevosDefectosEncontrados']; ?></td>
                            <td><?php echo $reporte['LotesEncontrados']; ?></td>
                            <td><?php echo htmlspecialchars($reporte['RazonTiempoMuerto'] ?? 'No'); ?></td>
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
            <p data-translate-key="no_history_records">Aún no hay registros de inspección para esta solicitud.</p>
        <?php endif; ?>

    </div>
</main>

<script>
    const opcionesDefectos = `<?php echo addslashes($defectos_options_html); ?>`;
    const defectosOriginalesMapa = <?php echo json_encode($defectos_originales_para_js); ?>;
    const cantidadTotalSolicitada = <?php echo intval($cantidadSolicitada); ?>;
    const totalPiezasInspeccionadasAnteriormente = <?php echo $totalPiezasInspeccionadasYa; ?>;
    const isVariosPartes = <?php echo json_encode($isVariosPartes); ?>;
    let nuevoDefectoCounter = 0;
    let editandoReporte = false;
    let valorOriginalInspeccionadoAlEditar = 0;


    document.addEventListener('DOMContentLoaded', function() {
        // --- INICIO: LÓGICA DE TRADUCCIÓN ---
        const translations = {
            es: {
                welcome: "Bienvenido",
                logout: "Cerrar Sesión",
                main_title: "Reporte de Inspección",
                folio: "Folio",
                part_number: "No. de Parte",
                responsible: "Responsable",
                total_qty: "Cantidad Total",
                defects: "Defectos",
                approved_method_title: "Método de Trabajo Aprobado",
                view_method_btn: "Ver Método de Trabajo",
                hide_method_btn: "Ocultar Método de Trabajo",
                action_required: "Acción Requerida:",
                upload_method_prompt: "Para continuar, por favor, sube el método de trabajo para esta solicitud.",
                method_rejected: "Método Rechazado:",
                upload_corrected_version: "El método de trabajo anterior fue rechazado. Por favor, sube una versión corregida.",
                notice: "Aviso:",
                method_pending_approval: "El método de trabajo está pendiente de aprobación. Puedes continuar con el registro.",
                inspection_complete_title: "Inspección Completada:",
                inspection_complete_desc: "Ya se ha registrado la cantidad total de piezas a inspeccionar. No se pueden crear nuevos reportes, pero puede editar o eliminar registros existentes si es necesario.",
                summary_title: "Resumen de Inspección",
                part_breakdown_label: "Desglose de Piezas por No. de Parte",
                add_part_number_btn: "Añadir No. de Parte",
                total_inspected: "Total de Piezas Inspeccionadas",
                accepted_pieces: "Piezas Aceptadas",
                reworked_pieces: "Piezas Retrabajadas",
                rejected_pieces_calc: "Piezas Rechazadas (Cálculo)",
                inspector_name: "Nombre del Inspector",
                inspection_date: "Fecha de Inspección",
                start_time: "Hora de Inicio",
                end_time: "Hora de Fin",
                original_defects_title: "Clasificación de Defectos Originales",
                available_to_classify: "Piezas rechazadas disponibles para clasificar:",
                add_lot_btn: "Añadir Lote",
                no_original_defects: "No hay defectos originales registrados en esta solicitud.",
                new_defects_title: "Nuevos Defectos Encontrados (Opcional)",
                add_new_defect_btn: "Añadir Nuevo Defecto",
                session_time_comments_title: "Tiempos y Comentarios de la Sesión",
                inspection_time_session: "Tiempo de Inspección (Esta Sesión)",
                downtime_question: "¿Hubo Tiempo Muerto?",
                downtime_reason: "Razón de Tiempo Muerto",
                select_reason: "Seleccione una razón",
                additional_comments: "Comentarios Adicionales de la Sesión",
                save_session_report_btn: "Guardar Reporte de Sesión",
                update_session_report_btn: "Actualizar Reporte de Sesión",
                cancel_edit_btn: "Cancelar Edición",
                finalize_containment_title: "Finalizar Contención (Tiempo Total)",
                total_time_registered_desc: "El tiempo total de las sesiones ya registradas es de",
                total_time_finalize_desc: "Al finalizar, este será el valor guardado.",
                finalize_containment_btn: "Finalizar Contención y Guardar Tiempo Total",
                containment_finalized_title: "Contención Finalizada:",
                containment_finalized_desc: "El tiempo total de inspección ya fue registrado.",
                upload_method_title: "Subir Método de Trabajo",
                method_name: "Nombre del Método",
                pdf_file: "Archivo PDF",
                select_file: "Seleccionar archivo...",
                upload_and_notify_btn: "Subir y Notificar",
                correct_method_title: "Corregir Método de Trabajo",
                history_title: "Historial de Registros de Inspección",
                th_report_id: "ID Reporte",
                th_part_number: "No. de Parte",
                th_inspection_date: "Fecha Inspección",
                th_time_range: "Rango Hora",
                th_shift_leader: "Turno Shift Leader",
                th_inspector: "Inspector",
                th_inspected: "Inspeccionadas",
                th_accepted: "Aceptadas",
                th_rejected: "Rechazadas",
                th_reworked: "Retrabajadas",
                th_defects_qty: "Defectos (Cant.)",
                th_new_defects_qty: "Nuevos Defectos (Cant.)",
                th_lot_number: "No. de Lote",
                th_downtime: "Tiempo Muerto",
                th_comments: "Comentarios",
                th_actions: "Acciones",
                no_history_records: "Aún no hay registros de inspección para esta solicitud.",
                yes: "Sí",
                no: "No",
                new_defect_header: "Nuevo Defecto",
                part_number_placeholder: "No. de Parte...",
                defect_type_label: "Tipo de Defecto",
                select_defect_option: "Seleccione un defecto",
                qty_label: "Cantidad de Piezas",
                qty_placeholder: "Cantidad con este defecto...",
                evidence_photo_label: "Foto de Evidencia (Opcional)",
                current_file_info: "Archivo actual:",
                view_photo: "Ver foto",
            },
            en: {
                welcome: "Welcome",
                logout: "Logout",
                main_title: "Inspection Report",
                folio: "Folio",
                part_number: "Part No.",
                responsible: "Responsible",
                total_qty: "Total Quantity",
                defects: "Defects",
                approved_method_title: "Approved Work Method",
                view_method_btn: "View Work Method",
                hide_method_btn: "Hide Work Method",
                action_required: "Action Required:",
                upload_method_prompt: "To continue, please upload the work method for this request.",
                method_rejected: "Method Rejected:",
                upload_corrected_version: "The previous work method was rejected. Please upload a corrected version.",
                notice: "Notice:",
                method_pending_approval: "The work method is pending approval. You can continue with the registration.",
                inspection_complete_title: "Inspection Completed:",
                inspection_complete_desc: "The total quantity of pieces to be inspected has already been registered. New reports cannot be created, but you can edit or delete existing records if necessary.",
                summary_title: "Inspection Summary",
                part_breakdown_label: "Piece Breakdown by Part No.",
                add_part_number_btn: "Add Part No.",
                total_inspected: "Total Inspected Pieces",
                accepted_pieces: "Accepted Pieces",
                reworked_pieces: "Reworked Pieces",
                rejected_pieces_calc: "Rejected Pieces (Calculated)",
                inspector_name: "Inspector's Name",
                inspection_date: "Inspection Date",
                start_time: "Start Time",
                end_time: "End Time",
                original_defects_title: "Original Defects Classification",
                available_to_classify: "Rejected pieces available for classification:",
                add_lot_btn: "Add Lot",
                no_original_defects: "There are no original defects registered for this request.",
                new_defects_title: "New Defects Found (Optional)",
                add_new_defect_btn: "Add New Defect",
                session_time_comments_title: "Session Times and Comments",
                inspection_time_session: "Inspection Time (This Session)",
                downtime_question: "Was there downtime?",
                downtime_reason: "Downtime Reason",
                select_reason: "Select a reason",
                additional_comments: "Additional Session Comments",
                save_session_report_btn: "Save Session Report",
                update_session_report_btn: "Update Session Report",
                cancel_edit_btn: "Cancel Edit",
                finalize_containment_title: "Finalize Containment (Total Time)",
                total_time_registered_desc: "The total time for the already registered sessions is",
                total_time_finalize_desc: "Upon finalization, this will be the saved value.",
                finalize_containment_btn: "Finalize Containment and Save Total Time",
                containment_finalized_title: "Containment Finalized:",
                containment_finalized_desc: "The total inspection time has already been recorded.",
                upload_method_title: "Upload Work Method",
                method_name: "Method Name",
                pdf_file: "PDF File",
                select_file: "Select file...",
                upload_and_notify_btn: "Upload and Notify",
                correct_method_title: "Correct Work Method",
                history_title: "Inspection Records History",
                th_report_id: "Report ID",
                th_part_number: "Part No.",
                th_inspection_date: "Inspection Date",
                th_time_range: "Time Range",
                th_shift_leader: "Shift Leader",
                th_inspector: "Inspector",
                th_inspected: "Inspected",
                th_accepted: "Accepted",
                th_rejected: "Rejected",
                th_reworked: "Reworked",
                th_defects_qty: "Defects (Qty.)",
                th_new_defects_qty: "New Defects (Qty.)",
                th_lot_number: "Lot No.",
                th_downtime: "Downtime",
                th_comments: "Comments",
                th_actions: "Actions",
                no_history_records: "There are no inspection records for this request yet.",
                yes: "Yes",
                no: "No",
                new_defect_header: "New Defect",
                part_number_placeholder: "Part No....",
                defect_type_label: "Defect Type",
                select_defect_option: "Select a defect",
                qty_label: "Quantity of Pieces",
                qty_placeholder: "Quantity with this defect...",
                evidence_photo_label: "Evidence Photo (Optional)",
                current_file_info: "Current file:",
                view_photo: "View photo",
            }
        };

        function setLanguage(lang) {
            document.documentElement.lang = lang;
            localStorage.setItem('language', lang);
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.getAttribute('data-translate-key');
                if (translations[lang] && translations[lang][key]) {
                    el.innerText = translations[lang][key];
                }
            });
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.lang === lang));

            // Update dynamic text if needed
            const togglePdfBtn = document.getElementById('togglePdfViewerBtn');
            const pdfWrapper = document.getElementById('pdfViewerWrapper');
            if(togglePdfBtn && pdfWrapper){
                const isHidden = pdfWrapper.style.display === 'none';
                togglePdfBtn.querySelector('span').innerText = isHidden ? translate('view_method_btn') : translate('hide_method_btn');
            }
        }

        function getCurrentLanguage() { return localStorage.getItem('language') || 'es'; }
        function translate(key) { const lang = getCurrentLanguage(); return translations[lang][key] || key; }

        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                setLanguage(btn.dataset.lang);
            });
        });

        // --- FIN: LÓGICA DE TRADUCCIÓN ---


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
            const desgloseContainer = document.getElementById('partes-inspeccionadas-container');

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
                    if (horas > 0) {
                        tiempoStr += `${horas} hora(s) `;
                    }
                    if (minutos > 0 || horas === 0) {
                        tiempoStr += `${minutos} minuto(s)`;
                    }

                    tiempoInspeccionInput.value = tiempoStr.trim();

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

                const piezasDeOtrosReportes = editandoReporte ? (totalPiezasInspeccionadasAnteriormente - valorOriginalInspeccionadoAlEditar) : totalPiezasInspeccionadasAnteriormente;
                const maximoPermitidoParaEsteReporte = cantidadTotalSolicitada - piezasDeOtrosReportes;
                const esCantidadInvalida = inspeccionadas > maximoPermitidoParaEsteReporte;

                if (esCantidadInvalida) {
                    piezasInspeccionadasInput.setCustomValidity(`La cantidad máxima para este reporte es ${Math.max(0, maximoPermitidoParaEsteReporte)} para no exceder el total de ${cantidadTotalSolicitada}.`);
                    piezasInspeccionadasInput.reportValidity();
                } else {
                    piezasInspeccionadasInput.setCustomValidity('');
                }

                const rechazadasBrutas = inspeccionadas - aceptadas;
                piezasRechazadasCalculadasInput.value = Math.max(0, rechazadasBrutas);
                const esRetrabajoInvalido = retrabajadas > rechazadasBrutas;

                if (esRetrabajoInvalido) {
                    piezasRetrabajadasInput.setCustomValidity('Las piezas retrabajadas no pueden exceder las piezas rechazadas.');
                    piezasRetrabajadasInput.reportValidity();
                } else {
                    piezasRetrabajadasInput.setCustomValidity('');
                }

                const rechazadasDisponibles = rechazadasBrutas - retrabajadas;
                let sumDefectosClasificados = 0;
                defectosOriginalesContainer.querySelectorAll('.defecto-cantidad').forEach(input => { sumDefectosClasificados += parseInt(input.value) || 0; });
                nuevosDefectosContainer.querySelectorAll('.nuevo-defecto-cantidad').forEach(input => { sumDefectosClasificados += parseInt(input.value) || 0; });
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

                if (esCantidadInvalida) {
                    deshabilitar = true;
                    titulo = 'La cantidad inspeccionada excede el total solicitado acumulado.';
                } else if (esRetrabajoInvalido) {
                    deshabilitar = true;
                    titulo = 'Las piezas retrabajadas no pueden exceder las piezas rechazadas.';
                } else if (sonDefectosInvalidos) {
                    deshabilitar = true;
                    titulo = restantes > 0 ? 'Aún faltan piezas por clasificar.' : 'La suma de defectos no puede exceder las piezas rechazadas disponibles.';
                }

                btnGuardarReporte.disabled = deshabilitar;
                btnGuardarReporte.title = titulo;
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

            function addNuevoDefectoBlock(id = null, idDefectoCatalogo = '', cantidad = '', rutaFoto = '', parte = '') {
                nuevoDefectoCounter++;
                const currentCounter = nuevoDefectoCounter;

                const parteInputHtml = isVariosPartes ? `
                    <div class="form-group">
                        <label data-translate-key="th_part_number">Número de Parte</label>
                        <input type="text" name="nuevos_defectos[${currentCounter}][parte]" placeholder="${translate('part_number_placeholder')}" required>
                    </div>` : '';

                const defectoHTML = `
                <div class="defecto-item" id="nuevo-defecto-${currentCounter}">
                    <div class="defecto-header">
                        <h4><span data-translate-key="new_defect_header">Nuevo Defecto</span> #${currentCounter}</h4>
                        <button type="button" class="btn-remove-defecto" data-defecto-id="${currentCounter}">&times;</button>
                    </div>
                    ${parteInputHtml}
                    <div class="form-row">
                        <div class="form-group w-50">
                            <label data-translate-key="defect_type_label">Tipo de Defecto</label>
                            <select name="nuevos_defectos[${currentCounter}][id]" required>
                                <option value="" disabled selected>${translate('select_defect_option')}</option>
                                ${opcionesDefectos}
                            </select>
                        </div>
                        <div class="form-group w-50">
                            <label data-translate-key="qty_label">Cantidad de Piezas</label>
                            <input type="number" class="nuevo-defecto-cantidad" name="nuevos_defectos[${currentCounter}][cantidad]" placeholder="${translate('qty_placeholder')}" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label data-translate-key="evidence_photo_label">Foto de Evidencia (Opcional)</label>
                        <label class="file-upload-label" for="nuevoDefectoFoto-${currentCounter}">
                            <i class="fa-solid fa-cloud-arrow-up"></i><span data-default-text="${translate('select_file')}" data-translate-key="select_file">${translate('select_file')}</span>
                        </label>
                        <input type="file" id="nuevoDefectoFoto-${currentCounter}" name="nuevos_defectos[${currentCounter}][foto]" accept="image/*">
                        ${rutaFoto ? `<p class="current-file-info"><span data-translate-key="current_file_info">Archivo actual:</span> <a href="${rutaFoto}" target="_blank" data-translate-key="view_photo">Ver foto</a> (Se reemplazará si subes uno nuevo)</p>
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
                if (isVariosPartes) {
                    const parteInput = newBlock.querySelector(`input[name="nuevos_defectos[${currentCounter}][parte]"]`);
                    if (parteInput) {
                        parteInput.value = parte || '';
                    }
                }
                document.getElementById(`nuevoDefectoFoto-${currentCounter}`).addEventListener('change', updateFileNameLabel);
                setLanguage(getCurrentLanguage());
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
                    toggleTiempoMuertoBtn.innerHTML = `<span data-translate-key="yes">Sí</span> <i class="fa-solid fa-toggle-on"></i>`;
                    toggleTiempoMuertoBtn.className = 'btn-primary';
                } else {
                    tiempoMuertoSection.style.display = 'none';
                    toggleTiempoMuertoBtn.innerHTML = `<span data-translate-key="no">No</span> <i class="fa-solid fa-toggle-off"></i>`;
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
                const tiempoTotalForm = this;
                const reporteForm = document.getElementById('reporteForm');
                const piezasInspeccionadasInput = document.getElementById('piezasInspeccionadas');
                let finalFormData;

                if (piezasInspeccionadasInput && piezasInspeccionadasInput.value && reporteForm.style.display !== 'none') {
                    if (btnGuardarReporte.disabled) {
                        Swal.fire('Error de Validación', 'El formulario de reporte de sesión tiene errores. Por favor, corríjalos antes de finalizar. Motivo: ' + btnGuardarReporte.title, 'error');
                        return;
                    }
                    finalFormData = new FormData(reporteForm);
                } else {
                    finalFormData = new FormData(tiempoTotalForm);
                }

                const tiempoTotalInput = tiempoTotalForm.querySelector('[name="tiempoTotalInspeccion"]');
                if (!finalFormData.has('tiempoTotalInspeccion')) {
                    finalFormData.append('tiempoTotalInspeccion', tiempoTotalInput.value);
                }
                if (!finalFormData.has('idSolicitud')) {
                    const idSolicitudInput = tiempoTotalForm.querySelector('[name="idSolicitud"]');
                    finalFormData.append('idSolicitud', idSolicitudInput.value);
                }

                Swal.fire({ title: 'Finalizando Contención...', text: 'Por favor, espera.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                fetch(tiempoTotalForm.action, { method: 'POST', body: finalFormData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('¡Éxito!', data.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor para finalizar.', 'error'));
            });


            async function cargarReporteParaEdicion(idReporte) {
                const reporteForm = document.getElementById('reporteForm');
                const mensajeCompletado = document.getElementById('mensajeInspeccionCompletada');
                if (reporteForm) reporteForm.style.display = 'block';
                if (mensajeCompletado) mensajeCompletado.style.display = 'none';

                Swal.fire({ title: 'Cargando Reporte...', text: 'Obteniendo datos para edición.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                try {
                    const response = await fetch(`dao/obtener_reporte_para_edicion.php?idReporte=${idReporte}`);
                    const data = await response.json();

                    if (data.status === 'success') {
                        const reporte = data.reporte;
                        const defectosOriginales = data.defectosOriginales;
                        const nuevosDefectos = data.nuevosDefectos;
                        const desglosePartes = data.desglosePartes;

                        valorOriginalInspeccionadoAlEditar = parseInt(reporte.PiezasInspeccionadas, 10) || 0;

                        idReporteInput.value = reporte.IdReporte;
                        piezasInspeccionadasInput.value = reporte.PiezasInspeccionadas;
                        piezasAceptadasInput.value = reporte.PiezasAceptadas;
                        piezasRetrabajadasInput.value = reporte.PiezasRetrabajadas;
                        fechaInspeccionInput.value = reporte.FechaInspeccion;
                        comentariosTextarea.value = reporte.Comentarios || '';

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
                            if (startStr) horaInicioInput.value = convertTo24Hour(startStr);
                            if (endStr) horaFinInput.value = convertTo24Hour(endStr);
                        } else {
                            horaInicioInput.value = '';
                            horaFinInput.value = '';
                        }

                        calcularYActualizarTiempo();

                        if (reporte.IdTiempoMuerto) {
                            toggleTiempoMuertoSection(true);
                            idTiempoMuertoSelect.value = reporte.IdTiempoMuerto;
                        } else {
                            toggleTiempoMuertoSection(false);
                        }

                        if (isVariosPartes && desgloseContainer) {
                            desgloseContainer.innerHTML = '';
                            parteCounter = 0;
                            if (desglosePartes && desglosePartes.length > 0) {
                                desglosePartes.forEach(parte => {
                                    addParteRow(parte.NumeroParte, parte.Cantidad);
                                });
                            } else {
                                addParteRow();
                            }
                        }

                        defectosOriginalesContainer.querySelectorAll('.defect-entries-container').forEach(container => {
                            const rows = container.querySelectorAll('.defect-entry-row');
                            for (let i = 1; i < rows.length; i++) rows[i].remove();
                            const firstRow = rows[0];
                            if (firstRow) {
                                firstRow.querySelectorAll('input').forEach(input => input.value = '');
                                firstRow.querySelector('.defecto-cantidad').value = 0;
                            }
                        });

                        if (defectosOriginales && defectosOriginales.length > 0) {
                            const defectosAgrupados = defectosOriginales.reduce((acc, def) => {
                                acc[def.IdDefecto] = acc[def.IdDefecto] || [];
                                acc[def.IdDefecto].push(def);
                                return acc;
                            }, {});

                            for (const idDefecto in defectosAgrupados) {
                                const entradas = defectosAgrupados[idDefecto];
                                const container = defectosOriginalesContainer.querySelector(`.form-group[data-id-defecto-original="${idDefecto}"] .defect-entries-container`);
                                if (container && entradas.length > 0) {
                                    const primeraEntrada = entradas.shift();
                                    const firstRow = container.querySelector('.defect-entry-row');
                                    firstRow.querySelector('.defecto-cantidad').value = primeraEntrada.CantidadEncontrada;
                                    firstRow.querySelector('.defecto-lote').value = primeraEntrada.Lote || '';
                                    if (isVariosPartes) firstRow.querySelector('.defecto-parte').value = primeraEntrada.NumeroParte || '';

                                    entradas.forEach(entrada => {
                                        const addBtn = container.nextElementSibling;
                                        if (addBtn) addBtn.click();
                                        const newRow = container.querySelector('.defect-entry-row:last-child');
                                        if (newRow) {
                                            newRow.querySelector('.defecto-cantidad').value = entrada.CantidadEncontrada;
                                            newRow.querySelector('.defecto-lote').value = entrada.Lote || '';
                                            if (isVariosPartes) newRow.querySelector('.defecto-parte').value = entrada.NumeroParte || '';
                                        }
                                    });
                                }
                            }
                        }

                        nuevosDefectosContainer.innerHTML = '';
                        nuevoDefectoCounter = 0;
                        if (nuevosDefectos && nuevosDefectos.length > 0) {
                            nuevosDefectos.forEach(defecto => {
                                addNuevoDefectoBlock(
                                    defecto.IdDefectoEncontrado,
                                    defecto.IdDefectoCatalogo,
                                    defecto.Cantidad,
                                    defecto.RutaFotoEvidencia,
                                    defecto.NumeroParte
                                );
                            });
                        }

                        editandoReporte = true;
                        actualizarContadores();

                        const btnGuardarReporte = document.getElementById('btnGuardarReporte');
                        btnGuardarReporte.querySelector('span').innerText = translate('update_session_report_btn');
                        reporteForm.action = 'dao/actualizar_reporte.php';

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
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    console.error("Error al cargar reporte:", error);
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

            const addParteBtn = document.getElementById('btn-add-parte-inspeccionada');

            if(isVariosPartes && desgloseContainer && addParteBtn) {
                let parteCounter = 0;

                function addParteRow(parte = '', cantidad = '') {
                    const newIndex = parteCounter++;
                    const newRowHtml = `
                        <div class="form-row parte-inspeccionada-row">
                            <div class="form-group">
                                <input type="text" name="partes_inspeccionadas[${newIndex}][parte]" placeholder="${translate('part_number_placeholder')}" required value="${parte}">
                            </div>
                            <div class="form-group">
                                <input type="number" name="partes_inspeccionadas[${newIndex}][cantidad]" class="cantidad-parte-inspeccionada" placeholder="Cantidad..." min="0" required value="${cantidad}">
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

                    piezasInspeccionadasInput.value = total;
                    piezasInspeccionadasInput.dispatchEvent(new Event('input'));
                }

                addParteBtn.addEventListener('click', () => addParteRow());

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
            const defaultText = labelSpan.dataset.defaultText || translate('select_file');
            if (e.target.files.length > 0) {
                labelSpan.textContent = e.target.files[0].name;
            } else {
                labelSpan.textContent = defaultText;
            }
        }
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', updateFileNameLabel);
        });

        const togglePdfBtn = document.getElementById('togglePdfViewerBtn');
        const pdfWrapper = document.getElementById('pdfViewerWrapper');

        if (togglePdfBtn && pdfWrapper) {
            togglePdfBtn.addEventListener('click', function() {
                const isHidden = pdfWrapper.style.display === 'none';
                if (isHidden) {
                    pdfWrapper.style.display = 'block';
                    this.querySelector('span').innerText = translate('hide_method_btn');
                    this.querySelector('i').className = 'fa-solid fa-eye-slash';
                    this.classList.remove('btn-secondary');
                    this.classList.add('btn-primary');
                } else {
                    pdfWrapper.style.display = 'none';
                    this.querySelector('span').innerText = translate('view_method_btn');
                    this.querySelector('i').className = 'fa-solid fa-eye';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-secondary');
                }
            });
        }

        const originalDefectList = document.querySelector('.original-defect-list');

        if (originalDefectList) {
            let batchCounters = {};

            originalDefectList.addEventListener('click', function(e) {
                const addBtn = e.target.closest('.btn-add-batch');
                if (addBtn) {
                    const defectoId = addBtn.dataset.defectoId;
                    const container = addBtn.previousElementSibling;

                    if (batchCounters[defectoId] === undefined) {
                        batchCounters[defectoId] = container.querySelectorAll('.form-row').length;
                    }

                    const newIndex = batchCounters[defectoId];
                    batchCounters[defectoId]++;

                    const parteInputHtml = isVariosPartes ? `
                        <div class="form-group">
                            <input type="text" class="defecto-parte" name="defectos_originales[${defectoId}][entries][${newIndex}][parte]" placeholder="${translate('part_number_placeholder')}" required>
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

                const removeBtn = e.target.closest('.btn-remove-batch');
                if (removeBtn) {
                    removeBtn.parentElement.remove();
                    actualizarContadores();
                }
            });
        }

        // Carga inicial del idioma
        setLanguage(getCurrentLanguage());
    });
</script>
</body>
</html>

