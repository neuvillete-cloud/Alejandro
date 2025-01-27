<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar que todos los datos requeridos están presentes
    if (isset($_POST['nombre'], $_POST['area'], $_POST['puesto'], $_POST['tipo'])) {
        // Obtener los datos del formulario
        $NumNomina = $_SESSION['NumNomina'] ?? null; // Obtener NumNomina desde la sesión
        $Nombre = $_POST['nombre'];
        $NombreArea = $_POST['area'];
        $Puesto = $_POST['puesto'];
        $TipoContratacion = $_POST['tipo'];

        // Asignar NombreReemplazo solo si el tipo de contratación es 'reemplazo'
        $NombreReemplazo = ($TipoContratacion == 'reemplazo' && isset($_POST['reemplazoNombre'])) ? $_POST['reemplazoNombre'] : null;

        if (!$NumNomina) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró el número de nómina en la sesión.']);
            exit();
        }

        $FechaSolicitud = date('Y-m-d H:i:s'); // Generar la fecha y hora actual
        $FolioSolicitud = uniqid('FOLIO-'); // Generar un folio único
        $IdEstatus = 1; // Estatus inicial

        $con = new LocalConector();
        $conex = $con->conectar();

        // Obtener el ID del área a partir del nombre del área
        $consultaArea = $conex->prepare("SELECT IdArea FROM Area WHERE NombreArea = ?");
        $consultaArea->bind_param("s", $NombreArea);
        $consultaArea->execute();
        $resultadoArea = $consultaArea->get_result();

        if ($resultadoArea->num_rows > 0) {
            $row = $resultadoArea->fetch_assoc();
            $IdArea = $row['IdArea'];

            // Insertar la solicitud en la base de datos
            $response = registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus);
        } else {
            $response = array('status' => 'error', 'message' => 'El área proporcionada no existe.');
        }

        $conex->close();
    } else {
        $response = array('status' => 'error', 'message' => 'Datos incompletos.');
    }
} else {
    $response = array('status' => 'error', 'message' => 'Se requiere método POST.');
}

echo json_encode($response);
exit();

// Función para registrar la solicitud en la base de datos
function registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus)
{
    $insertSolicitud = $conex->prepare("INSERT INTO Solicitudes (NumNomina, IdArea, Puesto, TipoContratacion, Nombre, NombreReemplazo, FechaSolicitud, FolioSolicitud, IdEstatus)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertSolicitud->bind_param("sissssssi", $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $NombreReemplazo, $FechaSolicitud, $FolioSolicitud, $IdEstatus);
    $resultado = $insertSolicitud->execute();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => 'Solicitud registrada exitosamente', 'folio' => $FolioSolicitud);
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar la solicitud');
    }

    return $response;
}

?>
