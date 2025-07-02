<?php
// Configuración para depurar
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Captura cualquier salida accidental
ob_start();

include_once("ConexionBD.php");

function devolverJSON($datos) {
    // Asegura que no haya salida antes
    ob_clean();
    echo json_encode($datos);
    exit;
}

if (!isset($_GET['IdPostulacion'])) {
    devolverJSON(['error' => 'Falta el parámetro IdPostulacion']);
}

$idPostulacion = $_GET['IdPostulacion'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Paso 1: obtener IdVacante
    $stmt = $conex->prepare("SELECT IdVacante FROM Postulaciones WHERE IdPostulacion = ?");
    $stmt->execute([$idPostulacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        devolverJSON(['error' => 'No se encontró la postulación']);
    }

    $idVacante = $row['IdVacante'];

    // Paso 2: obtener datos de la vacante
    $sql = "
        SELECT 
            V.IdVacante, 
            V.TituloVacante, 
            V.Ciudad, 
            V.Estado, 
            V.Sueldo, 
            V.Requisitos, 
            V.Beneficios, 
            V.Descripcion, 
            A.NombreArea AS Area, 
            V.EscolaridadMinima, 
            V.Idioma, 
            V.Especialidad, 
            V.Horario, 
            V.EspacioTrabajo, 
            V.Fecha AS FechaPublicacion
        FROM Vacantes V
        INNER JOIN Area A ON V.IdArea = A.IdArea
        WHERE V.IdVacante = ?
        LIMIT 1
    ";

    $stmtVacante = $conex->prepare($sql);
    $stmtVacante->execute([$idVacante]);
    $vacante = $stmtVacante->fetch(PDO::FETCH_ASSOC);

    if (!$vacante) {
        devolverJSON(['error' => 'No se encontró la vacante']);
    }

    // Enviar JSON final limpio
    devolverJSON($vacante);

} catch (PDOException $e) {
    devolverJSON(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
