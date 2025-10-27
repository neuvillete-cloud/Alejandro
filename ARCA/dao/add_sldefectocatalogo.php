<?php
include_once("verificar_sesion.php");
include_once("conexionArca.php");
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Acceso denegado o datos inválidos.'];

// Solo el SuperUsuario (rol 1) puede añadir al catálogo
if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1 && isset($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        $con = new LocalConector();
        $conex = $con->conectar();

        // 1. CAMBIO: Verificar en la nueva tabla 'SafeLaunchCatalogoDefectos' y el nuevo ID
        $stmt_check = $conex->prepare("SELECT IdSLDefectoCatalogo FROM SafeLaunchCatalogoDefectos WHERE NombreDefecto = ?");
        $stmt_check->bind_param("s", $nombre);
        $stmt_check->execute();

        if ($stmt_check->get_result()->num_rows > 0) {
            $response['message'] = 'Error: Este tipo de defecto ya existe en el catálogo de Safe Launch.';
        } else {
            // 2. CAMBIO: Insertar en la nueva tabla 'SafeLaunchCatalogoDefectos'
            $stmt_insert = $conex->prepare("INSERT INTO SafeLaunchCatalogoDefectos (NombreDefecto) VALUES (?)");
            $stmt_insert->bind_param("s", $nombre);

            if ($stmt_insert->execute()) {
                $new_id = $stmt_insert->insert_id; // Esto obtiene el nuevo 'IdSLDefectoCatalogo'
                $response = ['status' => 'success', 'message' => 'Defecto añadido al catálogo de Safe Launch.', 'data' => ['id' => $new_id, 'nombre' => $nombre]];
            } else {
                $response['message'] = 'Error al guardar en la base de datos.';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
        $conex->close();
    } else {
        $response['message'] = 'El nombre no puede estar vacío.';
    }
}

echo json_encode($response);
?>
