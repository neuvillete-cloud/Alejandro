<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once("ConexionBD.php");


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nombreAprobador'], $_POST['accion'], $_POST['folio'])) {
        $NombreAprobador = $_POST['nombreAprobador'];
        $Accion = $_POST['accion']; // Solo usado para lógica, NO se guarda en la BD
        $FolioSolicitud = $_POST['folio'];

        // Si la acción es "rechazar", obtenemos el comentario
        $Comentario = ($Accion == 'rechazar' && isset($_POST['comentario'])) ? $_POST['comentario'] : "";

        echo 'INSERT INTO Aprobadores (Nombre, IdEstatus, FolioSolicitud, Comentarios) VALUES ("'.$NombreAprobador.'", 1, "'.$FolioSolicitud.'", "'.$Comentario.'")';

        // Conectar a la base de datos
        $con = new LocalConector();
        $conex = $con->conectar();

        if (!$conex) {
            echo json_encode(['status' => 'error', 'message' => 'Error al conectar con la base de datos.']);
            exit();
        }

        // Guardamos la acción en la BD sin la variable "Accion"
        $response = registrarAprobacionEnDB($conex, $NombreAprobador, $Comentario, $FolioSolicitud);
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
function registrarAprobacionEnDB($conex, $NombreAprobador, $Comentario, $FolioSolicitud)
{
    $Estatus = 1; // Se asume que siempre es 1

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
