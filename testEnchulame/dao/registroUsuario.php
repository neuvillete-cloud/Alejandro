<?php
session_start(); // Iniciar sesión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];

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
    if (strlen($nomina) !== 8) {
        echo json_encode(array('status' => 'error', 'message' => 'La nómina debe tener exactamente 8 caracteres.'));
        exit();
    }

    // Lógica para registrar al usuario en la base de datos
    if (registrarUsuarioEnDB($nomina, $nombre, $correo, $password)) {
        echo json_encode(array('status' => 'success'));
        exit();
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Error al registrar al usuario. Puede que el número de nómina ya esté en uso.'));
        exit();
    }
}

// Función para registrar al usuario en la base de datos
function registrarUsuarioEnDB($nomina, $nombre, $correo, $password) {
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

