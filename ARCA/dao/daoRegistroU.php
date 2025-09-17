<?php
// AÑADE ESTAS TRES LÍNEAS AL INICIO DE TU SCRIPT
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Paso 1: Incluimos TU archivo de conexión.
include_once("conexionArca.php");

// Definimos la respuesta por defecto
$response = array('status' => 'error', 'message' => 'Ocurrió un error inesperado.');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nombre'], $_POST['nombreUsuario'], $_POST['password'])) {
        $nombre = trim($_POST['nombre']);
        $nombreUsuario = trim($_POST['nombreUsuario']);
        $password = trim($_POST['password']);

        // Paso 2: Usamos TU clase para crear la conexión.
        $con = new LocalConector();
        $conex = $con->conectar();

        // Llamar a la función de registro (el resto del código no cambia)
        $response = registrarUsuarioEnDB($conex, $nombre, $nombreUsuario, $password);

        // Cerrar la conexión
        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Se requiere método POST.');
}

// Devolver la respuesta en formato JSON
echo json_encode($response);
exit();

/**
 * Función para registrar al usuario en la base de datos de forma segura.
 * (Esta función se mantiene igual, es independiente de cómo se crea la conexión)
 */
function registrarUsuarioEnDB($conex, $nombre, $nombreUsuario, $password) {
    // 1. (SEGURIDAD) Validar requisitos de la contraseña en el servidor
    // La variable $pass fue corregida a $password
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return array('status' => 'error', 'message' => 'La contraseña no cumple los requisitos de seguridad.');
    }

    // 2. (SEGURIDAD) Verificar si el nombre de usuario ya existe para evitar duplicados
    $stmt_check = $conex->prepare("SELECT IdUsuario FROM Usuarios WHERE NombreUsuario = ?");
    $stmt_check->bind_param("s", $nombreUsuario);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    if ($resultado->num_rows > 0) {
        $stmt_check->close();
        return array('status' => 'error', 'message' => 'El nombre de usuario ya está en uso. Por favor, elige otro.');
    }
    $stmt_check->close();

    // 3. (SEGURIDAD) Hashear la contraseña antes de guardarla
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Asignar rol por defecto (ej: 2 = Usuario normal)
    $IdRol = 2;

    // 5. Insertar el nuevo usuario usando sentencias preparadas
    $stmt_insert = $conex->prepare("INSERT INTO Usuarios (Nombre, NombreUsuario, Contraseña, IdRol) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("sssi", $nombre, $nombreUsuario, $hashed_password, $IdRol);

    if ($stmt_insert->execute()) {
        $response = array('status' => 'success', 'message' => 'Usuario registrado exitosamente.');
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar el usuario en la base de datos.');
    }
    $stmt_insert->close();

    return $response;
}
?>
