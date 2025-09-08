<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json');

// --- CONFIGURACIÓN DE NÓMINA ESPECIAL ---
// Define aquí el número de nómina de la Gerenta de RRHH para el caso especial.
define('HR_MANAGER_NOMINA', '00030315');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $NumNomina = $_POST['NumNomina'];
    $Contrasena = $_POST['Contrasena'];
    $redirectUrl = $_POST['redirect_url'] ?? ''; // Opcional, para redirecciones específicas

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

        // --- INICIO DE LA LÓGICA DE REDIRECCIÓN CON CASO ESPECIAL ---

        // PRIORIDAD 1: Si el usuario es la Gerenta de RRHH, siempre va al panel de Administrador.
        if ($usuario['NumNomina'] === HR_MANAGER_NOMINA) {
            $response = ['status' => 'success', 'redirect' => 'Administrador.php'];
        }
        // PRIORIDAD 2: Si se especificó una URL de redirección, se usa esa.
        else if (!empty($redirectUrl)) {
            $response = ['status' => 'success', 'redirect' => $redirectUrl];
        }
        // PRIORIDAD 3: Para todos los demás usuarios, se usa la lógica de roles normal.
        else {
            switch ($usuario['IdRol']) {
                case 1:
                    $response = ['status' => 'success', 'redirect' => 'Administrador.php'];
                    break;
                case 2:
                    $response = ['status' => 'success', 'redirect' => 'Solicitante.php'];
                    break;
                case 3:
                    // El resto de gerentes (que no son de RRHH) van a la página de aprobación.
                    $response = ['status' => 'success', 'redirect' => 'AdministradorIng.php'];
                    break;
                default:
                    $response = ['status' => 'error', 'message' => 'Rol no reconocido.'];
                    break;
            }
        }
        // --- FIN DE LA LÓGICA DE REDIRECCIÓN ---

    } else {
        $response = ['status' => 'error', 'message' => 'Usuario no encontrado o credenciales incorrectas.'];
    }

    $stmt->close();
    $conex->close();

    return $response;
}
?>
