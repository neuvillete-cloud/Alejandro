<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

if (isset($_SESSION['NumNomina'])) {
    $numNomina = $_SESSION['NumNomina'];

    // Crear una conexión usando la clase `LocalConector`
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta SQL para obtener los datos del perfil
    $sql = "
        SELECT 
            u.Nombre AS Nombre, 
            u.NumNomina, 
            a.NombreArea AS Area
        FROM 
            Usuario u
        JOIN 
            Area a ON u.IdArea = a.IdArea
        WHERE 
            u.NumNomina = ?
    ";

    $stmt = $conex->prepare($sql);

    // Vincular parámetros y ejecutar la consulta
    $stmt->bind_param('s', $numNomina);
    $stmt->execute();

    // Obtener los resultados
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $perfil = $result->fetch_assoc();
        $response = [
            'status' => 'success',
            'perfil' => $perfil
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
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
