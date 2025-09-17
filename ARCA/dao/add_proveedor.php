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

        $stmt = $conex->prepare("INSERT INTO Provedores (NombreProvedor) VALUES (?)");
        $stmt->bind_param("s", $nombre);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $response = [
                'status' => 'success',
                'message' => 'Proveedor añadido exitosamente.',
                'data' => ['id' => $new_id, 'nombre' => $nombre]
            ];
        } else {
            $response['message'] = 'Error al guardar en la base de datos.';
        }
        $stmt->close();
        $conex->close();
    }
} else {
    $response['message'] = 'Acceso denegado o datos incompletos.';
}
echo json_encode($response);
?>
