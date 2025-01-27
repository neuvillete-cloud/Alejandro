<?php
session_start();
session_unset(); // Limpia todas las variables de sesión
session_destroy(); // Destruye la sesión
http_response_code(200); // Envía un código de éxito
exit;
?>
