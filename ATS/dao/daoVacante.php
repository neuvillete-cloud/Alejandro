<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);

session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$con = new LocalConector();
$conex = $con->conectar();

// --- NUEVO: OBTENER DATOS DE UNA VACANTE PARA EDITAR (MÉTODO GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $idVacante = intval($_GET['id']);
    $stmt = $conex->prepare("SELECT V.*, A.NombreArea FROM Vacantes V JOIN Area A ON V.IdArea = A.IdArea WHERE V.IdVacante = ?");
    $stmt->bind_param("i", $idVacante);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($vacante = $result->fetch_assoc()) {
        echo json_encode(['status' => 'success', 'data' => $vacante]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Vacante no encontrada.']);
    }
    $stmt->close();
    $conex->close();
    exit;
}

// --- LÓGICA MEJORADA PARA GUARDAR: CREAR (INSERT) O ACTUALIZAR (UPDATE) (MÉTODO POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos requeridos
    $camposRequeridos = ['titulo', 'area', 'tipo', 'escolaridad', 'pais', 'estado', 'ciudad', 'espacio', 'idioma', 'especialidad', 'descripcion'];
    foreach ($camposRequeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['status' => 'error', 'message' => "Campo obligatorio faltante: $campo"]);
            exit;
        }
    }

    // Obtener valores del formulario
    $idVacanteAEditar = !empty($_POST['idVacante']) ? intval($_POST['idVacante']) : null;
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
    $especialidad = $_POST['especialidad'];
    $requisitos = $_POST['requisitos'] ?? '';
    $beneficios = $_POST['beneficios'] ?? '';
    $descripcion = $_POST['descripcion'];
    $idSolicitud = $_POST['IdSolicitud'] ?? null;

    // Obtener ID del área
    $stmtArea = $conex->prepare("SELECT IdArea FROM Area WHERE NombreArea = ?");
    $stmtArea->bind_param("s", $nombreArea);
    $stmtArea->execute();
    $resultArea = $stmtArea->get_result();
    $rowArea = $resultArea->fetch_assoc();
    $idArea = $rowArea['IdArea'];
    $stmtArea->close();

    // Manejo de la imagen
    $nombreArchivo = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $baseUrl = "https://grammermx.com/AleTest/ATS/imagenes/imagenesVacantes/";
        $imagen = $_FILES['imagen'];
        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $numNomina = $_SESSION['NumNomina'] ?? 'admin';
        $nombreUnico = "vacante_" . $numNomina . "_" . date("Ymd_His") . "." . $extension;
        $rutaLocal = "../imagenes/imagenesVacantes/" . $nombreUnico;
        $rutaPublica = $baseUrl . $nombreUnico;

        if (move_uploaded_file($imagen['tmp_name'], $rutaLocal)) {
            $nombreArchivo = $rutaPublica;
        }
    }

    if ($idVacanteAEditar) {
        // --- MODO UPDATE ---
        $sql = "UPDATE Vacantes SET 
                    TituloVacante=?, IdArea=?, TipoContrato=?, Horario=?, Sueldo=?, EscolaridadMinima=?, 
                    Pais=?, Estado=?, Ciudad=?, EspacioTrabajo=?, Idioma=?, Especialidad=?, 
                    Requisitos=?, Beneficios=?, Descripcion=?";
        $params = [
            $titulo, $idArea, $tipo, $horario, $sueldo, $escolaridad,
            $pais, $estado, $ciudad, $espacio, $idioma, $especialidad,
            $requisitos, $beneficios, $descripcion
        ];
        $types = "sisssssssssssss";

        if ($nombreArchivo) {
            $sql .= ", Imagen=?";
            $params[] = $nombreArchivo;
            $types .= "s";
        }

        $sql .= " WHERE IdVacante = ?";
        $params[] = $idVacanteAEditar;
        $types .= "i";

        $stmt = $conex->prepare($sql);
        $stmt->bind_param($types, ...$params);

    } else {
        // --- MODO INSERT ---
        if (!$idSolicitud) {
            echo json_encode(['status' => 'error', 'message' => 'Falta el IdSolicitud para crear una nueva vacante.']);
            exit;
        }
        $fechaHoraActual = date('Y-m-d');
        $idEstatus = 1;

        $stmt = $conex->prepare("INSERT INTO Vacantes (
            TituloVacante, IdArea, TipoContrato, Horario, Sueldo, EscolaridadMinima, Pais, Estado, Ciudad, 
            EspacioTrabajo, Idioma, Especialidad, Requisitos, Beneficios, Descripcion, Imagen, Fecha, IdEstatus, IdSolicitud
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sisssssssssssssssii",
            $titulo, $idArea, $tipo, $horario, $sueldo, $escolaridad, $pais, $estado, $ciudad,
            $espacio, $idioma, $especialidad, $requisitos, $beneficios, $descripcion, $nombreArchivo,
            $fechaHoraActual, $idEstatus, $idSolicitud
        );
    }

    if ($stmt->execute()) {
        if (!$idVacanteAEditar && $idSolicitud) {
            $nuevoEstatusSolicitud = 10;
            $stmtUpdate = $conex->prepare("UPDATE Solicitudes SET IdEstatus = ? WHERE IdSolicitud = ?");
            $stmtUpdate->bind_param("ii", $nuevoEstatusSolicitud, $idSolicitud);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }
        echo json_encode(['status' => 'success', 'message' => 'Vacante guardada correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar la vacante: ' . $stmt->error]);
    }

    $stmt->close();
    $conex->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Método no soportado o petición inválida.']);
?>