<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

if (!isset($_GET['IdPostulacion'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro IdPostulacion']);
    exit;
}

$idPostulacion = intval($_GET['IdPostulacion']);

$conn = (new LocalConector())->conectar();

// 1. Obtener IdVacante de Postulaciones
$sql1 = "SELECT IdVacante FROM Postulaciones WHERE IdPostulacion = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $idPostulacion);
$stmt1->execute();
$result1 = $stmt1->get_result();

if (!$row1 = $result1->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontró la postulación']);
    $conn->close();
    exit;
}

$idVacante = $row1['IdVacante'];

// 2. Obtener detalles de la vacante
$sql2 = "SELECT 
            V.IdVacante, 
            V.TituloVacante, 
            V.Ciudad, 
            V.Estado, 
            V.Sueldo, 
            V.Requisitos, 
            V.Beneficios, 
            V.Descripcion, 
            A.NombreArea, 
            V.EscolaridadMinima, 
            V.Idioma, 
            V.Especialidad, 
            V.Horario, 
            V.EspacioTrabajo, 
            V.Fecha
        FROM Vacantes V
        INNER JOIN Area A ON V.IdArea = A.IdArea
        WHERE V.IdVacante = ? AND V.IdEstatus = 1
        LIMIT 1";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $idVacante);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($row2 = $result2->fetch_assoc()) {
    $vacante = [
        'IdVacante' => $row2['IdVacante'],
        'TituloVacante' => $row2['TituloVacante'],
        'Ciudad' => $row2['Ciudad'],
        'Estado' => $row2['Estado'],
        'Sueldo' => $row2['Sueldo'],
        'Requisitos' => $row2['Requisitos'],
        'Beneficios' => $row2['Beneficios'],
        'Descripcion' => $row2['Descripcion'],
        'Area' => $row2['NombreArea'],
        'EscolaridadMinima' => $row2['EscolaridadMinima'],
        'Idioma' => $row2['Idioma'],
        'Especialidad' => $row2['Especialidad'],
        'Horario' => $row2['Horario'],
        'EspacioTrabajo' => $row2['EspacioTrabajo'],
        'FechaPublicacion' => calcularTiempoTranscurrido($row2['Fecha'])
    ];

    echo json_encode($vacante, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontró la vacante']);
}

$conn->close();

function calcularTiempoTranscurrido($fechaPublicacion) {
    $fecha = new DateTime($fechaPublicacion);
    $hoy = new DateTime();
    $diferencia = $hoy->diff($fecha);

    if ($diferencia->days === 0) {
        return 'Hoy';
    } elseif ($diferencia->days === 1) {
        return 'Hace 1 día';
    } else {
        return 'Hace ' . $diferencia->days . ' días';
    }
}
?>
