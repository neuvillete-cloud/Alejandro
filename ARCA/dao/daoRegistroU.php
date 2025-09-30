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
    // MODIFICADO: Añadimos 'email' a la comprobación
    if (isset($_POST['nombre'], $_POST['nombreUsuario'], $_POST['email'], $_POST['password'])) {
        $nombre = trim($_POST['nombre']);
        $nombreUsuario = trim($_POST['nombreUsuario']);
        // AÑADIDO: Recibir y limpiar el correo electrónico
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Paso 2: Usamos TU clase para crear la conexión.
        $con = new LocalConector();
        $conex = $con->conectar();

        // MODIFICADO: Pasamos el email a la función de registro
        $response = registrarUsuarioEnDB($conex, $nombre, $nombreUsuario, $email, $password);

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
 * MODIFICADO: La función ahora acepta el parámetro $email.
 */
function registrarUsuarioEnDB($conex, $nombre, $nombreUsuario, $email, $password) {
    // 1. (SEGURIDAD) Validar requisitos de la contraseña en el servidor
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return array('status' => 'error', 'message' => 'La contraseña no cumple los requisitos de seguridad.');
    }

    // AÑADIDO: (SEGURIDAD) Validar formato de correo electrónico en el servidor
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array('status' => 'error', 'message' => 'El formato del correo electrónico no es válido.');
    }

    // 2. (SEGURIDAD) Verificar si el nombre de usuario ya existe
    $stmt_check_user = $conex->prepare("SELECT IdUsuario FROM Usuarios WHERE NombreUsuario = ?");
    $stmt_check_user->bind_param("s", $nombreUsuario);
    $stmt_check_user->execute();
    if ($stmt_check_user->get_result()->num_rows > 0) {
        $stmt_check_user->close();
        return array('status' => 'error', 'message' => 'El nombre de usuario ya está en uso. Por favor, elige otro.');
    }
    $stmt_check_user->close();

    // AÑADIDO: (SEGURIDAD) Verificar si el correo electrónico ya existe
    $stmt_check_email = $conex->prepare("SELECT IdUsuario FROM Usuarios WHERE Correo = ?");
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->num_rows > 0) {
        $stmt_check_email->close();
        return array('status' => 'error', 'message' => 'Este correo electrónico ya está registrado. Por favor, utiliza otro.');
    }
    $stmt_check_email->close();


    // 3. (SEGURIDAD) Hashear la contraseña antes de guardarla
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Asignar rol por defecto (ej: 2 = Usuario normal)
    $IdRol = 2;

    // 5. MODIFICADO: Insertar el nuevo usuario incluyendo el correo electrónico
    // Asumimos que tu columna se llama 'Correo'
    $stmt_insert = $conex->prepare("INSERT INTO Usuarios (Nombre, NombreUsuario, Correo, Contraseña, IdRol) VALUES (?, ?, ?, ?, ?)");
    // Se cambia "sssi" por "ssssi" para incluir el string del email
    $stmt_insert->bind_param("ssssi", $nombre, $nombreUsuario, $email, $hashed_password, $IdRol);

    if ($stmt_insert->execute()) {
        $response = array('status' => 'success', 'message' => 'Usuario registrado exitosamente.');
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar el usuario en la base de datos.');
    }
    $stmt_insert->close();

    return $response;
}
?>
