<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

if (isset($_SESSION['NumNomina'])) {
    $numNomina = $_SESSION['NumNomina'];

    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL para obtener las solicitudes del usuario
    $sql = "
        SELECT 
            s.id AS ID,
            s.fecha AS Fecha,
            s.estado AS Estado,
            s.descripcion AS Descripcion,
            u.Nombre AS Usuario
        FROM 
            Solicitudes s
        JOIN 
            Usuario u ON s.usuario = u.NumNomina
        WHERE 
            s.usuario = ?
    ";

    $stmt = $conex->prepare($sql);

    // Vincular parámetros y ejecutar la consulta
    $stmt->bind_param('s', $numNomina);
    $stmt->execute();

    // Obtener los resultados
    $result = $stmt->get_result();
    $solicitudes = [];

    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    if (!empty($solicitudes)) {
        $response = [
            'status' => 'success',
            'data' => $solicitudes
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'No se encontraron solicitudes'
        ];
    }

    // Cerrar recursos
    $stmt->close();
    $conex->close();
} else {
    $response = [
        'status' => 'error',
        'message' => 'Número de nómina no especificado en la sesión'
    ];
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
?>
