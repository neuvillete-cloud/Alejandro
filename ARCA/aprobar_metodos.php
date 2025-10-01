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
    <!-- Se enlaza a la hoja de estilos general que ya contiene los nuevos estilos -->
    <link rel="stylesheet" href="css/estilosAprobarM.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- INICIO DE MEJORAS DE DISEÑO -->
    <style>
        /* Añade un patrón de fondo sutil a toda la página */
        body {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23dbe1e8' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        /* Animación para que las filas aparezcan suavemente */
        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .panel-aprobacion .data-table tbody tr {
            animation: fadeInRow 0.5s ease-out forwards;
            opacity: 0;
        }

        .folio-cell {
            font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            color: var(--color-primario);
            font-size: 1.05em;
        }

        .panel-aprobacion .data-table td {
            padding-top: 16px;
            padding-bottom: 16px;
        }

        /* Estilo para los nuevos iconos dentro de las celdas y cabeceras */
        .data-table th i, .data-table td i.cell-icon {
            margin-right: 8px;
            color: var(--color-acento);
            font-size: 0.9em;
        }

        /* CORRECCIÓN: Se eliminó la regla que hacía invisibles los iconos del encabezado */

    </style>
    <!-- FIN DE MEJORAS DE DISEÑO -->
</head>
<body>
<header class="header">
    <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
    <div class="user-info">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
        <button class="logout-btn" onclick="window.location.href='dao/logout.php'">Cerrar Sesión <i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
</header>

<main class="container">
    <div class="panel-aprobacion">
        <div class="panel-header">
            <h1><i class="fa-solid fa-clipboard-check"></i> Métodos Pendientes</h1>
        </div>

        <?php if ($pendientes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th><i class="fa-solid fa-hashtag"></i>Folio Solicitud</th>
                        <th><i class="fa-solid fa-barcode"></i>No. de Parte</th>
                        <th><i class="fa-solid fa-user"></i>Responsable</th>
                        <th><i class="fa-solid fa-file-signature"></i>Nombre del Método</th>
                        <th><i class="fa-solid fa-download"></i>Archivo</th>
                        <th style="text-align: center;"><i class="fa-solid fa-cogs"></i>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $loop_iterator = 0; ?>
                    <?php while ($row = $pendientes->fetch_assoc()): ?>
                        <tr id="fila-metodo-<?php echo $row['IdMetodo']; ?>" style="animation-delay: <?php echo $loop_iterator; ?>s;">
                            <td class="folio-cell"><?php echo "S-" . str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><i class="fa-solid fa-tag cell-icon"></i><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                            <td><i class="fa-solid fa-user-tie cell-icon"></i><?php echo htmlspecialchars($row['NombreResponsable']); ?></td>
                            <td><?php echo htmlspecialchars($row['TituloMetodo']); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($row['RutaArchivo']); ?>" target="_blank" class="btn-accion ver-pdf">
                                    <i class="fa-solid fa-file-pdf"></i> Ver
                                </a>
                            </td>
                            <td class="acciones-cell">
                                <button class="btn-accion aprobar" data-id="<?php echo $row['IdMetodo']; ?>">
                                    <i class="fa-solid fa-check"></i> Aprobar
                                </button>
                                <button class="btn-accion rechazar" data-id="<?php echo $row['IdMetodo']; ?>" data-solicitud="S-<?php echo str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?>" data-email-responsable="<?php echo htmlspecialchars($row['EmailResponsable']); ?>">
                                    <i class="fa-solid fa-times"></i> Rechazar
                                </button>
                            </td>
                        </tr>
                        <?php $loop_iterator += 0.05; ?>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px 20px;">
                <i class="fa-solid fa-check-circle" style="font-size: 50px; color: var(--color-exito); margin-bottom: 15px;"></i>
                <h2 style="font-family: 'Montserrat', sans-serif; margin: 0;">¡Todo al día!</h2>
                <p style="font-size: 1.1em; color: #666; margin-top: 10px;">No hay métodos de trabajo pendientes de revisión.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-accion.aprobar').forEach(button => {
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

        document.querySelectorAll('.btn-accion.rechazar').forEach(button => {
            button.addEventListener('click', function() {
                const idMetodo = this.dataset.id;
                const folioSolicitud = this.dataset.solicitud;
                const emailResponsable = this.dataset.emailResponsable;

                Swal.fire({
                    title: `Rechazar Método - ${folioSolicitud}`,
                    html: `
                    <p style="text-align:left; margin-bottom:5px;">Se notificará a:</p>
                    <input type="email" id="swal-email" class="swal2-input" value="${emailResponsable}" placeholder="Correo del solicitante">
                    <textarea id="swal-motivo" class="swal2-textarea" placeholder="Describe aquí el motivo del rechazo..."></textarea>
                `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d63031',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Rechazar y Notificar',
                    cancelButtonText: 'Cancelar',
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

            fetch('dao/procesar_aprobacion.php', { method: 'POST', body: formData })
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
    });
</script>
</body>
</html>

