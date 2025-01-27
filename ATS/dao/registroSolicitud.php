<?php
session_start(); // Iniciar sesión
include_once("ConexionBD.php");

date_default_timezone_set('America/Mexico_City'); // Establecer zona horaria

// Revisar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar que todos los datos requeridos están presentes
    if (isset($_POST['NombreArea'], $_POST['Puesto'], $_POST['TipoContratacion'], $_POST['Nombre'])) {
        // Obtener los datos del formulario
        if (isset($_SESSION['NumNomina'])) {
            $NumNomina = $_SESSION['NumNomina']; // Obtener NumNomina desde la sesión
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'No se encontró NumNomina en la sesión.'));
            exit();
        }

        $NombreArea = $_POST['NombreArea'];
        $Puesto = $_POST['Puesto'];
        $TipoContratacion = $_POST['TipoContratacion'];
        $Nombre = $_POST['Nombre'];

        $FechaSolicitud = date('Y-m-d H:i:s'); // Generar la fecha y hora actual
        $FolioSolicitud = uniqid('FOLIO-'); // Generar un folio único
        $IdEstatus = 1; // Estatus inicial

        $con = new LocalConector();
        $conex = $con->conectar();

        // Obtener el ID del área a partir del nombre del área
        $consultaArea = $conex->prepare("SELECT IdArea FROM Areas WHERE NombreArea = ?");
        $consultaArea->bind_param("s", $NombreArea);
        $consultaArea->execute();
        $resultadoArea = $consultaArea->get_result();

        if ($resultadoArea->num_rows > 0) {
            $row = $resultadoArea->fetch_assoc();
            $IdArea = $row['IdArea'];

            // Insertar la solicitud en la base de datos
            $response = registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $FechaSolicitud, $FolioSolicitud, $IdEstatus);
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
function registrarSolicitudEnDB($conex, $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $FechaSolicitud, $FolioSolicitud, $IdEstatus)
{
    $insertSolicitud = $conex->prepare("INSERT INTO Solicitudes (NumNomina, IdArea, Puesto, TipoContratacion, Nombre, FechaSolicitud, FolioSolicitud, IdEstatus)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertSolicitud->bind_param("sisssssi", $NumNomina, $IdArea, $Puesto, $TipoContratacion, $Nombre, $FechaSolicitud, $FolioSolicitud, $IdEstatus);
    $resultado = $insertSolicitud->execute();

    if ($resultado) {
        $response = array('status' => 'success', 'message' => 'Solicitud registrada exitosamente', 'folio' => $FolioSolicitud);
    } else {
        $response = array('status' => 'error', 'message' => 'Error al registrar la solicitud');
    }

    return $response;
}
?>
