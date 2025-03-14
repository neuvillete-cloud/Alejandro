
<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

try {
    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener los folios que SOLO tengan estatus 1 (Aprobado)
    $sql = "
        SELECT FolioSolicitud
        FROM Aprobadores
        GROUP BY FolioSolicitud
        HAVING COUNT(DISTINCT IdEstatus) = 1 AND MAX(IdEstatus) = 4
    ";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $foliosAprobados = [];

    while ($row = $result->fetch_assoc()) {
        $foliosAprobados[] = $row['FolioSolicitud'];
    }

    $stmt->close();

    $solicitudes = [];

    if (!empty($foliosAprobados)) {
        // Construcción de placeholders dinámicos para la consulta segura
        $placeholders = implode(',', array_fill(0, count($foliosAprobados), '?'));

        // Consulta para obtener las solicitudes aprobadas con NombreArea
        $sqlSolicitudes = "
            SELECT s.*, a.NombreArea
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            WHERE s.FolioSolicitud IN ($placeholders)
        ";

        $stmtSolicitudes = $conex->prepare($sqlSolicitudes);
        if (!$stmtSolicitudes) {
            throw new Exception("Error en la preparación de la consulta: " . $conex->error);
        }

        // Crear array con los tipos de datos (s = string)
        $types = str_repeat('s', count($foliosAprobados));
        $stmtSolicitudes->bind_param($types, ...$foliosAprobados);
        $stmtSolicitudes->execute();
        $resultSolicitudes = $stmtSolicitudes->get_result();

        while ($row = $resultSolicitudes->fetch_assoc()) {
            $solicitudes[] = $row;
        }

        $stmtSolicitudes->close();
    }

    $conex->close();

    // Devolver respuesta JSON
    echo json_encode([
        'status' => 'success',
        'data' => $solicitudes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la consulta: ' . $e->getMessage()
    ]);
}
?>
