<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
$offset = ($pagina - 1) * $limite;

// Consulta con paginación y JOIN
$sql = "SELECT SQL_CALC_FOUND_ROWS 
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
            V.Fecha, 
            V.Imagen
        FROM Vacantes V
        INNER JOIN Area A ON V.IdArea = A.IdArea
        WHERE V.IdEstatus = 1
        ORDER BY V.Fecha DESC
        LIMIT $limite OFFSET $offset";

$resultado = $conn->query($sql);

// Obtener las vacantes
$vacantes = [];

while ($row = $resultado->fetch_assoc()) {
    $vacantes[] = [
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
        'FechaPublicacion' => calcularTiempoTranscurrido($row['Fecha']),
        'Imagen' => $row['Imagen']
    ];
}

// Obtener el total de resultados sin paginación
$totalResult = $conn->query("SELECT FOUND_ROWS() AS total")->fetch_assoc();
$totalVacantes = $totalResult['total'];

$conn->close();

// Responder en formato JSON
echo json_encode([
    'vacantes' => $vacantes,
    'total' => $totalVacantes,
    'pagina' => $pagina,
    'limite' => $limite
], JSON_UNESCAPED_UNICODE);

// Función para mostrar tiempo relativo
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
