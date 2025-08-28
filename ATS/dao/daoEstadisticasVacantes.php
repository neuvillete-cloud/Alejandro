<?php
include_once("ConexionBD.php"); // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

// IDs de Estatus de la tabla Postulaciones
define('ID_ESTATUS_POR_REVISAR', 1);
define('ID_ESTATUS_SIGUIENTE_FASE', 4);
define('ID_ESTATUS_PARA_CONTRATAR', 9);
define('ID_ESTATUS_DESCARTADO', 3);

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $sql = "
        SELECT 
            v.IdVacante,
            v.TituloVacante,
            v.Ciudad,
            v.Estado,
            v.Fecha AS FechaCreacion,
            v.Visitas, -- <--- CAMBIO 1: SELECCIONAMOS LA COLUMNA DE VISTAS
            (SELECT NombreEstatus FROM Estatus WHERE IdEstatus = v.IdEstatus) AS EstatusVacante,
            
            COUNT(CASE WHEN p.IdEstatus = " . ID_ESTATUS_POR_REVISAR . " THEN 1 END) AS PorRevisar,
            COUNT(CASE WHEN p.IdEstatus IN (" . ID_ESTATUS_SIGUIENTE_FASE . ", " . ID_ESTATUS_PARA_CONTRATAR . ") THEN 1 END) AS MeInteresan,
            COUNT(CASE WHEN p.IdEstatus = " . ID_ESTATUS_DESCARTADO . " THEN 1 END) AS Descartados
            
        FROM 
            Vacantes v
        LEFT JOIN 
            Postulaciones p ON v.IdVacante = p.IdVacante
        GROUP BY 
            v.IdVacante
        ORDER BY 
            v.Fecha DESC;
    ";

    $stmt = $conex->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $vacantes = $result->fetch_all(MYSQLI_ASSOC);

    // CAMBIO 2: El array de respuesta ya contendrá 'Visitas' para cada vacante
    echo json_encode(['status' => 'success', 'data' => $vacantes]);

    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $e->getMessage()]);
}
?>