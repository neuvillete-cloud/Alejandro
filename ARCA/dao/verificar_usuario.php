<?php
header('Content-Type: application/json');
include_once("conexionArca.php");

// La respuesta base
$response = array(
    'exists' => false,
    'suggestions' => []
);

if (isset($_GET['nombreUsuario'])) {
    $nombreUsuario = trim($_GET['nombreUsuario']);

    if (!empty($nombreUsuario)) {
        $con = new ConexionBD();
        $conex = $con->conectar();

        // Usamos sentencias preparadas para seguridad
        $stmt = $conex->prepare("SELECT IdUsuario FROM Usuarios WHERE NombreUsuario = ?");
        $stmt->bind_param("s", $nombreUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            // Si el usuario existe, preparamos la respuesta
            $response['exists'] = true;

            // Generamos 3 sugerencias simples
            $response['suggestions'][] = $nombreUsuario . rand(10, 99);
            $response['suggestions'][] = $nombreUsuario . date('y'); // ej: usuario25
            $response['suggestions'][] = str_replace('a', 'o', $nombreUsuario) . '1'; // ej: usuorio1
        }

        $stmt->close();
        $conex->close();
    }
}

echo json_encode($response);
?>
