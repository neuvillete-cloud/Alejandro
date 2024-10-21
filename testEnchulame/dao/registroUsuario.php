<?php
session_start(); // Iniciar sesión

// Habilitar la visualización de errores (puedes quitar esto en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Validar que los campos no estén vacíos
    if (empty($nomina) || empty($nombre) || empty($correo) || empty($password)) {
        $response = array('status' => 'error', 'message' => 'Por favor, complete todos los campos.');
        echo json_encode($response);
        exit();
    }

    // Validar formato del correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $response = array('status' => 'error', 'message' => 'Por favor, ingrese un correo electrónico válido.');
        echo json_encode($response);
        exit();
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (strlen($nomina) !== 8) {
        $response = array('status' => 'error', 'message' => 'La nómina debe tener exactamente 8 caracteres.');
        echo json_encode($response);
        exit();
    }

    // Lógica para registrar al usuario en la base de datos
    if (registrarUsuarioEnDB($nomina, $nombre, $correo, $password)) {
        $response = array('status' => 'success', 'message' => 'Registro exitoso.');
        echo json_encode($response);
        exit();
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar al usuario. Puede que el número de nómina ya esté en uso.');
        echo json_encode($response);
        exit();
    }
}

// Función para registrar al usuario en la base de datos
function registrarUsuarioEnDB($nomina, $nombre, $correo, $password) {
    // Conectar a la base de datos (ajusta esto según tu implementación)
    $con = new mysqli('localhost', 'tu_usuario', 'tu_contraseña', 'tu_base_de_datos');

    // Verificar la conexión
    if ($con->connect_error) {
        return false;
    }

    // Comprobar si el usuario ya existe
    $stmt = $con->prepare("SELECT * FROM usuario WHERE nomina = ?");
    $stmt->bind_param("s", $nomina);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        return false; // El usuario ya existe
    }

    // Encriptar la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $con->prepare("INSERT INTO usuario (nomina, nombre, correo, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nomina, $nombre, $correo, $hashedPassword);
    $stmt->execute();

    // Cerrar la conexión
    $stmt->close();
    $con->close();

    return true; // Registro exitoso
}
?>
