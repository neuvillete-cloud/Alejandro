<?php
session_start(); // Iniciar sesión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = $_POST['password']; // Cambiado de "contrasena" a "password"

    // Validar que los campos no estén vacíos
    if (empty($nomina) || empty($nombre) || empty($correo) || empty($password)) {
        echo json_encode(array('status' => 'error', 'message' => 'Por favor, complete todos los campos.'));
        exit();
    }

    // Validar formato del correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('status' => 'error', 'message' => 'Por favor, ingrese un correo electrónico válido.'));
        exit();
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (strlen($nomina) < 8) {
        // Completar con ceros a la izquierda
        $nomina = str_pad($nomina, 8, '0', STR_PAD_LEFT);
    } elseif (strlen($nomina) > 8) {
        echo json_encode(array('status' => 'error', 'message' => 'La nómina debe tener exactamente 8 caracteres.'));
        exit();
    }

    // Validar la contraseña
    if (!validarContrasena($password)) {
        echo json_encode(array('status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres y contener al menos una letra mayúscula, una letra minúscula, un número y un carácter especial.'));
        exit();
    }

    // Lógica para registrar al usuario en la base de datos
    if (registrarUsuario($nomina, $nombre, $correo, $password)) {
        // Redirigir al formulario de inicio de sesión
        header("Location: login.php");
        exit();
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Error al registrar al usuario. Puede que el número de nómina ya esté en uso.'));
        exit();
    }
}

// Función para validar la contraseña
function validarContrasena($password) {
    // Requiere al menos 8 caracteres, una letra mayúscula, una letra minúscula, un número y un carácter especial
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

// Función para registrar al usuario
function registrarUsuario($nomina, $nombre, $correo, $password) {
    // Conectar a la base de datos (ajusta esto según tu implementación)
    $con = new LocalConector();
    $conex = $con->conectar();

    // Comprobar si el usuario ya existe
    $stmt = $conex->prepare("SELECT * FROM usuario WHERE nomina = ?");
    $stmt->bind_param("s", $nomina);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        return false; // Usuario ya existe
    }

    // Encriptar la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $conex->prepare("INSERT INTO usuario (nomina, nombre, correo, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nomina, $nombre, $correo, $hashedPassword);
    $stmt->execute();

    // Cerrar conexión
    $stmt->close();
    $conex->close();

    return true; // Registro exitoso
}
?>

