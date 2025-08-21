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
 * Busca el nombre del usuario por su nómina y luego inserta el registro en la tabla Aprobadores.
 */
function registrarDecisionAprobador($conex, $NumNomina, $IdEstatus, $FolioSolicitud, $Comentario)
{
    // --- PASO ADICIONAL: Buscar el nombre del aprobador usando su nómina ---

    // Corregido a "Usuario" (singular) según tu tabla
    $stmtNombre = $conex->prepare("SELECT Nombre FROM Usuario WHERE NumNomina = ?");
    $stmtNombre->bind_param("s", $NumNomina);
    $stmtNombre->execute();
    $resultadoNombre = $stmtNombre->get_result();

    if ($resultadoNombre->num_rows === 0) {
        // Si no encontramos al usuario, devolvemos un error.
        return ['status' => 'error', 'message' => 'No se pudo encontrar al aprobador en la base de datos de usuarios.'];
    }

    // Guardamos el nombre completo que encontramos
    $fila = $resultadoNombre->fetch_assoc();
    $nombreCompletoAprobador = $fila['Nombre'];
    $stmtNombre->close();

    // --- FIN DEL PASO ADICIONAL ---


    // Ahora, procedemos a insertar el registro usando el nombre que encontramos
    $query = "INSERT INTO Aprobadores (Nombre, IdEstatus, FolioSolicitud, Comentarios) 
              VALUES (?, ?, ?, ?)";

    $insertAprobacion = $conex->prepare($query);

    if (!$insertAprobacion) {
        return ['status' => 'error', 'message' => 'Error en la preparación de la consulta de inserción: ' . $conex->error];
    }

    // Usamos el nombre completo que encontramos ($nombreCompletoAprobador) en lugar del NumNomina
    $insertAprobacion->bind_param("siss", $nombreCompletoAprobador, $IdEstatus, $FolioSolicitud, $Comentario);

    if ($insertAprobacion->execute()) {
        return ['success' => true, 'message' => "Acción registrada con éxito."];
    } else {
        return ['success' => false, 'message' => 'Error al registrar la acción: ' . $insertAprobacion->error];
    }
}
?>