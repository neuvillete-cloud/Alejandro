<?php
include_once("ConexionBD.php");
header('Content-Type: application/json');

if (empty($_POST['token']) || empty($_POST['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos.']);
    exit;
}

$token = $_POST['token'];
$password = $_POST['password'];

$con = new LocalConector();
$conex = $con->conectar();

// 1. Volver a verificar el token para máxima seguridad
$stmt = $conex->prepare("SELECT NumNomina FROM RestablecerContrasena WHERE Token = ? AND TokenValido = 1 AND Expira >= NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $numNomina = $row['NumNomina'];

    // 2. Hashear la nueva contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Actualizar la contraseña en la tabla Usuario e invalidar el token
    $conex->begin_transaction();
    try {
        $stmt_update = $conex->prepare("UPDATE Usuario SET Contrasena = ? WHERE NumNomina = ?");
        $stmt_update->bind_param("ss", $hashed_password, $numNomina);
        $stmt_update->execute();

        $stmt_invalidate = $conex->prepare("UPDATE RestablecerContrasena SET TokenValido = 0 WHERE Token = ?");
        $stmt_invalidate->bind_param("s", $token);
        $stmt_invalidate->execute();

        $conex->commit();
        echo json_encode(['status' => 'success']);

    } catch (mysqli_sql_exception $exception) {
        $conex->rollback();
        echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la contraseña.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'El token es inválido o ha expirado.']);
}

$stmt->close();
$conex->close();
?>
