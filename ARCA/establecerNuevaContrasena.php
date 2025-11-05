<?php
// 1. Incluir conexión y validar el token
include_once("dao/conexionArca.php");

$token = null;
$esTokenValido = false;
$mensajeError = "El enlace de recuperación no es válido, ha expirado o ya fue utilizado."; // Mensaje por defecto

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->set_charset("utf8mb4");

    // 2. Buscar el token en la base de datos
    $stmt = $conex->prepare("SELECT IdUsuario, Expira FROM ReestablecerContraseña WHERE Token = ? AND TokenValido = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        // Token encontrado, verificar expiración
        $fechaExpiracion = new DateTime($fila['Expira']);
        $fechaActual = new DateTime();

        if ($fechaActual < $fechaExpiracion) {
            // ¡Token válido y no expirado!
            $esTokenValido = true;
        } else {
            // Token expirado, invalidarlo en la BD
            $mensajeError = "Este enlace de recuperación ha expirado. Por favor, solicita uno nuevo.";
            $stmt_invalidar = $conex->prepare("UPDATE ReestablecerContraseña SET TokenValido = 0 WHERE Token = ?");
            $stmt_invalidar->bind_param("s", $token);
            $stmt_invalidar->execute();
            $stmt_invalidar->close();
        }
    }
    // Si no se encuentra la fila, $esTokenValido sigue en false y se usa el $mensajeError por defecto

    $stmt->close();
    $conex->close();
}
// Si no hay token en GET, $esTokenValido es false y se usa el $mensajeError por defecto
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña - ARCA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Usamos la misma hoja de estilos del Login -->
    <link rel="stylesheet" href="css/estilosAcceso.css">

</head>
<body>

<div class="login-wrapper">
    <div class="branding-panel">
        <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
        <h1>Sistema de Gestión de Contenciones y Calidad</h1>
        <p>Una herramienta interna para asegurar la integridad de los procesos y materiales de la compañía.</p>
    </div>
    <div class="login-panel">

        <!-- === Encabezado para Móviles === -->
        <div class="mobile-branding">
            <div class="logo"><i class="fa-solid fa-shield-halved"></i>ARCA</div>
            <p>Gestión de Contenciones y Calidad</p>
        </div>

        <div class="login-form-container">

            <?php if ($esTokenValido): ?>

                <!-- 3. Si el token es VÁLIDO, mostrar el formulario -->
                <h2>Establecer Nueva Contraseña</h2>
                <p class="subtitle">Por favor, introduce tu nueva contraseña.</p>

                <form id="resetForm" method="POST">
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" class="input-field" placeholder="Nueva Contraseña" required>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password_confirm" name="password_confirm" class="input-field" placeholder="Confirmar Contraseña" required>
                    </div>

                    <!-- Campo oculto para enviar el token -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <button type="submit" class="submit-btn">Actualizar Contraseña</button>
                </form>
                <div class="form-footer">
                    <p><a href="acceso.php">Volver a Inicio de sesión</a></p>
                </div>

            <?php else: ?>

                <!-- 4. Si el token es INVÁLIDO, mostrar mensaje de error -->
                <h2>Enlace Inválido</h2>
                <p class="subtitle" style="color: #a83232;"><?php echo $mensajeError; ?></p>

                <!-- === ¡AQUÍ ESTÁ LA CORRECCIÓN! === -->
                <!-- Añadimos 'display: block;' y 'box-sizing: border-box;' para que el enlace ocupe el 100% del ancho -->
                <a href="recuperarContra.php" class="submit-btn" style="text-align: center; text-decoration: none; display: block; box-sizing: border-box;">Solicitar nuevo enlace</a>

                <div class="form-footer">
                    <p><a href="acceso.php">Volver a Inicio de sesión</a></p>
                </div>

            <?php endif; ?>

        </div>
        <div class="version-info">
            ARCA v1.0.1
        </div>
    </div>
</div>

<script>
    // Solo añadimos el script si el formulario existe
    <?php if ($esTokenValido): ?>
    const form = document.getElementById('resetForm');

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(form);
        const password = formData.get('password');
        const passwordConfirm = formData.get('password_confirm');

        // Validación simple de contraseña
        if (password.length < 8) {
            Swal.fire({
                icon: 'error',
                title: 'Contraseña Insegura',
                text: 'Tu contraseña debe tener al menos 8 caracteres.',
            });
            return;
        }

        if (password !== passwordConfirm) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas no coinciden.',
            });
            return;
        }

        Swal.fire({
            title: 'Actualizando...',
            text: 'Por favor, espera.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Apuntamos al nuevo script de backend
        fetch('dao/daoActualizarContrasena.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        // Redirigir al login
                        window.location.href = 'acceso.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo comunicar con el servidor.',
                });
            });
    });
    <?php endif; ?>
</script>

</body>
</html>

