<?php
session_start();
include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $response = validarCredencialesCandidato($email, $password);
    echo json_encode($response);
    exit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

function validarCredencialesCandidato($email, $password) {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para validar las credenciales del candidato
    $stmt = $conex->prepare("SELECT * FROM Candidatos WHERE Correo = ? AND Contrasena = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $candidato = $resultado->fetch_assoc();

        // Guardar datos importantes en la sesión si es necesario
        $_SESSION['IdCandidato'] = $candidato['IdCandidato'];
        $_SESSION['NombreCandidato'] = $candidato['Nombre'];
        $_SESSION['CorreoCandidato'] = $candidato['Correo'];
        $_SESSION['ApellidosCandidato'] = $candidato['Apellidos'];
        $_SESSION['TelefonoCandidato'] = $candidato['Telefono'];

        // Redirección general para todos los candidatos
        return ['status' => 'success', 'redirect' => 'vacantes.php'];
    } else {
        return ['status' => 'error', 'message' => 'Correo o contraseña incorrectos.'];
    }
}
?>

