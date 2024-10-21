<?php
session_start(); // Iniciar sesión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];

    // Validar que los campos no estén vacíos
    if (empty($nomina) || empty($nombre) || empty($email)) {
        header("Location: login.html?error=Por favor, complete todos los campos.");
        exit();
    }

    // Validar formato del correo
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.html?error=Por favor, ingrese un correo electrónico válido.");
        exit();
    }

    // Validar que la nómina tenga exactamente 8 caracteres
    if (strlen($nomina) < 8) {
        // Completar con ceros a la izquierda
        $nomina = str_pad($nomina, 8, '0', STR_PAD_LEFT);
    } elseif (strlen($nomina) > 8) {
        header("Location: login.html?error=La nómina debe tener exactamente 8 caracteres.");
        exit();
    }

    // Aquí deberías agregar la lógica de autenticación para validar el usuario.
    // Por ejemplo, puedes verificar en la base de datos si el número de nómina y el correo son válidos.
    if (validarUsuario($nomina, $nombre, $email)) {
        // Guardar el número de nómina en la sesión
        $_SESSION['nomina'] = $nomina;

        // Redirigir a la página principal
        header("Location: index.php");
        exit();
    } else {
        // Redirigir de vuelta al formulario con un mensaje de error
        header("Location: login.html?error=Credenciales incorrectas.");
        exit();
    }
}

// Función de validación
function validarUsuario($nomina, $nombre, $email) {
    // Conectar a la base de datos (ajusta esto según tu implementación)
    $con = new LocalConector();
    $conex = $con->conectar();

    // Aquí deberías realizar una consulta a la base de datos para verificar si el usuario existe.
    $stmt = $conex->prepare("SELECT * FROM usuarios WHERE nomina = ? AND nombre = ? AND email = ?");
    $stmt->bind_param("sss", $nomina, $nombre, $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Cerrar conexión
    $conex->close();

    // Retornar verdadero si se encontró un usuario, falso de lo contrario
    return $resultado->num_rows > 0;
}

// Si no se está accediendo al script mediante POST, redirigir al login.
header("Location: login.html");
exit();
?>


