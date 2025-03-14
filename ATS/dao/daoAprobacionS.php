<?php
include_once("ConexionBD.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nombreAprobador'], $_POST['accion'], $_POST['folio'])) {
        $NombreAprobador = $_POST['nombreAprobador'];
        $Accion = $_POST['accion']; // Solo usado para lógica, NO se guarda en la BD
        $FolioSolicitud = $_POST['folio'];

        // Si la acción es "rechazar", obtenemos el comentario
        $Comentario = ($Accion == 'rechazar' && isset($_POST['comentario'])) ? $_POST['comentario'] : "";

        // Determinar el estatus según la acción
        $Estatus = ($Accion == 'rechazar') ? 3 : 4;

        // Conectar a la base de datos
        $con = new LocalConector();
        $conex = $con->conectar();

        if (!$conex) {
            echo json_encode(['status' => 'error', 'message' => 'Error al conectar con la base de datos.']);
            exit();
        }

        // Guardamos la acción en la BD con el estatus correcto
        $response = registrarAprobacionEnDB($conex, $NombreAprobador, $Estatus, $Comentario, $FolioSolicitud);
        $conex->close();
    } else {
        $response = ['status' => 'error', 'message' => 'Datos incompletos.'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Se requiere método POST.'];
}

echo json_encode($response);
exit();

// Función para registrar la aprobación/rechazo en la BD
function registrarAprobacionEnDB($conex, $NombreAprobador, $Estatus, $Comentario, $FolioSolicitud)
{
    $insertAprobacion = $conex->prepare("INSERT INTO Aprobadores (Nombre, IdEstatus, FolioSolicitud, Comentarios) 
                                        VALUES (?, ?, ?, ?)");

    if (!$insertAprobacion) {
        return ['status' => 'error', 'message' => 'Error en la preparación de la consulta.'];
    }

    $insertAprobacion->bind_param("siss", $NombreAprobador, $Estatus, $FolioSolicitud, $Comentario);

    if ($insertAprobacion->execute()) {
        return ['status' => 'success', 'message' => "Acción registrada con éxito."];
    } else {
        return ['status' => 'error', 'message' => 'Error al registrar la acción del aprobador: ' . $insertAprobacion->error];
    }
}
?>
