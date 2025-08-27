<?php
include_once("ConexionBD.php"); // Asegúrate de que la ruta sea correcta

header('Content-Type: application/json');

// --- IDs DE ESTATUS CORREGIDOS SEGÚN TU EXPLICACIÓN ---
// Estos son los IDs de la tabla Postulaciones.
define('ID_ESTATUS_POR_REVISAR', 1);     // Cuando un candidato recién se postula.
define('ID_ESTATUS_SIGUIENTE_FASE', 4); // Candidatos que pasan el primer filtro.
define('ID_ESTATUS_PARA_CONTRATAR', 9); // El candidato final seleccionado.
define('ID_ESTATUS_DESCARTADO', 3);     // Candidatos rechazados.

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Esta consulta obtiene todas las vacantes y calcula las estadísticas para cada una
    $sql = "
        SELECT 
            v.IdVacante,
            v.TituloVacante,
            v.Ciudad,
            v.Estado,
            v.Fecha AS FechaCreacion,
            -- El estatus de la vacante (Activa/Cerrada) viene de la tabla Vacantes
            (SELECT NombreEstatus FROM Estatus WHERE IdEstatus = v.IdEstatus) AS EstatusVacante,
            
            -- Contamos las postulaciones para cada categoría usando los IDs correctos
            COUNT(CASE WHEN p.IdEstatus = " . ID_ESTATUS_POR_REVISAR . " THEN 1 END) AS PorRevisar,
            -- 'Me interesan' ahora cuenta a los que pasaron a la siguiente fase Y al que fue seleccionado
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

    echo json_encode(['status' => 'success', 'data' => $vacantes]);

    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $e->getMessage()]);
}
?>
