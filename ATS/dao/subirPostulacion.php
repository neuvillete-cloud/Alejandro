<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');
session_start();

// Validación básica
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Se requiere método POST"]);
    exit;
}

if (!isset($_SESSION['IdCandidato']) || !isset($_GET['idVacante'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$idCandidato = intval($_SESSION['IdCandidato']);
$idVacante = intval($_GET['idVacante']);

// Validación de archivo
if (empty($_FILES['cv'])) {
    echo json_encode(["status" => "error", "message" => "Archivo no enviado"]);
    exit;
}

$archivo = $_FILES['cv'];
$extensionesPermitidas = ['pdf', 'doc', 'docx', 'rtf', 'txt'];
$maximoTamano = 5 * 1024 * 1024; // 5MB

$extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
if (!in_array(strtolower($extension), $extensionesPermitidas)) {
    echo json_encode(["status" => "error", "message" => "Formato de archivo no permitido"]);
    exit;
}

if ($archivo['size'] > $maximoTamano) {
    echo json_encode(["status" => "error", "message" => "El archivo excede el tamaño máximo permitido (5MB)"]);
    exit;
}

// Definir rutas
$baseUrl = "https://grammermx.com/AleTest/ATS/Cv/"; // URL pública
$uploadDir = "../Cv/"; // Carpeta física

// Crear carpeta si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Guardar archivo con nombre único
$nombreArchivo = "cv_" . uniqid() . "." . $extension;
$rutaLocal = $uploadDir . $nombreArchivo;
$rutaPublica = $baseUrl . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaLocal)) {
    echo json_encode(["status" => "error", "message" => "Error al subir el archivo"]);
    exit;
}

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$email = $_POST['email'] ?? '';
$ciudad = $_POST['ciudad'] ?? '';
$telefono = $_POST['telefono'] ?? '';

// Conectar y ejecutar transacción
$conn = (new LocalConector())->conectar();
$conn->begin_transaction();

try {
    // 1. Actualizar Candidato
    $sql1 = "UPDATE Candidatos 
             SET Nombre = ?, Apellidos = ?, Correo = ?, Telefono = ?, Ubicacion = ?, CV = ?
             WHERE IdCandidato = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $ciudad, $rutaPublica, $idCandidato);
    $stmt1->execute();

    // 2. Insertar en Postulaciones con IdEstatus = 1
    $sql2 = "INSERT INTO Postulaciones (IdCandidato, IdVacante, FechaPostulacion, IdEstatus) VALUES (?, ?, NOW(), ?)";
    $stmt2 = $conn->prepare($sql2);
    $idEstatus = 1;
    $stmt2->bind_param("iii", $idCandidato, $idVacante, $idEstatus);
    $stmt2->execute();

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Postulación registrada con éxito",
        "url_cv" => $rutaPublica
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => "Error en la base de datos: " . $e->getMessage()]);
}

$conn->close();
?>
