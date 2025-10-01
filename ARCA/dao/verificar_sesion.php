<?php
// Inicia la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    // CORRECCIÓN: Se añade 'cookie_path' => '/' para que la sesión sea válida en todo el dominio.
    session_start(['cookie_path' => '/']);
}

// Si el usuario NO está logueado pero SÍ tiene una cookie "remember_me"
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['remember_me'])) {

    // 1. Separa el ID de usuario y el token de la cookie
    list($user_id, $token) = explode(':', $_COOKIE['remember_me'], 2);

    if (!empty($user_id) && !empty($token)) {
        include_once("conexionArca.php");
        $con = new LocalConector();
        $conex = $con->conectar();

        // 2. Busca el token en la base de datos
        $stmt = $conex->prepare("SELECT Nombre, IdRol FROM Usuarios WHERE IdUsuario = ? AND remember_token = ?");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            // 3. ¡El token es válido! Iniciamos la sesión
            $usuario = $resultado->fetch_assoc();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_nombre'] = $usuario['Nombre'];
            $_SESSION['user_rol'] = $usuario['IdRol'];
            $_SESSION['loggedin'] = true;
        }

        $stmt->close();
        $conex->close();
    }
}
?>
