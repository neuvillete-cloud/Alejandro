<?php
include_once("dao/ConexionBD.php");

// --- CONFIGURACIÓN IMPORTANTE ---
$url_sitio = "https://grammermx.com/AleTest/ATS";

$token_valido = false;
$datos_solicitud = null;
$error_mensaje = 'Este enlace de aprobación no es válido, ya ha sido utilizado o ha expirado.';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    try {
        $con = new LocalConector();
        $conex = $con->conectar();

        $stmtToken = $conex->prepare("SELECT IdSolicitud FROM AprobacionDescripcion WHERE Token = ? AND TokenValido = 1 AND Estatus = 'pendiente'");
        $stmtToken->bind_param("s", $token);
        $stmtToken->execute();
        $resultToken = $stmtToken->get_result();

        if ($resultToken->num_rows > 0) {
            $idSolicitud = $resultToken->fetch_assoc()['IdSolicitud'];

            $stmtSol = $conex->prepare("
                SELECT s.Puesto, d.ArchivoDescripcion 
                FROM Solicitudes s 
                JOIN DescripcionPuesto d ON s.IdDescripcion = d.IdDescripcion 
                WHERE s.IdSolicitud = ?
            ");
            $stmtSol->bind_param("i", $idSolicitud);
            $stmtSol->execute();
            $resultSol = $stmtSol->get_result();

            if ($resultSol->num_rows > 0) {
                $token_valido = true;
                $datos_solicitud = $resultSol->fetch_assoc();
            }
        }
        $conex->close();
    } catch (Exception $e) {
        $error_mensaje = "Ocurrió un error al procesar tu solicitud.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar Descripción de Puesto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fc; color: #333; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 900px; padding: 40px; }
        h1, h2 { color: #063962; }
        h1 { border-bottom: 2px solid #063962; padding-bottom: 10px; }
        .document-viewer { border: 1px solid #ddd; border-radius: 8px; margin: 20px 0; padding: 20px; overflow-x: auto; max-height: 600px; }
        .download-link { display: block; text-align: center; margin-bottom: 30px; font-weight: bold; color: #063962; }
        .acciones { display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-aprobar { background-color: #198754; color: white; }
        .btn-rechazar { background-color: #dc3545; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }

        /* Estilo para botones desactivados */
        .btn:disabled { opacity: 0.65; cursor: not-allowed; }
        .btn:disabled:hover { transform: none; box-shadow: none; }

        #formRechazo { background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 20px; }
        #formRechazo h3 { margin-top: 0; color: #063962; }
        #formRechazo textarea { width: 95%; height: 80px; padding: 10px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 15px; font-family: Arial, sans-serif; font-size: 1rem; }
        #formRechazo input[type="file"] { margin-bottom: 15px; display: block; }
        #formRechazo button { background-color: #0d6efd; color: white; }
        .excel-sheet-title { margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        #excel-viewer table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 15px; }
        #excel-viewer th, #excel-viewer td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        #excel-viewer th { background-color: #f2f2f2; font-weight: bold; }
        #excel-viewer tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
<div class="container">
    <?php if ($token_valido): ?>
        <h1>Revisión de Descripción de Puesto</h1>
        <p>Por favor, revisa la descripción para el puesto de <strong><?= htmlspecialchars($datos_solicitud['Puesto']) ?></strong>.</p>

        <div id="excel-viewer" class="document-viewer">
            <p>Cargando previsualización del archivo...</p>
        </div>

        <?php
        $nombre_archivo = $datos_solicitud['ArchivoDescripcion'];
        $url_completa_archivo = $nombre_archivo;
        if (strpos($nombre_archivo, 'http') !== 0) {
            $url_completa_archivo = $url_sitio . "/descripciones/" . rawurlencode($nombre_archivo);
        }
        ?>
        <a class="download-link" href="<?= htmlspecialchars($url_completa_archivo) ?>" target="_blank">Descargar Archivo Original de Excel</a>

        <div class="acciones">
            <button id="btnAprobar" class="btn btn-aprobar"><i class="fas fa-check"></i> Aprobar</button>
            <button id="btnRechazar" class="btn btn-rechazar"><i class="fas fa-times"></i> Rechazar</button>
        </div>

        <div id="formRechazo" style="display:none;">
            <hr>
            <h3>Motivo del Rechazo y Corrección</h3>
            <p>Por favor, indica por qué se rechaza y sube la versión correcta del documento.</p>
            <form id="rechazoForm">
                <textarea id="comentarios" name="comentarios" placeholder="Escribe tus comentarios aquí..." required></textarea>
                <input type="file" id="archivoCorrecto" name="archivoCorrecto" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                <button type="submit" class="btn">Enviar Correcciones</button>
            </form>
        </div>
    <?php else: ?>
        <h1>Enlace Inválido</h1>
        <p><?= htmlspecialchars($error_mensaje) ?></p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($token_valido): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const fileUrl = "<?= htmlspecialchars($url_completa_archivo) ?>";
        const viewer = document.getElementById('excel-viewer');

        async function displayExcelFile(url) {
            try {
                const response = await fetch(url);
                if (!response.ok) { throw new Error(`Error de red: ${response.statusText}`); }
                const data = await response.arrayBuffer();
                const workbook = XLSX.read(data, { type: 'array' });
                let allSheetsHTML = '';
                workbook.SheetNames.forEach(sheetName => {
                    const worksheet = workbook.Sheets[sheetName];
                    const htmlTable = XLSX.utils.sheet_to_html(worksheet, {header: 1, raw: false});
                    allSheetsHTML += `<h2 class="excel-sheet-title">${sheetName}</h2>` + htmlTable;
                });
                viewer.innerHTML = allSheetsHTML;
            } catch (error) {
                viewer.innerHTML = '<p style="color: red;">No se pudo cargar la previsualización. Verifica que el archivo exista en el servidor. Por favor, descarga el archivo para revisarlo.</p>';
            }
        }

        displayExcelFile(fileUrl);

        const token = "<?= htmlspecialchars($token) ?>";
        const btnAprobar = document.getElementById('btnAprobar');
        const btnRechazar = document.getElementById('btnRechazar');
        const formRechazo = document.getElementById('formRechazo');
        const rechazoForm = document.getElementById('rechazoForm');
        const btnEnviarCorrecciones = rechazoForm.querySelector('button[type="submit"]');

        btnRechazar.addEventListener('click', () => {
            formRechazo.style.display = 'block';
            btnRechazar.style.display = 'none';
            btnAprobar.style.display = 'none';
        });

        btnAprobar.addEventListener('click', () => {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Se notificará al administrador que la descripción ha sido aprobada.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, aprobar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const originalButtonHTML = btnAprobar.innerHTML;
                    btnAprobar.disabled = true;
                    btnAprobar.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Procesando...`;

                    const formData = new FormData();
                    formData.append('accion', 'aprobar');
                    formData.append('token', token);

                    fetch('https://grammermx.com/Mailer/procesarAprobacion.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.querySelector('.container').innerHTML = '<h1>¡Gracias!</h1><p>La descripción ha sido aprobada correctamente. Ya puedes cerrar esta ventana.</p>';
                            } else {
                                Swal.fire('Error', data.message || 'Ocurrió un error.', 'error');
                                btnAprobar.disabled = false;
                                btnAprobar.innerHTML = originalButtonHTML;
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                            btnAprobar.disabled = false;
                            btnAprobar.innerHTML = originalButtonHTML;
                        });
                }
            });
        });

        rechazoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirmar envío',
                text: 'Se enviarán tus comentarios y el archivo corregido al administrador.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if(result.isConfirmed) {
                    const originalButtonHTML = btnEnviarCorrecciones.innerHTML;
                    btnEnviarCorrecciones.disabled = true;
                    btnEnviarCorrecciones.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Enviando...`;

                    const formData = new FormData(rechazoForm);
                    formData.append('accion', 'rechazar');
                    formData.append('token', token);

                    fetch('https://grammermx.com/Mailer/procesarAprobacion.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.querySelector('.container').innerHTML = '<h1>¡Gracias!</h1><p>Tus correcciones han sido enviadas al administrador. Ya puedes cerrar esta ventana.</p>';
                            } else {
                                Swal.fire('Error', data.message || 'Ocurrió un error.', 'error');
                                btnEnviarCorrecciones.disabled = false;
                                btnEnviarCorrecciones.innerHTML = originalButtonHTML;
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                            btnEnviarCorrecciones.disabled = false;
                            btnEnviarCorrecciones.innerHTML = originalButtonHTML;
                        });
                }
            });
        });
    });
    <?php endif; ?>
</script>
</body>
</html>