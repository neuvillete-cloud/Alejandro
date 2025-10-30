<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php"); // Asume que está en la misma carpeta dao/
include_once("conexionArca.php");    // Asume que está en la misma carpeta dao/
header('Content-Type: application/json');

// --- CONFIGURACIÓN DE BASE URL y RUTA ROOT (Ajusta si es necesario) ---
$baseUrl = "https://grammermx.com/AleTest/ARCA/";
$projectRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/AleTest/ARCA/';

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    // 1. Validar y obtener datos del formulario
    if (!isset($_POST['idSLReporte']) || empty($_POST['idSLReporte']) || !isset($_POST['idSafeLaunch'])) {
        throw new Exception("Faltan datos obligatorios (ID de Reporte o ID de Safe Launch) para actualizar.");
    }

    // --- CORRECCIÓN AQUÍ ---
    // El error estaba aquí. Debe ser 'idSLReporte' (con 'e' minúscula)
    $idSLReporte = intval($_POST['idSLReporte']);
    // --- FIN CORRECCIÓN ---

    $idSafeLaunch = intval($_POST['idSafeLaunch']);

    // Obtener los demás datos del formulario
    $nombreInspector = isset($_POST['nombreInspector']) ? trim($_POST['nombreInspector']) : '';
    $fechaInspeccion = isset($_POST['fechaInspeccion']) ? trim($_POST['fechaInspeccion']) : '';
    $rangoHora = isset($_POST['rangoHoraCompleto']) ? trim($_POST['rangoHoraCompleto']) : '';
    $piezasInspeccionadas = isset($_POST['piezasInspeccionadas']) ? intval($_POST['piezasInspeccionadas']) : 0;
    $piezasAceptadas = isset($_POST['piezasAceptadas']) ? intval($_POST['piezasAceptadas']) : 0;
    $piezasRetrabajadas = isset($_POST['piezasRetrabajadas']) ? intval($_POST['piezasRetrabajadas']) : 0;
    $tiempoInspeccion = isset($_POST['tiempoInspeccion']) ? trim($_POST['tiempoInspeccion']) : '';
    $comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

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

    // 2. Actualizar el registro principal en SafeLaunchReportesInspeccion
    $stmt_reporte = $conex->prepare("UPDATE SafeLaunchReportesInspeccion SET
                                        NombreInspector = ?, FechaInspeccion = ?, RangoHora = ?,
                                        PiezasInspeccionadas = ?, PiezasAceptadas = ?, PiezasRetrabajadas = ?,
                                        TiempoInspeccion = ?, Comentarios = ?
                                    WHERE IdSLReporte = ?");
    $stmt_reporte->bind_param("sssiisssi",
        $nombreInspector, $fechaInspeccion, $rangoHora,
        $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas,
        $tiempoInspeccion, $comentarios,
        $idSLReporte);

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al actualizar el reporte principal de Safe Launch: " . $stmt_reporte->error);
    }
    $stmt_reporte->close();

    // 3. Borrar los defectos antiguos (de la cuadrícula)
    $stmt_delete_defectos = $conex->prepare("DELETE FROM SafeLaunchReporteDefectos WHERE IdSLReporte = ?");
    $stmt_delete_defectos->bind_param("i", $idSLReporte);
    if (!$stmt_delete_defectos->execute()) {
        throw new Exception("Error al limpiar los defectos de cuadrícula antiguos: " . $stmt_delete_defectos->error);
    }
    $stmt_delete_defectos->close();

    // 4. Borrar los defectos opcionales antiguos (nuevos)
    $stmt_delete_nuevos = $conex->prepare("DELETE FROM SafeLaunchNuevosDefectos WHERE IdSLReporte = ?");
    $stmt_delete_nuevos->bind_param("i", $idSLReporte);
    if (!$stmt_delete_nuevos->execute()) {
        throw new Exception("Error al limpiar los defectos opcionales antiguos: " . $stmt_delete_nuevos->error);
    }
    $stmt_delete_nuevos->close();

    // 5. Manejar defectos marcados para eliminación (por si acaso, aunque la lógica de arriba ya borra todo)
    if (isset($_POST['defectos_sl_a_eliminar']) && is_array($_POST['defectos_sl_a_eliminar'])) {
        // Esta lógica ya no es estrictamente necesaria porque borramos todo,
        // pero la dejamos por si se cambia la estrategia a futuro.
        // No hace nada activamente si ya borramos todo.
    }

    // 6. Insertar los detalles de defectos (cuadrícula)
    $totalDefectosClasificados = 0;
    if (isset($_POST['defectos']) && is_array($_POST['defectos'])) {
        foreach ($_POST['defectos'] as $idDefectoCatalogo => $defectData) {
            $cantidad = isset($defectData['cantidad']) ? intval($defectData['cantidad']) : 0;
            if ($cantidad > 0) {
                $lote = isset($defectData['lote']) ? trim($defectData['lote']) : null;
                $idDefectoCatalogo = intval($idDefectoCatalogo);

                $stmt_defecto = $conex->prepare("INSERT INTO SafeLaunchReporteDefectos (IdSLReporte, IdSLDefectoCatalogo, CantidadEncontrada, BachLote) VALUES (?, ?, ?, ?)");
                $stmt_defecto->bind_param("iiis", $idSLReporte, $idDefectoCatalogo, $cantidad, $lote);
                if (!$stmt_defecto->execute()) {
                    throw new Exception("Error al re-insertar defecto de cuadrícula #{$idDefectoCatalogo}: " . $stmt_defecto->error);
                }
                $stmt_defecto->close();
                $totalDefectosClasificados += $cantidad;
            }
        }
    }

    // 7. Insertar los detalles de defectos (nuevos/opcionales)
    if (isset($_POST['nuevos_defectos_sl']) && is_array($_POST['nuevos_defectos_sl'])) {
        foreach ($_POST['nuevos_defectos_sl'] as $tempId => $defectoData) {
            // Solo procesar si no está marcado para eliminación (aunque ya borramos todo, esto es doble seguridad)
            if (isset($defectoData['idDefectoEncontrado']) && in_array($defectoData['idDefectoEncontrado'], $_POST['defectos_sl_a_eliminar'] ?? [])) {
                continue; // Saltar este defecto, fue marcado para borrar
            }

            $cantidad = isset($defectoData['cantidad']) ? intval($defectoData['cantidad']) : 0;
            if ($cantidad > 0) {
                $idDefectoCatalogo = intval($defectoData['id']);

                $stmt_nuevo_defecto = $conex->prepare("INSERT INTO SafeLaunchNuevosDefectos (IdSLReporte, IdSLDefectoCatalogo, Cantidad) VALUES (?, ?, ?)");
                $stmt_nuevo_defecto->bind_param("iii", $idSLReporte, $idDefectoCatalogo, $cantidad);
                if (!$stmt_nuevo_defecto->execute()) {
                    throw new Exception("Error al re-insertar nuevo defecto #{$idDefectoCatalogo}: " . $stmt_nuevo_defecto->error);
                }
                $stmt_nuevo_defecto->close();
                $totalDefectosClasificados += $cantidad;
            }
        }
    }

    // 8. Validación final
    $rechazadasDisponibles = $piezasRechazadasBrutas - $piezasRetrabajadas;
    if ($totalDefectosClasificados != $rechazadasDisponibles) {
        throw new Exception("Error de validación al actualizar: La suma de todos los defectos ({$totalDefectosClasificados}) no coincide con las piezas rechazadas disponibles ({$rechazadasDisponibles}).");
    }

    $conex->commit(); // Confirmar transacción si todo fue exitoso
    echo json_encode(['status' => 'success', 'message' => 'Reporte Safe Launch actualizado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback(); // Revertir cambios si hubo algún error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close(); // Siempre cerrar la conexión
    }
}
?>
