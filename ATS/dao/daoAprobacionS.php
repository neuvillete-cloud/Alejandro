<?php
header('Content-Type: application/json');
session_start();
include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'], $_POST['folio'], $_POST['num_nomina_aprobador'])) {

        $Accion = $_POST['accion'];
        $FolioSolicitud = $_POST['folio'];
        $NumNominaAprobador = $_POST['num_nomina_aprobador'];
        $Comentario = ($Accion == 'rechazar' && isset($_POST['comentario'])) ? $_POST['comentario'] : "";
        $IdEstatus = ($Accion == 'rechazar') ? 3 : 5;

        $con = new LocalConector();
        $conex = $con->conectar();

        if (!$conex) {
            echo json_encode(['status' => 'error', 'message' => 'Error al conectar con la base de datos.']);
            exit();
        }

        // Llamamos a la función que ahora buscará el nombre antes de insertar
        $response = registrarDecisionAprobador($conex, $NumNominaAprobador, $IdEstatus, $FolioSolicitud, $Comentario);

        $conex->close();
    } else {
        $response = ['status' => 'error', 'message' => 'Datos incompletos. Faltó folio, acción o nómina.'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Se requiere método POST.'];
}

echo json_encode($response);
exit();


/**
 * Busca el nombre del usuario, verifica si ya votó, y luego inserta el registro.
 */
function registrarDecisionAprobador($conex, $NumNomina, $IdEstatus, $FolioSolicitud, $Comentario)
{
    // 1. Buscamos el nombre completo del aprobador usando su nómina
    $stmtNombre = $conex->prepare("SELECT Nombre FROM Usuario WHERE NumNomina = ?");
    $stmtNombre->bind_param("s", $NumNomina);
    $stmtNombre->execute();
    $resultadoNombre = $stmtNombre->get_result();

    if ($resultadoNombre->num_rows === 0) {
        return ['status' => 'error', 'message' => 'No se pudo encontrar al aprobador en la base de datos de usuarios.'];
    }
    $nombreCompletoAprobador = $resultadoNombre->fetch_assoc()['Nombre'];
    $stmtNombre->close();

    // 2. CON EL NOMBRE COMPLETO, verificamos si ya existe un registro para este usuario y folio
    $stmtCheck = $conex->prepare("SELECT IdAprobador FROM Aprobadores WHERE FolioSolicitud = ? AND Nombre = ?");
    $stmtCheck->bind_param("ss", $FolioSolicitud, $nombreCompletoAprobador);
    $stmtCheck->execute();

    if ($stmtCheck->get_result()->num_rows > 0) {
        // Si ya existe, devolvemos un error
        return ['status' => 'error', 'message' => 'Ya has registrado una acción para esta solicitud previamente.'];
    }
    $stmtCheck->close();

    // 3. Si no existe, procedemos a insertar el registro
    $query = "INSERT INTO Aprobadores (Nombre, IdEstatus, FolioSolicitud, Comentarios) VALUES (?, ?, ?, ?)";
    $insertAprobacion = $conex->prepare($query);

    if (!$insertAprobacion) {
        return ['status' => 'error', 'message' => 'Error en la preparación de la consulta de inserción: ' . $conex->error];
    }

    $insertAprobacion->bind_param("siss", $nombreCompletoAprobador, $IdEstatus, $FolioSolicitud, $Comentario);

    if ($insertAprobacion->execute()) {
        return ['status' => 'success', 'message' => "Acción registrada con éxito."];
    } else {
        return ['status' => 'error', 'message' => 'Error al registrar la acción: ' . $insertAprobacion->error];
    }
}
?>