<?php
// 1. Incluir dependencias
include_once("conexionArca.php");
header('Content-Type: application/json; charset=UTF-8');

// 2. Validar datos de entrada
if (!isset($_POST['token'], $_POST['password'], $_POST['password_confirm'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    exit;
}

$token = $_POST['token'];
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

// 3. Validar lógica de negocio
if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Token no proporcionado."]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "La contraseña debe tener al menos 8 caracteres."]);
    exit;
}

if ($password !== $password_confirm) {
    echo json_encode(["status" => "error", "message" => "Las contraseñas no coinciden."]);
    exit;
}

// 4. Hashear la nueva contraseña (¡MUY IMPORTANTE!)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
if ($hashedPassword === false) {
    echo json_encode(["status" => "error", "message" => "Error al procesar la contraseña."]);
    exit;
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->set_charset("utf8mb4");
$conex->begin_transaction();

try {
    // 5. Verificar el token NUEVAMENTE (Seguridad: doble verificación)
    $stmt_check = $conex->prepare("SELECT IdUsuario, Expira FROM ReestablecerContraseña WHERE Token = ? AND TokenValido = 1");
    $stmt_check->bind_param("s", $token);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();

    if (!($fila = $resultado->fetch_assoc())) {
        throw new Exception("Enlace inválido o ya utilizado.");
    }

    // 6. Verificar expiración (por si acaso)
    $fechaExpiracion = new DateTime($fila['Expira']);
    $fechaActual = new DateTime();

    if ($fechaActual > $fechaExpiracion) {
        throw new Exception("El enlace de recuperación ha expirado.");
    }

    $idUsuario = $fila['IdUsuario'];
    $stmt_check->close();

    // 7. Actualizar la contraseña del usuario en la tabla 'Usuarios'
    $stmt_update_pass = $conex->prepare("UPDATE Usuarios SET Contraseña = ? WHERE IdUsuario = ?");
    $stmt_update_pass->bind_param("si", $hashedPassword, $idUsuario);
    if (!$stmt_update_pass->execute()) {
        throw new Exception("No se pudo actualizar la contraseña.");
    }
    $stmt_update_pass->close();

    // 8. Invalidar el token para que no se vuelva a usar
    $stmt_invalidate = $conex->prepare("UPDATE ReestablecerContraseña SET TokenValido = 0 WHERE Token = ?");
    $stmt_invalidate->bind_param("s", $token);
    if (!$stmt_invalidate->execute()) {
        throw new Exception("Error al invalidar el token.");
    }
    $stmt_invalidate->close();

    // 9. Confirmar transacción
    $conex->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Tu contraseña ha sido actualizada con éxito."
    ]);

} catch (Exception $e) {
    $conex->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    if (isset($conex)) {
        $conex->close();
    }
}
?>

