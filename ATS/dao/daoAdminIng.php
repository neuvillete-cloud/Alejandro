<?php
session_start();
include_once("ConexionBD.php");

// Verificamos que el usuario haya iniciado sesión y tengamos su número de nómina
if (!isset($_SESSION['NumNomina'])) {
    // Si no hay sesión, devolvemos un error y terminamos la ejecución
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Acceso denegado. Se requiere iniciar sesión.'
    ]);
    exit;
}

// Obtenemos el número de nómina del aprobador que ha iniciado sesión
$numNominaAprobador = $_SESSION['NumNomina'];

header('Content-Type: application/json');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- LA NUEVA CONSULTA SQL INTELIGENTE ---
    // Esta consulta busca solicitudes que están en estado 1 (Pendiente) o 4 (Aprobado Parcialmente)
    // Y donde el usuario actual es uno de los aprobadores designados Y su voto aún está pendiente (es NULL).
    $sql = "SELECT 
                s.*, 
                a.NombreArea, 
                e.NombreEstatus 
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            WHERE 
                (s.IdEstatus = 1 OR s.IdEstatus = 4)
                AND 
                (
                    (s.IdAprobador1 = ? AND s.Aprobacion1 IS NULL)
                    OR
                    (s.IdAprobador2 = ? AND s.Aprobacion2 IS NULL)
                )";

    $stmt = $conex->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conex->error);
    }

    // Ligamos el número de nómina del aprobador a los dos placeholders (?) de la consulta
    // 's' indica que el tipo de dato es un string (cadena de texto)
    $stmt->bind_param("ss", $numNominaAprobador, $numNominaAprobador);

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conex->close();

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
