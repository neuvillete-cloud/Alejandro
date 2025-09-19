<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Acceso denegado o datos inválidos.'];

if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1 && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    if (!empty($nombre)) {
        $con = new LocalConector();
        $conex = $con->conectar();

        $stmt_check = $conex->prepare("SELECT IdLugar FROM Lugares WHERE NombreLugar = ?");
        $stmt_check->bind_param("s", $nombre);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $response['message'] = 'Error: Este lugar ya existe.';
        } else {
            $stmt_insert = $conex->prepare("INSERT INTO Lugares (NombreLugar) VALUES (?)");
            $stmt_insert->bind_param("s", $nombre);
            if ($stmt_insert->execute()) {
                $new_id = $stmt_insert->insert_id;
                $response = ['status' => 'success', 'message' => 'Lugar añadido exitosamente.', 'data' => ['id' => $new_id, 'nombre' => $nombre]];
            } else {
                $response['message'] = 'Error al guardar en la base de datos.';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
        $conex->close();
    }
}
echo json_encode($response);
?>
