<?php
// Se revierte a las rutas relativas que funcionan en tus otros archivos.
include_once("dao/verificar_sesion.php");

if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 1) { die("Acceso denegado. Esta página es solo para administradores."); }

// Conexión a la base de datos
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Se corrigió u.Email por u.Correo
$query = "
    SELECT 
        s.IdSolicitud,
        s.NumeroParte,
        u.Nombre AS NombreResponsable,
        u.Correo AS EmailResponsable, 
        m.IdMetodo,
        m.TituloMetodo,
        m.RutaArchivo
    FROM Solicitudes s
    JOIN Metodos m ON s.IdMetodo = m.IdMetodo
    JOIN Usuarios u ON s.IdUsuario = u.IdUsuario
    WHERE m.EstatusAprobacion = 'Pendiente'
    ORDER BY s.IdSolicitud ASC
";
$pendientes = $conex->query($query);

if ($pendientes === false) {
    die("Error al ejecutar la consulta en la base de datos: " . $conex->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar Métodos de Trabajo - ARCA</title>
    <!-- Se enlaza a la hoja de estilos de Historial para unificar el diseño -->
    <link rel="stylesheet" href="css/estilosHistorial.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Estilos adicionales para los botones de aprobar/rechazar y responsividad -->
    <style>
        .btn-icon.aprobar { background-color: #e8f5e9; color: #1b5e20; }
        .btn-icon.aprobar:hover { background-color: var(--color-exito); color: var(--color-blanco); }
        .btn-icon.rechazar { background-color: #fdecea; color: #a83232; }
        .btn-icon.rechazar:hover { background-color: var(--color-error); color: var(--color-blanco); }

        .actions-cell {
            justify-content: center; /* Centra los botones de ícono dentro de su celda */
        }

        /* --- INICIO DE ESTILOS PERSONALIZADOS PARA SWEETALERT --- */
        .swal-custom-container .swal2-title {
            font-family: 'Montserrat', sans-serif;
            color: var(--color-primario);
        }
        .swal-custom-container .swal2-html-container {
            font-family: 'Lato', sans-serif;
        }
        .swal-custom-input, .swal-custom-textarea {
            font-family: 'Lato', sans-serif !important;
            font-size: 16px !important;
            border-radius: 6px !important;
            border: 1px solid #ccc !important;
            box-shadow: none !important;
        }
        .swal-custom-textarea {
            min-height: 100px;
        }
        /* --- FIN DE ESTILOS PERSONALIZADOS PARA SWEETALERT --- */

        /* --- INICIO DE ESTILOS RESPONSIVOS --- */
        @media (max-width: 768px) {
            .results-container {
                background-color: transparent !important;
                box-shadow: none !important;
                border: none !important;
            }
            .results-table thead { display: none; }
            .results-table tr {
                display: block;
                margin-bottom: 15px;
                border-radius: 8px;
                background-color: var(--color-blanco);
                box-shadow: var(--sombra-suave);
                overflow: hidden;
                border: 1px solid var(--color-borde);
            }
            .results-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 15px;
                border-bottom: 1px solid #f0f0f0;
                text-align: right;
            }
            .results-table td:last-child { border-bottom: none; }
            .results-table td::before {
                content: attr(data-label);
                font-weight: bold;
                color: var(--color-primario);
                text-align: left;
                margin-right: 15px;
            }
            .actions-cell {
                justify-content: flex-end;
                gap: 15px;
                padding: 15px;
            }
            .page-header h1 { font-size: 22px; }
        }
        /* --- FIN DE ESTILOS RESPONSIVOS --- */

    </style>
</head>
<body>
<header class="header">
    <div class="header-left">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <nav class="main-nav">
            <a href="index.php">Dashboard</a>
            <a href="Historial.php">Mis Solicitudes</a>
            <a href="aprobar_metodos.php" class="active">Aprobar Métodos</a>
        </nav>
    </div>
    <div class="user-info">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="page-header">
        <h1><i class="fa-solid fa-clipboard-check"></i> Métodos Pendientes de Aprobación</h1>
    </div>

    <div class="results-container">
        <table class="results-table">
            <thead>
            <tr>
                <th><i class="fa-solid fa-hashtag"></i>Folio</th>
                <th><i class="fa-solid fa-barcode"></i>No. de Parte</th>
                <th><i class="fa-solid fa-user"></i>Responsable</th>
                <th><i class="fa-solid fa-file-signature"></i>Nombre del Método</th>
                <th style="text-align: center;"><i class="fa-solid fa-cogs"></i>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($pendientes->num_rows > 0): ?>
                <?php while ($row = $pendientes->fetch_assoc()): ?>
                    <tr id="fila-metodo-<?php echo $row['IdMetodo']; ?>">
                        <td data-label="Folio"><?php echo "S-" . str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td data-label="No. de Parte"><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                        <td data-label="Responsable"><?php echo htmlspecialchars($row['NombreResponsable']); ?></td>
                        <td data-label="Método"><?php echo htmlspecialchars($row['TituloMetodo']); ?></td>
                        <td data-label="Acciones" class="actions-cell">
                            <a href="<?php echo htmlspecialchars($row['RutaArchivo']); ?>" target="_blank" class="btn-icon" title="Ver PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            <button class="btn-icon aprobar" data-id="<?php echo $row['IdMetodo']; ?>" title="Aprobar Método">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="btn-icon rechazar" data-id="<?php echo $row['IdMetodo']; ?>" data-solicitud="S-<?php echo str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?>" data-email-responsable="<?php echo htmlspecialchars($row['EmailResponsable']); ?>" title="Rechazar Método">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-results-cell">
                        <div class="no-results-content">
                            <i class="fa-solid fa-check-circle"></i>
                            <p>¡Todo al día! No hay métodos pendientes de revisión.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-icon.aprobar').forEach(button => {
            button.addEventListener('click', function() {
                const idMetodo = this.dataset.id;
                Swal.fire({
                    title: '¿Confirmas la aprobación?',
                    text: "El método de trabajo será marcado como válido.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, aprobar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        procesarDecision('aprobar', idMetodo);
                    }
                });
            });
        });

        document.querySelectorAll('.btn-icon.rechazar').forEach(button => {
            button.addEventListener('click', function() {
                const idMetodo = this.dataset.id;
                const folioSolicitud = this.dataset.solicitud;
                const emailResponsable = this.dataset.emailResponsable;

                Swal.fire({
                    title: `Rechazar Método - ${folioSolicitud}`,
                    html: `
                    <p style="text-align:left; margin-bottom:15px; font-size: 1em;">Se enviará una notificación a:</p>
                    <input type="email" id="swal-email" class="swal2-input" value="${emailResponsable}" placeholder="Correo del solicitante">
                    <textarea id="swal-motivo" class="swal2-textarea" placeholder="Describe aquí el motivo del rechazo..."></textarea>
                `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d63031',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Rechazar y Notificar',
                    cancelButtonText: 'Cancelar',
                    // --- INICIO DE LA MODIFICACIÓN ---
                    customClass: {
                        container: 'swal-custom-container',
                        input: 'swal-custom-input',
                        textarea: 'swal-custom-textarea',
                    },
                    // --- FIN DE LA MODIFICACIÓN ---
                    preConfirm: () => {
                        const email = document.getElementById('swal-email').value;
                        const motivo = document.getElementById('swal-motivo').value;
                        if (!email || !motivo) {
                            Swal.showValidationMessage('El correo y el motivo son obligatorios.');
                            return false;
                        }
                        if (!/^\S+@\S+\.\S+$/.test(email)) {
                            Swal.showValidationMessage('Por favor, ingresa un correo válido.');
                            return false;
                        }
                        return { email: email, motivo: motivo };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        procesarDecision('rechazar', idMetodo, result.value.email, result.value.motivo);
                    }
                });
            });
        });

        function procesarDecision(accion, idMetodo, email = null, motivo = null) {
            const formData = new FormData();
            formData.append('action', accion);
            formData.append('idMetodo', idMetodo);
            if (email) formData.append('email', email);
            if (motivo) formData.append('motivo', motivo);

            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('https://grammermx.com/Mailer/procesar_aprobacion.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('¡Éxito!', data.message, 'success');
                        const fila = document.getElementById(`fila-metodo-${idMetodo}`);
                        if (fila) {
                            fila.style.transition = 'opacity 0.5s ease';
                            fila.style.opacity = '0';
                            setTimeout(() => {
                                fila.remove();
                            }, 500);
                        }
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
                });
        }

        const tableRows = document.querySelectorAll('.results-table tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
        });
    });
</script>
</body>
</html>

