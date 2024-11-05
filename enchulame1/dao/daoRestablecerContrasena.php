<?php
include_once('conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numNomina = $_POST['numNomina'];
    $token = $_POST['token'];
    $nuevaContrasena = $_POST['nuevaContrasena'];

    // Valida el token y la fecha de expiración
    $con = new LocalConector();
    $conexion = $con->conectar();

    if (!$conexion) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la conexión a la base de datos.']);
        exit();
    }

    // Verificar si el token es válido y no ha expirado
    $stmt = $conexion->prepare("SELECT * FROM restablecerContrasena WHERE NumNomina = ? AND Token = ? AND Expira > NOW() AND TokenValido = 1");
    $stmt->bind_param('ss', $numNomina, $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Actualiza la contraseña en la tabla Usuario
        $stmt = $conexion->prepare("UPDATE Usuario SET Contrasena = ? WHERE NumNomina = ?");


        if ($stmt->execute()) {
            // Elimina el token de la tabla restablecerContrasena
            $stmt = $conexion->prepare("DELETE FROM restablecerContrasena WHERE NumNomina = ? AND Token = ?");
            $stmt->bind_param('ss', $numNomina, $token);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada con éxito.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la contraseña.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Token inválido o expirado.']);
    }

    $stmt->close();
    $conexion->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
