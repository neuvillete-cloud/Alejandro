<?php
require_once "ConexionBD.php";

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

// Autocompletado: títulos de vacante
if (isset($_GET['autocomplete']) && $_GET['autocomplete'] === 'titulo' && !empty($_GET['q'])) {
    $termino = "%" . trim($_GET['q']) . "%";
    $stmt = $conn->prepare("
        SELECT DISTINCT V.TituloVacante 
        FROM Vacantes V
        INNER JOIN Area A ON V.IdArea = A.IdArea
        WHERE (V.TituloVacante LIKE ? OR A.NombreArea LIKE ?)
        AND V.IdEstatus = 1
        ORDER BY V.TituloVacante ASC
        LIMIT 10
    ");
    $stmt->bind_param("ss", $termino, $termino);
    $stmt->execute();
    $res = $stmt->get_result();

    $sugerencias = [];
    while ($row = $res->fetch_assoc()) {
        $sugerencias[] = $row['TituloVacante'];
    }
    echo json_encode($sugerencias, JSON_UNESCAPED_UNICODE);
    $stmt->close();
    $conn->close();
    exit;
}

// Autocompletado: nombre de candidato
if (isset($_GET['autocomplete']) && $_GET['autocomplete'] === 'nombre' && !empty($_GET['q'])) {
    $termino = "%" . trim($_GET['q']) . "%";
    $stmt = $conn->prepare("
        SELECT DISTINCT CONCAT(Nombre, ' ', Apellidos) AS NombreCompleto 
        FROM Candidatos 
        WHERE CONCAT(Nombre, ' ', Apellidos) LIKE ?
        ORDER BY Nombre ASC
        LIMIT 10
    ");
    $stmt->bind_param("s", $termino);
    $stmt->execute();
    $res = $stmt->get_result();

    $sugerencias = [];
    while ($row = $res->fetch_assoc()) {
        $sugerencias[] = $row['NombreCompleto'];
    }
    echo json_encode($sugerencias, JSON_UNESCAPED_UNICODE);
    $stmt->close();
    $conn->close();
    exit;
}

// ----------- Filtro General de Candidatos -----------

$filtros = [];
$params = [];
$tipos = "";

if (!empty($_GET['titulo'])) {
    $filtros[] = "v.TituloVacante LIKE ?";
    $params[] = '%' . $_GET['titulo'] . '%';
    $tipos .= "s";
}

if (!empty($_GET['nombre'])) {
    $filtros[] = "CONCAT(c.Nombre, ' ', c.Apellidos) LIKE ?";
    $params[] = '%' . $_GET['nombre'] . '%';
    $tipos .= "s";
}

if (!empty($_GET['area'])) {
    $filtros[] = "a.NombreArea = ?";
    $params[] = $_GET['area'];
    $tipos .= "s";
}

$sql = "SELECT 
            p.IdPostulacion,
            p.fechaSeleccion,
            c.Nombre AS NombreCandidato,
            c.Apellidos AS ApellidosCandidato,
            c.Telefono,
            c.Correo,
            v.TituloVacante,
            e.NombreEstatus,
            a.NombreArea,
            s.Nombre AS NombreSelector
        FROM Postulaciones p
        INNER JOIN Candidatos c ON c.IdCandidato = p.IdCandidato
        INNER JOIN Vacantes v ON v.IdVacante = p.IdVacante
        INNER JOIN Estatus e ON e.IdEstatus = p.IdEstatus
        INNER JOIN Area a ON a.IdArea = v.IdArea
        INNER JOIN Solicitudes s ON s.IdSolicitud = v.IdSolicitud
        WHERE p.IdEstatus = 9";

if (!empty($filtros)) {
    $sql .= " AND " . implode(" AND ", $filtros);
}

// Ordenamiento
$orden = "p.fechaSeleccion DESC";
if (!empty($_GET['fecha']) && $_GET['fecha'] === 'antiguas') {
    $orden = "p.fechaSeleccion ASC";
}
$sql .= " ORDER BY $orden";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al preparar la consulta']);
    $conn->close();
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$candidatos = [];

while ($row = $result->fetch_assoc()) {
    $fecha = $row['fechaSeleccion'];
    $fechaFormateada = $fecha && $fecha !== '0000-00-00 00:00:00'
        ? date("d/m/Y", strtotime($fecha))
        : "Sin definir";

    $candidatos[] = [
        'IdPostulacion'   => $row['IdPostulacion'],
        'NombreCompleto'  => $row['NombreCandidato'] . ' ' . $row['ApellidosCandidato'],
        'Telefono'        => $row['Telefono'],
        'Correo'          => $row['Correo'],
        'TituloVacante'   => $row['TituloVacante'],
        'NombreEstatus'   => $row['NombreEstatus'],
        'NombreArea'      => $row['NombreArea'],
        'NombreSelector'  => $row['NombreSelector'],
        'FechaSeleccion'  => $fechaFormateada,
        'Foto'            => 'imagenes/user-default.png'
    ];
}

echo json_encode($candidatos, JSON_UNESCAPED_UNICODE);
$conn->close();
