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

    <!-- Estilos adicionales para los botones de aprobar/rechazar -->
    <style>
        .btn-icon.aprobar { background-color: #e8f5e9; color: #1b5e20; }
        .btn-icon.aprobar:hover { background-color: var(--color-exito); color: var(--color-blanco); }
        .btn-icon.rechazar { background-color: #fdecea; color: #a83232; }
        .btn-icon.rechazar:hover { background-color: var(--color-error); color: var(--color-blanco); }
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
                        <td><?php echo "S-" . str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreResponsable']); ?></td>
                        <td><?php echo htmlspecialchars($row['TituloMetodo']); ?></td>
                        <td class="actions-cell">
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
                            // La animación de desvanecimiento ya está en el CSS
                            fila.style.opacity = '0';
                            setTimeout(() => {
                                fila.remove();
                            }, 500); // Espera a que termine la animación
                        }
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
                });
        }

        // Aplica la animación a las filas al cargar la página
        const tableRows = document.querySelectorAll('.results-table tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
        });
    });
</script>
</body>
</html>

