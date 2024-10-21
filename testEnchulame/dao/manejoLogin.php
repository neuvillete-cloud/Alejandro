<?php
session_start(); // Inicia la sesión para manejar la sesión del usuario

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibe los datos del formulario
    $nomina = $_POST['nomina'];
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];

    // Aquí deberías realizar la validación de los datos, como verificar en la base de datos
    // Simularemos la autenticación con un simple chequeo (puedes reemplazarlo por la consulta a tu base de datos)
    if (!empty($nomina) && !empty($nombre) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Guarda el número de nómina en la sesión
        $_SESSION['nomina'] = $nomina;

        // Redirecciona a la página principal después del login
        header("Location: index.php");
        exit();
    } else {
        $error = "Datos inválidos. Asegúrate de que todos los campos sean correctos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2 class="text-danger"><?php if (isset($error)) echo $error; ?></h2>
</div>
</body>
</html>

