<?php
include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['idSolicitud']) || empty($_FILES['documento'])) {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $idSolicitud = intval($_POST['idSolicitud']);
    $baseUrl = "https://grammermx.com/AleTest/ATS/descripciones/"; // URL pública de archivos
    $uploadDir = "./descripciones/"; // Carpeta en el servidor

    $archivo = $_FILES['documento'];
    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), $extensionesPermitidas)) {
        echo json_encode(["status" => "error", "message" => "Formato de archivo no permitido"]);
        exit;
    }

    if ($archivo['size'] > 5000000) { // Máximo 5MB
        echo json_encode(["status" => "error", "message" => "El archivo excede el tamaño máximo permitido (5MB)"]);
        exit;
    }

    // Generar un nombre único
    $nombreArchivo = "documento_" . uniqid() . "." . $extension;
    $rutaLocal = $uploadDir . $nombreArchivo;
    $rutaPublica = $baseUrl . $nombreArchivo;

    // Mover el archivo al servidor
    if (!move_uploaded_file($archivo['tmp_name'], $rutaLocal)) {
        echo json_encode(["status" => "error", "message" => "Error al subir el archivo"]);
        exit;
    }

    // Conectar a la base de datos
    $con = new LocalConector();
    $conn = $con->conectar();

    // Insertar en la tabla `DescripcionPuesto`
    $stmt = $conn->prepare("INSERT INTO DescripcionPuesto (ArchivoDescripcion) VALUES (?)");
    $stmt->bind_param("s", $rutaPublica);

    if ($stmt->execute()) {
        $idDescripcion = $stmt->insert_id; // Obtener el ID recién insertado
        $stmt->close();

        // Actualizar la tabla `Solicitudes` con el ID de la nueva descripción
        $stmtUpdate = $conn->prepare("UPDATE Solicitudes SET IdDescripcion = ? WHERE IdSolicitud = ?");
        $stmtUpdate->bind_param("ii", $idDescripcion, $idSolicitud);

        if ($stmtUpdate->execute()) {
            echo json_encode(["status" => "success", "message" => "Documento subido correctamente", "url" => $rutaPublica, "idDescripcion" => $idDescripcion]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al actualizar la solicitud con la nueva descripción"]);
        }

        $stmtUpdate->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar la descripción en la base de datos"]);
    }

    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Se requiere método POST"]);
}
?>

