<?php
include_once("dao/ConexionBD.php");
$token_valido = false;
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $con = new LocalConector();
    $conex = $con->conectar();

    $stmt = $conex->prepare("SELECT * FROM RestablecerContrasena WHERE Token = ? AND TokenValido = 1 AND Expira >= NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token_valido = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña | ATS Grammer</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reset-container { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; width: 90%; max-width: 400px; }
        .reset-container h1 { color: #063962; margin-bottom: 20px; }
        .reset-container p { color: #6b7280; margin-bottom: 30px; }
        .reset-container input { width: calc(100% - 24px); padding: 12px; margin-bottom: 20px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; }
        .reset-container button { width: 100%; padding: 12px; background-color: #063962; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .reset-container button:hover { background-color: #084c81; }
        .error { color: #dc3545; }

        /* --- ESTILOS PARA EL MEDIDOR DE CONTRASEÑA --- */
        #strength-meter-container { width: 100%; height: 8px; background-color: #e5e7eb; border-radius: 4px; margin-top: -10px; overflow: hidden; }
        #strength-meter-bar { height: 100%; width: 0; border-radius: 4px; transition: width 0.3s ease, background-color 0.3s ease; }

        /* CAMBIO 1: Se ajustan los márgenes para reducir el espacio */
        #strength-meter-text { font-size: 0.85rem; font-weight: 600; text-align: left; height: 1.2em; margin-top: 5px; margin-bottom: 5px; }
        .requirements-text { font-size: 0.8rem; color: #6b7280; text-align: left; margin-top: 0; margin-bottom: 20px; }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #fd7e14; }
        .strength-strong { color: #198754; }
    </style>
</head>
<body>
<div class="reset-container">
    <h1>Restablecer Contraseña</h1>
    <?php if ($token_valido): ?>
        <p>Ingresa tu nueva contraseña a continuación.</p>
        <form id="resetForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" id="password" name="password" placeholder="Nueva Contraseña" required>

            <div id="strength-meter-container">
                <div id="strength-meter-bar"></div>
            </div>
            <p id="strength-meter-text"></p>

            <p class="requirements-text">
                Debe contener mayúscula, número y símbolo.
            </p>

            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Nueva Contraseña" required>
            <button type="submit">Guardar nueva contraseña</button>
        </form>
    <?php else: ?>
        <p class="error">El enlace para restablecer la contraseña es inválido o ha expirado. Por favor, solicita uno nuevo.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if ($token_valido): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // El script de JS no necesita cambios, solo el HTML y CSS
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strength-meter-bar');
        const strengthText = document.getElementById('strength-meter-text');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const result = checkPasswordStrength(password);

            strengthText.textContent = result.text;
            strengthText.className = result.className;
            strengthBar.style.width = result.width;
            strengthBar.style.backgroundColor = result.color;
        });

        function checkPasswordStrength(password) {
            let score = 0;
            let results = { text: '', width: '0%', color: 'transparent', className: '' };

            if (password.length === 0) return results;

            if (password.length >= 8) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++; // Mayúsculas y minúsculas
            if (/[0-9]/.test(password)) score++; // Números
            if (/[^a-zA-Z0-9]/.test(password)) score++; // Símbolos

            // Puntuación ajustada
            switch (score) {
                case 1:
                    results = { text: 'Débil', width: '25%', color: '#dc3545', className: 'strength-weak' };
                    break;
                case 2:
                    results = { text: 'Media', width: '50%', color: '#fd7e14', className: 'strength-medium' };
                    break;
                case 3:
                    results = { text: 'Fuerte', width: '75%', color: '#198754', className: 'strength-strong' };
                    break;
                case 4:
                    results = { text: 'Muy Fuerte', width: '100%', color: '#198754', className: 'strength-strong' };
                    break;
                default:
                    results = { text: 'Muy Débil', width: '10%', color: '#dc3545', className: 'strength-weak' };
                    break;
            }
            return results;
        }

        document.getElementById('resetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            if (password !== confirm_password) {
                Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
                return;
            }

            const formData = new FormData(this);

            fetch('dao/updatePassword.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Contraseña actualizada!',
                            text: 'Tu contraseña ha sido cambiada con éxito.',
                            confirmButtonText: 'Iniciar Sesión'
                        }).then(() => {
                            window.location.href = 'login.php';
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        });
    });
    <?php endif; ?>
</script>
</body>
</html>