<?php
// --- INICIO DE LÓGICA DE SESIÓN MEJORADA ---
session_start(['cookie_path' => '/']);

if (isset($_GET['token']) && !isset($_SESSION['loggedin'])) {
    $_SESSION['url_destino_post_login'] = 'Historial.php?token=' . urlencode($_GET['token']);
    header('Location: acceso.php');
    exit();
}
if (isset($_GET['token']) && isset($_SESSION['loggedin'])) {
    $_SESSION['vista_token_actual'] = $_GET['token'];
    header('Location: Historial.php');
    exit();
}
if (isset($_GET['modo']) && $_GET['modo'] === 'propio') {
    unset($_SESSION['vista_token_actual']);
    header('Location: Historial.php');
    exit();
}
if (!isset($_SESSION['loggedin'])) {
    header('Location: acceso.php');
    exit();
}
// --- FIN DE LÓGICA DE SESIÓN MEJORADA ---

include_once("dao/verificar_sesion.php");

$idioma_actual = 'es';
if (isset($_GET['lang']) && $_GET['lang'] == 'en') {
    $idioma_actual = 'en';
}
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

$modoVista = 'usuario_logueado';
$tituloPagina = "Mis Solicitudes de Contención";
$params = [];
$types = "";

if (isset($_SESSION['vista_token_actual'])) {
    $modoVista = 'invitado';
    $tituloPagina = "Solicitud Compartida";
    $token = $_SESSION['vista_token_actual'];

    $sql_base = "SELECT s.IdSolicitud, s.NumeroParte, s.FechaRegistro, p.NombreProvedor, e.NombreEstatus, m.EstatusAprobacion
                 FROM Solicitudes s
                 JOIN SolicitudesCompartidas sc ON s.IdSolicitud = sc.IdSolicitud
                 JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                 JOIN Estatus e ON s.IdEstatus = e.IdEstatus
                 LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo
                 WHERE sc.Token = ?";
    $params[] = $token;
    $types = "s";

} else {
    // --- CONSULTA MODIFICADA PARA INCLUIR EL ESTATUS DE APROBACIÓN ---
    $sql_base = "SELECT 
                    s.IdSolicitud, 
                    s.NumeroParte, 
                    s.FechaRegistro, 
                    p.NombreProvedor, 
                    e.NombreEstatus,
                    MAX(sc.IdCompartido) as IdCompartido,
                    m.EstatusAprobacion
                 FROM Solicitudes s
                 JOIN Provedores p ON s.IdProvedor = p.IdProvedor
                 JOIN Estatus e ON s.IdEstatus = e.IdEstatus
                 LEFT JOIN SolicitudesCompartidas sc ON s.IdSolicitud = sc.IdSolicitud
                 LEFT JOIN Metodos m ON s.IdMetodo = m.IdMetodo";

    $where_clauses = [];

    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] != 1) {
        $where_clauses[] = "s.IdUsuario = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }

    if (!empty($_GET['folio'])) {
        $where_clauses[] = "s.IdSolicitud = ?";
        $params[] = $_GET['folio'];
        $types .= "i";
    }

    if (!empty($_GET['fecha'])) {
        $where_clauses[] = "DATE(s.FechaRegistro) = ?";
        $params[] = $_GET['fecha'];
        $types .= "s";
    }

    if (count($where_clauses) > 0) {
        $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql_base .= " GROUP BY s.IdSolicitud";
}

$sql_base .= " ORDER BY s.IdSolicitud DESC";
$stmt = $conex->prepare($sql_base);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$solicitudes = $stmt->get_result();
$conex->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?> - ARCA</title>
    <link rel="stylesheet" href="css/estilosHistorial.css?v=1.2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilos para el nuevo botón de resubir método */
        .btn-icon.resubir {
            background-color: #fff3e0; /* Naranja/amarillo suave */
            color: #b75c09; /* Naranja oscuro */
        }
        .btn-icon.resubir:hover {
            background-color: #ff9800; /* Naranja más fuerte */
            color: var(--color-blanco);
        }
    </style>
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <nav class="main-nav">
            <?php if ($modoVista === 'usuario_logueado' && isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1): ?>
                <a href="index.php">Dashboard</a>
                <a href="Historial.php" class="active">Mis Solicitudes</a>
                <a href="aprobar_metodos.php">Aprobar Métodos</a>
            <?php elseif ($modoVista === 'usuario_logueado'): ?>
                <a href="index.php">Dashboard</a>
                <a href="Historial.php" class="active">Mis Solicitudes</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="user-info">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-list-check"></i><span><?php echo htmlspecialchars($tituloPagina); ?></span></h1>
        <?php if ($modoVista === 'usuario_logueado'): ?>
            <a href="nueva_solicitud.php" class="btn-primary"><i class="fa-solid fa-plus"></i> <span>Crear Nueva Solicitud</span></a>
        <?php endif; ?>
    </div>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th><i class="fa-solid fa-hashtag"></i><span>Folio</span></th>
                <th><i class="fa-solid fa-barcode"></i><span>No. Parte</span></th>
                <th><i class="fa-solid fa-truck-fast"></i><span>Proveedor</span></th>
                <th><i class="fa-solid fa-calendar-days"></i><span>Fecha de Registro</span></th>
                <th><i class="fa-solid fa-circle-info"></i><span>Estatus</span></th>
                <th><i class="fa-solid fa-cogs"></i><span>Acciones</span></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($solicitudes->num_rows > 0): ?>
                <?php while($row = $solicitudes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo "S-" . str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreProvedor']); ?></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?></td>
                        <td>
                            <?php $estatus_clase = "status-" . strtolower(str_replace(' ', '-', $row['NombreEstatus'])); ?>
                            <span class="status <?php echo $estatus_clase; ?>"><?php echo htmlspecialchars($row['NombreEstatus']); ?></span>
                        </td>
                        <td class="actions-cell">
                            <button class="btn-icon btn-details" data-id="<?php echo $row['IdSolicitud']; ?>" title="Ver Detalles"><i class="fa-solid fa-eye"></i></button>

                            <?php if ($modoVista === 'usuario_logueado'): ?>
                                <?php if (isset($row['IdCompartido'])): ?>
                                    <button class="btn-icon btn-email sent" title="Correo Enviado" disabled><i class="fa-solid fa-check"></i></button>
                                <?php else: ?>
                                    <button class="btn-icon btn-email" data-id="<?php echo $row['IdSolicitud']; ?>" title="Enviar por Correo"><i class="fa-solid fa-envelope"></i></button>
                                <?php endif; ?>

                                <!-- NUEVO BOTÓN PARA RESUBIR MÉTODO RECHAZADO -->
                                <?php if (isset($row['EstatusAprobacion']) && $row['EstatusAprobacion'] === 'Rechazado'): ?>
                                    <button class="btn-icon resubir" data-id="<?php echo $row['IdSolicitud']; ?>" title="Corregir Método de Trabajo"><i class="fa-solid fa-upload"></i></button>
                                <?php endif; ?>

                            <?php else: // Modo Invitado ?>
                                <a href="trabajar_solicitud.php?id=<?php echo $row['IdSolicitud']; ?>" class="btn-icon" title="Empezar a Trabajar" style="text-decoration: none; background-color: #5c85ad; color: #ffffff;"><i class="fa-solid fa-hammer"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="no-results-cell"><div class="no-results-content"><i class="fa-solid fa-folder-open"></i><p>No se encontraron solicitudes</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL DE DETALLES (Existente) -->
<div id="details-modal" class="modal-overlay">
    <div class="modal-content"><div class="modal-header"><h2><span>Detalles de la Solicitud</span> <span id="modal-folio"></span></h2><button id="modal-close" class="modal-close-btn">&times;</button></div><div id="modal-body" class="modal-body view-mode"></div></div>
</div>

<!-- NUEVO MODAL PARA RESUBIR MÉTODO -->
<div id="resubmit-modal" class="modal-overlay">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Corregir Método de Trabajo</h2>
            <button id="resubmit-modal-close" class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 1em; color: #555;">El método anterior fue rechazado. Por favor, sube una nueva versión corregida en formato PDF.</p>
            <form id="resubmitMetodoForm" action="dao/resubir_metodo.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idSolicitud" id="resubmitIdSolicitud">
                <div class="form-group">
                    <label class="file-upload-label" for="metodoFileResubmit" style="margin-top: 15px;">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span data-default-text="Seleccionar nuevo archivo PDF...">Seleccionar nuevo archivo PDF...</span>
                    </label>
                    <input type="file" id="metodoFileResubmit" name="metodoFile" accept=".pdf" required>
                </div>
                <div class="form-actions" style="margin-top: 25px;">
                    <button type="submit" class="btn-primary">Subir y Notificar al Administrador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/ver_solicitudes.js?v=1.1"></script>
<script>
    // Se añade la nueva lógica directamente aquí para simplicidad
    document.addEventListener('DOMContentLoaded', function() {
        const resubmitModal = document.getElementById('resubmit-modal');
        const resubmitModalCloseBtn = document.getElementById('resubmit-modal-close');
        const resubmitForm = document.getElementById('resubmitMetodoForm');
        const resubmitIdInput = document.getElementById('resubmitIdSolicitud');
        const fileInputResubmit = document.getElementById('metodoFileResubmit');
        const fileLabelSpan = fileInputResubmit.previousElementSibling.querySelector('span');
        const defaultFileText = fileLabelSpan.dataset.defaultText;

        // Abrir el modal
        document.querySelectorAll('.btn-icon.resubir').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                resubmitIdInput.value = id;
                resubmitModal.classList.add('visible');
            });
        });

        // Cerrar el modal
        function closeResubmitModal() {
            resubmitModal.classList.remove('visible');
            resubmitForm.reset();
            fileLabelSpan.textContent = defaultFileText;
        }
        resubmitModalCloseBtn.addEventListener('click', closeResubmitModal);
        resubmitModal.addEventListener('click', (e) => {
            if (e.target === resubmitModal) closeResubmitModal();
        });

        // Actualizar nombre de archivo en la etiqueta
        fileInputResubmit.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileLabelSpan.textContent = this.files[0].name;
            } else {
                fileLabelSpan.textContent = defaultFileText;
            }
        });

        // Enviar el formulario del modal
        resubmitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Subiendo Método...',
                text: 'Por favor, espera.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
                });
        });
    });
</script>
</body>
</html>
