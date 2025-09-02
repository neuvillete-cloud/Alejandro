<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | ATS Grammer</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7fc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .reset-container { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; width: 90%; max-width: 400px; }
        .reset-container h1 { color: #063962; margin-bottom: 20px; }
        .reset-container p { color: #6b7280; margin-bottom: 30px; }
        .reset-container input { width: calc(100% - 24px); padding: 12px; margin-bottom: 20px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; }
        .reset-container button { width: 100%; padding: 12px; background-color: #063962; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .reset-container button:hover { background-color: #084c81; }
    </style>
</head>
<body>
<div class="reset-container">
    <h1>Recuperar Contraseña</h1>
    <p>Ingresa tu número de nómina y te enviaremos un enlace a tu correo para restablecer tu contraseña.</p>
    <form id="requestForm">
        <input type="text" id="numNomina" name="numNomina" placeholder="Número de Nómina" required>
        <button type="submit">Enviar enlace de recuperación</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const numNomina = document.getElementById('numNomina').value;
        const formData = new FormData();
        formData.append('numNomina', numNomina);

        fetch('https://grammermx.com/Mailer/requestReset.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Revisa tu correo!',
                        text: 'Si tu número de nómina existe en nuestro sistema, hemos enviado un enlace para recuperar tu contraseña.',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    // Por seguridad, mostramos el mismo mensaje incluso si hay un error
                    Swal.fire({
                        icon: 'success',
                        title: '¡Revisa tu correo!',
                        text: 'Si tu número de nómina existe en nuestro sistema, hemos enviado un enlace para recuperar tu contraseña.',
                        confirmButtonText: 'Entendido'
                    });
                }
            });
    });
</script>
</body>
</html>
