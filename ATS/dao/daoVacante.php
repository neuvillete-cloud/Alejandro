<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json');

// Establecer la zona horaria
date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos requeridos
    $camposRequeridos = ['titulo', 'area', 'tipo', 'escolaridad', 'pais', 'estado', 'ciudad', 'espacio', 'idioma', 'descripcion'];
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['status' => 'error', 'message' => "Campo obligatorio faltante: $campo"]);
            exit;
        }
    }

    // Obtener valores del formulario
    $titulo = $_POST['titulo'];
    $nombreArea = $_POST['area'];
    $tipo = $_POST['tipo'];
    $horario = $_POST['horario'] ?? '';
    $sueldo = $_POST['sueldo'] ?? '';
    $escolaridad = $_POST['escolaridad'];
    $pais = $_POST['pais'];
    $estado = $_POST['estado'];
    $ciudad = $_POST['ciudad'];
    $espacio = $_POST['espacio'];
    $idioma = $_POST['idioma'];
    $requisitos = $_POST['requisitos'] ?? '';
    $beneficios = $_POST['beneficios'] ?? '';
    $descripcion = $_POST['descripcion'];

    // Obtener fecha y hora actual
    $fechaHoraActual = date('Y-m-d');

    $con = new LocalConector();
    $conex = $con->conectar();

    // Obtener ID del área
    $stmtArea = $conex->prepare("SELECT IdArea FROM Area WHERE NombreArea = ?");
    $stmtArea->bind_param("s", $nombreArea);
    $stmtArea->execute();
    $resultArea = $stmtArea->get_result();

    if ($resultArea->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'El área ingresada no existe en la base de datos.']);
        $stmtArea->close();
        $conex->close();
        exit;
    }

    $rowArea = $resultArea->fetch_assoc();
    $idArea = $rowArea['IdArea'];
    $stmtArea->close();

    // Manejo de la imagen
    $baseUrl = "https://grammermx.com/AleTest/ATS/imagenes/imagenesVacantes/";
    $nombreArchivo = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($imagen['type'], $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'La imagen debe ser JPEG, PNG o GIF']);
            exit;
        }

        if ($imagen['size'] > 5000000) {
            echo json_encode(['status' => 'error', 'message' => 'La imagen excede el tamaño máximo permitido (5MB)']);
            exit;
        }

        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $numNomina = $_SESSION['NumNomina'] ?? 'desconocido';
        $nombreUnico = "vacante_" . $numNomina . "_" . date("Ymd_His") . "." . $extension;
        $rutaLocal = "../imagenes/imagenesVacantes/" . $nombreUnico;
        $rutaPublica = $baseUrl . $nombreUnico;

        if (!move_uploaded_file($imagen['tmp_name'], $rutaLocal)) {
            echo json_encode(['status' => 'error', 'message' => 'Error al subir la imagen']);
            exit;
        }

        $nombreArchivo = $rutaPublica;
    }

    // Insertar vacante
    $stmt = $conex->prepare("INSERT INTO Vacantes (TituloVacante, IdArea, TipoContrato, Horario, Sueldo, EscolaridadMinima, Pais, Estado, Ciudad, EspacioTrabajo, Idioma, Requisitos, Beneficios, Descripcion, Imagen, Fecha)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssssssssssssss",
        $titulo, $idArea, $tipo, $horario, $sueldo, $escolaridad, $pais, $estado, $ciudad,
        $espacio, $idioma, $requisitos, $beneficios, $descripcion, $nombreArchivo, $fechaHoraActual
    );

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
