<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 5;
$offset = ($pagina - 1) * $limite;

// Filtros recibidos
$filtros = [];

// Salario
if (!empty($_GET['salario'])) {
    $rango = explode("-", $_GET['salario']);
    if (count($rango) === 2) {
        $min = intval($rango[0]);
        $max = intval($rango[1]);
        $filtros[] = "(CAST(REPLACE(REPLACE(V.Sueldo, '$', ''), ',', '') AS UNSIGNED) BETWEEN $min AND $max)";
    }
}

// Modalidad
if (!empty($_GET['modalidad'])) {
    $modalidad = $conn->real_escape_string($_GET['modalidad']);
    $filtros[] = "V.EspacioTrabajo = '$modalidad'";
}

// Tipo de contrato
if (!empty($_GET['contrato'])) {
    $contrato = $conn->real_escape_string($_GET['contrato']);
    $filtros[] = "V.TipoContrato = '$contrato'";
}

// Escolaridad
if (!empty($_GET['educacion'])) {
    $educacion = $conn->real_escape_string($_GET['educacion']);
    $filtros[] = "V.EscolaridadMinima LIKE '%$educacion%'";
}

// Orden por fecha
if (!empty($_GET['fecha'])) {
    $ordenFecha = $_GET['fecha'] === 'antiguas' ? "V.Fecha ASC" : "V.Fecha DESC";
} else {
    $ordenFecha = "V.Fecha DESC";
}

// Búsqueda por título o área
if (!empty($_GET['busqueda'])) {
    $busqueda = $conn->real_escape_string($_GET['busqueda']);
    $filtros[] = "(
        CONVERT(V.TituloVacante USING utf8) COLLATE utf8_general_ci LIKE '%$busqueda%' 
        OR CONVERT(A.NombreArea USING utf8) COLLATE utf8_general_ci LIKE '%$busqueda%'
    )";
}

// Búsqueda por ubicación
if (!empty($_GET['ubicacion'])) {
    $ubicacion = $conn->real_escape_string($_GET['ubicacion']);
    $filtros[] = "(
        CONVERT(V.Ciudad USING utf8) COLLATE utf8_general_ci LIKE '%$ubicacion%' 
        OR CONVERT(V.Estado USING utf8) COLLATE utf8_general_ci LIKE '%$ubicacion%'
    )";
}

// Condición base
$condiciones = ["V.IdEstatus = 1"];

// Agregar filtros a la cláusula WHERE
$condiciones = array_merge($condiciones, $filtros);
$whereSQL = "WHERE " . implode(" AND ", $condiciones);

// Consulta principal
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
            V.Imagen,
            V.Visitas  -- <--- CAMBIO APLICADO AQUÍ
        FROM Vacantes V
        INNER JOIN Area A ON V.IdArea = A.IdArea
        $whereSQL
        ORDER BY $ordenFecha
        LIMIT $limite OFFSET $offset";

$resultado = $conn->query($sql);

// Resultado de vacantes
$vacantes = [];
while ($row = $resultado->fetch_assoc()) {
    $vacantes[] = [
        'IdVacante'      => $row['IdVacante'],
        'Visitas'        => $row['Visitas'], // <--- CAMBIO APLICADO AQUÍ
        'Titulo'         => $row['TituloVacante'],
        'Ciudad'         => $row['Ciudad'],
        'Estado'         => $row['Estado'],
        'Sueldo'         => $row['Sueldo'],
        'Requisitos'     => $row['Requisitos'],
        'Beneficios'     => $row['Beneficios'],
        'Descripcion'    => $row['Descripcion'],
        'Area'           => $row['NombreArea'],
        'Escolaridad'    => $row['EscolaridadMinima'],
        'Idioma'         => $row['Idioma'],
        'Especialidad'   => $row['Especialidad'],
        'Horario'        => $row['Horario'],
        'EspacioTrabajo' => $row['EspacioTrabajo'],
        'FechaPublicacion' => calcularTiempoTranscurrido($row['Fecha']),
        'Imagen'         => $row['Imagen']
    ];
}

// Total de resultados
$totalResult = $conn->query("SELECT FOUND_ROWS() AS total")->fetch_assoc();
$totalVacantes = $totalResult['total'];

$conn->close();

// Enviar JSON al cliente
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
        return "Publicado hoy";
    } elseif ($diferencia->days === 1) {
        return "Hace 1 día";
    } else {
        return "Hace " . $diferencia->days . " días";
    }
}
?>