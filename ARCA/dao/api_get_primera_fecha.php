<?php
header('Content-Type: application/json');
include_once("conexionArca.php");

// Verificamos que se haya enviado un IdSolicitud
if (!isset($_GET['idSolicitud']) || empty($_GET['idSolicitud'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó IdSolicitud']);
    exit;
}

$idSolicitud = (int)$_GET['idSolicitud'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    /*
    * IMPORTANTE: ¡Debes ajustar esta consulta!
    * No sé exactamente cómo se llaman tu tabla y columna de inspecciones.
    *
    * Basado en tu JavaScript, parece que iteras sobre "dia.entradas".
    * Supongo que tienes una tabla como `EntradasHoras` o `RegistrosInspeccion`.
    * Y en esa tabla, una columna de fecha como `Fecha` o `FechaInspeccion`.
    *
    * AJUSTA LA SIGUIENTE LÍNEA con tus nombres reales:
    * - `EntradasHoras` -> El nombre de tu tabla de registros de inspección.
    * - `Fecha` -> El nombre de tu columna de fecha (si es DATETIME, el MIN(DATE(Fecha)) la convertirá a solo fecha).
    */

    // Consulta actualizada con los nombres de tu tabla (ReportesInspeccion) y columna (FechaInspeccion)
    // Como 'FechaInspeccion' ya es de tipo DATE, no necesitamos la función DATE().
    $query = "SELECT MIN(FechaInspeccion) AS primeraFecha FROM ReportesInspeccion WHERE IdSolicitud = ?";

    $stmt = $conex->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conex->error);
    }

    $stmt->bind_param("i", $idSolicitud);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();

    $stmt->close();
    $conex->close();

    if ($fila && $fila['primeraFecha']) {
        // Encontramos la fecha, la devolvemos
        echo json_encode(['status' => 'success', 'primeraFecha' => $fila['primeraFecha']]);
    } else {
        // No se encontraron registros para esa solicitud
        echo json_encode(['status' => 'not_found', 'message' => 'No se encontraron registros de inspección.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

