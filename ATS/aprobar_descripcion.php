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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #004a8d;
            --color-secundario: #005cb9;
            --color-fondo: #f4f7fc;
            --color-texto: #343a40;
            --color-blanco: #ffffff;
            --color-exito: #198754;
            --color-peligro: #dc3545;
            --color-info: #0d6efd;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .main-container {
            width: 100%;
            max-width: 800px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, var(--color-primario), var(--color-secundario));
            padding: 30px;
            text-align: center;
        }

        .header img {
            max-width: 200px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }

        .content-card {
            background: var(--color-blanco);
            padding: 40px;
            text-align: center;
        }

        h1 {
            color: var(--color-primario);
            font-weight: 700;
            font-size: 2.2rem;
            margin-top: 0;
            margin-bottom: 15px;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #555;
            margin-bottom: 25px;
        }

        .acciones {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .btn-aprobar { background-color: var(--color-exito); color: white; }
        .btn-rechazar { background-color: var(--color-peligro); color: white; }
        .btn-descargar { background: linear-gradient(45deg, var(--color-secundario), var(--color-primario)); color: white; margin-top: 15px; margin-bottom: 30px; width: 90%; max-width: 450px; font-size: 1.1rem; }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.2); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn:disabled:hover { transform: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        #formRechazo {
            text-align: left;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin-top: 40px;
        }

        #formRechazo h3 { margin-top: 0; color: var(--color-primario); font-weight: 600; }
        #formRechazo textarea { width: 95%; height: 90px; padding: 12px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 15px; font-family: 'Poppins', sans-serif; font-size: 1rem; resize: vertical; }
        #formRechazo input[type="file"] { margin-bottom: 15px; display: block; }
        #formRechazo button { background-color: var(--color-secundario); color: white; width: 100%; }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .content-card { padding: 25px; }
            h1 { font-size: 1.8rem; }
            p { font-size: 1rem; }
            .acciones { flex-direction: column; gap: 15px; }
            .btn { width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header">
        <img src="imagenes/logo_blanco.png" alt="Logo Grammer">
    </div>
    <div class="content-card">
        <?php if ($token_valido): ?>
            <h1>Revisión de Descripción de Puesto</h1>
            <p>Por favor, revisa la descripción para el puesto de <strong><?= htmlspecialchars($datos_solicitud['Puesto']) ?></strong>.</p>
            <p>Haz clic en el siguiente botón para ver o descargar el documento.</p>

            <?php
            $nombre_archivo = $datos_solicitud['ArchivoDescripcion'];
            $url_completa_archivo = $nombre_archivo;
            if (strpos($nombre_archivo, 'http') !== 0) {
                $url_completa_archivo = $url_sitio . "/descripciones/" . rawurlencode($nombre_archivo);
            }
            ?>
            <a class="btn btn-descargar" href="<?= htmlspecialchars($url_completa_archivo) ?>" target="_blank">
                <i class="fas fa-file-download"></i> Ver/Descargar Descripción
            </a>

            <div class="acciones">
                <button id="btnAprobar" class="btn btn-aprobar"><i class="fas fa-check"></i> Aprobar</button>
                <button id="btnRechazar" class="btn btn-rechazar"><i class="fas fa-times"></i> Rechazar</button>
            </div>

            <div id="formRechazo" style="display:none;">
                <hr>
                <h3>Motivo del Rechazo y Corrección</h3>
                <p style="text-align: left;">Por favor, indica por qué se rechaza y sube la versión correcta del documento.</p>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($token_valido): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const token = "<?= htmlspecialchars($token) ?>";
        const btnAprobar = document.getElementById('btnAprobar');
        const btnRechazar = document.getElementById('btnRechazar');
        const formRechazo = document.getElementById('formRechazo');
        const rechazoForm = document.getElementById('rechazoForm');
        const btnEnviarCorrecciones = rechazoForm.querySelector('button[type="submit"]');

        btnRechazar.addEventListener('click', () => {
            formRechazo.style.display = 'block';
            document.querySelector('.acciones').style.display = 'none';
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
                    btnRechazar.disabled = true;
                    btnAprobar.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Procesando...`;

                    const formData = new FormData();
                    formData.append('accion', 'aprobar');
                    formData.append('token', token);

                    fetch('https://grammermx.com/Mailer/procesarAprobacion.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                document.querySelector('.content-card').innerHTML = '<h1>¡Gracias!</h1><p>La descripción ha sido aprobada correctamente. Ya puedes cerrar esta ventana.</p>';
                            } else {
                                Swal.fire('Error', data.message || 'Ocurrió un error.', 'error');
                                btnAprobar.disabled = false;
                                btnRechazar.disabled = false;
                                btnAprobar.innerHTML = originalButtonHTML;
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                            btnAprobar.disabled = false;
                            btnRechazar.disabled = false;
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
                                document.querySelector('.content-card').innerHTML = '<h1>¡Gracias!</h1><p>Tus correcciones han sido enviadas al administrador. Ya puedes cerrar esta ventana.</p>';
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

