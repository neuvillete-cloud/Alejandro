<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['NumNomina'])) {
        $response = array('status' => 'error', 'message' => 'Usuario no autenticado.');
        echo json_encode($response);
        exit();
    }

    // Obtener la nómina del usuario autenticado
    $NumNomina = $_SESSION['NumNomina'];

    // Obtener los reportes desde la base de datos
    $response = obtenerReportes($NumNomina);
    echo json_encode($response);
    exit();
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido. Solo se permite POST.');
    echo json_encode($response);
    exit();
}

// Función para obtener los reportes del usuario
function obtenerReportes($NumNomina) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener los reportes y la información del usuario
    $stmt = $conex->prepare("
        SELECT 
            r.IdReporte, 
            u.Nombre AS Usuario, 
            a.NombreArea AS Area, 
            r.Ubicacion, 
            r.FechaRegistro, 
            r.DescripcionProblema, 
            r.DescripcionLugar, 
            r.FotoProblema, 
            r.FotoEvidencia, 
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
            r.NumNomina = ?
        ORDER BY 
            r.FechaRegistro DESC
    ");

    $stmt->bind_param("s", $NumNomina);
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
