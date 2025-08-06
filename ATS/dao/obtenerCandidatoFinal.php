<?php
require_once "ConexionBD.php";

header('Content-Type: application/json');
date_default_timezone_set('America/Mexico_City');

$conn = (new LocalConector())->conectar();

// Filtros dinámicos
$filtros = [];
$params = [];
$tipos = "";

// Filtro: título
if (!empty($_GET['titulo'])) {
    $filtros[] = "v.TituloVacante LIKE ?";
    $params[] = '%' . $_GET['titulo'] . '%';
    $tipos .= "s";
}

// Filtro: nombre (nombre + apellidos)
if (!empty($_GET['nombre'])) {
    $filtros[] = "CONCAT(c.Nombre, ' ', c.Apellidos) LIKE ?";
    $params[] = '%' . $_GET['nombre'] . '%';
    $tipos .= "s";
}

// Filtro: área
if (!empty($_GET['area'])) {
    $filtros[] = "a.NombreArea = ?";
    $params[] = $_GET['area'];
    $tipos .= "s";
}

// Base SQL
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

// Agregar filtros si hay
if (!empty($filtros)) {
    $sql .= " AND " . implode(" AND ", $filtros);
}

// Ordenamiento por fecha
$orden = "p.fechaSeleccion DESC"; // por defecto
if (!empty($_GET['fecha']) && $_GET['fecha'] === 'antiguas') {
    $orden = "p.fechaSeleccion ASC";
}
$sql .= " ORDER BY $orden";

// Preparar y ejecutar
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
