<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

$sql = "SELECT IdVacante, Titulo, Ciudad, Estado, Sueldo, Requisitos, Beneficios, Descripcion, Area, Escolaridad, Idioma, Horario, EspacioTrabajo, FechaPublicacion
        FROM Vacantes 
        WHERE IdEstatus = 1
        ORDER BY FechaPublicacion DESC";

$resultado = $conn->query($sql);

$vacantes = [];

while ($row = $resultado->fetch_assoc()) {
    $row['FechaPublicacion'] = calcularTiempoTranscurrido($row['FechaPublicacion']); // Reemplazamos la fecha por "Hace X días"
    $vacantes[] = $row;
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
