<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de vacante no proporcionado']);
    exit;
}

$idVacante = intval($_GET['id']);
$conn = (new LocalConector())->conectar();

$sql = "SELECT 
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

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idVacante);
$stmt->execute();
$resultado = $stmt->get_result();

if ($row = $resultado->fetch_assoc()) {
    $vacante = [
        'IdVacante' => $row['IdVacante'],
        'Titulo' => $row['TituloVacante'],
        'Ciudad' => $row['Ciudad'],
        'Estado' => $row['Estado'],
        'Sueldo' => $row['Sueldo'],
        'Requisitos' => $row['Requisitos'],
        'Beneficios' => $row['Beneficios'],
        'Descripcion' => $row['Descripcion'],
        'Area' => $row['NombreArea'],
        'Escolaridad' => $row['EscolaridadMinima'],
        'Idioma' => $row['Idioma'],
        'Especialidad' => $row['Especialidad'],
        'Horario' => $row['Horario'],
        'EspacioTrabajo' => $row['EspacioTrabajo'],
        'FechaPublicacion' => calcularTiempoTranscurrido($row['Fecha'])
    ];

    echo json_encode($vacante, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Vacante no encontrada']);
}

$conn->close();

// Función reutilizada
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
