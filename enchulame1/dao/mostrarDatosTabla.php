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
            r.FechaCompromiso,
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
        $sql .= " AND r.Ubicacion = ?";
    }

    // Agregar LIMIT si reportCount no es 0
    if ($reportCount > 0) {
        $sql .= " ORDER BY r.IdReporte ASC LIMIT ?";
    } else {
        $sql .= " ORDER BY r.IdReporte ASC"; // Sin LIMIT
    }

    // Preparar y ejecutar la consulta
    $stmt = $conex->prepare($sql);

    // Determinar los parámetros para bind_param
    if ($searchId != '' && $nave != '') {
        if ($reportCount > 0) {
            $stmt->bind_param("isi", $searchId, $nave, $reportCount);
        } else {
            $stmt->bind_param("is", $searchId, $nave);
        }
    } elseif ($searchId != '') {
        if ($reportCount > 0) {
            $stmt->bind_param("ii", $searchId, $reportCount);
        } else {
            $stmt->bind_param("i", $searchId);
        }
    } elseif ($nave != '') {
        if ($reportCount > 0) {
            $stmt->bind_param("si", $nave, $reportCount);
        } else {
            $stmt->bind_param("s", $nave);
        }
    } else {
        if ($reportCount > 0) {
            $stmt->bind_param("i", $reportCount);
        }
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

