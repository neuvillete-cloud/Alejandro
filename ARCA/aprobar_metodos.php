<?php
// Incluye el script que verifica si hay una sesión activa y si es SuperUsuario
include_once("dao/verificar_sesion.php");
if (!isset($_SESSION['loggedin'])) { header('Location: acceso.php'); exit(); }
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 1) { die("Acceso denegado. Esta página es solo para administradores."); }

// Conexión a la base de datos
include_once("dao/conexionArca.php");
$con = new LocalConector();
$conex = $con->conectar();

// Consulta para obtener las solicitudes con métodos pendientes
$query = "
    SELECT 
        s.IdSolicitud,
        s.NumeroParte,
        u.Nombre AS NombreResponsable,
        u.Email AS EmailResponsable,
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar Métodos de Trabajo - ARCA</title>
    <link rel="stylesheet" href="css/estilosT.css"> <!-- Reutilizamos tus estilos existentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <h1><i class="fa-solid fa-clipboard-check"></i> Métodos de Trabajo Pendientes de Aprobación</h1>

    <?php if ($pendientes->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Folio Solicitud</th>
                    <th>No. de Parte</th>
                    <th>Responsable</th>
                    <th>Nombre del Método</th>
                    <th>Archivo</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $pendientes->fetch_assoc()): ?>
                    <tr id="fila-metodo-<?php echo $row['IdMetodo']; ?>">
                        <td><?php echo "S-" . str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['NumeroParte']); ?></td>
                        <td><?php echo htmlspecialchars($row['NombreResponsable']); ?></td>
                        <td><?php echo htmlspecialchars($row['TituloMetodo']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($row['RutaArchivo']); ?>" target="_blank" class="btn-primary btn-small">
                                <i class="fa-solid fa-file-pdf"></i> Ver PDF
                            </a>
                        </td>
                        <td>
                            <button class="btn-aprobar btn-primary btn-small" data-id="<?php echo $row['IdMetodo']; ?>" style="background-color: var(--color-exito);">
                                <i class="fa-solid fa-check"></i> Aprobar
                            </button>
                            <button class="btn-rechazar btn-danger btn-small" data-id="<?php echo $row['IdMetodo']; ?>" data-solicitud="S-<?php echo str_pad($row['IdSolicitud'], 4, '0', STR_PAD_LEFT); ?>" data-email-responsable="<?php echo htmlspecialchars($row['EmailResponsable']); ?>">
                                <i class="fa-solid fa-times"></i> Rechazar
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.1em; color: #666; margin-top: 30px;">¡Excelente! No hay métodos de trabajo pendientes de revisión.</p>
    <?php endif; ?>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-aprobar').forEach(button => {
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

        document.querySelectorAll('.btn-rechazar').forEach(button => {
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
                    confirmButtonColor: '#a83232',
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
                        document.getElementById(`fila-metodo-${idMetodo}`).remove();
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



