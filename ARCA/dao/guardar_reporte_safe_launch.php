<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php"); // Asume que está en la misma carpeta dao/
include_once("conexionArca.php");    // Asume que está en la misma carpeta dao/
header('Content-Type: application/json');

// Validar que sea POST y que el usuario esté logueado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['loggedin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Obtener datos del formulario principal (SafeLaunchReportesInspeccion)
    if (!isset($_POST['idSafeLaunch'])) {
        throw new Exception("ID de Safe Launch no proporcionado.");
    }
    $idSafeLaunch = intval($_POST['idSafeLaunch']);

    // No necesitamos verificar 'isVariosPartes' para Safe Launch

    $nombreInspector = isset($_POST['nombreInspector']) ? trim($_POST['nombreInspector']) : '';
    $fechaInspeccion = isset($_POST['fechaInspeccion']) ? trim($_POST['fechaInspeccion']) : '';
    $rangoHora = isset($_POST['rangoHoraCompleto']) ? trim($_POST['rangoHoraCompleto']) : '';
    $piezasInspeccionadas = isset($_POST['piezasInspeccionadas']) ? intval($_POST['piezasInspeccionadas']) : 0;
    $piezasAceptadas = isset($_POST['piezasAceptadas']) ? intval($_POST['piezasAceptadas']) : 0;
    $piezasRetrabajadas = isset($_POST['piezasRetrabajadas']) ? intval($_POST['piezasRetrabajadas']) : 0;
    $tiempoInspeccion = isset($_POST['tiempoInspeccion']) ? trim($_POST['tiempoInspeccion']) : '';
    $comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';
    // $idTiempoMuerto ya no se usa

    // Validaciones básicas
    if (empty($nombreInspector) || empty($fechaInspeccion) || empty($rangoHora) || $piezasInspeccionadas < 0 || $piezasAceptadas < 0 || $piezasRetrabajadas < 0) {
        throw new Exception("Por favor, complete todos los campos requeridos y asegúrese de que las cantidades sean válidas.");
    }
    if ($piezasAceptadas > $piezasInspeccionadas) {
        throw new Exception("Las piezas aceptadas no pueden ser mayores que las piezas inspeccionadas.");
    }
    $piezasRechazadasBrutas = $piezasInspeccionadas - $piezasAceptadas;
    if ($piezasRetrabajadas > $piezasRechazadasBrutas) {
        throw new Exception("Las piezas retrabajadas no pueden exceder las piezas rechazadas (" . $piezasRechazadasBrutas . ").");
    }

    // 2. Insertar en SafeLaunchReportesInspeccion
    $stmt_reporte = $conex->prepare("INSERT INTO SafeLaunchReportesInspeccion (
                                        IdSafeLaunch, NombreInspector, FechaInspeccion, RangoHora,
                                        PiezasInspeccionadas, PiezasAceptadas, PiezasRetrabajadas,
                                        TiempoInspeccion, Comentarios
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // Ajustar bind_param: removido el tipo 'i' de IdTiempoMuerto
    $stmt_reporte->bind_param("isssiiiss",
        $idSafeLaunch, $nombreInspector, $fechaInspeccion, $rangoHora,
        $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
        $tiempoInspeccion, $comentarios);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al guardar el reporte de inspección Safe Launch: " . $stmt_reporte->error);
    }
    $lastIdSLReporte = $stmt_reporte->insert_id; // Obtener el ID del nuevo reporte
    $stmt_reporte->close();

    // Lógica para actualizar estatus de Solicitud eliminada (no aplica a SL)
    // Lógica para Desglose de Partes eliminada (no aplica a SL)

    // 3. Procesar Clasificación de Defectos (de la cuadrícula)
    $totalDefectosClasificados = 0; // Se inicializa aquí
    if (isset($_POST['defectos']) && is_array($_POST['defectos'])) {
        foreach ($_POST['defectos'] as $idDefectoCatalogo => $defectData) {
            $cantidad = isset($defectData['cantidad']) ? intval($defectData['cantidad']) : 0;
            if ($cantidad > 0) {
                $lote = isset($defectData['lote']) ? trim($defectData['lote']) : null;
                $idDefectoCatalogo = intval($idDefectoCatalogo); // Asegurar que es entero

                $stmt_defecto = $conex->prepare("INSERT INTO SafeLaunchReporteDefectos (IdSLReporte, IdSLDefectoCatalogo, CantidadEncontrada, BachLote) VALUES (?, ?, ?, ?)");
                $stmt_defecto->bind_param("iiis", $lastIdSLReporte, $idDefectoCatalogo, $cantidad, $lote);
                if (!$stmt_defecto->execute()) {
                    throw new Exception("Error al guardar el defecto del catálogo #{$idDefectoCatalogo}: " . $stmt_defecto->error);
                }
                $stmt_defecto->close();
                $totalDefectosClasificados += $cantidad; // Sumar a la cuenta total
            }
        }
    }

    // --- INICIO NUEVO: Procesar Nuevos Defectos Encontrados ---
    // Asegúrate de tener una tabla 'SafeLaunchNuevosDefectos' similar a 'DefectosEncontrados' pero sin la columna de foto.
    // Columnas asumidas: IdSLNuevoDefecto (PK, AutoInc), IdSLReporte (FK), IdSLDefectoCatalogo (FK), Cantidad
    if (isset($_POST['nuevos_defectos_sl']) && is_array($_POST['nuevos_defectos_sl'])) {
        foreach ($_POST['nuevos_defectos_sl'] as $tempId => $defectoData) {
            // tempId es el contador del frontend (1, 2, 3...)
            // id es el IdSLDefectoCatalogo
            // cantidad es la cantidad ingresada
            $cantidad = isset($defectoData['cantidad']) ? intval($defectoData['cantidad']) : 0;

            if ($cantidad > 0) {
                $idDefectoCatalogo = isset($defectoData['id']) ? intval($defectoData['id']) : 0;

                // Validar que se haya seleccionado un tipo de defecto
                if ($idDefectoCatalogo <= 0) {
                    throw new Exception("Se ingresó cantidad para un nuevo defecto (#{$tempId}) pero no se seleccionó el tipo de defecto.");
                }

                // Insertar en la tabla de nuevos defectos para Safe Launch
                // ¡¡¡ASEGÚRATE DE QUE LA TABLA SafeLaunchNuevosDefectos EXISTA!!!
                $stmt_nuevo_defecto = $conex->prepare("INSERT INTO SafeLaunchNuevosDefectos (IdSLReporte, IdSLDefectoCatalogo, Cantidad) VALUES (?, ?, ?)");
                // El bind_param debe coincidir con las columnas de tu tabla
                $stmt_nuevo_defecto->bind_param("iii", $lastIdSLReporte, $idDefectoCatalogo, $cantidad);

                if (!$stmt_nuevo_defecto->execute()) {
                    throw new Exception("Error al guardar el nuevo defecto encontrado (#{$tempId}) en la base de datos: " . $stmt_nuevo_defecto->error);
                }
                $stmt_nuevo_defecto->close();
                $totalDefectosClasificados += $cantidad; // Sumar también estos al total
            }
        }
    }
    // --- FIN NUEVO ---


    // --- VALIDACIÓN FINAL MODIFICADA ---
    // La suma de defectos (cuadrícula + nuevos) debe coincidir con las rechazadas disponibles
    $rechazadasDisponibles = $piezasRechazadasBrutas - $piezasRetrabajadas;
    if ($totalDefectosClasificados != $rechazadasDisponibles) {
        throw new Exception("Error de validación: La suma total de defectos clasificados ({$totalDefectosClasificados}) no coincide con las piezas rechazadas disponibles para clasificar ({$rechazadasDisponibles}).");
    }

    $conex->commit(); // Confirmar transacción si todo fue exitoso
    echo json_encode(['status' => 'success', 'message' => 'Reporte de inspección Safe Launch guardado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback(); // Revertir cambios si hubo algún error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
}
?>
