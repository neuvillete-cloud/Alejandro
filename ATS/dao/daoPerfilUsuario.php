<?php
session_start();
include_once("ConexionBD.php"); // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

// Verificamos que el usuario haya iniciado sesión
if (!isset($_SESSION['NumNomina'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado.']);
    exit;
}
$numNomina = $_SESSION['NumNomina'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener los datos del usuario y los nombres de Rol y Área
    $sql = "
        SELECT 
            u.NumNomina,
            u.Nombre,
            u.Correo,
            r.NombreRol,  -- Obtenemos el nombre del Rol
            a.NombreArea  -- Obtenemos el nombre del Área
        FROM 
            Usuario u
        LEFT JOIN 
            Rol r ON u.IdRol = r.IdRol
        LEFT JOIN 
            Area a ON u.IdArea = a.IdArea
        WHERE 
            u.NumNomina = ?;
    ";

    $stmt = $conex->prepare($sql);
    $stmt->bind_param("s", $numNomina);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $usuario]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró el perfil del usuario.']);
    }

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $e->getMessage()]);
}
?>
