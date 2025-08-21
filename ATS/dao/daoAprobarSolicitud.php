<?php
session_start();
include_once("ConexionBD.php");

header('Content-Type: application/json');

if (!isset($_SESSION['NumNomina'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit;
}
$numNominaActual = $_SESSION['NumNomina'];

if (isset($_GET['folio'])) {
    $folio = $_GET['folio'];
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- LÓGICA CORREGIDA ---
    // 1. Buscamos el NOMBRE COMPLETO del usuario que está en la sesión
    $nombreCompletoActual = '';
    $stmtNombre = $conex->prepare("SELECT Nombre FROM Usuario WHERE NumNomina = ?");
    $stmtNombre->bind_param("s", $numNominaActual);
    $stmtNombre->execute();
    $resNombre = $stmtNombre->get_result();
    if($resNombre->num_rows > 0) {
        $nombreCompletoActual = $resNombre->fetch_assoc()['Nombre'];
    }
    $stmtNombre->close();
    // --- FIN DE LA CORRECCIÓN ---

    $sql = "SELECT s.*, a.NombreArea, e.NombreEstatus 
            FROM Solicitudes s
            JOIN Area a ON s.IdArea = a.IdArea
            JOIN Estatus e ON s.IdEstatus = e.IdEstatus
            WHERE s.FolioSolicitud = ?";
    $stmt = $conex->prepare($sql);
    $stmt->bind_param("s", $folio);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $solicitud = $result->fetch_assoc();

        // 2. Verificamos si el NOMBRE COMPLETO ya votó en la tabla Aprobadores
        $haVotado = false;
        if (!empty($nombreCompletoActual)) {
            $stmtCheck = $conex->prepare("SELECT IdAprobador FROM Aprobadores WHERE FolioSolicitud = ? AND Nombre = ?");
            $stmtCheck->bind_param("ss", $folio, $nombreCompletoActual);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $haVotado = true;
            }
            $stmtCheck->close();
        }

        echo json_encode([
            'status' => 'success',
            'data' => $solicitud,
            'usuario_ya_voto' => $haVotado
        ]);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró la solicitud.']);
    }
    $stmt->close();
    $conex->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se proporcionó folio.']);
}
?>