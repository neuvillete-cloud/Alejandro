<?php
include_once ("conexion.php");
session_start(); // Iniciar sesión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre']; // Mantener el campo nombre
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
    if (strlen($nomina) < 8) {
        // Completar con ceros a la izquierda
        $nomina = str_pad($nomina, 8, '0', STR_PAD_LEFT);
    } elseif (strlen($nomina) > 8) {
        echo json_encode(array('status' => 'error', 'message' => 'La nómina debe tener exactamente 8 caracteres.'));
        exit();
    }

    // Lógica para verificar las credenciales del usuario
    if (validarCredenciales($nomina, $nombre, $correo, $password)) {
        // Guardar el número de nómina en la sesión
        $_SESSION['nomina'] = $nomina;

        // Redirigir al index
        header("Location: index.php");
        exit();
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Credenciales incorrectas.'));
        exit();
    }
}

// Función para validar las credenciales
function validarCredenciales($nomina, $nombre, $correo, $password) {
    // Conectar a la base de datos
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta a la base de datos para verificar si el usuario existe
    $stmt = $conex->prepare("SELECT * FROM usuario WHERE nomina = ? AND correo = ?");
    $stmt->bind_param("ss", $nomina, $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si se encontró al usuario
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Comparar el nombre y el password
        if ($usuario['nombre'] === $nombre && password_verify($password, $usuario['password'])) {
            return true; // Credenciales correctas
        }
    }

    // Cerrar conexión
    $conex->close();

    return false; // Usuario no encontrado o credenciales incorrectas
}
?>

