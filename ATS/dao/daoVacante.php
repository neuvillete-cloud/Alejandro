<?php
session_start();
include_once("ConexionBD.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos requeridos
    $camposRequeridos = ['titulo', 'area', 'tipo', 'escolaridad', 'pais', 'estado', 'ciudad', 'espacio', 'descripcion'];
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['status' => 'error', 'message' => "Campo obligatorio faltante: $campo"]);
            exit;
        }
    }

    // Obtener valores del formulario
    $titulo = $_POST['titulo'];
    $area = $_POST['area'];
    $tipo = $_POST['tipo'];
    $horario = $_POST['horario'] ?? '';
    $sueldo = $_POST['sueldo'] ?? '';
    $escolaridad = $_POST['escolaridad'];
    $pais = $_POST['pais'];
    $estado = $_POST['estado'];
    $ciudad = $_POST['ciudad'];
    $espacio = $_POST['espacio'];
    $requisitos = $_POST['requisitos'] ?? '';
    $beneficios = $_POST['beneficios'] ?? '';
    $descripcion = $_POST['descripcion'];

    $con = new LocalConector();
    $conex = $con->conectar();

    $baseUrl = "https://grammermx.com/AleTest/ATS/imagenes/imagenesVacantes/";
    $nombreArchivo = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($imagen['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'El archivo debe ser una imagen JPEG, PNG o GIF']);
            exit;
        }

        if ($imagen['size'] > 5000000) {
            echo json_encode(['status' => 'error', 'message' => 'El archivo excede el tamaño máximo permitido (5MB)']);
            exit;
        }

        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $nombreUnico = "vacante_" . $_SESSION['NumNomina'] . "_" . date("Ymd_His") . "." . $extension;
        $rutaLocal = "../imagenes/imagenesVacantes/" . $nombreUnico;
        $rutaPublica = $baseUrl . $nombreUnico;

        if (!move_uploaded_file($imagen['tmp_name'], $rutaLocal)) {
            echo json_encode(['status' => 'error', 'message' => 'Error al subir la imagen']);
            exit;
        }
        $nombreArchivo = $rutaPublica;
    }

    $stmt = $conex->prepare("INSERT INTO Vacantes (TituloVacante, IdArea, TipoContrato, Horario, Sueldo, EscolaridadMinima, Pais, Estado, Ciudad, EspacioTrabajo, Requisitos, Beneficios, Descripcion, Imagen)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssssssssss", $titulo, $area, $tipo, $horario, $sueldo, $escolaridad, $pais, $estado, $ciudad, $espacio, $requisitos, $beneficios, $descripcion, $nombreArchivo);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Vacante guardada exitosamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar en la base de datos']);
    }

    $stmt->close();
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Se requiere método POST']);
}
?>

