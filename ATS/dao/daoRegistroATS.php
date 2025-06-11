<?php
session_start();
include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (
        isset($_POST['email'], $_POST['nombre'], $_POST['apellidos'], $_POST['telefono'], $_POST['contrasena'],
            $_POST['sueldo'], $_POST['nivel_estudios'], $_POST['ubicacion'], $_POST['area'],
            $_POST['especialidad'], $_POST['fecha_nacimiento'])
    ) {
        $email = trim($_POST['email']);
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $telefono = trim($_POST['telefono']);
        $contrasena = trim($_POST['contrasena']);
        $sueldo = trim($_POST['sueldo']);
        $educacion = trim($_POST['nivel_estudios']);
        $ubicacion = trim($_POST['ubicacion']);
        $area = trim($_POST['area']);
        $especialidad = trim($_POST['especialidad']);
        $fechaNacimiento = trim($_POST['fecha_nacimiento']);

        // Generar folio único
        $folio = 'FOL-' . uniqid();

        // IdEstatus por defecto
        $idEstatus = 6;

        $con = new LocalConector();
        $conex = $con->conectar();

        // Verificar si ya existe el correo
        $check = $conex->prepare("SELECT IdCandidato FROM Candidatos WHERE Correo = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $resultCheck = $check->get_result();

        if ($resultCheck->num_rows > 0) {
            $response = array('status' => 'error', 'message' => 'Este correo ya está registrado.');
        } else {
            // Insertar nuevo candidato
            $stmt = $conex->prepare("
                INSERT INTO Candidatos (
                    Correo, Nombre, Apellidos, Telefono, Contrasena,
                    IdEstatus, FolioSolCand,
                    Sueldo, Educacion, Ubicacion, Area, Especialidad, FechaNacimiento
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssisssssss",
                $email, $nombre, $apellidos, $telefono, $contrasena,
                $idEstatus, $folio,
                $sueldo, $educacion, $ubicacion, $area, $especialidad, $fechaNacimiento
            );

            if ($stmt->execute()) {
                $response = array('status' => 'success', 'message' => 'Candidato registrado exitosamente');
            } else {
                $response = array('status' => 'error', 'message' => 'Error al registrar candidato.');
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
