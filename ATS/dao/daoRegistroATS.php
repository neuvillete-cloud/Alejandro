<?php
session_start();
include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (
        isset($_POST['email'], $_POST['nombre'], $_POST['apellidos'], $_POST['telefono'], $_POST['contrasena'])
    ) {
        $email = trim($_POST['email']);
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $telefono = trim($_POST['telefono']);
        $contrasena = trim($_POST['contrasena']);

        // Generar el folio único usando uniqid()
        $folio = 'FOL-' . uniqid();

        // IdEstatus por defecto
        $idEstatus = 6;

        // Conectarse a la base de datos
        $con = new LocalConector();
        $conex = $con->conectar();

        // Verificar si el correo ya existe en Candidatos
        $check = $conex->prepare("SELECT IdCandidato FROM Candidatos WHERE Correo = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $resultCheck = $check->get_result();

        if ($resultCheck->num_rows > 0) {
            $response = array('status' => 'error', 'message' => 'Este correo ya está registrado.');
        } else {
            // Insertar el nuevo candidato con IdEstatus = 6 y FolioSolCand generado
            $stmt = $conex->prepare("INSERT INTO Candidatos (Correo, Nombre, Apellidos, Telefono, Contrasena, IdEstatus, FolioSolCand) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $email, $nombre, $apellidos, $telefono, $contrasena, $idEstatus, $folio);

            if ($stmt->execute()) {
                $response = array('status' => 'success', 'message' => 'Candidato registrado exitosamente');
            } else {
                $response = array('status' => 'error', 'message' => 'Error al registrar candidato');
            }

            $stmt->close();
        }

        $check->close();
        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Faltan datos obligatorios.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Método no permitido.');
}

echo json_encode($response);
exit();
?>


