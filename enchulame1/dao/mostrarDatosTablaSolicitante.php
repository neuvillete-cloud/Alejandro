<?php
session_start(); // Iniciar sesión

// Verificar si la sesión está iniciada
if (!isset($_SESSION['NumNomina']) || empty($_SESSION['NumNomina'])) {
    // Redirigir al usuario a la página de inicio de sesión si no está autenticado
    header("Location: login.php");
    exit;
}

include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar que la sesión contenga el NumNomina
    if (!isset($_SESSION['NumNomina']) || empty($_SESSION['NumNomina'])) {
        $response = array('status' => 'error', 'message' => 'Usuario no autenticado.');
        echo json_encode($response);
        exit;
    }

    // Verificar el encabezado HTTP_ORIGIN o REFERER para asegurar que la solicitud provenga de tu dominio
    if (!isset($_SERVER['HTTP_ORIGIN']) || $_SERVER['HTTP_ORIGIN'] != 'https://grammermx.com/AleTest/enchulame1/reportes.php') {
        $response = array('status' => 'error', 'message' => 'Origen no autorizado.');
        echo json_encode($response);
        exit;
    }

    // Verificar contenido JSON válido
    $data = json_decode(file_get_contents("php://input"), true);
    if ($data === null) {
        $response = array('status' => 'error', 'message' => 'Datos inválidos.');
        echo json_encode($response);
        exit;
    }

    // Procesar la solicitud como lo tienes actualmente
    $numNomina = $_SESSION['NumNomina'];
    $nave = isset($data['nave']) ? $data['nave'] : '';
    $reportCount = isset($data['reportCount']) ? $data['reportCount'] : 5;

    $response = obtenerReportes($numNomina, $nave, $reportCount);
    echo json_encode($response);
    exit;
}
 else {
    $response = array('status' => 'error', 'message' => 'Método no permitido. Solo se permite POST.');
    echo json_encode($response);
    exit();
}

// Función para obtener los reportes (filtrados por NumNomina, Nave y con paginación)
function obtenerReportes($numNomina, $nave, $reportCount) {
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
        WHERE 
            r.NumNomina = ?"; // Filtrar por NumNomina

    // Filtrar por Nave (si se proporciona)
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
    if ($nave != '') {
        if ($reportCount > 0) {
            $stmt->bind_param("ssi", $numNomina, $nave, $reportCount);
        } else {
            $stmt->bind_param("ss", $numNomina, $nave);
        }
    } else {
        if ($reportCount > 0) {
            $stmt->bind_param("si", $numNomina, $reportCount);
        } else {
            $stmt->bind_param("s", $numNomina);
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
?>
