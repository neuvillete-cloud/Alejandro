<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json'); // Es bueno ponerlo al principio

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $NumNomina = $_POST['NumNomina'];
    $Contrasena = $_POST['Contrasena'];
    // Obtenemos la URL de redirección que enviamos desde el JS
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

    $stmt = $conex->prepare("SELECT * FROM Usuario WHERE NumNomina = ? AND Contrasena = ?");
    $stmt->bind_param("ss", $NumNomina, $Contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $_SESSION['NumNomina'] = $NumNomina;
        $_SESSION['Nombre'] = $usuario['Nombre'];
        $_SESSION['Rol'] = $usuario['IdRol'];

        // --- LÓGICA DE REDIRECCIÓN MEJORADA ---
        // Si recibimos una URL de destino, la usamos como prioridad.
        if (!empty($redirectUrl)) {
            $response = ['status' => 'success', 'redirect' => $redirectUrl];
        } else {
            // Si no hay URL de destino, usamos la lógica de roles como antes.
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
        // --- FIN DE LA LÓGICA MEJORADA ---

    } else {
        $response = ['status' => 'error', 'message' => 'Usuario no encontrado o credenciales incorrectas.'];
    }

    $stmt->close();
    $conex->close();

    return $response;
}
?>