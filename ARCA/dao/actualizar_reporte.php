<?php
include_once("conexionArca.php"); // Asegúrate de incluir tu conexión
include_once("verificar_sesion.php");

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => ''];

if (!isset($_SESSION['loggedin'])) {
    $response['message'] = 'No se ha iniciado sesión.';
    echo json_encode($response);
    exit();
}

$con = new LocalConector();
$conex = $con->conectar();
$conex->begin_transaction(); // Iniciar transacción

try {
    $idSolicitud = $_POST['idSolicitud'] ?? null;
    $idReporte = $_POST['idReporte'] ?? null;
    $piezasInspeccionadas = $_POST['piezasInspeccionadas'] ?? null;
    $piezasAceptadas = $_POST['piezasAceptadas'] ?? null;
    $piezasRetrabajadas = $_POST['piezasRetrabajadas'] ?? null;
    $nombreInspector = $_POST['nombreInspector'] ?? null;
    $fechaInspeccion = $_POST['fechaInspeccion'] ?? null;
    $idRangoHora = $_POST['idRangoHora'] ?? null;
    $tiempoInspeccion = $_POST['tiempoInspeccion'] ?? null;
    $idTiempoMuerto = $_POST['idTiempoMuerto'] ?? null;
    $comentarios = $_POST['comentarios'] ?? null;

    if (!$idSolicitud || !$idReporte || !$piezasInspeccionadas || !$piezasAceptadas || !$nombreInspector || !$fechaInspeccion || !$idRangoHora) {
        throw new Exception("Faltan datos obligatorios para actualizar el reporte.");
    }

    // --- 1. Actualizar Reporte de Inspección principal ---
    $stmt = $conex->prepare("UPDATE ReportesInspeccion SET 
                            PiezasInspeccionadas = ?, 
                            PiezasAceptadas = ?, 
                            PiezasRetrabajadas = ?, 
                            NombreInspector = ?, 
                            FechaInspeccion = ?, 
                            IdRangoHora = ?, 
                            TiempoInspeccion = ?, 
                            IdTiempoMuerto = ?, 
                            Comentarios = ?,
                            FechaActualizacion = NOW()
                            WHERE IdReporte = ? AND IdSolicitud = ?");
    $stmt->bind_param("iiississsii",
        $piezasInspeccionadas, $piezasAceptadas, $piezasRetrabajadas, $nombreInspector, $fechaInspeccion,
        $idRangoHora, $tiempoInspeccion, $idTiempoMuerto, $comentarios, $idReporte, $idSolicitud
    );
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el reporte principal: " . $stmt->error);
    }

    // --- 2. Actualizar/Insertar Defectos Originales ---
    if (isset($_POST['defectos_originales']) && is_array($_POST['defectos_originales'])) {
        foreach ($_POST['defectos_originales'] as $idDefectoOriginal => $data) {
            $cantidad = intval($data['cantidad'] ?? 0);
            $lote = trim($data['lote'] ?? '');

            // Verificar si ya existe un registro para este defecto original en este reporte
            $check_stmt = $conex->prepare("SELECT COUNT(*) FROM ReporteDefectosOriginales WHERE IdReporte = ? AND IdDefecto = ?");
            $check_stmt->bind_param("ii", $idReporte, $idDefectoOriginal);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_row()[0];
            $check_stmt->close();

            if ($exists > 0) {
                // Actualizar
                $update_rdo = $conex->prepare("UPDATE ReporteDefectosOriginales SET CantidadEncontrada = ?, Lote = ? WHERE IdReporte = ? AND IdDefecto = ?");
                $update_rdo->bind_param("isii", $cantidad, $lote, $idReporte, $idDefectoOriginal);
                if (!$update_rdo->execute()) {
                    throw new Exception("Error al actualizar defecto original (IdDefecto: $idDefectoOriginal): " . $update_rdo->error);
                }
                $update_rdo->close();
            } else {
                // Insertar
                $insert_rdo = $conex->prepare("INSERT INTO ReporteDefectosOriginales (IdReporte, IdDefecto, CantidadEncontrada, Lote) VALUES (?, ?, ?, ?)");
                $insert_rdo->bind_param("iiis", $idReporte, $idDefectoOriginal, $cantidad, $lote);
                if (!$insert_rdo->execute()) {
                    throw new Exception("Error al insertar nuevo defecto original (IdDefecto: $idDefectoOriginal): " . $insert_rdo->error);
                }
                $insert_rdo->close();
            }
        }
    }

    // --- 3. Eliminar Defectos Encontrados (Nuevos Defectos) marcados para eliminación ---
    if (isset($_POST['defectos_encontrados_a_eliminar']) && is_array($_POST['defectos_encontrados_a_eliminar'])) {
        foreach ($_POST['defectos_encontrados_a_eliminar'] as $idDefectoEncontrado) {
            // Obtener ruta de la foto para eliminarla
            $select_foto = $conex->prepare("SELECT RutaFotoEvidencia FROM DefectosEncontrados WHERE IdDefectoEncontrado = ? AND IdReporte = ?");
            $select_foto->bind_param("ii", $idDefectoEncontrado, $idReporte);
            $select_foto->execute();
            $result_foto = $select_foto->get_result();
            if ($row_foto = $result_foto->fetch_assoc()) {
                if (!empty($row_foto['RutaFotoEvidencia']) && file_exists($row_foto['RutaFotoEvidencia'])) {
                    unlink($row_foto['RutaFotoEvidencia']); // Eliminar archivo físico
                }
            }
            $select_foto->close();

            $delete_de = $conex->prepare("DELETE FROM DefectosEncontrados WHERE IdDefectoEncontrado = ? AND IdReporte = ?");
            $delete_de->bind_param("ii", $idDefectoEncontrado, $idReporte);
            if (!$delete_de->execute()) {
                throw new Exception("Error al eliminar defecto encontrado (IdDefectoEncontrado: $idDefectoEncontrado): " . $delete_de->error);
            }
            $delete_de->close();
        }
    }


    // --- 4. Insertar/Actualizar Nuevos Defectos Encontrados ---
    $upload_dir = 'uploads/defectos/'; // Directorio para las fotos de defectos
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    if (isset($_POST['nuevos_defectos']) && is_array($_POST['nuevos_defectos'])) {
        foreach ($_POST['nuevos_defectos'] as $key => $data) {
            $idDefectoCatalogo = $data['id'] ?? null;
            $cantidad = intval($data['cantidad'] ?? 0);
            $idDefectoEncontrado = $data['idDefectoEncontrado'] ?? null; // Si existe, es una actualización

            $rutaFoto = $data['foto_existente'] ?? null; // Ruta de la foto existente si no se sube una nueva

            // Manejo de la subida de la nueva foto
            if (isset($_FILES['nuevos_defectos']['name'][$key]['foto']) && $_FILES['nuevos_defectos']['error'][$key]['foto'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['nuevos_defectos']['tmp_name'][$key]['foto'];
                $fileName = uniqid() . '-' . basename($_FILES['nuevos_defectos']['name'][$key]['foto']);
                $newFilePath = $upload_dir . $fileName;

                if (move_uploaded_file($fileTmpPath, $newFilePath)) {
                    // Si ya existía una foto anterior, la eliminamos
                    if (!empty($rutaFoto) && file_exists($rutaFoto)) {
                        unlink($rutaFoto);
                    }
                    $rutaFoto = $newFilePath;
                } else {
                    throw new Exception("Error al subir la foto para el nuevo defecto: " . $_FILES['nuevos_defectos']['error'][$key]['foto']);
                }
            } elseif (empty($rutaFoto) && !$idDefectoEncontrado) { // Si es un nuevo defecto y no tiene foto
                throw new Exception("Se requiere una foto para el nuevo defecto.");
            }


            if ($idDefectoEncontrado) {
                // Actualizar Defecto Encontrado existente
                $update_de = $conex->prepare("UPDATE DefectosEncontrados SET IdDefectoCatalogo = ?, Cantidad = ?, RutaFotoEvidencia = ? WHERE IdDefectoEncontrado = ? AND IdReporte = ?");
                $update_de->bind_param("iisii", $idDefectoCatalogo, $cantidad, $rutaFoto, $idDefectoEncontrado, $idReporte);
                if (!$update_de->execute()) {
                    throw new Exception("Error al actualizar defecto encontrado (Id: $idDefectoEncontrado): " . $update_de->error);
                }
                $update_de->close();
            } else {
                // Insertar Nuevo Defecto Encontrado
                $insert_de = $conex->prepare("INSERT INTO DefectosEncontrados (IdReporte, IdDefectoCatalogo, Cantidad, RutaFotoEvidencia) VALUES (?, ?, ?, ?)");
                $insert_de->bind_param("iiis", $idReporte, $idDefectoCatalogo, $cantidad, $rutaFoto);
                if (!$insert_de->execute()) {
                    throw new Exception("Error al insertar nuevo defecto encontrado: " . $insert_de->error);
                }
                $insert_de->close();
            }
        }
    }

    $conex->commit(); // Confirmar la transacción
    $response['status'] = 'success';
    $response['message'] = 'Reporte actualizado exitosamente.';

} catch (Exception $e) {
    $conex->rollback(); // Revertir la transacción en caso de error
    $response['message'] = $e->getMessage();
} finally {
    $conex->close();
    echo json_encode($response);
}
?>
