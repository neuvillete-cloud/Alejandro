<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

$sql = "SELECT IdVacante, TituloVacante, Ciudad, Estado, Sueldo, Requisitos, Beneficios, Descripcion, IdArea, EscolaridadMinima, Idioma, Horario, EspacioTrabajo, Fecha
        FROM Vacantes 
        WHERE IdEstatus = 1
        ORDER BY Fecha DESC";

$resultado = $conn->query($sql);

$vacantes = [];

while ($row = $resultado->fetch_assoc()) {
    // Convertir los textos con saltos de línea en arreglos
    $requisitos = array_filter(array_map('trim', explode("\n", $row['Requisitos'])));
    $beneficios = array_filter(array_map('trim', explode("\n", $row['Beneficios'])));
    $descripcion = array_filter(array_map('trim', explode("\n", $row['Descripcion'])));

    $vacantes[] = [
        'IdVacante' => $row['IdVacante'],
        'Titulo' => $row['TituloVacante'],
        'Ciudad' => $row['Ciudad'],
        'Estado' => $row['Estado'],
        'Sueldo' => $row['Sueldo'],
        'Requisitos' => $requisitos,
        'Beneficios' => $beneficios,
        'Descripcion' => $descripcion,
        'Area' => $row['IdArea'],
        'Escolaridad' => $row['EscolaridadMinima'],
        'Idioma' => $row['Idioma'],
        'Horario' => $row['Horario'],
        'EspacioTrabajo' => $row['EspacioTrabajo'],
        'FechaPublicacion' => calcularTiempoTranscurrido($row['Fecha'])
    ];
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
