<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los parámetros enviados desde JavaScript
    $data = json_decode(file_get_contents("php://input"), true);
    $searchId = isset($data['searchId']) ? $data['searchId'] : '';
    $nave = isset($data['nave']) ? $data['nave'] : '';
    $reportCount = isset($data['reportCount']) ? $data['reportCount'] : 5; // Default to 5 if not set

    // Obtener los reportes desde la base de datos
    $response = obtenerReportes($searchId, $nave, $reportCount);
    echo json_encode($response);
    exit();
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido. Solo se permite POST.');
    echo json_encode($response);
    exit();
}

// Función para obtener los reportes (filtrados por ID y Nave, con paginación)
function obtenerReportes($searchId, $nave, $reportCount) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL base para obtener los reportes
    $sql = "
        SELECT 
            r.IdReporte, 
            u.Nombre AS NombreUsuario, 
            a.NombreArea AS Area, 
            r.Ubicacion, 
            r.FechaRegistro, 
            r.DescripcionProblema, 
            r.FechaFinalizado,
            r.DescripcionLugar, 
            e.NombreEstatus AS Estatus
        FROM 
            Reportes r
        JOIN 
            Usuario u ON r.NumNomina = u.NumNomina
        JOIN 
            Area a ON r.IdArea = a.IdArea
        JOIN 
            Estatus e ON r.IdEstatus = e.IdEstatus
        WHERE 1=1"; // Esto es para agregar condiciones de filtrado

    // Filtrar por ID de reporte
    if ($searchId != '') {
        $sql .= " AND r.IdReporte = ?";
    }

    // Filtrar por Nave (utilizando 'Ubicacion' en lugar de 'NombreArea')
    if ($nave != '') {
        $sql .= " AND r.Ubicacion = ?";  // Aquí cambiamos 'a.NombreArea' por 'r.Ubicacion'
    }

    // Paginación
    $sql .= " ORDER BY r.FechaRegistro DESC LIMIT ?";

    // Preparar y ejecutar la consulta
    $stmt = $conex->prepare($sql);

    // Determinar los parámetros para bind_param
    if ($searchId != '' && $nave != '') {
        // Si ambos parámetros están presentes, el orden debe ser: searchId, nave, reportCount
        $stmt->bind_param("iss", $searchId, $nave, $reportCount);
    } elseif ($searchId != '') {
        // Si solo se filtra por searchId
        $stmt->bind_param("si", $searchId, $reportCount);
    } elseif ($nave != '') {
        // Si solo se filtra por nave
        $stmt->bind_param("ss", $nave, $reportCount);
    } else {
        // Si no se filtra por nada, solo limitamos la cantidad de reportes
        $stmt->bind_param("i", $reportCount);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Crear un array para almacenar los reportes
    $reportes = array();

    // Verificar si hay resultados
    if ($result->num_rows > 0) {
        while ($reporte = $result->fetch_assoc()) {
            $reportes[] = $reporte;
        }
        $response = array('status' => 'success', 'data' => $reportes);
    } else {
        $response = array('status' => 'error', 'message' => 'No se encontraron reportes.');
    }

    // Cerrar la conexión
    $stmt->close();
    $conex->close();

    return $response;
}
