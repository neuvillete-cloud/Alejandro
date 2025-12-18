<?php
// --- MODO DE DEPURACIÓN (eliminar en producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN MODO DE DEPURACIÓN ---

include_once("verificar_sesion.php");
include_once("conexionArca.php");
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
    // 1. Obtener datos del formulario principal
    if (!isset($_POST['idCeroDefectos'])) {
        throw new Exception("ID de Cero Defectos no proporcionado.");
    }
    $idCeroDefectos = intval($_POST['idCeroDefectos']);

    $nombreInspector = isset($_POST['nombreInspector']) ? trim($_POST['nombreInspector']) : '';
    $fechaInspeccion = isset($_POST['fechaInspeccion']) ? trim($_POST['fechaInspeccion']) : '';
    $turno = isset($_POST['turno']) ? trim($_POST['turno']) : '';

    // Nuevos campos de conteo
    $piezasProducidas = isset($_POST['piezasProducidas']) ? intval($_POST['piezasProducidas']) : 0;
    $piezasAceptadas = isset($_POST['piezasAceptadas']) ? intval($_POST['piezasAceptadas']) : 0;
    $comentarios = isset($_POST['comentarios']) ? trim($_POST['comentarios']) : '';

    // Validaciones básicas
    if (empty($nombreInspector) || empty($fechaInspeccion) || empty($turno)) {
        throw new Exception("Por favor, complete todos los campos obligatorios (Inspector, Fecha, Turno).");
    }
    if ($piezasProducidas < 0 || $piezasAceptadas < 0) {
        throw new Exception("Las cantidades de piezas no pueden ser negativas.");
    }
    if ($piezasAceptadas > $piezasProducidas) {
        throw new Exception("Las piezas aceptadas ({$piezasAceptadas}) no pueden ser mayores que las producidas ({$piezasProducidas}).");
    }

    // Cálculo de rechazadas (Simple: Producidas - Aceptadas)
    $piezasRechazadasCalculadas = $piezasProducidas - $piezasAceptadas;


    // 1b. Verificar si este es el primer reporte para este registro (para cambiar estatus)
    $stmt_check_count = $conex->prepare("SELECT COUNT(IdCDReporte) as count FROM CeroDefectosReportesInspeccion WHERE IdCeroDefectos = ?");
    $stmt_check_count->bind_param("i", $idCeroDefectos);
    $stmt_check_count->execute();
    $result_count = $stmt_check_count->get_result();
    $reportCount = $result_count->fetch_assoc()['count'];
    $stmt_check_count->close();

    $esPrimerReporte = ($reportCount == 0);


    // 2. Insertar en CeroDefectosReportesInspeccion
    // Nota: Eliminamos PiezasRetrabajadas, RangoHora, TiempoInspeccion. Agregamos Turno.
    $stmt_reporte = $conex->prepare("INSERT INTO CeroDefectosReportesInspeccion (
                                        IdCeroDefectos, NombreInspector, FechaInspeccion, Turno,
                                        PiezasProducidas, PiezasAceptadas, Comentarios
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt_reporte->bind_param("isssiis",
        $idCeroDefectos, $nombreInspector, $fechaInspeccion, $turno,
        $piezasProducidas, $piezasAceptadas, $comentarios
    );

    if (!$stmt_reporte->execute()) {
        throw new Exception("Error al guardar el reporte de Cero Defectos: " . $stmt_reporte->error);
    }
    $lastIdCDReporte = $stmt_reporte->insert_id; // ID del nuevo reporte
    $stmt_reporte->close();


    // 2b. Si fue el primer reporte, actualizar el estatus de la solicitud principal a 'En Proceso' (IdEstatus = 3)
    if ($esPrimerReporte) {
        $stmt_update_status = $conex->prepare("UPDATE CeroDefectosSolicitudes SET IdEstatus = 3 WHERE IdCeroDefectos = ?");
        $stmt_update_status->bind_param("i", $idCeroDefectos);
        if (!$stmt_update_status->execute()) {
            // No detenemos el flujo, pero idealmente se registra el error
        }
        $stmt_update_status->close();
    }


    // 3. Procesar Defectos Registrados (Tabla Dinámica)
    $totalDefectosRegistrados = 0;

    if (isset($_POST['defectos']) && is_array($_POST['defectos'])) {
        $stmt_defecto = $conex->prepare("INSERT INTO CeroDefectosReporteDefectos 
            (IdCDReporte, IdDefectoCatalogo, Cantidad, Prioridad, EncontradoEn, Severidad) 
            VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($_POST['defectos'] as $indice => $defectoData) {
            // Validar que vengan los datos mínimos
            if (empty($defectoData['id']) || empty($defectoData['cantidad'])) {
                continue; // Saltar filas vacías o incompletas
            }

            $idDefectoCatalogo = intval($defectoData['id']);
            $cantidad = intval($defectoData['cantidad']);
            $prioridad = isset($defectoData['prioridad']) ? $defectoData['prioridad'] : null;
            $encontradoEn = isset($defectoData['encontrado']) ? $defectoData['encontrado'] : null;
            $severidad = isset($defectoData['severidad']) ? $defectoData['severidad'] : null;

            if ($cantidad > 0) {
                $stmt_defecto->bind_param("iiisss",
                    $lastIdCDReporte, $idDefectoCatalogo, $cantidad,
                    $prioridad, $encontradoEn, $severidad
                );

                if (!$stmt_defecto->execute()) {
                    throw new Exception("Error al guardar detalle de defecto: " . $stmt_defecto->error);
                }
                $totalDefectosRegistrados += $cantidad;
            }
        }
        $stmt_defecto->close();
    }

    // 4. VALIDACIÓN FINAL DE CANTIDADES
    // En Cero Defectos, asumimos que cada pieza rechazada tiene al menos un defecto.
    // La suma de cantidades de defectos debe coincidir con las piezas rechazadas.
    if ($totalDefectosRegistrados != $piezasRechazadasCalculadas) {
        throw new Exception("Error de validación: La suma de defectos registrados ({$totalDefectosRegistrados}) no coincide con las piezas rechazadas calculadas ({$piezasRechazadasCalculadas}).");
    }

    $conex->commit(); // Confirmar transacción
    echo json_encode(['status' => 'success', 'message' => 'Reporte de Cero Defectos guardado exitosamente.']);

} catch (Exception $e) {
    $conex->rollback(); // Revertir cambios si hubo error
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}
?>
