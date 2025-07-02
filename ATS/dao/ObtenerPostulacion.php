<?php
ini_set('display_errors', 0);  // Oculta errores para que no rompa el JSON
ini_set('log_errors', 1);      // Los manda al log de errores del servidor
error_reporting(E_ALL);
header('Content-Type: application/json');

include_once("ConexionBD.php");

if (!isset($_GET['IdPostulacion'])) {
    echo json_encode(['error' => 'Falta el parámetro IdPostulacion']);
    exit;
}

$idPostulacion = $_GET['IdPostulacion'];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // 1. Obtener el IdVacante desde la tabla Postulaciones
    $stmt = $conex->prepare("SELECT IdVacante FROM Postulaciones WHERE IdPostulacion = ?");
    $stmt->execute([$idPostulacion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => 'No se encontró la postulación']);
        exit;
    }

    $idVacante = $row['IdVacante'];

    // 2. Obtener los datos de la vacante usando el IdVacante, con JOIN para área
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
        echo json_encode(['error' => 'No se encontró la vacante']);
        exit;
    }

    echo json_encode($vacante);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}

