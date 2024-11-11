<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

if (isset($_GET['id'])) {
    $reporteId = $_GET['id'];

    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL para obtener el reporte específico incluyendo `FotoProblema`
    $stmt = $conex->prepare("
        SELECT 
            r.IdReporte, 
            u.Nombre AS NombreUsuario, 
            a.NombreArea AS Area, 
            r.Ubicacion, 
            r.FechaRegistro, 
            r.DescripcionProblema, 
            r.DescripcionLugar, 
            e.NombreEstatus AS Estatus,
            r.FotoProblema
        FROM 
            Reportes r
        JOIN 
            Usuario u ON r.NumNomina = u.NumNomina
        JOIN 
            Area a ON r.IdArea = a.IdArea
        JOIN 
            Estatus e ON r.IdEstatus = e.IdEstatus
        WHERE 
            r.IdReporte = ?
    ");

    $stmt->bind_param('i', $reporteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reporte = $result->fetch_assoc();
        $reporte['FotoProblemaURL'] = 'ruta_a_tus_imagenes/' . $reporte['FotoProblema'];  // Ajusta la ruta según tu estructura de archivos

        $response = array('status' => 'success', 'reporte' => $reporte);
    } else {
        $response = array('status' => 'error', 'message' => 'Reporte no encontrado');
    }

    $stmt->close();
    $conex->close();
} else {
    $response = array('status' => 'error', 'message' => 'ID de reporte no especificado');
}

echo json_encode($response);
?>
