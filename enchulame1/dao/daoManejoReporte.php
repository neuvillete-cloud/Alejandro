<?php
session_start(); // Iniciar sesión
include_once("conexion.php");

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar que la sesión esté iniciada
    if (!isset($_SESSION['numNomina']) || empty($_SESSION['numNomina'])) {
        echo json_encode(["status" => "error", "message" => "Sesión no iniciada o número de nómina inválido"]);
        exit;
    }

    $numNomina = $_SESSION['numNomina'];

    // Obtener los datos del formulario
    if (isset($_POST['descripcion'], $_POST['lugar'], $_POST['planta'], $_POST['descripcionLugar'], $_POST['idArea'])) {
        $descripcion = $_POST['descripcion'];
        $lugar = $_POST['lugar'];
        $planta = $_POST['planta'];
        $descripcionLugar = $_POST['descripcionLugar'];
        $idArea = $_POST['idArea']; // Recibido desde el JS
        $idEstatus = 1; // Estado inicial

        // Generar la fecha y hora exacta
        $fechaRegistro = date('Y-m-d H:i:s');

        // Conectar a la base de datos
        $con = new LocalConector();
        $conn = $con->conectar();

        // Obtener el nombre del usuario utilizando el número de nómina
        $stmt = $conn->prepare("SELECT Nombre FROM Usuario WHERE NumNomina = ?");
        $stmt->bind_param("s", $numNomina);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            $nombreUsuario = $usuario['Nombre'];
        } else {
            echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
            exit;
        }

        $stmt->close();

        // Manejo de la imagen con un nombre único
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = $_FILES['foto'];

            // Verificar el tipo y tamaño del archivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($foto['type'], $allowedTypes)) {
                echo json_encode(["status" => "error", "message" => "El archivo debe ser una imagen JPEG, PNG o GIF"]);
                exit;
            }

            if ($foto['size'] > 5000000) { // Limitar el tamaño a 5MB
                echo json_encode(["status" => "error", "message" => "El archivo excede el tamaño máximo permitido (5MB)"]);
                exit;
            }

            // Generar un nombre único para la imagen usando número de nómina y fecha y hora de registro
            $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $nombreUnico = "reporte_" . $numNomina . "_" . date("Ymd_His") . "." . $extension;

            // Definir la ruta de guardado (ahora la carpeta 'imagenes/fotosSolicitantes')
            $fotoPath = "imagenes/fotosSolicitantes/" . $nombreUnico;

            // Mover el archivo a la carpeta de destino
            if (!move_uploaded_file($foto['tmp_name'], $fotoPath)) {
                echo json_encode(["status" => "error", "message" => "Error al subir la imagen"]);
                exit;
            }
        } else {
            $fotoPath = null;
        }

        // Insertar el reporte en la base de datos
        $stmt = $conn->prepare("INSERT INTO Reportes (NumNomina, IdEstatus, IdArea, FotoProblema, Ubicacion, DescripcionProblema, DescripcionLugar, FechaRegistro) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisssss", $numNomina, $idEstatus, $idArea, $fotoPath, $lugar, $descripcion, $descripcionLugar, $fechaRegistro);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Reporte registrado exitosamente",
                "nombreUsuario" => $nombreUsuario // Incluimos el nombre del usuario en la respuesta
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al registrar el reporte"]);
        }

        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Se requiere método POST.']);
}
?>

