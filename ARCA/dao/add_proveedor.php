<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php"); // Asegúrate de que la ruta sea correcta
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Datos inválidos o insuficientes.'];

// Solo los Super Usuarios (rol 1) pueden ejecutar esto
if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1 && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        $con = new LocalConector();
        $conex = $con->conectar();

        // --- INICIA NUEVA LÓGICA DE VERIFICACIÓN ---

        // 1. Preparamos una consulta para buscar si el proveedor ya existe
        $stmt_check = $conex->prepare("SELECT IdProvedor FROM Provedores WHERE NombreProvedor = ?");
        $stmt_check->bind_param("s", $nombre);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result();

        // 2. Si la consulta devuelve una o más filas, significa que ya existe
        if ($resultado->num_rows > 0) {
            $response['message'] = 'Error: El proveedor "' . htmlspecialchars($nombre) . '" ya existe en la base de datos.';
        } else {
            // --- FIN NUEVA LÓGICA --- (Si no existe, procedemos con la inserción)

            $stmt_insert = $conex->prepare("INSERT INTO Provedores (NombreProvedor) VALUES (?)");
            $stmt_insert->bind_param("s", $nombre);

            if ($stmt_insert->execute()) {
                $new_id = $stmt_insert->insert_id;
                $response = [
                    'status' => 'success',
                    'message' => 'Proveedor añadido exitosamente.',
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