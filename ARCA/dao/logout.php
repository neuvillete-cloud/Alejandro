<?php
// Inicia la sesión para poder acceder a las variables de sesión.
session_start();

// 1. Limpia todas las variables de la sesión.
$_SESSION = array();

// 2. Si existe una cookie "remember_me", la elimina.
//    Esto se hace estableciendo su fecha de expiración en el pasado.
if (isset($_COOKIE['remember_me'])) {
    unset($_COOKIE['remember_me']);
    setcookie('remember_me', '', time() - 3600, '/'); // Expira hace 1 hora
}

// 3. Destruye la sesión en el servidor.
session_destroy();

// 4. Redirige al usuario a la página de inicio de sesión.
//    Usamos ../ porque este script está en la carpeta /dao y necesitamos "subir" un nivel.
header("Location: ../acceso.php");
exit(); // Asegura que el script se detenga después de la redirección.
?>
