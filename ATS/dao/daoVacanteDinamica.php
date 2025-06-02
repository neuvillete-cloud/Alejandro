<?php
require_once("ConexionBD.php");

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$con = new LocalConector();
$conn = $con->conectar();

$sql = "SELECT IdVacante, TituloVacante, Ciudad, Estado, Fecha, Sueldo FROM Vacantes ORDER BY Fecha DESC";
$result = $conn->query($sql);

$vacantes = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $vacantes[] = [
            'id' => $row['IdVacante'],
            'titulo' => $row['TituloVacante'],
            'ubicacion' => $row['Ciudad'] . ', ' . $row['Estado'],
            'fecha' => calcularTiempoTranscurrido($row['Fecha']),
            'sueldo' => $row['Sueldo'] !== '' ? $row['Sueldo'] : 'Sueldo no mostrado por la empresa'
        ];
    }
}

echo json_encode($vacantes, JSON_UNESCAPED_UNICODE);
$conn->close();


// Función para calcular "Hace X días"
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

