<?php
include_once("ConexionBD.php");

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar que los datos requeridos están presentes
    if (isset($_POST['nombreAprobador'], $_POST['accion'], $_POST['folio'])) {
        // Obtener los datos del formulario
        $NombreAprobador = $_POST['nombreAprobador'];
        $Accion = $_POST['accion'];
        $FolioSolicitud = $_POST['folio'];

        // Asignar Comentario solo si la acción es "rechazar", de lo contrario, se deja vacío
        $Comentario = ($Accion == 'rechazar' && isset($_POST['comentario'])) ? $_POST['comentario'] : "";

        // Conectar a la base de datos
        $con = new LocalConector();
        $conex = $con->conectar();
        $response = registrarAprobacionEnDB($conex, $NombreAprobador, $Accion, $Comentario, $FolioSolicitud);
        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Se requiere método POST.');
}

echo json_encode($response);
exit();

// Función para registrar la aprobación/rechazo en la base de datos
function registrarAprobacionEnDB($conex, $NombreAprobador, $Accion, $Comentario, $FolioSolicitud)
{
    // Siempre establecer el estatus en 1
    $Estatus = 1;

    // Insertar la acción del aprobador en la tabla de Aprobadores
    $insertAprobacion = $conex->prepare("INSERT INTO Aprobadores (Nombre, IdEstatus, FolioSolicitud, Comentarios)
                                        VALUES (?, ?, ?, ?)");
    $insertAprobacion->bind_param("siss", $NombreAprobador, $Estatus, $FolioSolicitud, $Comentario);

    $resultado = $insertAprobacion->execute();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => "Solicitud {$Accion} con éxito.");
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar la acción del aprobador.');
    }

    return $response;
}
?>
