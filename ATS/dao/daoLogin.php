<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $NumNomina = $_POST['NumNomina'];
    $Contrasena = $_POST['Contrasena'];
    $redirectUrl = $_POST['redirect_url'] ?? '';

    $response = validarCredenciales($NumNomina, $Contrasena, $redirectUrl);
    echo json_encode($response);
    exit();
} else {
    $response = ['status' => 'error', 'message' => 'Método no permitido.'];
    echo json_encode($response);
    exit();
}

function validarCredenciales($NumNomina, $Contrasena, $redirectUrl) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // CAMBIO 1: La consulta ya no compara la contraseña. Solo busca al usuario por su NumNomina.
    $stmt = $conex->prepare("SELECT NumNomina, Nombre, IdRol, Contrasena FROM Usuario WHERE NumNomina = ?");

    // CAMBIO 2: Solo pasamos el NumNomina a la consulta.
    $stmt->bind_param("s", $NumNomina);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $hash_guardado = $usuario['Contrasena']; // Obtenemos la contraseña encriptada de la BD

        // CAMBIO 3: Usamos password_verify() para la comparación segura.
        if (password_verify($Contrasena, $hash_guardado)) {
            // ¡La contraseña es correcta! Iniciamos sesión.
            $_SESSION['NumNomina'] = $NumNomina;
            $_SESSION['Nombre'] = $usuario['Nombre'];
            $_SESSION['Rol'] = $usuario['IdRol'];

            // Lógica de redirección (sin cambios)
            if (!empty($redirectUrl)) {
                $response = ['status' => 'success', 'redirect' => $redirectUrl];
            } else {
                if ($usuario['IdRol'] == 1) {
                    $response = ['status' => 'success', 'redirect' => 'Administrador.php'];
                } elseif ($usuario['IdRol'] == 2) {
                    $response = ['status' => 'success', 'redirect' => 'Solicitante.php'];
                } elseif ($usuario['IdRol'] == 3) {
                    $response = ['status' => 'success', 'redirect' => 'AdministradorIng.php'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Rol no reconocido.'];
                }
            }
        } else {
            // La contraseña NO es correcta.
            $response = ['status' => 'error', 'message' => 'Credenciales incorrectas.'];
        }
    } else {
        // El usuario no fue encontrado.
        $response = ['status' => 'error', 'message' => 'Credenciales incorrectas.'];
    }

    $stmt->close();
    $conex->close();

    return $response;
}
?>