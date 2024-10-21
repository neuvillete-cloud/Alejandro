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

    // Aquí deberías agregar la lógica de autenticación para validar el usuario.
    // Por ejemplo, puedes verificar en la base de datos si el número de nómina y el correo son válidos.
    if (validarUsuario($nomina, $nombre, $correo, $password)) {
        // Guardar el número de nómina en la sesión
        $_SESSION['nomina'] = $nomina;

        // Retornar éxito
        echo json_encode(array('status' => 'success'));
        exit();
    } else {
        // Retornar error de credenciales
        echo json_encode(array('status' => 'error', 'message' => 'Credenciales incorrectas.'));
        exit();
    }
}

// Función de validación
function validarUsuario($nomina, $nombre, $correo, $password) {
    // Conectar a la base de datos (ajusta esto según tu implementación)
    $con = new LocalConector();
    $conex = $con->conectar();

    // Aquí deberías realizar una consulta a la base de datos para verificar si el usuario existe.
    $stmt = $conex->prepare("SELECT * FROM usuario WHERE nomina = ? AND nombre = ? AND correo = ?");
    $stmt->bind_param("sss", $nomina, $nombre, $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si se encontró al usuario
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Comparar el password (ajusta según tu método de almacenamiento de contraseñas, aquí se usa `password_verify`)
        if (password_verify($password, $usuario['password'])) { // Cambiado de "contrasena" a "password"
            return true; // Password correcto
        }
    }

    // Cerrar conexión
    $conex->close();

    return false; // Usuario no encontrado o password incorrecto
}

// Si no se está accediendo al script mediante POST, no se hace nada (será manejado por el front-end)
