<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Datos inválidos o insuficientes.'];

if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1 && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        $con = new LocalConector();
        $conex = $con->conectar();

        // 1. Verifica si el commodity ya existe
        $stmt_check = $conex->prepare("SELECT IdCommodity FROM Commodity WHERE NombreCommodity = ?");
        $stmt_check->bind_param("s", $nombre);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result();

        if ($resultado->num_rows > 0) {
            $response['message'] = 'Error: El commodity "' . htmlspecialchars($nombre) . '" ya existe.';
        } else {
            // 2. Si no existe, lo inserta
            $stmt_insert = $conex->prepare("INSERT INTO Commodity (NombreCommodity) VALUES (?)");
            $stmt_insert->bind_param("s", $nombre);

            if ($stmt_insert->execute()) {
                $new_id = $stmt_insert->insert_id;
                $response = [
                    'status' => 'success',
                    'message' => 'Commodity añadido exitosamente.',
                    'data' => ['id' => $new_id, 'nombre' => $nombre]
                ];
            } else {
                $response['message'] = 'Error al guardar en la base de datos.';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
        $conex->close();
    }
} else {
    $response['message'] = 'Acceso denegado o datos incompletos.';
}

echo json_encode($response);
?>
